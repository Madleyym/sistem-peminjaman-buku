<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Debug session
error_log('Session data: ' . print_r($_SESSION, true));

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';

header('Content-Type: application/json');

// Validasi login dengan session yang sesuai dengan login.php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'staff') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Akses ditolak',
        'debug' => [
            'user_id' => $_SESSION['user_id'] ?? 'not set',
            'logged_in' => $_SESSION['logged_in'] ?? 'not set',
            'role' => $_SESSION['role'] ?? 'not set'
        ]
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

    if (!isset($data['borrow_id']) || !isset($data['action'])) {
        throw new Exception('Data tidak lengkap');
    }

    $borrowId = filter_var($data['borrow_id'], FILTER_VALIDATE_INT);
    $action = $data['action'];
    $staffId = $_SESSION['user_id'];
    $staffUsername = $_SESSION['username'];
    $currentDateTime = '2025-01-31 16:30:48'; // Sesuai dengan waktu yang diberikan

    if (!$borrowId) {
        throw new Exception('ID peminjaman tidak valid');
    }

    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception('Aksi tidak valid');
    }

    $database = new Database();
    $conn = $database->getConnection();

    // Mulai transaction
    $conn->beginTransaction();

    // 1. Cek status peminjaman saat ini
    $checkStmt = $conn->prepare("
        SELECT b.*, bk.title, bk.available_quantity, u.name as user_name
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = :borrow_id
    ");

    $checkStmt->execute(['borrow_id' => $borrowId]);
    $borrowing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$borrowing) {
        throw new Exception('Peminjaman tidak ditemukan');
    }

    if ($borrowing['status'] !== 'pending') {
        throw new Exception('Peminjaman sudah diproses sebelumnya');
    }

    // 2. Update status peminjaman
    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $updateBorrowingStmt = $conn->prepare("
        UPDATE borrowings 
        SET 
            status = :status,
            approved_by = :staff_id,
            updated_at = :updated_at,
            notes = CONCAT(COALESCE(notes, ''), '\n', :note)
        WHERE id = :borrow_id
    ");

    $note = sprintf(
        "%s oleh %s pada %s",
        $action === 'approve' ? 'Disetujui' : 'Ditolak',
        'Madleyym', // Sesuai dengan login yang diberikan
        $currentDateTime
    );

    $updateBorrowingStmt->execute([
        'status' => $newStatus,
        'staff_id' => $staffId,
        'updated_at' => $currentDateTime,
        'note' => $note,
        'borrow_id' => $borrowId
    ]);

    // 3. Update ketersediaan buku jika ditolak
    if ($action === 'reject') {
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
    }

    // 4. Tambahkan log aktivitas
    $insertLogStmt = $conn->prepare("
        INSERT INTO activity_logs 
        (user_id, action, action_type, ip_address, created_at)
        VALUES 
        (:user_id, :action, :action_type, :ip_address, :created_at)
    ");

    $logAction = sprintf(
        '%s peminjaman buku "%s" oleh %s',
        $action === 'approve' ? 'Menyetujui' : 'Menolak',
        $borrowing['title'],
        $borrowing['user_name']
    );

    $insertLogStmt->execute([
        'user_id' => $staffId,
        'action' => $logAction,
        'action_type' => $action === 'approve' ? 'approve_borrow' : 'reject_borrow',
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'created_at' => $currentDateTime
    ]);

    // Commit transaction
    $conn->commit();

    // Response sukses
    echo json_encode([
        'success' => true,
        'message' => $action === 'approve'
            ? 'Peminjaman berhasil disetujui'
            : 'Peminjaman berhasil ditolak',
        'data' => [
            'borrow_id' => $borrowId,
            'new_status' => $newStatus,
            'processed_at' => $currentDateTime,
            'processed_by' => 'Madleyym'
        ]
    ]);
} catch (Exception $e) {
    // Rollback jika terjadi error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log error
    error_log("Error in proses-konfirmasi.php: " . $e->getMessage());

    // Response error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memproses permintaan: ' . $e->getMessage()
    ]);
}
