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

echo "<h2>Creating Marks Table</h2>";

// Create marks table
$create_marks_table = "
CREATE TABLE IF NOT EXISTS `subject_marks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `exam_type` enum('internal','midterm','final') NOT NULL,
  `marks_obtained` float NOT NULL,
  `max_marks` float NOT NULL,
  `remarks` text DEFAULT NULL,
  `added_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `course_id` (`course_id`),
  KEY `added_by` (`added_by`),
  CONSTRAINT `marks_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `marks_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `marks_teacher_fk` FOREIGN KEY (`added_by`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if ($conn->query($create_marks_table) === TRUE) {
    echo "<p>Marks table created successfully</p>";
} else {
    echo "<p>Error creating marks table: " . $conn->error . "</p>";
}

echo "<p>Setup complete! <a href='index.php'>Go back to home</a></p>";

$conn->close();
?>