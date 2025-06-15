<?php
require_once 'session_config.php';

function isStudentLoggedIn() {
    return isset($_SESSION['student_id']);
}

function redirectIfNotLoggedIn() {
    if (!isStudentLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}
?>