<?php
// config/constants.php

// System Configuration
define('SITE_NAME', 'Sistem Peminjaman Buku');
define('SITE_URL', 'http://localhost/SistemPeminjamanBuku');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'peminjaman_buku');

// Security Configuration
define('HASH_SALT', 'your_unique_salt_here');
define('JWT_SECRET', 'your_jwt_secret_key');

// File Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('BOOK_UPLOAD_PATH', UPLOAD_PATH . '/books');
define('USER_UPLOAD_PATH', UPLOAD_PATH . '/users');

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

