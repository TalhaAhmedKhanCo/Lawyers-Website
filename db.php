<?php
// Include configuration so database constants are available.
require_once __DIR__ . '/config.php';

// Enable explicit error reporting for mysqli to throw exceptions on failure.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Create a mysqli connection object for MySQL communication.
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Stop execution if the database connection fails.
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Force UTF-8 so all text is stored and displayed correctly.
$conn->set_charset('utf8mb4');
?>
