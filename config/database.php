<?php
// Database configuration
define('DB_HOST', 'sql12.freesqldatabase.com');
define('DB_USER', 'sql12777871');
define('DB_PASS', 'IGLK4Y3syj');
define('DB_NAME', 'sql12777871');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?> 