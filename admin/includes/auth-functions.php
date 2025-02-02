<?php
// File: C:\xampp\htdocs\sistem\admin\includes\auth-functions.php

if (!defined('SITE_NAME')) {
    header('HTTP/1.1 403 Forbidden');
    die('Direct access not allowed');
}

require_once __DIR__ . '/../../config/auth-session.php';

function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function isAdmin()
{
    return isset($_SESSION['user_role']) &&
        $_SESSION['user_role'] === AuthSession::ROLES['ADMIN'] &&
        AuthSession::isLoggedIn();
}
