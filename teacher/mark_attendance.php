<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug information
echo "<!-- Debug: Script started -->";

session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Verify database connection
if ($conn->connect_error) {
    die("<!-- Connection failed: " . $conn->connect_error . " -->");
}
echo "<!-- Database connection successful -->";

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id']) || !isset($_SESSION['is_teacher'])) {
    header("Location: ../teacher_login.php");
    exit();
}

// Debug POST data
echo "<!-- POST data: " . json_encode($_POST) . " -->";

$teacher_id = $_SESSION['teacher_id'];
$page_title = "Mark Attendance";
$message = '';
$error = '';
$selected_course = '';
$selected_date = date('Y-m-d');
$attendance_recorded = false;

// Get courses taught by the teacher
$courses_sql = "SELECT c.id, c.course_code, c.course_name FROM courses c 
               JOIN teacher_courses tc ON c.id = tc.course_id 
               WHERE tc.teacher_id = ?";
$courses_stmt = $conn->prepare($courses_sql);
$courses_stmt->bind_param("i", $teacher_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();

// Debug output
// echo "Courses query executed.<br>";

// Handle URL parameters for loading students
if (isset($_GET['course_id']) && !isset($_POST['load_students']) && !isset($_POST['mark_attendance'])) {
    $selected_course = $_GET['course_id'];
    $selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $selected_time_slot = isset($_GET['time_slot']) ? $_GET['time_slot'] : 'Regular';
    
    // Debug output
    echo "<!-- Loading students from URL parameters. Course ID: " . $selected_course . " -->";
}

// Handle form submission for loading students
if (isset($_POST['load_students'])) {
    $selected_course = isset($_POST['course_id']) ? $_POST['course_id'] : '';
    $selected_date = isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d');
    $selected_time_slot = isset($_POST['time_slot']) ? $_POST['time_slot'] : 'Regular';
    
    // Debug output
    echo "<!-- Load students form submitted. Course ID: " . $selected_course . " -->";
}

// Handle form submission for marking attendance
if (isset($_POST['mark_attendance'])) {
    $selected_course = $_POST['course_id'];
    $selected_date = $_POST['attendance_date'];
    $attendance_status = $_POST['attendance_status'];
    $selected_time_slot = isset($_POST['time_slot']) ? $_POST['time_slot'] : 'Regular';
    
    // Check if attendance is already marked for the selected date and course
    $check_sql = "SELECT COUNT(*) as count FROM attendance 
                 WHERE course_id = ? AND date = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $selected_course, $selected_date);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['count'] > 0) {
        // Delete existing attendance records for the selected date and course
        $delete_sql = "DELETE FROM attendance 
                      WHERE course_id = ? AND date = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("is", $selected_course, $selected_date);
        $delete_stmt->execute();
    }
    
    // Insert new attendance records
    $success_count = 0;
    $error_count = 0;
    
    foreach ($attendance_status as $student_id => $status) {
        $insert_sql = "INSERT INTO attendance (student_id, course_id, date, time_slot, status, marked_by) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iisssi", $student_id, $selected_course, $selected_date, $selected_time_slot, $status, $teacher_id);
        
        if ($insert_stmt->execute()) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    if ($error_count == 0) {
        $message = "Attendance marked successfully for " . $success_count . " students.";
        $attendance_recorded = true;
    } else {
        $error = "Error: " . $error_count . " records failed to be recorded.";
    }
}

