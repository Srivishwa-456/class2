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
$page_title = "Attendance Details";
$is_student = true;

// Get course code from URL parameter
$course_code = isset($_GET['course']) ? $_GET['course'] : '';

// If no course code provided, redirect to dashboard
if (empty($course_code)) {
    header("Location: dashboard.php");
    exit();
}

// Get course details from database
$course_sql = "SELECT c.* FROM courses c WHERE c.course_code = ?";
$course_stmt = $conn->prepare($course_sql);
$course_stmt->bind_param("s", $course_code);
$course_stmt->execute();
$course_result = $course_stmt->get_result();
$course = $course_result->fetch_assoc();

// Debug info - course query
echo "<!-- Course query: " . ($course ? "Found course: " . $course['course_name'] : "Course not found") . " -->";

// If course not found, redirect to dashboard
if (!$course) {
    $_SESSION['error'] = "Course not found.";
    header("Location: dashboard.php");
    exit();
}

// Get student's attendance records for this course
$attendance_sql = "SELECT a.*, DATE_FORMAT(a.date, '%d-%m-%Y') as formatted_date 
                  FROM attendance a 
                  WHERE a.student_id = ? AND a.course_id = ? 
                  ORDER BY a.date DESC";
$attendance_stmt = $conn->prepare($attendance_sql);
$attendance_stmt->bind_param("ii", $student_id, $course['id']);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

// Debug info - attendance records
echo "<!-- Attendance query: Found " . $attendance_result->num_rows . " records -->";

$present_classes = [];
$absent_classes = [];
$sl_no_present = 1;
$sl_no_absent = 1;

while ($record = $attendance_result->fetch_assoc()) {
    $entry = [
        'date' => $record['formatted_date'],
        'time' => $record['time_slot'],
        'status' => ucfirst($record['status'])
    ];
    
    if ($record['status'] == 'present') {
        $entry['sl_no'] = $sl_no_present++;
        $present_classes[] = $entry;
    } else {
        $entry['sl_no'] = $sl_no_absent++;
        $absent_classes[] = $entry;
    }
}

// Count total classes
$present_count = count($present_classes);
$absent_count = count($absent_classes);
$total_classes = $present_count + $absent_count;

// Calculate attendance percentage
$attendance_percentage = $total_classes > 0 ? round(($present_count / $total_classes) * 100, 2) : 0;

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
        
        .attendance-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .attendance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .attendance-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .attendance-count {
            background-color: #4caf50;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .absent-count {
            background-color: #f44336;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .attendance-table th, 
        .attendance-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .attendance-table th {
            background-color: #f8f9fc;
            font-weight: 600;
            color: #4e73df;
            font-size: 14px;
        }
        
        .attendance-table tr:last-child td {
            border-bottom: none;
        }
        
        .attendance-table tr:hover {
            background-color: #f8f9fc;
        }
        
        .status-present {
            color: #4caf50;
            font-weight: 500;
        }
        
        .status-absent {
            color: #f44336;
            font-weight: 500;
        }
        
        .back-button {
            background-color: #4e73df;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: all 0.2s;
        }
        
        .back-button:hover {
            background-color: #2e59d9;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .course-info {
            background-color: #e8f5e9;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .course-details h4 {
            margin: 0;
            color: #2e7d32;
            font-size: 20px;
        }
        
        .course-details p {
            margin: 5px 0 0;
            color: #555;
            font-size: 14px;
        }
        
        .attendance-percentage {
            background-color: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
            font-weight: bold;
            color: #2e7d32;
            border: 3px solid #4caf50;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
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
                <span style="font-size: 14px;"><?php echo isset($student['class']) && isset($student['section']) ? htmlspecialchars($student['class'] . ', ' . $student['section']) : ''; ?></span>
            </div>
        </div>
        <div class="profile-img">
            <i class="fas fa-user-graduate"></i>
        </div>
    </div>
    
    <div class="container mt-4">
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
        
        <div class="course-info">
            <div class="course-details">
                <h4><?php echo htmlspecialchars($course_code); ?>: <?php echo htmlspecialchars($course['course_name']); ?></h4>
                <p>Total Classes: <?php echo $total_classes; ?> | Present: <?php echo $present_count; ?> | Absent: <?php echo $absent_count; ?></p>
            </div>
            <div class="attendance-percentage">
                <?php echo $attendance_percentage; ?>%
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="attendance-card">
                    <div class="attendance-header">
                        <h5 class="attendance-title">
                            Present <span class="attendance-count">CLASSES <?php echo $present_count; ?></span>
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>SL NO</th>
                                    <th>DATE</th>
                                    <th>TIME</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($present_classes) > 0): ?>
                                    <?php foreach ($present_classes as $class): ?>
                                    <tr>
                                        <td><?php echo $class['sl_no']; ?></td>
                                        <td><?php echo $class['date']; ?></td>
                                        <td><?php echo $class['time']; ?></td>
                                        <td class="status-present"><?php echo $class['status']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3">No present records found in database.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="attendance-card">
                    <div class="attendance-header">
                        <h5 class="attendance-title">
                            Absent List <span class="attendance-count absent-count">CLASSES <?php echo $absent_count; ?></span>
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>SL NO</th>
                                    <th>DATE</th>
                                    <th>TIME</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($absent_classes) > 0): ?>
                                    <?php foreach ($absent_classes as $class): ?>
                                    <tr>
                                        <td><?php echo $class['sl_no']; ?></td>
                                        <td><?php echo $class['date']; ?></td>
                                        <td><?php echo $class['time']; ?></td>
                                        <td class="status-absent"><?php echo $class['status']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3">No absences recorded in database.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include_once '../includes/footer.php'; ?>
</body>
</html>