<?php
require_once 'includes/session_config.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
    setcookie(session_name(), '', time() - 3600, '/', '', false, true);
}

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Clear session data
session_write_close();

// Redirect to login page
header("Location: login.php");
exit();
?>