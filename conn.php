<?php
// Enable MySQLi exceptions for better error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $host = "sql12.freesqldatabase.com";
    $dbname = "sql12777871";
    $username = "sql12777871";
    $password = "IGLK4Y3syj";

    // Attempt connection
    $conn = new mysqli($host, $username, $password, $dbname);

    // Set charset
    $conn->set_charset("utf8mb4");

    echo "✅ Connected successfully to the cloud database";
} catch (mysqli_sql_exception $e) {
    echo "❌ Connection failed with error: " . $e->getMessage();
    // Optional: log the full exception details
    error_log($e);
}
?>
