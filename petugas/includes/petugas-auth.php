<?php
function checkAdminAuth() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in and is an admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'petugas') {
        header("Location: /sistem/petugas/auth/login.php");
        exit();
    }
}