<?php
session_start();
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/sistem/config/database.php';

// Cek autentikasi petugas perpustakaan
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'librarian') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Akses ditolak. Anda harus login sebagai petugas perpustakaan.'
    ]);
    exit();
}

// Terima data JSON
$data = json_decode(file_get_contents('php://input'), true);

// Validasi data yang diterima
if (!isset($data['book_id']) || !isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak lengkap. ID buku dan ID peminjam diperlukan.'
    ]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Mulai transaction untuk menjamin konsistensi data
    $conn->beginTransaction();

    // 1. Cek apakah buku ada dan tersedia
    $stmt = $conn->prepare("
        SELECT id, title, available_quantity 
        FROM books 
        WHERE id = ? 
        FOR UPDATE
    ");
    $stmt->execute([$data['book_id']]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        throw new Exception('Buku tidak ditemukan dalam sistem.');
    }

    if ($book['available_quantity'] <= 0) {
        throw new Exception('Maaf, buku ini sedang tidak tersedia untuk dipinjam.');
    }

    // 2. Cek apakah peminjam ada dalam sistem
    $stmt = $conn->prepare("
        SELECT id, name, status 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$data['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Peminjam tidak ditemukan dalam sistem.');
    }

    // 3. Cek apakah peminjam memiliki peminjaman aktif untuk buku yang sama
    $stmt = $conn->prepare("
        SELECT id 
        FROM loans 
        WHERE user_id = ? 
        AND book_id = ? 
        AND status = 'borrowed'
    ");
    $stmt->execute([$data['user_id'], $data['book_id']]);
    if ($stmt->fetch()) {
        throw new Exception('Peminjam sudah meminjam buku ini dan belum mengembalikannya.');
    }

    // 4. Cek jumlah peminjaman aktif dari peminjam
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM loans 
        WHERE user_id = ? 
        AND status = 'borrowed'
    ");
    $stmt->execute([$data['user_id']]);
    $activeLoans = $stmt->fetchColumn();

    if ($activeLoans >= 3) { // Maksimal 3 peminjaman aktif
        throw new Exception('Peminjam telah mencapai batas maksimal peminjaman buku (3 buku).');
    }

    // 5. Generate loan_id unik
    $loan_id = 'LOAN-' . date('Ymd') . '-' . substr(uniqid(), -5);

    // 6. Hitung tanggal jatuh tempo (14 hari dari sekarang)
    $due_date = date('Y-m-d H:i:s', strtotime('+14 days'));

    // 7. Buat record peminjaman
    $stmt = $conn->prepare("
        INSERT INTO loans (
            loan_id, 
            book_id, 
            user_id, 
            loan_date, 
            due_date, 
            status, 
            processed_by,
            request_id
        ) VALUES (
            ?, ?, ?, NOW(), ?, 'borrowed', ?, ?
        )
    ");

    $stmt->execute([
        $loan_id,
        $data['book_id'],
        $data['user_id'],
        $due_date,
        $_SESSION['user_id'],
        $data['request_id'] ?? $loan_id // Gunakan request_id dari QR jika ada, jika tidak gunakan loan_id
    ]);

    // 8. Update ketersediaan buku
    $stmt = $conn->prepare("
        UPDATE books 
        SET available_quantity = available_quantity - 1,
            last_borrowed = NOW(),
            borrow_count = borrow_count + 1
        WHERE id = ?
    ");
    $stmt->execute([$data['book_id']]);

    // 9. Catat log peminjaman
    $stmt = $conn->prepare("
        INSERT INTO loan_logs (
            loan_id,
            action,
            action_by,
            action_date,
            notes
        ) VALUES (
            ?, 'borrowed', ?, NOW(), ?
        )
    ");
    $stmt->execute([
        $loan_id,
        $_SESSION['user_id'],
        'Peminjaman baru melalui ' . (isset($data['request_id']) ? 'QR Code' : 'input manual')
    ]);

    // Commit transaction
    $conn->commit();

    // Kirim response sukses
    echo json_encode([
        'success' => true,
        'message' => 'Peminjaman berhasil diproses',
        'data' => [
            'loan_id' => $loan_id,
            'book_title' => $book['title'],
            'due_date' => $due_date,
            'processed_at' => date('Y-m-d H:i:s'),
            'processed_by' => $_SESSION['username']
        ]
    ]);
} catch (Exception $e) {
    // Rollback jika terjadi error
    if (isset($conn)) {
        $conn->rollBack();
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Log aktivitas peminjaman (di luar transaction)
try {
    if (isset($loan_id)) {
        $logMessage = sprintf(
            "Peminjaman buku ID: %s untuk pengguna ID: %s oleh petugas: %s pada: %s",
            $data['book_id'],
            $data['user_id'],
            $_SESSION['username'],
            date('Y-m-d H:i:s')
        );

        error_log($logMessage, 3, $_SERVER['DOCUMENT_ROOT'] . '/sistem/logs/loan.log');
    }
} catch (Exception $e) {
    // Log error tapi jangan ganggu response ke user
    error_log("Error logging loan activity: " . $e->getMessage());
}
