<?php
// Define base paths first (before including any other files)
define('BASE_URL', '/sistem');
define('ROOT_PATH', dirname(__FILE__));  // This will now point to C:\xampp\htdocs\sistem
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads/book_covers');

// Create necessary directories
$directories = [
    ROOT_PATH . '/uploads',
    ROOT_PATH . '/uploads/book_covers',
    ROOT_PATH . '/uploads/books',
    ROOT_PATH . '/uploads/users',
    ROOT_PATH . '/logs'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}


