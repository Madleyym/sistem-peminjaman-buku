<?php
// Start session first
session_start();

// Enable full error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log all errors to existing log file
ini_set('error_log', 'C:\xampp\htdocs\sistem\logs\error.log');

// Correct require_once statements with absolute paths
// require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../config/constants.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../classes/Book.php';

try {
    // Authentication check
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /sistem/public/login.php?error=login_required');
        exit();
    }

    // Validate book ID
    $book_id = filter_input(INPUT_GET, 'book_id', FILTER_VALIDATE_INT);
    if (!$book_id) {
        header('Location: ../daftar-buku.php');
        exit();
    }

    // Rest of the existing code remains the same...
} catch (Exception $e) {
    error_log("Loan Process Error: " . $e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan sistem.';
    header('Location: ../daftar-buku.php');
    exit();
}

class LoanManager
{
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    public function createLoan($userId, $bookId, $loanPeriod)
    {
        try {
            // Check book availability
            if (!$this->checkBookAvailability($bookId)) {
                return ['status' => false, 'message' => 'Buku tidak tersedia'];
            }

            $loanDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime("+{$loanPeriod} days"));

            $this->conn->beginTransaction();

            // Create loan record
            $query = "INSERT INTO loans (user_id, book_id, loan_date, due_date, status) 
                      VALUES (:user_id, :book_id, :loan_date, :due_date, 'active')";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
            $stmt->bindParam(':loan_date', $loanDate);
            $stmt->bindParam(':due_date', $dueDate);
            $stmt->execute();

            // Reduce book availability
            $updateQuery = "UPDATE books SET available_quantity = available_quantity - 1 WHERE id = :book_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
            $updateStmt->execute();

            $this->conn->commit();

            return [
                'status' => true,
                'message' => 'Peminjaman berhasil',
                'loan_date' => $loanDate,
                'due_date' => $dueDate
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['status' => false, 'message' => 'Gagal memproses peminjaman: ' . $e->getMessage()];
        }
    }

    private function checkBookAvailability($bookId)
    {
        $query = "SELECT available_quantity FROM books WHERE id = :book_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result && $result['available_quantity'] > 0;
    }
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $bookManager = new Book($conn);
    $book = $bookManager->getBookById($book_id);

    // Check book availability
    if (!$book || $book['available_quantity'] <= 0) {
        $_SESSION['error'] = 'Buku tidak tersedia untuk dipinjam.';
        header('Location: ../books.php');
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

        $loanManager = new LoanManager($conn);
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
                    'loanDate' => $loan_result['loan_date'],
                    'dueDate' => $loan_result['due_date']
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
    header('Location: ../books.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjam Buku - <?= htmlspecialchars($book['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/defer"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<style>
    :root {
        --primary-color: #3498db;
        --secondary-color: #2ecc71;
        --accent-color: #e74c3c;
        --text-color: #2c3e50;
        --background-color: #f4f7f6;
        --white: #ffffff;
        --shadow-color: rgba(0, 0, 0, 0.1);
        --border-radius: 16px;
    }

    body {
        font-family: 'Inter', 'Arial', sans-serif;
        line-height: 1.6;
        color: var(--text-color);
        background-color: var(--background-color);
    }

    .loan-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #f4f7f6 100%);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.05);
        border-radius: var(--border-radius);
    }

    .loan-form {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 2rem;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        border-left: 5px solid var(--primary-color);
    }

    .loan-input {
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .loan-input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    }

    .loan-button {
        background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        transition: all 0.4s ease;
        position: relative;
        overflow: hidden;
    }

    .loan-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: all 0.4s ease;
    }

    .loan-button:hover::before {
        left: 100%;
    }
</style>

<body class="bg-gray-50 font-inter min-h-screen flex flex-col">
    <!-- Navigation (same as books-detail.php) -->

    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="loan-container rounded-2xl overflow-hidden">
            <div class="grid md:grid-cols-2 gap-8 p-8">
                <!-- Book Cover -->
                <!-- Book Cover -->
                <div class="book-cover-container">
                    <img
                        src="<?= !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : '../assets/images/default-book-cover.jpg' ?>"
                        alt="<?= htmlspecialchars($book['title']) ?>"
                        class="book-cover w-full h-[500px] object-cover rounded-xl">
                </div>

                <!-- Loan Form -->
                <div class="loan-form">
                    <h2 class="text-4xl font-bold text-blue-700 mb-6 border-b-2 border-blue-100 pb-4">
                        Pinjam Buku
                    </h2>

                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-600">Nama Lengkap</label>
                            <input
                                type="text"
                                name="name"
                                required
                                class="loan-input w-full px-3 py-2 border rounded-lg" />
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-600">Email</label>
                            <input
                                type="email"
                                name="email"
                                required
                                class="loan-input w-full px-3 py-2 border rounded-lg" />
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-600">Nomor Telepon</label>
                            <input
                                type="tel"
                                name="phone"
                                required
                                class="loan-input w-full px-3 py-2 border rounded-lg" />
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-600">Alamat</label>
                            <textarea
                                name="address"
                                required
                                class="loan-input w-full px-3 py-2 border rounded-lg"></textarea>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-600">Periode Peminjaman</label>
                            <select
                                name="loanPeriod"
                                class="loan-input w-full px-3 py-2 border rounded-lg">
                                <option value="14">2 Minggu</option>
                                <option value="21">3 Minggu</option>
                                <option value="30">1 Bulan</option>
                            </select>
                        </div>

                        <button
                            type="submit"
                            class="loan-button w-full text-white py-4 rounded-lg text-center">
                            Ajukan Peminjaman
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer (same as books-detail.php) -->
</body>

</html>
<?php if (!empty($_SESSION['success'])): ?>
    <div class="bg-green-100 text-green-700 px-4 py-3 rounded mb-4">
        <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php elseif (!empty($_SESSION['error'])): ?>
    <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4">
        <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>