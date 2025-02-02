<?php
// File: admin/auth/admin-logout.php

require_once '../../config/auth-session.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the logout activity if user was logged in
if (AuthSession::isAdmin()) {
    try {
        AuthSession::logActivity(
            'Logout dari sistem admin',
            AuthSession::ROLES['ADMIN'],
            $_SESSION['user_id'] ?? null
        );
    } catch (Exception $e) {
        error_log("Logout Error: " . $e->getMessage());
    }
}

// Perform logout
AuthSession::logout(AuthSession::ROLES['ADMIN']);
