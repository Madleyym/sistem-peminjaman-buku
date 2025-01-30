<?php
define('BASE_URL', '/sistem');
define('ROOT_PATH', dirname(__FILE__));  // This will now point to C:\xampp\htdocs\sistem
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads/book_covers');

// Create necessary directories
$directories = [
    UPLOAD_PATH,
    UPLOAD_PATH . '/book_covers',
    UPLOAD_PATH . '/books',
    UPLOAD_PATH . '/users',
    ROOT_PATH . '/logs'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0777, true)) {
            die('Failed to create directory: ' . $dir);
        }
    }
}

