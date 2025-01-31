<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/sistem/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/sistem/config/constants.php';

// Function untuk mengirim response JSON
function sendJsonResponse($success, $message, $data = null)
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Validasi session
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Sesi login tidak valid');
}

// Validasi method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Method tidak valid');
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Validasi input
    $required_fields = ['name', 'email', 'phone_number', 'address', 'book_id', 'user_id'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field $field wajib diisi");
        }
    }

    // Sanitasi input
    $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    $book_id = filter_var($_POST['book_id'], FILTER_VALIDATE_INT);
    $phone_number = preg_replace('/[^0-9]/', '', $_POST['phone_number']);
    $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);
    $borrow_date = date('Y-m-d H:i:s');
    $due_date = date('Y-m-d H:i:s', strtotime('+7 days'));
    $status = 'pending';

    // Begin transaction
    $conn->beginTransaction();

    // 1. Check book availability
    $checkBook = $conn->prepare("
        SELECT id, available_quantity 
        FROM books 
        WHERE id = ? AND available_quantity > 0 
        FOR UPDATE
    ");
    $checkBook->execute([$book_id]);
    $bookData = $checkBook->fetch(PDO::FETCH_ASSOC);

    if (!$bookData || $bookData['available_quantity'] <= 0) {
        throw new Exception("Buku tidak tersedia untuk dipinjam");
    }

    // 2. Update user data
    $updateUser = $conn->prepare("
        UPDATE users 
        SET phone_number = ?, 
            address = ?
        WHERE id = ?
    ");
    $updateUser->execute([$phone_number, $address, $user_id]);

    // 3. Create borrowing record
    $createBorrow = $conn->prepare("
        INSERT INTO borrowings (
            user_id, book_id, borrow_date, due_date, status
        ) VALUES (?, ?, ?, ?, ?)
    ");
    $createBorrow->execute([
        $user_id,
        $book_id,
        $borrow_date,
        $due_date,
        $status
    ]);
    $borrow_id = $conn->lastInsertId();

    // 4. Update book quantity
    $updateBook = $conn->prepare("
        UPDATE books 
        SET available_quantity = available_quantity - 1
        WHERE id = ?
    ");
    $updateBook->execute([$book_id]);

    // 5. Create notification
    $createNotif = $conn->prepare("
        INSERT INTO notifications (
            user_id, type, message, related_id, status
        ) VALUES (?, 'borrow_request', ?, ?, 'unread')
    ");
    $createNotif->execute([
        $user_id,
        "Permintaan peminjaman buku sedang diproses",
        $borrow_id
    ]);

    // Commit transaction
    $conn->commit();

    // Success response with redirect URL
    sendJsonResponse(true, 'Permintaan peminjaman berhasil diajukan', [
        'borrow_id' => $borrow_id,
        'redirect_url' => '/sistem/user/pinjaman.php?success=true'
    ]);
} catch (Exception $e) {
    // Rollback transaction if error occurs
    if (isset($conn)) {
        $conn->rollBack();
    }

    // Log error
    error_log("Borrowing Error: " . $e->getMessage());

    // Send error response
    sendJsonResponse(false, $e->getMessage());
}
