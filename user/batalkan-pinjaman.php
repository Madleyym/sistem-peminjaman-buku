<?php
session_start();
require_once '../config/database.php';
require_once '../config/constants.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Silakan login terlebih dahulu'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan'
    ]);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['borrow_id'])) {
        throw new Exception('ID peminjaman tidak ditemukan');
    }

    $borrowId = filter_var($data['borrow_id'], FILTER_VALIDATE_INT);
    $userId = $_SESSION['user_id'];
    $currentDateTime = date('Y-m-d H:i:s');
    $userIP = $_SERVER['REMOTE_ADDR'];

    if (!$borrowId) {
        throw new Exception('ID peminjaman tidak valid');
    }

    $database = new Database();
    $conn = $database->getConnection();

    // Mulai transaction
    $conn->beginTransaction();

    // 1. Cek status peminjaman saat ini
    $checkStmt = $conn->prepare("
        SELECT b.*, bk.title, bk.available_quantity
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.id = :borrow_id AND b.user_id = :user_id
    ");

    $checkStmt->execute([
        'borrow_id' => $borrowId,
        'user_id' => $userId
    ]);

    $borrowing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$borrowing) {
        throw new Exception('Peminjaman tidak ditemukan');
    }

    if ($borrowing['status'] !== 'pending') {
        throw new Exception('Hanya peminjaman dengan status menunggu yang dapat dibatalkan');
    }

    // 2. Update status peminjaman
    $updateBorrowingStmt = $conn->prepare("
        UPDATE borrowings 
        SET 
            status = 'cancelled',
            updated_at = :updated_at,
            notes = CONCAT(COALESCE(notes, ''), '\nDibatalkan oleh pengguna pada ', :cancel_date)
        WHERE id = :borrow_id AND user_id = :user_id
    ");

    $updateBorrowingStmt->execute([
        'borrow_id' => $borrowId,
        'user_id' => $userId,
        'updated_at' => $currentDateTime,
        'cancel_date' => $currentDateTime
    ]);

    // 3. Update ketersediaan buku
    $updateBookStmt = $conn->prepare("
        UPDATE books 
        SET 
            available_quantity = available_quantity + 1,
            updated_at = :updated_at
        WHERE id = :book_id
    ");

    $updateBookStmt->execute([
        'book_id' => $borrowing['book_id'],
        'updated_at' => $currentDateTime
    ]);

    // 4. Tambahkan log ke activity_logs
    $insertLogStmt = $conn->prepare("
        INSERT INTO activity_logs 
        (user_id, action, action_type, ip_address, created_at)
        VALUES 
        (:user_id, :action, :action_type, :ip_address, :created_at)
    ");

    $logAction = sprintf(
        'Membatalkan peminjaman buku "%s"',
        $borrowing['title']
    );

    $insertLogStmt->execute([
        'user_id' => $userId,
        'action' => $logAction,
        'action_type' => 'cancel_borrow',
        'ip_address' => $userIP,
        'created_at' => $currentDateTime
    ]);

    // Commit transaction
    $conn->commit();

    // Kirim response sukses
    echo json_encode([
        'success' => true,
        'message' => 'Peminjaman berhasil dibatalkan',
        'data' => [
            'borrow_id' => $borrowId,
            'book_title' => $borrowing['title'],
            'cancelled_at' => $currentDateTime
        ]
    ]);
} catch (Exception $e) {
    // Rollback jika terjadi error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log error
    error_log("Error in batalkan-pinjaman.php: " . $e->getMessage());

    // Kirim response error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal membatalkan peminjaman: ' . $e->getMessage()
    ]);
}
