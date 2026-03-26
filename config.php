<?php
// Start the PHP session so login state can be stored securely.
session_start();

// Define database connection settings for the project.
define('DB_HOST', 'localhost');
define('DB_NAME', 'lawyer_portal');
define('DB_USER', 'root');
define('DB_PASS', '');

// Define the base URL for local development.
define('BASE_URL', 'http://localhost/lawyer-appointment-php/project');
?>
