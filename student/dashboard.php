<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if student is logged in
if (!isset($_SESSION['student_id']) || !isset($_SESSION['is_student'])) {
    header("Location: ../student_login.php");
    exit();
}

// Debug info - Show connection status
echo "<!-- Database connection: " . ($conn ? "SUCCESS" : "FAILED") . " -->";

$student_id = $_SESSION['student_id'];
$page_title = "Student Dashboard";
$is_student = true;

// Get student details with error handling
$sql = "SELECT s.*, 
        COALESCE((SELECT COUNT(*) FROM attendance WHERE student_id = s.id AND status = 'present'), 0) as present_count,
        COALESCE((SELECT COUNT(*) FROM attendance WHERE student_id = s.id AND status = 'absent'), 0) as absent_count,
        COALESCE((SELECT COUNT(*) FROM attendance WHERE student_id = s.id), 0) as total_attendance
        FROM students s 
        WHERE s.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Debug info - Display student query result
echo "<!-- Student query: " . ($student ? "Data found" : "No data found") . " -->";

// Add error handling for when student data is not found
if (!$student) {
    $_SESSION['error'] = "Student data not found. Please contact administrator.";
    header("Location: ../student_login.php");
    exit();
}

// Calculate attendance percentage with null check
$attendance_percentage = $student['total_attendance'] > 0 
    ? round(($student['present_count'] / $student['total_attendance']) * 100, 2) 
    : 0;

// Fetch all available courses and indicate enrollment status
$all_courses_sql = "SELECT 
    c.id,
    c.course_code,
    c.course_name,
    c.semester,
    IF(sc.student_id IS NULL, 0, 1) as is_enrolled,
    COUNT(a.id) as total_classes,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count
FROM 
    courses c
LEFT JOIN 
    student_courses sc ON c.id = sc.course_id AND sc.student_id = ?
LEFT JOIN 
    attendance a ON a.student_id = ? AND a.course_id = c.id
GROUP BY 
    c.id, c.course_code, c.course_name, c.semester, is_enrolled
ORDER BY
    is_enrolled DESC, c.course_code ASC";

$all_courses_stmt = $conn->prepare($all_courses_sql);
$all_courses_stmt->bind_param("ii", $student_id, $student_id);
$all_courses_stmt->execute();
$all_courses_result = $all_courses_stmt->get_result();
$all_courses = [];

while ($course = $all_courses_result->fetch_assoc()) {
    // Calculate attendance percentage for each course
    $course_attendance_percentage = $course['total_classes'] > 0 
        ? round(($course['present_count'] / $course['total_classes']) * 100, 2) 
        : 0;
    
    // Determine status based on attendance percentage
    if (!$course['is_enrolled']) {
        $status = "Not Enrolled";
        $status_class = "secondary";
    } elseif ($course_attendance_percentage >= 75) {
        $status = "Good";
        $status_class = "success";
    } elseif ($course_attendance_percentage >= 50) {
        $status = "Warning";
        $status_class = "warning";
    } else {
        $status = "Low";
        $status_class = "danger";
    }
    
    $course['attendance_percentage'] = $course_attendance_percentage;
    $course['status'] = $status;
    $course['status_class'] = $status_class;
    $all_courses[] = $course;
}

// Debug info - Display courses count
echo "<!-- ALL Courses query: " . count($all_courses) . " courses found -->";

