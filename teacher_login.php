<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('config/config.php');

// Initialize variables
$error = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Prepare SQL statement to check teachers table
    $stmt = $conn->prepare("SELECT id, full_name, email, password, employee_id, department FROM teachers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $teacher = $result->fetch_assoc();
        
        // Verify password (simple comparison since passwords are stored as plain text in our sample)
        if ($password === $teacher['password']) {
            // Set teacher-specific session variables
            $_SESSION['teacher_id'] = $teacher['id'];
            $_SESSION['full_name'] = $teacher['full_name'];
            $_SESSION['email'] = $teacher['email'];
            $_SESSION['employee_id'] = $teacher['employee_id'];
            $_SESSION['department'] = $teacher['department'];
            $_SESSION['is_teacher'] = true;
            
            // Set success message
            $_SESSION['login_success'] = "Login successful!";
            
            // Check if dashboard file exists
            $dashboard_path = "teacher/dashboard.php";
            if (file_exists($dashboard_path)) {
                // Redirect to teacher dashboard
                header("Location: " . $dashboard_path);
                exit();
            } else {
                $error = "Dashboard file not found. Path: " . $dashboard_path;
            }
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Teacher account not found";
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login | Attendance Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #224abe;
            --success-color: #1cc88a;
            --error-color: #e74a3b;
            --text-color: #5a5c69;
            --light-bg: #f8f9fc;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 15px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .login-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .logo-circle {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--primary-color);
            font-size: 32px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .login-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.25);
            outline: none;
        }
        
        .form-group .input-icon {
            position: absolute;
            right: 15px;
            top: 42px;
            color: #a0aec0;
        }
        
        .forgot-password {
            display: block;
            text-align: right;
            color: var(--primary-color);
            font-size: 14px;
            margin-bottom: 25px;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .forgot-password:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .btn-login {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .login-switch {
            text-align: center;
            margin-top: 25px;
            color: var(--text-color);
            font-size: 14px;
        }
        
        .login-switch a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }
        
        .login-switch a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background-color: #feebc8;
            color: var(--error-color);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .error-message i {
            margin-right: 10px;
            font-size: 16px;
        }
        
        .back-to-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            font-size: 14px;
            display: flex;
            align-items: center;
            text-decoration: none;
            opacity: 0.9;
            transition: opacity 0.3s;
        }
        
        .back-to-home:hover {
            opacity: 1;
        }
        
        .back-to-home i {
            margin-right: 5px;
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 0;
            }
            
            .login-header {
                padding: 20px;
            }
            
            .login-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <a href="index.php" class="back-to-home">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
                <div class="logo-circle">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h1>Teacher Login</h1>
                <p>Enter your credentials to access your dashboard</p>
            </div>
            
            <div class="login-form">
                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form action="teacher_login.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    
                    <a href="#" class="forgot-password">Forgot password?</a>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Login
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>