// Get students for the selected course
$students = [];
if (!empty($selected_course)) {
    // Add debug to check if we're entering this block
    echo "<!-- Attempting to get students for course ID: {$selected_course} -->";
    
    try {
        // First, check if any students are enrolled in this course
        $check_sql = "SELECT COUNT(*) as count FROM student_courses WHERE course_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $selected_course);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        
        echo "<!-- Found {$check_row['count']} student enrollments for course ID: {$selected_course} -->";
        
        if ($check_row['count'] > 0) {
            // There are students enrolled, so we should proceed with fetching them
            $students_sql = "SELECT 
                s.id, 
                s.usn, 
                s.full_name, 
                s.class, 
                s.section
            FROM 
                students s
            JOIN 
                student_courses sc ON s.id = sc.student_id
            WHERE 
                sc.course_id = ?
            ORDER BY 
                s.class ASC, s.section ASC, s.full_name ASC";
                
            $students_stmt = $conn->prepare($students_sql);
            $students_stmt->bind_param("i", $selected_course);
            $students_stmt->execute();
            $students_result = $students_stmt->get_result();
            
            echo "<!-- Students query executed. Found " . $students_result->num_rows . " results -->";
            
            while ($student = $students_result->fetch_assoc()) {
                // Check if attendance already exists for this student, course and date
                $existing_sql = "SELECT status FROM attendance 
                                WHERE student_id = ? AND course_id = ? AND date = ?";
                $existing_stmt = $conn->prepare($existing_sql);
                $existing_stmt->bind_param("iis", $student['id'], $selected_course, $selected_date);
                $existing_stmt->execute();
                $existing_result = $existing_stmt->get_result();
                
                if ($existing_row = $existing_result->fetch_assoc()) {
                    $student['status'] = $existing_row['status'];
                    $attendance_recorded = true;
                } else {
                    $student['status'] = 'present'; // Default status
                }
                
                $students[] = $student;
            }
        } else {
            echo "<!-- No students are enrolled in this course -->";
            $error = "No students are enrolled in this course.";
        }
        
        // Debug output
        echo "<!-- Final student count: " . count($students) . " -->";
    } catch (Exception $e) {
        // If there's an error, print it as an HTML comment so it doesn't affect the page layout
        echo "<!-- Error: " . $e->getMessage() . " -->";
        $error = "Error loading students: " . $e->getMessage();
    }
}

