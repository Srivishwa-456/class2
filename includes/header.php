<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Attendance Management System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo isset($is_admin) || isset($is_student) ? '../assets/css/style.css' : 'assets/css/style.css'; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo isset($is_admin) || isset($is_student) ? '../index.php' : 'index.php'; ?>">
                Attendance System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo isset($is_admin) ? 'dashboard.php' : 'admin/dashboard.php'; ?>">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo isset($is_admin) ? 'manage-students.php' : 'admin/manage-students.php'; ?>">
                                    <i class="fas fa-users"></i> Manage Students
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo isset($is_admin) ? 'manage-attendance.php' : 'admin/manage-attendance.php'; ?>">
                                    <i class="fas fa-clipboard-check"></i> Manage Attendance
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo isset($is_admin) ? 'reports.php' : 'admin/reports.php'; ?>">
                                    <i class="fas fa-chart-bar"></i> Reports
                                </a>
                            </li>
                        <?php elseif ($_SESSION['role'] === 'student'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo isset($is_student) ? 'dashboard.php' : 'student/dashboard.php'; ?>">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo isset($is_student) ? 'mark-attendance.php' : 'student/mark-attendance.php'; ?>">
                                    <i class="fas fa-check-circle"></i> Mark Attendance
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo isset($is_student) ? 'view-attendance.php' : 'student/view-attendance.php'; ?>">
                                    <i class="fas fa-calendar-alt"></i> View Attendance
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['full_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo isset($is_admin) ? 'profile.php' : (isset($is_student) ? 'profile.php' : ($_SESSION['role'] === 'admin' ? 'admin/profile.php' : 'student/profile.php')); ?>">
                                        <i class="fas fa-id-card"></i> Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo isset($is_admin) || isset($is_student) ? '../logout.php' : 'logout.php'; ?>">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isset($is_admin) || isset($is_student) ? '../login.php' : 'login.php'; ?>">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?> 