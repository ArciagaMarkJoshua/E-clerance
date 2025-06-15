<?php
if (session_status() === PHP_SESSION_NONE) {
    // Set session lifetime to 0 (session ends when browser closes)
    ini_set('session.gc_maxlifetime', 0);
    ini_set('session.cookie_lifetime', 0);

    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0, // Cookie expires when browser closes
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // Start the session
    session_start();
}

// Function to check if session is expired
function isSessionExpired() {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return false;
    }
    
    $inactive = 30 * 60; // 30 minutes
    $session_life = time() - $_SESSION['last_activity'];
    
    if ($session_life > $inactive) {
        session_unset();
        session_destroy();
        return true;
    }
    
    $_SESSION['last_activity'] = time();
    return false;
}

// Check session on every page load
if (isset($_SESSION['student_id']) && isSessionExpired()) {
    header("Location: login.php");
    exit();
}
?> 