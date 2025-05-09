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
$page_title = "Teacher Dashboard";
$is_teacher = true;

// Get teacher details
$sql = "SELECT * FROM teachers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

// Add error handling for when teacher data is not found
if (!$teacher) {
    $_SESSION['error'] = "Teacher data not found. Please contact administrator.";
    header("Location: ../teacher_login.php");
    exit();
}

// Get teacher's assigned courses
$courses_sql = "SELECT 
    c.id, 
    c.course_code, 
    c.course_name, 
    c.semester,
    COUNT(DISTINCT sc.student_id) as student_count
FROM 
    courses c
JOIN 
    teacher_courses tc ON c.id = tc.course_id
LEFT JOIN 
    student_courses sc ON c.id = sc.course_id
WHERE 
    tc.teacher_id = ?
GROUP BY 
    c.id, c.course_code, c.course_name, c.semester";

$courses_stmt = $conn->prepare($courses_sql);
$courses_stmt->bind_param("i", $teacher_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();
$courses = [];

while($course = $courses_result->fetch_assoc()) {
    $courses[] = $course;
}

// Get recent attendance sessions
$recent_attendance_sql = "SELECT 
    c.course_code, 
    c.course_name, 
    DATE_FORMAT(a.date, '%d-%m-%Y') as attendance_date,
    a.time_slot,
    COUNT(DISTINCT a.student_id) as marked_students
FROM 
    attendance a
JOIN 
    courses c ON a.course_id = c.id
JOIN 
    teacher_courses tc ON c.id = tc.course_id AND tc.teacher_id = ?
WHERE 
    a.marked_by = ?
GROUP BY 
    c.id, a.date, a.time_slot
ORDER BY 
    a.date DESC, a.time_slot DESC
LIMIT 5";

$recent_stmt = $conn->prepare($recent_attendance_sql);
$recent_stmt->bind_param("ii", $teacher_id, $teacher_id);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();
$recent_attendance = [];

while($attendance = $recent_result->fetch_assoc()) {
    $recent_attendance[] = $attendance;
}
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
        
        .teacher-info {
            background: linear-gradient(135deg, #155e75 0%, #0ea5e9 100%);
            color: white;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .teacher-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .teacher-details {
            text-align: left;
            font-size: 16px;
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
        
        .dashboard-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            flex: 1;
            min-width: 200px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .stat-icon i {
            font-size: 28px;
            color: white;
        }
        
        .stat-details {
            flex-grow: 1;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        
        .icon-courses {
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
        }
        
        .icon-students {
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
        }
        
        .icon-attendance {
            background: linear-gradient(135deg, #b45309 0%, #f59e0b 100%);
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1e40af;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .action-card {
            flex: 1;
            min-width: 180px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .action-header {
            background: linear-gradient(90deg, #2563eb 0%, #3b82f6 100%);
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: 500;
        }
        
        .action-body {
            padding: 20px;
            text-align: center;
        }
        
        .action-icon {
            font-size: 36px;
            margin-bottom: 15px;
            color: #3b82f6;
        }
        
        .action-link {
            display: inline-block;
            padding: 8px 20px;
            background-color: #eff6ff;
            color: #1d4ed8;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .action-link:hover {
            background-color: #dbeafe;
        }
        
        .courses-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .courses-table th,
        .courses-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .courses-table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .courses-table tr:last-child td {
            border-bottom: none;
        }
        
        .courses-table tr:hover {
            background-color: #f8fafc;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-primary {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        
        .badge-success {
            background-color: #dcfce7;
            color: #15803d;
        }
        
        .badge-warning {
            background-color: #fef3c7;
            color: #b45309;
        }
        
        .badge-danger {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .btn-sm {
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: #2563eb;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #1d4ed8;
            color: white;
        }
        
        .btn-success {
            background-color: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #059669;
            color: white;
        }
        
        .btn-warning {
            background-color: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #d97706;
            color: white;
        }
        
        .btn-group {
            display: flex;
            gap: 5px;
        }
        
        .recent-activity {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .activity-header {
            background-color: #f8fafc;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .activity-header h5 {
            margin: 0;
            color: #334155;
            font-weight: 600;
        }
        
        .activity-body {
            padding: 0;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            flex-shrink: 0;
        }
        
        .activity-icon i {
            font-size: 18px;
            color: white;
        }
        
        .activity-details {
            flex-grow: 1;
        }
        
        .activity-course {
            font-weight: 500;
            margin-bottom: 3px;
        }
        
        .activity-meta {
            display: flex;
            font-size: 12px;
            color: #6b7280;
        }
        
        .activity-date {
            margin-right: 15px;
        }
        
        .activity-count {
            font-weight: 500;
            color: #334155;
        }
        
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #d1d5db;
        }
        
        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Header with Navigation -->
    <div class="header">
        <div class="header-container">
            <div class="header-logo">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Teacher Dashboard</span>
            </div>
            <div class="header-nav">
                <div class="header-nav-item">
                    <a href="../logout.php" class="header-nav-link logout-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Teacher Info -->
    <div class="teacher-info">
        <div>
            <div class="teacher-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $teacher['full_name']); ?></div>
            <div class="teacher-details">
                <div>Email: <?php echo htmlspecialchars($teacher['email'] ?? ''); ?></div>
                <div>Department: <?php echo htmlspecialchars($teacher['department'] ?? ''); ?></div>
                <div>ID: <?php echo htmlspecialchars($teacher['employee_id'] ?? ''); ?></div>
            </div>
        </div>
        <div class="profile-img">
            <i class="fas fa-user-tie"></i>
        </div>
    </div>
    
    <div class="container mt-4">        
        <!-- Quick Actions -->
        <h5 class="section-title">Quick Actions</h5>
        <div class="quick-actions">
            <div class="action-card">
                <div class="action-header">
                    Mark Attendance
                </div>
                <div class="action-body">
                    <div class="action-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <a href="mark_attendance.php" class="action-link">Start Now</a>
                </div>
            </div>
            
            <div class="action-card">
                <div class="action-header">
                    View Reports
                </div>
                <div class="action-body">
                    <div class="action-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <a href="view_attendance.php" class="action-link">View Reports</a>
                </div>
            </div>
            
            <div class="action-card">
                <div class="action-header">
                    Student List
                </div>
                <div class="action-body">
                    <div class="action-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <a href="view_students.php" class="action-link">View Students</a>
                </div>
            </div>
            
            <div class="action-card">
                <div class="action-header">
                    Settings
                </div>
                <div class="action-body">
                    <div class="action-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <a href="settings.php" class="action-link">Go to Settings</a>
                </div>
            </div>
            
            <div class="action-card">
                <div class="action-header">
                    Subject Marks
                </div>
                <div class="action-body">
                    <div class="action-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <a href="manage_marks.php" class="action-link">Manage Marks</a>
                </div>
            </div>
        </div>
        
        <!-- Courses Section -->
        <h5 class="section-title">Your Courses</h5>
        <?php if(empty($courses)): ?>
            <div class="empty-state">
                <i class="fas fa-book"></i>
                <p>You don't have any assigned courses yet.</p>
                <a href="contact_admin.php" class="btn-sm btn-primary">Contact Admin</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="courses-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Course Name</th>
                            <th>Semester</th>
                            <th>Students</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($courses as $course): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><span class="badge badge-primary"><?php echo htmlspecialchars($course['semester']); ?></span></td>
                                <td><?php echo $course['student_count']; ?> students</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="mark_attendance.php?course=<?php echo $course['id']; ?>" class="btn-sm btn-success">Mark Attendance</a>
                                        <a href="view_attendance.php?course=<?php echo $course['id']; ?>" class="btn-sm btn-primary">View Record</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Recent Activities -->
        <h5 class="section-title">Recent Attendance Activities</h5>
        <div class="recent-activity">
            <div class="activity-header">
                <h5>Last Marked Attendance</h5>
            </div>
            <div class="activity-body">
                <?php if(empty($recent_attendance)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No recent attendance activities found.</p>
                        <a href="mark_attendance.php" class="btn-sm btn-primary">Mark Attendance Now</a>
                    </div>
                <?php else: ?>
                    <?php foreach($recent_attendance as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-course"><?php echo htmlspecialchars($activity['course_code'] . ' - ' . $activity['course_name']); ?></div>
                                <div class="activity-meta">
                                    <div class="activity-date"><i class="far fa-calendar-alt me-1"></i> <?php echo $activity['attendance_date']; ?></div>
                                    <div class="activity-time"><i class="far fa-clock me-1"></i> <?php echo $activity['time_slot']; ?></div>
                                </div>
                            </div>
                            <div class="activity-count">
                                <span class="badge badge-success"><?php echo $activity['marked_students']; ?> students</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include_once '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>