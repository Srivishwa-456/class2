<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if student is logged in
if (!isset($_SESSION['student_id']) || !isset($_SESSION['is_student'])) {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$message = '';
$error = '';

// Process enrollment if course ID is provided
if (isset($_GET['course']) && is_numeric($_GET['course'])) {
    $course_id = $_GET['course'];
    
    // Check if the course exists
    $course_sql = "SELECT * FROM courses WHERE id = ?";
    $course_stmt = $conn->prepare($course_sql);
    $course_stmt->bind_param("i", $course_id);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();
    $course = $course_result->fetch_assoc();
    
    if (!$course) {
        $error = "Course not found.";
    } else {
        // Check if student is already enrolled
        $check_sql = "SELECT * FROM student_courses WHERE student_id = ? AND course_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $student_id, $course_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "You are already enrolled in this course.";
        } else {
            // Enroll the student
            $enroll_sql = "INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)";
            $enroll_stmt = $conn->prepare($enroll_sql);
            $enroll_stmt->bind_param("ii", $student_id, $course_id);
            
            if ($enroll_stmt->execute()) {
                $message = "Successfully enrolled in " . $course['course_code'] . ": " . $course['course_name'] . ". Redirecting to dashboard...";
                // Redirect after 2 seconds
                echo '<meta http-equiv="refresh" content="2;url=dashboard.php">';
            } else {
                $error = "Error enrolling in course: " . $conn->error;
            }
        }
    }
} else {
    $error = "Invalid course selection.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Enrollment - Attendance System</title>
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
        
        .enrollment-container {
            max-width: 600px;
            margin: 50px auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .enrollment-header {
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .enrollment-body {
            padding: 30px;
        }
        
        .message-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .message-error {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .btn-back {
            background-color: #7b1fa2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            text-align: center;
            width: 100%;
        }
        
        .btn-back:hover {
            background-color: #6a1b9a;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
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
    
    <div class="enrollment-container">
        <div class="enrollment-header">
            <h2><i class="fas fa-book-reader me-2"></i>Course Enrollment</h2>
        </div>
        <div class="enrollment-body">
            <?php if (!empty($message)): ?>
                <div class="message-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="message-error">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include_once '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 