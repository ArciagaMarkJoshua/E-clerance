<?php
ini_set('display_errors', 'Off');
error_reporting(E_ALL);
$host = "localhost"; 
$user = "root"; // Default XAMPP MySQL user
$password = ""; // Default XAMPP password (empty)
$database = "studentprofiledb"; // Your database name

$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Sorry, we're experiencing technical difficulties. Please try again later or contact support.");
}
?> 