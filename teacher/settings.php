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
$page_title = "Settings";
$message = '';
$error = '';

// Get teacher details
$teacher_sql = "SELECT * FROM teachers WHERE id = ?";
$teacher_stmt = $conn->prepare($teacher_sql);
$teacher_stmt->bind_param("i", $teacher_id);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();
$teacher = $teacher_result->fetch_assoc();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    
    // Validate inputs
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists for another teacher
        $check_email_sql = "SELECT id FROM teachers WHERE email = ? AND id != ?";
        $check_email_stmt = $conn->prepare($check_email_sql);
        $check_email_stmt->bind_param("si", $email, $teacher_id);
        $check_email_stmt->execute();
        $check_email_result = $check_email_stmt->get_result();
        
        if ($check_email_result->num_rows > 0) {
            $errors[] = "Email is already in use by another teacher";
        }
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    if (empty($department)) {
        $errors[] = "Department is required";
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        // Handle profile image upload
        $profile_image = $teacher['profile_image']; // Default to current image
        
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_image']['name'];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($file_ext, $allowed)) {
                // Create unique filename
                $new_filename = 'teacher_' . $teacher_id . '_' . time() . '.' . $file_ext;
                $upload_path = '../uploads/profile_images/' . $new_filename;
                
                // Create directory if it doesn't exist
                if (!file_exists('../uploads/profile_images/')) {
                    mkdir('../uploads/profile_images/', 0777, true);
                }
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Delete old image if it exists and is not the default
                    if (!empty($teacher['profile_image']) && $teacher['profile_image'] != 'default.png' && file_exists('../uploads/profile_images/' . $teacher['profile_image'])) {
                        unlink('../uploads/profile_images/' . $teacher['profile_image']);
                    }
                    
                    $profile_image = $new_filename;
                } else {
                    $errors[] = "Failed to upload image";
                }
            } else {
                $errors[] = "Invalid file type. Only JPG, JPEG, PNG and GIF are allowed";
            }
        }
        
        if (empty($errors)) {
            // Update teacher profile
            $update_sql = "UPDATE teachers SET full_name = ?, email = ?, phone = ?, department = ?, profile_image = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssssi", $full_name, $email, $phone, $department, $profile_image, $teacher_id);
            
            if ($update_stmt->execute()) {
                $message = "Profile updated successfully";
                
                // Update session data
                $_SESSION['teacher_name'] = $full_name;
                
                // Refresh teacher data
                $teacher_stmt->execute();
                $teacher_result = $teacher_stmt->get_result();
                $teacher = $teacher_result->fetch_assoc();
            } else {
                $error = "Failed to update profile: " . $conn->error;
            }
        } else {
            $error = implode("<br>", $errors);
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($current_password)) {
        $errors[] = "Current password is required";
    }
    
    if (empty($new_password)) {
        $errors[] = "New password is required";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($new_password != $confirm_password) {
        $errors[] = "New passwords do not match";
    }
    
    // Verify current password
    if (empty($errors)) {
        if (!password_verify($current_password, $teacher['password'])) {
            $errors[] = "Current password is incorrect";
        }
    }
    
    // If no errors, update password
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_sql = "UPDATE teachers SET password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $hashed_password, $teacher_id);
        
        if ($update_stmt->execute()) {
            $message = "Password changed successfully";
        } else {
            $error = "Failed to change password: " . $conn->error;
        }
    } else {
        $error = implode("<br>", $errors);
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
        
        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #d1d5db;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }
        
        .profile-image-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 1.5rem;
            position: relative;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-image-upload {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0,0,0,0.5);
            padding: 0.5rem;
            color: white;
            font-size: 0.8rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .profile-image-upload:hover {
            background-color: rgba(0,0,0,0.7);
        }
        
        .profile-image-upload input[type="file"] {
            display: none;
        }
        
        .form-label {
            color: #475569;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .nav-tabs {
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #64748b;
            padding: 1rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            color: #334155;
            border-color: transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: #2563eb;
            border-bottom: 3px solid #2563eb;
            font-weight: 600;
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
        <h1 class="page-title"><i class="fas fa-cog text-primary"></i> Settings</h1>
        
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
        
        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="true">
                    <i class="fas fa-user me-2"></i> Profile Settings
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-tab-pane" type="button" role="tab" aria-controls="password-tab-pane" aria-selected="false">
                    <i class="fas fa-lock me-2"></i> Change Password
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="settingsTabsContent">
            <!-- Profile Settings -->
            <div class="tab-pane fade show active" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                <div class="card">
                    <div class="card-header">
                        <h5>Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="profile-image-container">
                                <?php
                                $profile_image_path = '../uploads/profile_images/';
                                $profile_image = !empty($teacher['profile_image']) ? $teacher['profile_image'] : 'default.png';
                                
                                if (!file_exists($profile_image_path . $profile_image)) {
                                    $profile_image = 'default.png';
                                }
                                ?>
                                <img src="<?php echo $profile_image_path . $profile_image; ?>" alt="Profile Image" class="profile-image">
                                <label for="profile_image" class="profile-image-upload">
                                    <i class="fas fa-camera me-1"></i> Change Photo
                                    <input type="file" name="profile_image" id="profile_image" accept="image/*">
                                </label>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" name="full_name" id="full_name" class="form-control" value="<?php echo htmlspecialchars($teacher['full_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($teacher['phone']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="department" class="form-label">Department</label>
                                        <input type="text" name="department" id="department" class="form-control" value="<?php echo htmlspecialchars($teacher['department']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="tab-pane fade" id="password-tab-pane" role="tabpanel" aria-labelledby="password-tab" tabindex="0">
                <div class="card">
                    <div class="card-header">
                        <h5>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" name="current_password" id="current_password" class="form-control" required>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" name="new_password" id="new_password" class="form-control" required>
                                <small class="text-muted">Password must be at least 6 characters long</small>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i> Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-end mt-3">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include_once '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview image before upload
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-image').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 