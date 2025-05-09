<?php
// Start session
session_start();

// Include functions file
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$page_title = 'Home - Attendance Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Hero Section with Gradient Background -->
<div class="hero-container py-5 mb-5">
    <div class="row mt-4">
        <div class="col-md-8 offset-md-2 text-center hero-section">
            <h1 class="display-4 mb-4 fw-bold text-white">Welcome to Attendance Management System</h1>
            <p class="lead mb-5 text-white">A comprehensive solution for tracking and managing student attendance records.</p>
            
            <div class="mt-5 dashboard-buttons">
                <a href="teacher_login.php" class="btn btn-light btn-lg px-4 me-3 shadow dashboard-btn">
                    <i class="fas fa-user-shield me-2"></i> teacher Login
                </a>
                <a href="student_login.php" class="btn btn-success btn-lg px-4 shadow dashboard-btn">
                    <i class="fas fa-user-graduate me-2"></i> Student Login
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Feature Cards Section -->
<div class="container">
    <div class="row mb-5">
        <div class="col-md-4 mb-4">
            <div class="card feature-card h-100 shadow-sm">
                <div class="card-body">
                    <div class="feature-icon bg-primary text-white">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h5 class="card-title mt-4">Easy Attendance Tracking</h5>
                    <p class="card-text">Mark and monitor attendance with just a few clicks. Streamlined for both students and administrators.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card feature-card h-100 shadow-sm">
                <div class="card-body">
                    <div class="feature-icon bg-success text-white">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h5 class="card-title mt-4">Comprehensive Reports</h5>
                    <p class="card-text">Generate detailed attendance reports and statistics. Export data in multiple formats.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card feature-card h-100 shadow-sm">
                <div class="card-body">
                    <div class="feature-icon bg-info text-white">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <h5 class="card-title mt-4">User-Friendly Interface</h5>
                    <p class="card-text">Intuitive design for both students and administrators. Easy to learn and use daily.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="row mt-5 mb-5 stats-section">
        <div class="col-12 text-center mb-4">
            <h2 class="section-title">System Statistics</h2>
            <p class="text-muted">Real-time data from our attendance management system</p>
        </div>
        <div class="col-md-3 mb-4">
            <div class="stat-card text-center shadow-sm">
                <div class="stat-icon">
                    <i class="fas fa-users fa-3x text-primary"></i>
                </div>
                <h3 class="stat-number">500+</h3>
                <p class="stat-label">Active Students</p>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="stat-card text-center shadow-sm">
                <div class="stat-icon">
                    <i class="fas fa-school fa-3x text-success"></i>
                </div>
                <h3 class="stat-number">20+</h3>
                <p class="stat-label">Classes</p>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="stat-card text-center shadow-sm">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-check fa-3x text-info"></i>
                </div>
                <h3 class="stat-number">10,000+</h3>
                <p class="stat-label">Attendance Records</p>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="stat-card text-center shadow-sm">
                <div class="stat-icon">
                    <i class="fas fa-chart-line fa-3x text-warning"></i>
                </div>
                <h3 class="stat-number">98%</h3>
                <p class="stat-label">Accuracy Rate</p>
            </div>
        </div>
    </div>

    <!-- Contact Us Form Section -->
    <div class="row my-5">
        <div class="col-12">
            <div class="contact-section p-5 rounded shadow-sm">
                <h3 class="mb-3 text-center">Contact Us</h3>
                <p class="mb-4 text-center">Have questions about our attendance system? Send us a message!</p>
                
                <form id="contactForm" action="process_contact.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" required>
                                <label for="name">Your Name</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" placeholder="Your Email" required>
                                <label for="email">Your Email</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject" required>
                            <label for="subject">Subject</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-floating">
                            <textarea class="form-control" id="message" name="message" placeholder="Your Message" style="height: 150px" required></textarea>
                            <label for="message">Your Message</label>
                        </div>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary btn-lg px-4 shadow-sm">
                            <i class="fas fa-paper-plane me-2"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add this CSS before the footer -->
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f8f9fa;
    }
    
    .hero-container {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        padding: 60px 0;
        border-radius: 0 0 50px 50px;
        margin-bottom: 50px;
    }
    
    .dashboard-btn {
        transition: all 0.3s ease;
        border-radius: 30px;
        font-weight: 600;
    }
    
    .dashboard-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    
    .feature-card {
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: none;
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }
    
    .feature-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    
    .stat-card {
        padding: 25px;
        border-radius: 10px;
        background: white;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 15px 0 5px;
    }
    
    .stat-label {
        color: #6c757d;
        font-weight: 500;
    }
    
    .section-title {
        position: relative;
        padding-bottom: 15px;
        margin-bottom: 30px;
        font-weight: 700;
    }
    
    .section-title:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 3px;
        background: #4e73df;
    }
    
    .contact-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
    }
    
    .form-floating > .form-control {
        border-radius: 10px;
        border: 1px solid #ced4da;
    }
    
    .form-floating > .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
    }
    
    .form-floating > label {
        padding: 1rem 1.25rem;
    }
    
    .btn-primary {
        background-color: #4e73df;
        border-color: #4e73df;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background-color: #2e59d9;
        border-color: #2653d4;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
</style>
<?php
// Include footer
include_once 'includes/footer.php';
?>
<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>