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
$page_title = "View Students";
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
$students = [];

// Get students data if course is selected
if (isset($_GET['course']) && is_numeric($_GET['course'])) {
    $course_id = $_GET['course'];
    
    // Get course details
    $course_sql = "SELECT * FROM courses WHERE id = ?";
    $course_stmt = $conn->prepare($course_sql);
    $course_stmt->bind_param("i", $course_id);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();
    $selected_course = $course_result->fetch_assoc();
    
    // Get students enrolled in the selected course
    $students_sql = "SELECT 
        s.id,
        s.full_name,
        s.usn,
        s.email,
        s.phone,
        s.class,
        s.section,
        sc.date_enrolled
    FROM 
        students s
    JOIN 
        student_courses sc ON s.id = sc.student_id
    WHERE 
        sc.course_id = ?
    ORDER BY 
        s.class ASC, s.section ASC, s.full_name ASC";
    
    $students_stmt = $conn->prepare($students_sql);
    $students_stmt->bind_param("i", $course_id);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    
    while ($student = $students_result->fetch_assoc()) {
        $students[] = $student;
    }
}

// Search functionality
$search_query = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $_GET['search'];
    
    if (!empty($students)) {
        $filtered_students = [];
        foreach ($students as $student) {
            if (
                stripos($student['full_name'], $search_query) !== false ||
                stripos($student['usn'], $search_query) !== false ||
                stripos($student['email'], $search_query) !== false ||
                stripos($student['class'] . $student['section'], $search_query) !== false
            ) {
                $filtered_students[] = $student;
            }
        }
        $students = $filtered_students;
    }
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
        
        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .students-table th,
        .students-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .students-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .students-table tr:last-child td {
            border-bottom: none;
        }
        
        .students-table tr:hover {
            background-color: #f8fafc;
        }
        
        .students-table .email, .students-table .phone {
            color: #4b5563;
            font-size: 0.9rem;
        }
        
        .students-table .usn {
            font-weight: 600;
            color: #1e40af;
        }
        
        .students-table .name {
            font-weight: 500;
        }
        
        .students-table .class-section {
            background-color: #f1f5f9;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.85rem;
            display: inline-block;
        }
        
        .date-enrolled {
            font-size: 0.85rem;
            color: #64748b;
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
        
        .search-box {
            display: flex;
            gap: 0.5rem;
        }
        
        .student-count {
            margin-bottom: 1rem;
            font-size: 0.95rem;
            color: #64748b;
        }
        
        .student-count strong {
            color: #1e40af;
        }
        
        .class-group-header {
            background-color: #f8fafc;
            padding: 10px 15px;
            margin-top: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            border-left: 4px solid #2563eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #334155;
            font-size: 1.1rem;
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
        <h1 class="page-title"><i class="fas fa-user-graduate text-primary"></i> View Students</h1>
        
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
                <h5>Select Course</h5>
            </div>
            <div class="card-body">
                <form method="get" id="filter-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="course" class="form-label">Course</label>
                                <select name="course" id="course" class="form-select" required onchange="this.form.submit()">
                                    <option value="">Select a course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" <?php echo (isset($_GET['course']) && $_GET['course'] == $course['id']) ? 'selected' : ''; ?>>
                                            <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($selected_course): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Students enrolled in <?php echo $selected_course['course_code'] . ' - ' . $selected_course['course_name']; ?></h5>
                    
                    <form method="get" class="search-box">
                        <input type="hidden" name="course" value="<?php echo $selected_course['id']; ?>">
                        <input type="text" name="search" class="form-control" placeholder="Search students..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (empty($students)): ?>
                        <div class="no-records">
                            <i class="fas fa-users-slash"></i>
                            <?php if (!empty($search_query)): ?>
                                <p>No students found matching your search query.</p>
                            <?php else: ?>
                                <p>No students are enrolled in this course yet.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="student-count">
                            <strong><?php echo count($students); ?></strong> students found
                            <?php if (!empty($search_query)): ?>
                                for search query: <strong><?php echo htmlspecialchars($search_query); ?></strong>
                            <?php endif; ?>
                        </div>
                        
                        <?php
                        // Group students by class and section
                        $grouped_students = [];
                        foreach ($students as $student) {
                            $class_section = $student['class'] . ' ' . $student['section'];
                            if (!isset($grouped_students[$class_section])) {
                                $grouped_students[$class_section] = [];
                            }
                            $grouped_students[$class_section][] = $student;
                        }
                        
                        // Sort by class and section
                        ksort($grouped_students);
                        
                        foreach ($grouped_students as $class_section => $class_students):
                        ?>
                            <div class="class-group-header">
                                <span><?php echo $class_section; ?></span>
                                <span class="badge bg-primary"><?php echo count($class_students); ?> students</span>
                            </div>
                            
                            <table class="students-table">
                                <thead>
                                    <tr>
                                        <th width="5%">S.No</th>
                                        <th width="15%">USN</th>
                                        <th width="25%">Student Name</th>
                                        <th width="25%">Email</th>
                                        <th width="15%">Phone</th>
                                        <th width="15%">Date Enrolled</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $count = 1; ?>
                                    <?php foreach ($class_students as $student): ?>
                                        <tr>
                                            <td><?php echo $count++; ?></td>
                                            <td class="usn"><?php echo htmlspecialchars($student['usn']); ?></td>
                                            <td class="name"><?php echo htmlspecialchars($student['full_name']); ?></td>
                                            <td class="email"><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td class="phone"><?php echo htmlspecialchars($student['phone']); ?></td>
                                            <td class="date-enrolled"><?php echo date('d M Y', strtotime($student['date_enrolled'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-end mt-3">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 