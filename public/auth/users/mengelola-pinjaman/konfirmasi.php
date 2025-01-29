<?php
session_start();
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../classes/Book.php';
require_once '../../classes/Loan.php';
// require_once __DIR__ . '/../../../vendor/autoload.php';

class LoanManager
{
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    public function createLoan($userId, $bookId, $loanPeriod)
    {
        // Check book availability
        if (!$this->checkBookAvailability($bookId)) {
            throw new Exception("Buku tidak tersedia");
        }

        $loanDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime("+{$loanPeriod} days"));

        $query = "INSERT INTO loans (user_id, book_id, loan_date, due_date, status) 
                  VALUES (:user_id, :book_id, :loan_date, :due_date, 'active')";

        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
            $stmt->bindParam(':loan_date', $loanDate);
            $stmt->bindParam(':due_date', $dueDate);
            $stmt->execute();

            // Update book availability
            $this->updateBookAvailability($bookId, false);

            $this->conn->commit();

            return [
                'loanDate' => $loanDate,
                'dueDate' => $dueDate,
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Gagal membuat peminjaman: " . $e->getMessage());
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

    private function updateBookAvailability($bookId, $availability)
    {
        $query = "UPDATE books SET available_quantity = available_quantity " .
            ($availability ? '+ 1' : '- 1') .
            " WHERE id = :book_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        $stmt->execute();
    }
}

class LoanConfirmationModal
{
    private $book;
    private $loanDetails;

    public function __construct($book, $loanDetails)
    {
        $this->book = $book;
        $this->loanDetails = $loanDetails;
    }

    public function render()
    {
?>
        <!DOCTYPE html>
        <html lang="id">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Konfirmasi Peminjaman</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
        </head>

        <body class="bg-gray-50 font-inter min-h-screen flex items-center justify-center">
            <div class="confirmation-container w-full max-w-md bg-white p-6 rounded-lg shadow-md">
                <div class="text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto text-green-500 mb-4" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>

                    <h2 class="text-2xl font-bold text-green-700 mt-4">Peminjaman Berhasil!</h2>

                    <div class="mt-6 text-left space-y-3 bg-gray-50 p-4 rounded-lg">
                        <p><strong>Buku:</strong> <?= htmlspecialchars($this->book['title']) ?></p>
                        <p><strong>Tanggal Pinjam:</strong> <?= htmlspecialchars($this->loanDetails['loanDate']) ?></p>
                        <p><strong>Tanggal Kembali:</strong> <?= htmlspecialchars($this->loanDetails['dueDate']) ?></p>
                        <p class="text-sm text-gray-600">Harap kembalikan buku tepat waktu.</p>
                    </div>

                    <a href="/sistem/public/auth/users/book-loan.php" class="mt-6 inline-block text-white bg-green-500 py-3 px-6 rounded-lg text-center">Kembali ke Daftar Buku</a>
                </div>
            </div>
        </body>

        </html>
<?php
    }
}

// Contoh penggunaan
try {
    $database = new Database();
    $conn = $database->getConnection();

    $loanManager = new LoanManager($conn);

    // Pastikan Anda memiliki parameter yang benar
    $userId = $_SESSION['user_id'] ?? 1; // Gunakan user ID dari sesi atau default
    $bookId = $_GET['book_id'] ?? 1; // Dapatkan book ID dari parameter
    $loanPeriod = 14; // Misalnya 14 hari

    // Ambil detail buku
    $bookManager = new Book($conn);
    $book = $bookManager->getBookById($bookId);

    // Buat peminjaman
    $loanDetails = $loanManager->createLoan($userId, $bookId, $loanPeriod);

    // Tampilkan konfirmasi
    $modal = new LoanConfirmationModal($book, $loanDetails);
    $modal->render();
} catch (Exception $e) {
    // Tangani kesalahan
    echo "<div class='bg-red-100 text-red-700 p-4 rounded'>";
    echo "Kesalahan: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>