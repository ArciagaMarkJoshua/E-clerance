<?php
session_start(); // Start the session if it's not already started
$_SESSION = array(); // Unset all of the session variables
session_destroy(); // Destroy the session
header("Location: login.php"); // Redirect to the login page
exit();
?> 