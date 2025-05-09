<?php
// Database configuration
$db_host = "sql12.freesqldatabase.com";
$db_user = "sql12777871";         // Default XAMPP MySQL username
$db_password = "IGLK4Y3syj";         // Default XAMPP MySQL password (empty)
$db_name = "sql12777871";

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    // Record the error but don't expose details to users
    error_log("Database connection failed: " . $conn->connect_error);
    echo "<!-- Database connection error: " . $conn->connect_error . " -->";
    die("Connection failed: Unable to connect to the database.");
}

// Set charset to ensure proper encoding
$conn->set_charset("utf8mb4");

// Debug mode - change to false in production
$debug = true;

// Function to log debug info
function debug_log($message) {
    global $debug;
    if ($debug) {
        error_log($message);
        echo "<!-- Debug: " . htmlspecialchars($message) . " -->";
    }
}

// Log successful connection
debug_log("Database connected successfully");
?>