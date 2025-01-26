<?php
session_start();
require_once dirname(__DIR__, 4) . '/vendor/autoload.php';
require_once dirname(__DIR__, 4) . '/config/constants.php';
require_once dirname(__DIR__, 4) . '/config/database.php';
require_once dirname(__DIR__, 4) . '/classes/Book.php';
require_once dirname(__DIR__, 4) . '/classes/Loan.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Validate book ID
$book_id = filter_input(INPUT_GET, 'book_id', FILTER_VALIDATE_INT);
if (!$book_id) {
    header('Location: books.php');
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $bookManager = new Book($conn);
    $book = $bookManager->getBookById($book_id);

    // Check book availability
    if (!$book || $book['available_quantity'] <= 0) {
        $_SESSION['error'] = 'Buku tidak tersedia untuk dipinjam.';
        header('Location: books.php');
        exit();
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize and validate inputs
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $loan_period = filter_input(INPUT_POST, 'loanPeriod', FILTER_VALIDATE_INT);

        // Check required fields
        if (!$name || !$email || !$phone || !$address || !$loan_period) {
            $_SESSION['error'] = 'Semua field harus diisi dengan benar.';
            header("Location: loan.php?book_id={$book_id}");
            exit();
        }

        $loanManager = new Loan($conn);
        $loan_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime("+{$loan_period} days"));

        $loanData = [
            'user_id' => $_SESSION['user_id'],
            'book_id' => $book_id,
            'loan_date' => $loan_date,
            'due_date' => $due_date,
            'status' => 'active'
        ];

        // Create loan
        $loan_result = $loanManager->createLoan(
            $_SESSION['user_id'],
            $book_id,
            $loan_period
        );

        if ($loan_result['status']) {
            // Save loan details for confirmation
            $_SESSION['loan_confirmation'] = [
                'book' => $book,
                'loan_details' => [
                    'loanDate' => $loan_date,
                    'dueDate' => $due_date
                ]
            ];

            header('Location: confirm.php');
            exit();
        } else {
            $_SESSION['error'] = $loan_result['message'];
            header("Location: loan.php?book_id={$book_id}");
            exit();
        }
    }
} catch (Exception $e) {
    error_log("Loan Process Error: " . $e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem.';
    header('Location: books.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjam Buku: <?= htmlspecialchars($book['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <form method="POST" class="max-w-md mx-auto bg-white p-8 rounded-xl shadow-lg">
            <h2 class="text-2xl font-bold text-blue-700 mb-6">Pinjam Buku: <?= htmlspecialchars($book['title']) ?></h2>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="space-y-4">
                <div>
                    <label class="block mb-2">Nama Lengkap</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block mb-2">Email</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block mb-2">Nomor Telepon</label>
                    <input type="tel" name="phone" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block mb-2">Alamat</label>
                    <textarea name="address" required class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
                <div>
                    <label class="block mb-2">Periode Peminjaman</label>
                    <select name="loanPeriod" class="w-full px-3 py-2 border rounded-lg">
                        <option value="14">2 Minggu</option>
                        <option value="21">3 Minggu</option>
                        <option value="30">1 Bulan</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600">
                    Ajukan Peminjaman
                </button>
            </div>
        </form>
    </div>
</body>

</html>