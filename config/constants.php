<?php

// Check if core constants exist
if (!defined('BASE_URL') || !defined('ROOT_PATH') || !defined('UPLOAD_PATH')) {
    require_once __DIR__ . '/../config/bootstrap.php';
}

if (!defined('SITE_NAME')) {
    // System Configuration
    define('SITE_NAME', 'Sistem Peminjaman Buku');
    define('DEFAULT_BOOK_COVER', '/sistem/uploads/books/book-default.png');
    define('BOOK_COVERS_PATH', UPLOAD_PATH . '/book_covers');
    define('BOOK_COVERS_URL', BASE_URL . '/uploads/book_covers');
    define('BOOK_UPLOAD_PATH', UPLOAD_PATH . '/books');
    define('USER_UPLOAD_PATH', UPLOAD_PATH . '/users');
    // Di config/constants.php
    define('ENVIRONMENT', 'development');

  
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'peminjaman_buku');

// Security Configuration
define('HASH_SALT', 'your_unique_salt_here');
define('JWT_SECRET', 'your_jwt_secret_key');

// Logging Configuration
define('ERROR_LOG_PATH', ROOT_PATH . '/logs/error.log');
define('ACCESS_LOG_PATH', ROOT_PATH . '/logs/access.log');

// Pagination Settings
define('ITEMS_PER_PAGE', 10);

// Loan Configuration
define('MAX_LOAN_DAYS', 14);
define('FINE_PER_DAY', 5000); // Rp 5,000 per day

// Roles
define('ROLE_ADMIN', 1);
define('ROLE_USER', 2);

// Response Codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_SERVER_ERROR', 500);
