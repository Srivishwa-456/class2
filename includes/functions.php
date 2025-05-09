<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

// Function to sanitize input data
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if user is admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to check if user is student
function is_student() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

// Function to redirect user
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to get user details by ID
function get_user_by_id($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    $sql = "SELECT * FROM users WHERE id = $user_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to get student details by user ID
function get_student_by_user_id($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    $sql = "SELECT s.* FROM students s WHERE s.user_id = $user_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to get student details by student ID
function get_student_by_id($student_id) {
    global $conn;
    $student_id = (int)$student_id;
    $sql = "SELECT * FROM students WHERE id = $student_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to calculate attendance percentage
function calculate_attendance_percentage($student_id, $month = null, $year = null) {
    global $conn;
    $student_id = (int)$student_id;
    
    $date_condition = "";
    if ($month && $year) {
        $month = (int)$month;
        $year = (int)$year;
        $date_condition = "AND MONTH(date) = $month AND YEAR(date) = $year";
    }
    
    // Total working days
    $sql_total = "SELECT COUNT(DISTINCT date) as total_days FROM attendance WHERE 1=1 $date_condition";
    $result_total = $conn->query($sql_total);
    $total_days = ($result_total && $result_total->num_rows > 0) ? $result_total->fetch_assoc()['total_days'] : 0;
    
    if ($total_days == 0) {
        return 0;
    }
    
    // Present days
    $sql_present = "SELECT COUNT(*) as present_days FROM attendance 
                    WHERE student_id = $student_id AND status = 'present' $date_condition";
    $result_present = $conn->query($sql_present);
    $present_days = ($result_present && $result_present->num_rows > 0) ? $result_present->fetch_assoc()['present_days'] : 0;
    
    return ($present_days / $total_days) * 100;
}

// Function to get all students
function get_all_students() {
    global $conn;
    $sql = "SELECT s.*, u.full_name, u.email, u.username 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE u.role = 'student'
            ORDER BY s.roll_number";
    $result = $conn->query($sql);
    
    $students = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    
    return $students;
}

// Function to get attendance records for a student
function get_student_attendance($student_id, $month = null, $year = null) {
    global $conn;
    $student_id = (int)$student_id;
    
    $date_condition = "";
    if ($month && $year) {
        $month = (int)$month;
        $year = (int)$year;
        $date_condition = "AND MONTH(a.date) = $month AND YEAR(a.date) = $year";
    }
    
    $sql = "SELECT a.*, u.full_name as marked_by_name 
            FROM attendance a 
            JOIN users u ON a.marked_by = u.id 
            WHERE a.student_id = $student_id $date_condition 
            ORDER BY a.date DESC";
    
    $result = $conn->query($sql);
    
    $attendance = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $attendance[] = $row;
        }
    }
    
    return $attendance;
}

// Function to get attendance for a specific date
function get_attendance_by_date($date) {
    global $conn;
    $date = sanitize_input($date);
    
    $sql = "SELECT a.*, s.roll_number, u.full_name as student_name, u2.full_name as marked_by_name 
            FROM attendance a 
            JOIN students s ON a.student_id = s.id 
            JOIN users u ON s.user_id = u.id 
            JOIN users u2 ON a.marked_by = u2.id 
            WHERE a.date = '$date' 
            ORDER BY s.roll_number";
    
    $result = $conn->query($sql);
    
    $attendance = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $attendance[] = $row;
        }
    }
    
    return $attendance;
}

// Function to generate monthly report
function generate_monthly_report($month, $year) {
    global $conn;
    $month = (int)$month;
    $year = (int)$year;
    
    $sql = "SELECT s.id as student_id, s.roll_number, u.full_name as student_name, 
            COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
            COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count,
            COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_count,
            COUNT(a.id) as total_count
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            LEFT JOIN attendance a ON s.id = a.student_id AND MONTH(a.date) = $month AND YEAR(a.date) = $year
            WHERE u.role = 'student'
            GROUP BY s.id, s.roll_number, u.full_name
            ORDER BY s.roll_number";
    
    $result = $conn->query($sql);
    
    $report = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['percentage'] = ($row['total_count'] > 0) ? 
                                 (($row['present_count'] / $row['total_count']) * 100) : 0;
            $report[] = $row;
        }
    }
    
    return $report;
}
?>