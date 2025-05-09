<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config/config.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Creating Teachers Table and Sample Data</h2>";

// Create teachers table
$create_teachers_table = "
CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `department` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

// Create teacher_courses table
$create_teacher_courses_table = "
CREATE TABLE IF NOT EXISTS `teacher_courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_course_unique` (`teacher_id`, `course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

// Sample teacher data
$insert_teachers = "
INSERT INTO `teachers` (`full_name`, `email`, `password`, `employee_id`, `department`) VALUES
('John Smith', 'john.smith@example.com', '1234', 'EMP001', 'Computer Science'),
('Sarah Johnson', 'sarah.johnson@example.com', '1234', 'EMP002', 'Information Technology'),
('Michael Brown', 'michael.brown@example.com', '1234', 'EMP003', 'Engineering');
";

// Check if courses table exists, if not create it
$check_courses_table = "SHOW TABLES LIKE 'courses'";
$courses_table_exists = $conn->query($check_courses_table);

if ($courses_table_exists->num_rows == 0) {
    // Create courses table
    $create_courses_table = "
    CREATE TABLE IF NOT EXISTS `courses` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `course_code` varchar(20) NOT NULL,
      `course_name` varchar(100) NOT NULL,
      `semester` varchar(20) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `course_code` (`course_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    // Insert sample course data
    $insert_courses = "
    INSERT INTO `courses` (`course_code`, `course_name`, `semester`) VALUES
    ('22CSE161', 'Data Structures', 'Spring 2025'),
    ('22CSE162', 'Database Management', 'Spring 2025'),
    ('22CSE163', 'Operating Systems', 'Spring 2025'),
    ('22CSE164', 'Web Development', 'Spring 2025'),
    ('22CSE168', 'Artificial Intelligence', 'Spring 2025'),
    ('22CSE1653', 'Computer Networks', 'Spring 2025'),
    ('22MEC1674', 'Python Programming', 'Spring 2025');
    ";
    
    if ($conn->query($create_courses_table) === TRUE) {
        echo "<p>Courses table created successfully</p>";
        if ($conn->query($insert_courses) === TRUE) {
            echo "<p>Sample course data inserted successfully</p>";
        } else {
            echo "<p>Error inserting sample course data: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Error creating courses table: " . $conn->error . "</p>";
    }
}

// Execute SQL statements
if ($conn->query($create_teachers_table) === TRUE) {
    echo "<p>Teachers table created successfully</p>";
    
    // Check if teachers table is empty
    $check_teachers = "SELECT * FROM teachers LIMIT 1";
    $result = $conn->query($check_teachers);
    
    if ($result->num_rows == 0) {
        // Insert sample teacher data
        if ($conn->query($insert_teachers) === TRUE) {
            echo "<p>Sample teacher data inserted successfully</p>";
        } else {
            echo "<p>Error inserting sample teacher data: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Teachers table already has data</p>";
    }
} else {
    echo "<p>Error creating teachers table: " . $conn->error . "</p>";
}

if ($conn->query($create_teacher_courses_table) === TRUE) {
    echo "<p>Teacher courses table created successfully</p>";
    
    // Check if teacher_courses table is empty
    $check_teacher_courses = "SELECT * FROM teacher_courses LIMIT 1";
    $result = $conn->query($check_teacher_courses);
    
    if ($result->num_rows == 0) {
        // First, ensure we have courses
        $check_courses = "SELECT id FROM courses LIMIT 7";
        $courses_result = $conn->query($check_courses);
        
        if ($courses_result->num_rows > 0) {
            // Insert sample teacher-course assignments
            $insert_teacher_courses = "
            INSERT INTO `teacher_courses` (`teacher_id`, `course_id`) VALUES
            (1, 1), (1, 2), (2, 3), (2, 4), (3, 5), (3, 6), (3, 7);
            ";
            
            if ($conn->query($insert_teacher_courses) === TRUE) {
                echo "<p>Sample teacher-course assignments inserted successfully</p>";
            } else {
                echo "<p>Error inserting teacher-course assignments: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>No courses found to assign to teachers</p>";
        }
    } else {
        echo "<p>Teacher courses table already has data</p>";
    }
} else {
    echo "<p>Error creating teacher courses table: " . $conn->error . "</p>";
}

// Also create teacher directory if it doesn't exist
if (!file_exists('teacher')) {
    mkdir('teacher', 0755, true);
    echo "<p>Created teacher directory</p>";
} else {
    echo "<p>Teacher directory already exists</p>";
}

echo "<p>Setup complete! <a href='teacher_login.php'>Go back to login</a></p>";

$conn->close();
?> 