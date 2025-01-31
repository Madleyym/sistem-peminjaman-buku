<?php
session_start();

// Define constants if not already defined
if (!defined('LOGIN_URL')) {
    define('LOGIN_URL', '/sistem/public/auth/login.php');
}

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destroy the session
session_destroy();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login page
header("Location: " . LOGIN_URL . "?logout=success&t=" . time());
exit();
