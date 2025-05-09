<?php
// Database configuration
$db_host = "sql12.freesqldatabase.com";
$db_user = "sql12777871";
$db_password = "IGLK4Y3syj";
$db_name = "sql12777871";

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: Unable to connect to the database.");
}

// Set charset to ensure proper encoding
$conn->set_charset("utf8mb4");

// Debug mode - change to false in production
$debug = false;

// Function to log debug info
function debug_log($message) {
    global $debug;
    if ($debug) {
        error_log($message);
        // echo "<!-- Debug: " . htmlspecialchars($message) . " -->"; // remove or comment this out
    }
}

debug_log("Database connected successfully");

// no closing PHP tag to prevent accidental output