?>
<!DOCTYPE html>
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
            background: linear-gradient(90deg, #1a237e 0%, #283593 100%);
            color: white;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
            z-index: 10;
        }
        
        /* New header navigation styles */
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
        
        .header-nav-link i {
            margin-right: 8px;
        }
        
        .logout-link {
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }
        
        .logout-link:hover {
            background-color: rgba(255, 255, 255, 0.25);
        }
        
        /* Enhanced Header Styles */
        .header {
            background: linear-gradient(90deg, #4a148c 0%, #7b1fa2 100%);
            color: white;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
            z-index: 10;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            margin-left: 20px;
        }
        
        .logo-img {
            height: 45px;
            margin-right: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            background-color: white;
            padding: 3px;
        }
        
        .header-title {
            display: flex;
            flex-direction: column;
        }
        
        .system-name {
            font-weight: bold;
            font-size: 18px;
            letter-spacing: 0.5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .portal-name {
            font-size: 13px;
            opacity: 0.9;
            letter-spacing: 0.5px;
        }
        
        .header-right {
            margin-right: 20px;
            display: flex;
            gap: 20px;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 4px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .nav-link:hover {
            background-color: rgba(255,255,255,0.15);
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .nav-link i {
            font-size: 14px;
        }
        
        .header-left img {
            height: 40px;
            margin-right: 10px;
        }
        
        .header-right {
            margin-right: 20px;
        }
        
        .header-right a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            font-size: 14px;
        }
        
        .student-info {
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            color: white;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .student-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .student-usn {
            text-align: left;
            font-size: 18px;
        }
        
        .profile-img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.8);
            background-color: rgba(255,255,255,0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            margin-left: 20px;
        }
        
        .profile-img i {
            font-size: 50px;
            color: white;
        }
        
        .theme-switch {
            background-color: #ddd;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 10px;
            display: inline-block;
        }
        
        .dashboard-section {
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .attendance-gauge {
            position: relative;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }
        
        .gauge-container {
            position: relative;
            height: 150px;
            overflow: hidden;
        }
        
        .gauge-bg {
            width: 100%;
            height: 300px;
            border-radius: 300px 300px 0 0;
            background: linear-gradient(90deg, #f44336 0%, #ffeb3b 50%, #4caf50 100%);
            position: absolute;
            top: 0;
            left: 0;
            overflow: hidden;
        }
        
        .gauge-cover {
            width: 80%;
            height: 240px;
            background-color: #f8f9fc;
            border-radius: 240px 240px 0 0;
            position: absolute;
            top: 30px;
            left: 10%;
        }
        
        .gauge-value {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            font-size: 24px;
            font-weight: bold;
        }
        
        .gauge-needle {
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 4px;
            height: 120px;
            background-color: #333;
            transform-origin: bottom center;
            transform: translateX(-50%) rotate(<?php echo ($attendance_percentage / 100) * 180; ?>deg);
            z-index: 10;
        }
        
        .gauge-needle::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: -5px;
            width: 14px;
            height: 14px;
            background-color: #333;
            border-radius: 50%;
        }
        
        .attendance-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            flex: 1;
            margin: 0 5px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        /* Enhanced course table styles */
        .course-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .course-table th, .course-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e3e6f0;
            vertical-align: middle;
        }
        
        .course-table th {
            background-color: #f8f9fc;
            font-weight: 600;
            color: #4e73df;
            font-size: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .course-table tr:hover {
            background-color: #f8f9fc;
        }
        
        .course-table tr:last-child td {
            border-bottom: none;
        }
        
        .progress {
            height: 10px;
            background-color: #eaecf4;
            border-radius: 10px;
            overflow: hidden;
            margin: 0;
        }
        
        .progress-bar {
            border-radius: 10px;
        }
        
        .btn-view {
            background-color: #4e73df;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-view:hover {
            background-color: #2e59d9;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-attendance {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            cursor: pointer;
        }
        
        .btn-notes {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .btn-view {
            background-color: #fff3e0;
            color: #ff9800;
        }
        
        .btn-cie {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px 0;
            font-size: 12px;
            margin-top: 20px;
        }
        
        .footer a {
            color: #ddd;
            text-decoration: none;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .badge-success {
            background-color: #4caf50;
            color: white;
        }
        
        .badge-danger {
            background-color: #f44336;
            color: white;
        }
        
        .badge-warning {
            background-color: #ff9800;
            color: white;
        }
        
        /* Logout button styles */
        .logout-btn {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.4);
            font-size: 12px;
            padding: 6px 15px;
            border-radius: 20px;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            margin-top: 8px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .logout-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .logout-btn i {
            font-size: 14px;
        }
        
        /* Enhanced chart styles - removed */
        
        /* Course legend styles - removed */
    </style>
</head>
<body>
    <!-- Header with Navigation -->
    <div class="header">
        <div class="header-container">
            <div class="header-logo">
                <i class="fas fa-graduation-cap"></i>
                <span>Student Dashboard</span>
            </div>
                <div class="header-nav-item">
                <a href="../logout.php" class="header-nav-link logout-link">
                <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Student Info -->
    <div class="student-info">
        <div>
            <div class="student-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            <div class="student-usn">
                USN: <?php echo htmlspecialchars($_SESSION['usn']); ?><br>
                <span style="font-size: 14px;"><?php echo htmlspecialchars($student['class'] . ', ' . $student['section']); ?></span>
            </div>
        </div>
        <div class="d-flex flex-column align-items-center">
            <div class="profile-img">
                <i class="fas fa-user-graduate"></i>
            </div>
        </div>
    </div>
    
    <div class="container mt-4">
        <div class="row">            
            <!-- Attendance Chart section removed -->
        </div>
        
        <!-- Course Registration -->
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Course Registration and Attendance Status</h5>
                <div class="badge badge-success">Current Semester</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="course-table">
                        <thead>
                            <tr>
                                <th>COURSE CODE</th>
                                <th>COURSE NAME</th>
                                <th>ATTENDANCE %</th>
                                <th>STATUS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_courses)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No courses available for this semester. Database query returned no results.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($all_courses as $course): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td>
                                        <?php if ($course['is_enrolled']): ?>
                                        <div class="progress">
                                            <div class="progress-bar bg-<?php echo $course['status_class']; ?>" role="progressbar" 
                                                style="width: <?php echo $course['attendance_percentage']; ?>%;" 
                                                aria-valuenow="<?php echo $course['attendance_percentage']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $course['attendance_percentage']; ?>%
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">Not enrolled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-<?php echo $course['status_class']; ?>"><?php echo $course['status']; ?></span></td>
                                    <td>
                                        <?php if ($course['is_enrolled']): ?>
                                            <a href="view_attendence.php?course=<?php echo urlencode($course['course_code']); ?>" class="btn-attendance btn-view">VIEW DETAILS</a>
                                        <?php else: ?>
                                            <a href="enroll_course.php?course=<?php echo urlencode($course['id']); ?>" class="btn-attendance btn-notes">ENROLL</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Subject Marks Table -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Subject Marks</h5>
            <div class="badge badge-success">Current Semester</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php
                // Get enrolled courses with marks
                $marks_sql = "SELECT 
                    c.course_code, 
                    c.course_name,
                    sm.exam_type,
                    sm.marks_obtained,
                    sm.max_marks,
                    sm.remarks,
                    t.full_name as teacher_name,
                    DATE_FORMAT(sm.created_at, '%d-%m-%Y') as formatted_date
                FROM 
                    student_courses sc
                JOIN 
                    courses c ON sc.course_id = c.id
                LEFT JOIN 
                    subject_marks sm ON sc.student_id = sm.student_id AND sc.course_id = sm.course_id
                LEFT JOIN 
                    teachers t ON sm.added_by = t.id
                WHERE 
                    sc.student_id = ?
                ORDER BY 
                    c.course_code ASC, sm.exam_type ASC";
                    
                $marks_stmt = $conn->prepare($marks_sql);
                $marks_stmt->bind_param("i", $student_id);
                $marks_stmt->execute();
                $marks_result = $marks_stmt->get_result();
                
                // Organize marks by course and exam type
                $marks_data = [];
                while ($mark = $marks_result->fetch_assoc()) {
                    $course_code = $mark['course_code'];
                    if (!isset($marks_data[$course_code])) {
                        $marks_data[$course_code] = [
                            'course_name' => $mark['course_name'],
                            'exams' => []
                        ];
                    }
                    
                    if ($mark['exam_type']) {
                        $marks_data[$course_code]['exams'][$mark['exam_type']] = [
                            'marks_obtained' => $mark['marks_obtained'],
                            'max_marks' => $mark['max_marks'],
                            'percentage' => $mark['max_marks'] > 0 ? round(($mark['marks_obtained'] / $mark['max_marks'] * 100), 2) : 0,
                            'remarks' => $mark['remarks'],
                            'teacher_name' => $mark['teacher_name'],
                            'date' => $mark['formatted_date']
                        ];
                    }
                }
                ?>
                
                <?php if (empty($marks_data)): ?>
                    <div class="alert alert-info">No marks data available for your enrolled courses.</div>
                <?php else: ?>
                    <table class="course-table">
                        <thead>
                            <tr>
                                <th>COURSE</th>
                                <th>INTERNAL</th>
                                <th>MIDTERM</th>
                                <th>FINAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($marks_data as $course_code => $course_data): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($course_code); ?></strong><br>
                                        <small><?php echo htmlspecialchars($course_data['course_name']); ?></small>
                                    </td>
                                    
                                    <!-- Internal Exam -->
                                    <td>
                                        <?php if (isset($course_data['exams']['internal'])): 
                                            $exam = $course_data['exams']['internal']; ?>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <strong><?php echo $exam['marks_obtained']; ?>/<?php echo $exam['max_marks']; ?></strong>
                                                    <div class="progress mt-1" style="height: 5px; width: 60px;">
                                                        <div class="progress-bar bg-<?php echo $exam['percentage'] >= 40 ? 'success' : 'danger'; ?>" 
                                                            style="width: <?php echo $exam['percentage']; ?>%"></div>
                                                    </div>
                                                </div>
                                                <span class="badge bg-<?php echo $exam['percentage'] >= 40 ? 'success' : 'danger'; ?>">
                                                    <?php echo $exam['percentage']; ?>%
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Not available</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Midterm Exam -->
                                    <td>
                                        <?php if (isset($course_data['exams']['midterm'])): 
                                            $exam = $course_data['exams']['midterm']; ?>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <strong><?php echo $exam['marks_obtained']; ?>/<?php echo $exam['max_marks']; ?></strong>
                                                    <div class="progress mt-1" style="height: 5px; width: 60px;">
                                                        <div class="progress-bar bg-<?php echo $exam['percentage'] >= 40 ? 'success' : 'danger'; ?>" 
                                                            style="width: <?php echo $exam['percentage']; ?>%"></div>
                                                    </div>
                                                </div>
                                                <span class="badge bg-<?php echo $exam['percentage'] >= 40 ? 'success' : 'danger'; ?>">
                                                    <?php echo $exam['percentage']; ?>%
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Not available</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Final Exam -->
                                    <td>
                                        <?php if (isset($course_data['exams']['final'])): 
                                            $exam = $course_data['exams']['final']; ?>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <strong><?php echo $exam['marks_obtained']; ?>/<?php echo $exam['max_marks']; ?></strong>
                                                    <div class="progress mt-1" style="height: 5px; width: 60px;">
                                                        <div class="progress-bar bg-<?php echo $exam['percentage'] >= 40 ? 'success' : 'danger'; ?>" 
                                                            style="width: <?php echo $exam['percentage']; ?>%"></div>
                                                    </div>
                                                </div>
                                                <span class="badge bg-<?php echo $exam['percentage'] >= 40 ? 'success' : 'danger'; ?>">
                                                    <?php echo $exam['percentage']; ?>%
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Not available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include_once '../includes/footer.php'; ?>
</body>
</html>
