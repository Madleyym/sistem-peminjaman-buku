<?php
// File: sistem/admin/includes/admin_auth.php

function checkAdminAuth() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in and is an admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: /sistem/admin/auth/login.php");
        exit();
    }
}