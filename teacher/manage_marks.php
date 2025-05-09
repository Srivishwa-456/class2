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
$page_title = "Manage Student Marks";
$message = '';
$error = '';
$is_teacher = true;

// Get teacher details
$sql = "SELECT * FROM teachers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

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
$exam_types = ['internal', 'midterm', 'final'];

// Process form submission for adding/updating marks
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_marks'])) {
    $course_id = $_POST['course_id'];
    $exam_type = $_POST['exam_type'];
    $max_marks = $_POST['max_marks'];
    
    // Validate max marks
    if (!is_numeric($max_marks) || $max_marks <= 0) {
        $error = "Maximum marks must be a positive number";
    } else {
        // Process each student's marks
        $success_count = 0;
        $error_count = 0;
        
        foreach ($_POST['marks'] as $student_id => $marks) {
            $marks_obtained = trim($marks['obtained']);
            $remarks = trim($marks['remarks']);
            
            // Validate marks
            if (!is_numeric($marks_obtained) || $marks_obtained < 0 || $marks_obtained > $max_marks) {
                $error .= "Invalid marks for student ID $student_id. ";
                $error_count++;
                continue;
            }
            
            // Check if marks already exist for this student, course and exam type
            $check_sql = "SELECT id FROM subject_marks 
                          WHERE student_id = ? AND course_id = ? AND exam_type = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("iis", $student_id, $course_id, $exam_type);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing marks
                $mark_id = $check_result->fetch_assoc()['id'];
                $update_sql = "UPDATE subject_marks 
                              SET marks_obtained = ?, max_marks = ?, remarks = ?, updated_at = NOW() 
                              WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ddsi", $marks_obtained, $max_marks, $remarks, $mark_id);
                
                if ($update_stmt->execute()) {
                    $success_count++;
                } else {
                    $error .= "Failed to update marks for student ID $student_id. ";
                    $error_count++;
                }
            } else {
                // Insert new marks
                $insert_sql = "INSERT INTO subject_marks 
                              (student_id, course_id, exam_type, marks_obtained, max_marks, remarks, added_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";  
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iisddsi", $student_id, $course_id, $exam_type, $marks_obtained, $max_marks, $remarks, $teacher_id);
                
                if ($insert_stmt->execute()) {
                    $success_count++;
                } else {
                    $error .= "Failed to add marks for student ID $student_id. ";
                    $error_count++;
                }
            }
        }
        
        if ($success_count > 0) {
            $message = "Successfully saved marks for $success_count students.";
            if ($error_count > 0) {
                $error = "Failed to save marks for $error_count students. ";
            }
        }
    }
    
    // Set selected course for form redisplay
    $selected_course = $course_id;
}

// Get students data if course is selected
if (isset($_GET['course']) && is_numeric($_GET['course'])) {
    $selected_course = $_GET['course'];
    
    // Get course details
    $course_sql = "SELECT * FROM courses WHERE id = ?";
    $course_stmt = $conn->prepare($course_sql);
    $course_stmt->bind_param("i", $selected_course);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();
    $course = $course_result->fetch_assoc();
    
    // Get students enrolled in this course
    $students_sql = "SELECT 
        s.id, 
        s.full_name, 
        s.usn, 
        s.class, 
        s.section
    FROM 
        students s
    JOIN 
        student_courses sc ON s.id = sc.student_id
    WHERE 
        sc.course_id = ?
    ORDER BY 
        s.full_name ASC";
    
    $students_stmt = $conn->prepare($students_sql);
    $students_stmt->bind_param("i", $selected_course);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    
    while($student = $students_result->fetch_assoc()) {
        // Get existing marks for each student if available
        if (isset($_GET['exam_type'])) {
            $exam_type = $_GET['exam_type'];
            $marks_sql = "SELECT * FROM subject_marks 
                         WHERE student_id = ? AND course_id = ? AND exam_type = ?";
            $marks_stmt = $conn->prepare($marks_sql);
            $marks_stmt->bind_param("iis", $student['id'], $selected_course, $exam_type);
            $marks_stmt->execute();
            $marks_result = $marks_stmt->get_result();
            
            if ($marks_result->num_rows > 0) {
                $marks_data = $marks_result->fetch_assoc();
                $student['marks'] = $marks_data['marks_obtained'];
                $student['max_marks'] = $marks_data['max_marks'];
                $student['remarks'] = $marks_data['remarks'];
            }
        }
        
        $students[] = $student;
    }
}
// Close database connection
$conn->close();
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .content-wrapper {
            flex: 1;
        }
        
        footer {
            margin-top: auto;
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
        
        .card {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #f8fafc;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .card-header h5 {
            margin: 0;
            color: #1e40af;
            font-weight: 600;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1e40af;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .btn-primary {
            background-color: #2563eb;
            border-color: #2563eb;
        }
        
        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }
        
        .table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: 600;
            font-size: 14px;
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
                    <a href="dashboard.php" class="header-nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </div>
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
        <h5 class="section-title"><?php echo $page_title; ?></h5>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Select Course and Exam Type</h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-5">
                        <label for="course" class="form-label">Course</label>
                        <select name="course" id="course" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php foreach($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo ($selected_course == $course['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="exam_type" class="form-label">Exam Type</label>
                        <select name="exam_type" id="exam_type" class="form-select" required>
                            <?php foreach($exam_types as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo (isset($_GET['exam_type']) && $_GET['exam_type'] == $type) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Load Students</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (!empty($students) && $selected_course): ?>
            <div class="card">
                <div class="card-header">
                    <h5>Manage Marks for <?php echo htmlspecialchars($course['course_name']); ?> - <?php echo ucfirst($_GET['exam_type']); ?> Exam</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="course_id" value="<?php echo $selected_course; ?>">
                        <input type="hidden" name="exam_type" value="<?php echo $_GET['exam_type']; ?>">
                        
                        <div class="mb-3">
                            <label for="max_marks" class="form-label">Maximum Marks</label>
                            <input type="number" class="form-control" id="max_marks" name="max_marks" value="<?php echo isset($students[0]['max_marks']) ? $students[0]['max_marks'] : '100'; ?>" required>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 25%">Student</th>
                                        <th style="width: 15%">USN</th>
                                        <th style="width: 15%">Class & Section</th>
                                        <th style="width: 15%">Marks</th>
                                        <th style="width: 25%">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sl_no = 1; foreach($students as $student): ?>
                                        <tr>
                                            <td><?php echo $sl_no++; ?></td>
                                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['usn']); ?></td>
                                            <td><?php echo htmlspecialchars($student['class'] . ', ' . $student['section']); ?></td>
                                            <td>
                                                <input type="number" class="form-control" name="marks[<?php echo $student['id']; ?>][obtained]" value="<?php echo isset($student['marks']) ? $student['marks'] : ''; ?>" step="0.01" min="0">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control" name="marks[<?php echo $student['id']; ?>][remarks]" value="<?php echo isset($student['remarks']) ? htmlspecialchars($student['remarks']) : ''; ?>">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                            <button type="submit" name="submit_marks" class="btn btn-primary">Save Marks</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($selected_course): ?>
            <div class="alert alert-info">No students enrolled in this course.</div>
        <?php endif; ?>
    </div>
</body>
</html>