<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/Book.php';

// Prevent direct access
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../../books.php');
    exit();
}

try {
    // Database connection
    $database = new Database();
    $conn = $database->getConnection();
    $bookManager = new Book($conn);

    // Get book ID from URL
    $bookId = $_GET['id'];

    // Attempt to delete book
    $result = $bookManager->deleteBook($bookId);

    if ($result) {
        $_SESSION['success_message'] = "Buku berhasil dihapus";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus buku. Mungkin buku masih memiliki peminjaman aktif.";
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

// Redirect back to books page
header('Location: ../../books.php');
exit();
?>
