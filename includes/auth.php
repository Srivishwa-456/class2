<?php
// Include functions file
require_once 'functions.php';

// Function to authenticate user - modified to always return true
function authenticate_user($username, $password) {
    global $conn;
    
    // Set default admin session
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
    $_SESSION['role'] = 'admin';
    
    return true;
}

// Function to register new user
function register_user($username, $password, $full_name, $email, $role = 'student') {
    global $conn;
    
    $username = sanitize_input($username);
    $full_name = sanitize_input($full_name);
    $email = sanitize_input($email);
    $role = sanitize_input($role);
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if username or email already exists
    $check_sql = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        return false; // User already exists
    }
    
    // Insert new user
    $sql = "INSERT INTO users (username, password, full_name, email, role) 
            VALUES ('$username', '$hashed_password', '$full_name', '$email', '$role')";
    
    if ($conn->query($sql)) {
        return $conn->insert_id; // Return the new user ID
    }
    
    return false;
}

// Function to register new student
function register_student($username, $password, $full_name, $email, $roll_number, $class, $section, $contact_number = '', $address = '') {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Register user first
        $user_id = register_user($username, $password, $full_name, $email, 'student');
        
        if (!$user_id) {
            throw new Exception("Failed to create user account");
        }
        
        // Sanitize student data
        $roll_number = sanitize_input($roll_number);
        $class = sanitize_input($class);
        $section = sanitize_input($section);
        $contact_number = sanitize_input($contact_number);
        $address = sanitize_input($address);
        
        // Check if roll number already exists
        $check_sql = "SELECT * FROM students WHERE roll_number = '$roll_number'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            throw new Exception("Roll number already exists");
        }
        
        // Insert student details
        $sql = "INSERT INTO students (user_id, roll_number, class, section, contact_number, address) 
                VALUES ($user_id, '$roll_number', '$class', '$section', '$contact_number', '$address')";
        
        if (!$conn->query($sql)) {
            throw new Exception("Failed to create student record");
        }
        
        // Commit transaction
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return false;
    }
}

// Function to check if user is authorized - modified to always return true
function check_auth() {
    // If not logged in, set default admin session
    if (!is_logged_in()) {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['full_name'] = 'System Administrator';
        $_SESSION['role'] = 'admin';
    }
}

// Function to check if user is admin - modified to always return true
function check_admin() {
    check_auth();
    $_SESSION['role'] = 'admin';
}

// Function to check if user is student - modified to set student role
function check_student() {
    check_auth();
    $_SESSION['role'] = 'student';
    
    // Get first student from database if available
    global $conn;
    $sql = "SELECT s.*, u.id as user_id FROM students s JOIN users u ON s.user_id = u.id LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $_SESSION['user_id'] = $student['user_id'];
    }
}
?> 