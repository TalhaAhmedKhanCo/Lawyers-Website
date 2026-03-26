<?php
// Start the session and clear all user session data.
require_once __DIR__ . '/config.php';
session_unset();
session_destroy();

// Redirect the user back to the home page after logout.
header('Location: index.php');
exit;
?>
