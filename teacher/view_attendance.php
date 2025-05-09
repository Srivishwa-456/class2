<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id']) || !isset($_SESSION['is_teacher'])) {
    header("Location: ../teacher_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$page_title = "View Attendance Records";
$message = '';
$error = '';

// Get teacher's courses
$courses_sql = "SELECT 
    c.id, 
    c.course_code, 
    c.course_name, 
    c.semester
FROM 
    courses c
JOIN 
    teacher_courses tc ON c.id = tc.course_id
WHERE 
    tc.teacher_id = ?
ORDER BY 
    c.course_code ASC";

$courses_stmt = $conn->prepare($courses_sql);
$courses_stmt->bind_param("i", $teacher_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();
$courses = [];

while($course = $courses_result->fetch_assoc()) {
    $courses[] = $course;
}

// Initialize variables
$selected_course = null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '2025-02-02';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '2025-04-26';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$attendance_records = [];
$attendance_summary = [];

// Get attendance data if course is selected
if (isset($_GET['course']) && is_numeric($_GET['course'])) {
    $course_id = $_GET['course'];
    
    // Get course details
    $course_sql = "SELECT * FROM courses WHERE id = ?";
    $course_stmt = $conn->prepare($course_sql);
    $course_stmt->bind_param("i", $course_id);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();
    $selected_course = $course_result->fetch_assoc();
    
    // Get attendance records for the selected date range
    $attendance_sql = "SELECT 
        a.id,
        a.student_id,
        a.date,
        a.time_slot,
        a.status,
        s.full_name,
        s.usn,
        s.class,
        s.section
    FROM 
        attendance a
    JOIN 
        students s ON a.student_id = s.id
    WHERE 
        a.course_id = ? AND
        a.date BETWEEN ? AND ?";
        
    // Add status filter if not "all"
    if ($status_filter != 'all') {
        $attendance_sql .= " AND a.status = ?";
    }
    
    $attendance_sql .= " ORDER BY a.date DESC, a.time_slot ASC, s.full_name ASC";
    
    $attendance_stmt = $conn->prepare($attendance_sql);
    
    if ($status_filter != 'all') {
        $attendance_stmt->bind_param("isss", $course_id, $date_from, $date_to, $status_filter);
    } else {
        $attendance_stmt->bind_param("iss", $course_id, $date_from, $date_to);
    }
    
    $attendance_stmt->execute();
    $attendance_result = $attendance_stmt->get_result();
    
    $attendance_by_date = [];
    $student_attendance = [];
    
    while ($record = $attendance_result->fetch_assoc()) {
        $date = $record['date'];
        $student_id = $record['student_id'];
        $time_slot = $record['time_slot'];
        
        if (!isset($attendance_by_date[$date])) {
            $attendance_by_date[$date] = [];
        }
        
        if (!isset($attendance_by_date[$date][$time_slot])) {
            $attendance_by_date[$date][$time_slot] = [];
        }
        
        $attendance_by_date[$date][$time_slot][] = $record;
        
        // Track attendance by student for summary
        if (!isset($student_attendance[$student_id])) {
            $student_attendance[$student_id] = [
                'student_id' => $student_id,
                'full_name' => $record['full_name'],
                'usn' => $record['usn'],
                'class' => $record['class'] . ' ' . $record['section'],
                'total' => 0,
                'present' => 0,
                'absent' => 0
            ];
        }
        
        $student_attendance[$student_id]['total']++;
        if ($record['status'] == 'present') {
            $student_attendance[$student_id]['present']++;
        } else {
            $student_attendance[$student_id]['absent']++;
        }
    }
    
    $attendance_records = $attendance_by_date;
    $attendance_summary = $student_attendance;
}

// Handle attendance edit if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attendance'])) {
    $attendance_id = $_POST['attendance_id'];
    $new_status = $_POST['new_status'];
    
    $update_sql = "UPDATE attendance SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_status, $attendance_id);
    
    if ($update_stmt->execute()) {
        $message = "Attendance status updated successfully.";
        
        // Refresh the page to show updated data
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        $error = "Error updating attendance status: " . $conn->error;
    }
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv' && $selected_course) {
    try {
        // Clean any previous output
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set the filename
        $filename = "Attendance_" . $selected_course['course_code'] . "_" . date('Y-m-d') . ".csv";
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Export type (detailed or summary)
        $export_type = isset($_GET['export_type']) ? $_GET['export_type'] : 'summary';
        
        if ($export_type == 'summary') {
            // Add header row
            fputcsv($output, array(
                'Attendance Summary for ' . $selected_course['course_code'] . ' - ' . $selected_course['course_name']
            ));
            fputcsv($output, array(
                'Period: ' . date('d M Y', strtotime($date_from)) . ' to ' . date('d M Y', strtotime($date_to))
            ));
            fputcsv($output, array()); // Empty row
            
            // Add column headers
            fputcsv($output, array('S.No', 'USN', 'Student Name', 'Class', 'Present', 'Absent', 'Percentage'));
            
            // Add data rows
            $count = 1;
            foreach ($attendance_summary as $student) {
                $percentage = $student['total'] > 0 ? round(($student['present'] / $student['total']) * 100) : 0;
                fputcsv($output, array(
                    $count,
                    $student['usn'],
                    $student['full_name'],
                    $student['class'],
                    $student['present'],
                    $student['absent'],
                    $percentage . '%'
                ));
                $count++;
            }
        } else {
            // Detailed view export
            fputcsv($output, array(
                'Attendance Records for ' . $selected_course['course_code'] . ' - ' . $selected_course['course_name']
            ));
            fputcsv($output, array(
                'Period: ' . date('d M Y', strtotime($date_from)) . ' to ' . date('d M Y', strtotime($date_to))
            ));
            
            foreach ($attendance_records as $date => $time_slots) {
                fputcsv($output, array()); // Empty row
                fputcsv($output, array(date('d F Y (l)', strtotime($date))));
                
                foreach ($time_slots as $time_slot => $records) {
                    fputcsv($output, array('Time: ' . $time_slot));
                    fputcsv($output, array('S.No', 'USN', 'Student Name', 'Class', 'Status'));
                    
                    $count = 1;
                    foreach ($records as $record) {
                        fputcsv($output, array(
                            $count,
                            $record['usn'],
                            $record['full_name'],
                            $record['class'] . ' ' . $record['section'],
                            ucfirst($record['status'])
                        ));
                        $count++;
                    }
                }
            }
        }
        
        fclose($output);
        exit();
    } catch (Exception $e) {
        // Log the error
        error_log("CSV Export Error: " . $e->getMessage());
        // Redirect back with error message
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=export_failed");
        exit();
    }
}
?>
<!DOCTYPE html
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fc;
            margin: 0;
            padding: 0;
        }
        
        .header {
            background: linear-gradient(90deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
            z-index: 10;
        }
        
        .header-container {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .header-logo {
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .header-logo i {
            margin-right: 10px;
            font-size: 28px;
        }
        
        .header-nav {
            display: flex;
            align-items: center;
        }
        
        .header-nav-item {
            margin-left: 20px;
            position: relative;
        }
        
        .header-nav-link {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .header-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1e40af;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 600;
            color: #334155;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .alert {
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: none;
        }
        
        .alert-success {
            background-color: #dcfce7;
            color: #15803d;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .btn-primary {
            background-color: #2563eb;
            border-color: #2563eb;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .btn-secondary {
            background-color: #94a3b8;
            border-color: #94a3b8;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.3s;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #64748b;
            border-color: #64748b;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .form-select, .form-control {
            border-radius: 6px;
            border: 1px solid #d1d5db;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }
        
        .attendance-date-header {
            background-color: #f8fafc;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            border-left: 4px solid #2563eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .attendance-date {
            font-weight: 600;
            color: #334155;
            font-size: 1.1rem;
        }
        
        .attendance-day {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .time-slot-header {
            background-color: #f8fafc;
            padding: 8px 15px;
            margin: 10px 0;
            border-radius: 6px;
            color: #475569;
            font-weight: 600;
            border-left: 3px solid #94a3b8;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .attendance-table th,
        .attendance-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .attendance-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.45rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .status-present {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #15803d;
            border: 1px solid #86efac;
        }
        
        .status-absent {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        
        .attendance-summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .attendance-summary-table th,
        .attendance-summary-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .attendance-summary-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .attendance-summary-table tr:last-child td {
            border-bottom: none;
        }
        
        .attendance-summary-table tr:hover {
            background-color: #f8fafc;
        }
        
        .percentage {
            font-weight: 600;
        }
        
        .percentage-high {
            color: #15803d;
        }
        
        .percentage-medium {
            color: #ca8a04;
        }
        
        .percentage-low {
            color: #b91c1c;
        }
        
        .no-records {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }
        
        .no-records i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
            display: block;
        }
        
        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }
        
        .tab-button {
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tab-button.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }
        
        .tab-button:hover:not(.active) {
            color: #4b5563;
            border-bottom-color: #e5e7eb;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-edit {
            background-color: #f59e0b;
            border-color: #f59e0b;
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #d97706;
            border-color: #d97706;
            color: white;
        }
        
        .modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        
        .modal-title {
            font-weight: 600;
            color: #334155;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            border-top: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <div class="header-logo">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Teacher Dashboard</span>
            </div>
            <div class="header-nav">
                <div class="header-nav-item">
                    <a href="../logout.php" class="header-nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mt-4 mb-5">
        <h1 class="page-title"><i class="fas fa-clipboard-list text-primary"></i> View Attendance Records</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5>Select Course and Date Range</h5>
            </div>
            <div class="card-body">
                <form method="get" id="filter-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="course" class="form-label">Course</label>
                                <select name="course" id="course" class="form-select" required>
                                    <option value="">Select a course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" <?php echo (isset($_GET['course']) && $_GET['course'] == $course['id']) ? 'selected' : ''; ?>>
                                            <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $date_from; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo $date_to; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="status_filter" class="form-label">Status</label>
                                <select name="status_filter" id="status_filter" class="form-select">
                                    <option value="all" <?php echo (!isset($_GET['status_filter']) || $_GET['status_filter'] == 'all') ? 'selected' : ''; ?>>All</option>
                                    <option value="present" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'present') ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'absent') ? 'selected' : ''; ?>>Absent</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group d-flex align-items-end h-100">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($selected_course): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Attendance Records for <?php echo $selected_course['course_code'] . ' - ' . $selected_course['course_name']; ?></h5>
                    <div class="export-buttons">
                        <div class="dropdown">
                            <button class="btn btn-success dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-file-csv me-2"></i> Export to CSV
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                <li><a class="dropdown-item" href="view_attendance.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv', 'export_type' => 'detailed'])); ?>">
                                    <i class="fas fa-list me-2"></i> Export Detailed View
                                </a></li>
                                <li><a class="dropdown-item" href="view_attendance.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv', 'export_type' => 'summary'])); ?>">
                                    <i class="fas fa-chart-pie me-2"></i> Export Summary View
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="tab-buttons">
                        <button class="tab-button active" data-tab="detailed-view">Detailed View</button>
                        <button class="tab-button" data-tab="summary-view">Summary View</button>
                    </div>
                    
                    <div class="tab-content active" id="detailed-view">
                        <?php if (empty($attendance_records)): ?>
                            <div class="no-records">
                                <i class="fas fa-calendar-times"></i>
                                <p>No attendance records found for the selected date range.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($attendance_records as $date => $time_slots): ?>
                                <div class="attendance-date-header">
                                    <div>
                                        <span class="attendance-date"><?php echo date('d F Y', strtotime($date)); ?></span>
                                        <span class="attendance-day">(<?php echo date('l', strtotime($date)); ?>)</span>
                                    </div>
                                </div>
                                
                                <?php foreach ($time_slots as $time_slot => $records): ?>
                                    <div class="time-slot-header">Time: <?php echo $time_slot; ?></div>
                                    
                                    <table class="attendance-table">
                                        <thead>
                                            <tr>
                                                <th width="5%">S.No</th>
                                                <th width="15%">USN</th>
                                                <th width="30%">Student Name</th>
                                                <th width="15%">Class</th>
                                                <th width="15%">Status</th>
                                                <th width="20%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $count = 1; ?>
                                            <?php foreach ($records as $record): ?>
                                                <tr>
                                                    <td><?php echo $count++; ?></td>
                                                    <td><?php echo htmlspecialchars($record['usn']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['class'] . ' ' . $record['section']); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $record['status']; ?>">
                                                            <?php if ($record['status'] == 'present'): ?>
                                                                <i class="fas fa-check-circle me-1"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-times-circle me-1"></i>
                                                            <?php endif; ?>
                                                            <?php echo ucfirst($record['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <button type="button" class="btn btn-sm btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $record['id']; ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                        </div>
                                                        
                                                        <!-- Edit Modal -->
                                                        <div class="modal fade" id="editModal<?php echo $record['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $record['id']; ?>" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="editModalLabel<?php echo $record['id']; ?>">Edit Attendance Status</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <form method="post">
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="attendance_id" value="<?php echo $record['id']; ?>">
                                                                            
                                                                            <p><strong>Student:</strong> <?php echo htmlspecialchars($record['full_name']); ?></p>
                                                                            <p><strong>Date:</strong> <?php echo date('d F Y', strtotime($record['date'])); ?></p>
                                                                            <p><strong>Time Slot:</strong> <?php echo $record['time_slot']; ?></p>
                                                                            
                                                                            <div class="form-group mt-3">
                                                                                <label for="new_status" class="form-label">Status</label>
                                                                                <select name="new_status" id="new_status" class="form-select">
                                                                                    <option value="present" <?php echo $record['status'] == 'present' ? 'selected' : ''; ?>>Present</option>
                                                                                    <option value="absent" <?php echo $record['status'] == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <button type="submit" name="update_attendance" class="btn btn-primary">Update Status</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-content" id="summary-view">
                        <?php if (empty($attendance_summary)): ?>
                            <div class="no-records">
                                <i class="fas fa-chart-pie"></i>
                                <p>No attendance records found for the selected date range.</p>
                            </div>
                        <?php else: ?>
                            <div class="mb-3 d-flex justify-content-end">
                                <div style="width: 250px;">
                                    <select id="attendance-threshold" class="form-select">
                                        <option value="0">Show All Students</option>
                                        <option value="85">Below 85% Attendance</option>
                                        <option value="75">Below 75% Attendance (Critical)</option>
                                    </select>
                                </div>
                            </div>
                            <table class="attendance-summary-table" id="summaryTable">
                                <thead>
                                    <tr>
                                        <th width="5%">S.No</th>
                                        <th width="15%">USN</th>
                                        <th width="30%">Student Name</th>
                                        <th width="15%">Class</th>
                                        <th width="10%">Present</th>
                                        <th width="10%">Absent</th>
                                        <th width="15%">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $count = 1; ?>
                                    <?php foreach ($attendance_summary as $student): ?>
                                        <?php 
                                            $percentage = $student['total'] > 0 ? round(($student['present'] / $student['total']) * 100) : 0;
                                            $percentage_class = 'percentage-high';
                                            if ($percentage < 75) {
                                                $percentage_class = 'percentage-low';
                                            } else if ($percentage < 85) {
                                                $percentage_class = 'percentage-medium';
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo $count++; ?></td>
                                            <td><?php echo htmlspecialchars($student['usn']); ?></td>
                                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['class']); ?></td>
                                            <td><?php echo $student['present']; ?></td>
                                            <td><?php echo $student['absent']; ?></td>
                                            <td class="percentage <?php echo $percentage_class; ?>"><?php echo $percentage; ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-end mt-3">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <script src="https://cdn.jsdelivivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab switching functionality
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons and contents
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Add active class to current button and corresponding content
                button.classList.add('active');
                const tabId = button.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Attendance threshold filtering
        const thresholdFilter = document.getElementById('attendance-threshold');
        if (thresholdFilter) {
            thresholdFilter.addEventListener('change', function() {
                const threshold = parseInt(this.value);
                const rows = document.querySelectorAll('#summaryTable tbody tr');
                
                rows.forEach(row => {
                    const percentage = parseInt(row.querySelector('.percentage').textContent);
                    if (threshold === 0 || percentage < threshold) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    </script>
</body>
</html>