// Get course details if course is selected
$course_name = '';
if (!empty($selected_course)) {
    $course_sql = "SELECT course_name FROM courses WHERE id = ?";
    $course_stmt = $conn->prepare($course_sql);
    $course_stmt->bind_param("i", $selected_course);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();
    if ($course_row = $course_result->fetch_assoc()) {
        $course_name = $course_row['course_name'];
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
            overflow: hidden;
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
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            border: none;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(37, 99, 235, 0.2);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #94a3b8 100%);
            border: none;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(107, 114, 128, 0.2);
            color: white;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #4b5563 0%, #6b7280 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(107, 114, 128, 0.3);
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
        
        .attendance-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .attendance-table th {
            background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
            font-weight: 600;
            color: #334155;
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .attendance-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        
        .attendance-table tr:last-child td {
            border-bottom: none;
        }
        
        .attendance-table tr:hover {
            background-color: #f8fafc;
        }
        
        .student-row:nth-child(even) {
            background-color: #fafafa;
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
        
        .status-late {
            background-color: #fef9c3;
            color: #854d0e;
        }
        
        .status-excused {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        
        .form-check-input:checked + .form-check-label .status-present {
            box-shadow: 0 3px 8px rgba(22, 163, 74, 0.2);
            transform: translateY(-1px);
        }
        
        .form-check-input:checked + .form-check-label .status-absent {
            box-shadow: 0 3px 8px rgba(220, 38, 38, 0.2);
            transform: translateY(-1px);
        }
        
        .form-check {
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            margin-right: 12px;
        }
        
        .form-check-input {
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.5rem;
            cursor: pointer;
            border-width: 2px;
        }
        
        .form-check-input:checked {
            background-color: #2563eb;
            border-color: #2563eb;
        }
        
        .form-check-label {
            margin-bottom: 0;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: flex-start;
            padding: 0.5rem 0;
        }
        
        .student-info {
            display: flex;
            flex-direction: column;
        }
        
        .student-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .student-usn {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .class-section {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
            margin-bottom: 1.5rem;
        }
        
        .filter-form .form-group {
            flex: 1;
            min-width: 200px;
            margin-bottom: 0;
        }
        
        .filter-form .btn {
            height: 47px;
        }
        
        @media (max-width: 768px) {
            .radio-group {
                flex-direction: row;
                gap: 0.5rem;
                margin-bottom: 0.5rem;
            }
            
            .attendance-table th:nth-child(3), 
            .attendance-table td:nth-child(3) {
                display: none;
            }
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
                    <a href="dashboard.php" class="header-nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </div>
                <div class="header-nav-item">
                    <a href="../logout.php" class="header-nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mt-4 mb-5">
        <h1 class="page-title"><i class="fas fa-calendar-check text-primary"></i> Mark Attendance</h1>
        
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
                <h5>Select Course and Date</h5>
            </div>
            <div class="card-body">
                <form method="post" class="filter-form">
                    <div class="form-group">
                        <label for="course_id" class="form-label">Course</label>
                        <select name="course_id" id="course_id" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php while ($course = $courses_result->fetch_assoc()): ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo ($selected_course == $course['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="attendance_date" class="form-label">Date</label>
                        <input type="date" name="attendance_date" id="attendance_date" class="form-control" value="<?php echo $selected_date; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="time_slot" class="form-label">Time Slot</label>
                        <select name="time_slot" id="time_slot" class="form-select">
                            <option value="Morning">Morning</option>
                            <option value="Afternoon">Afternoon</option>
                            <option value="Regular" selected>Regular</option>
                            <option value="Evening">Evening</option>
                        </select>
                    </div>
                    <button type="submit" name="load_students" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i> Load Students
                    </button>
                </form>
            </div>
        </div>
        
        <?php if (!empty($students)): ?>
            <div class="card">
                <div class="card-header">
                    <h5>Mark Attendance for <?php echo htmlspecialchars($course_name); ?> on <?php echo date('d-m-Y', strtotime($selected_date)); ?></h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="course_id" value="<?php echo $selected_course; ?>">
                        <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                        <input type="hidden" name="time_slot" value="<?php echo $selected_time_slot; ?>">
                        
                        <div class="table-responsive">
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 35%">Student</th>
                                        <th style="width: 20%">Class & Section</th>
                                        <th style="width: 40%">Attendance Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 1; foreach ($students as $student): ?>
                                        <tr class="student-row">
                                            <td><?php echo $i++; ?></td>
                                            <td>
                                                <div class="student-info">
                                                    <span class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></span>
                                                    <span class="student-usn"><?php echo htmlspecialchars($student['usn']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="class-section"><?php echo htmlspecialchars($student['class'] . ' - ' . $student['section']); ?></span>
                                            </td>
                                            <td>
                                                <div class="radio-group">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="attendance_status[<?php echo $student['id']; ?>]" id="present_<?php echo $student['id']; ?>" value="present" <?php echo ($student['status'] == 'present') ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="present_<?php echo $student['id']; ?>">
                                                            <span class="status-badge status-present"><i class="fas fa-check-circle me-1"></i> Present</span>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="attendance_status[<?php echo $student['id']; ?>]" id="absent_<?php echo $student['id']; ?>" value="absent" <?php echo ($student['status'] == 'absent') ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="absent_<?php echo $student['id']; ?>">
                                                            <span class="status-badge status-absent"><i class="fas fa-times-circle me-1"></i> Absent</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" id="mark-all-present" class="btn btn-secondary">
                                <i class="fas fa-check-circle me-2"></i> Mark All Present
                            </button>
                            <button type="submit" name="mark_attendance" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Save Attendance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif (isset($_POST['load_students']) || isset($_GET['course_id'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo !empty($error) ? $error : 'No students found for the selected course.'; ?>
            </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-end mt-3">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mark all students as present
            const markAllPresentBtn = document.getElementById('mark-all-present');
            if (markAllPresentBtn) {
                markAllPresentBtn.addEventListener('click', function() {
                    const presentRadios = document.querySelectorAll('input[type="radio"][value="present"]');
                    presentRadios.forEach(radio => {
                        radio.checked = true;
                    });
                });
            }
            
            // Current date in the date input
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('attendance_date');
            if (dateInput && !dateInput.value) {
                dateInput.value = today;
            }
        });
    </script>
</body>
</html> 