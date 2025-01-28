<?php
session_start();
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../classes/Loan.php';
require_once '../../classes/Book.php';
require_once '../../classes/User.php';

// Cek login admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header('Location: /sistem/admin/auth/login.php');
    exit;
}

// Get current datetime and logged in admin
$currentDateTime = date('Y-m-d H:i:s'); // Waktu sekarang dari server
$currentUser = $_SESSION['admin_username']; // Admin yang sedang login

try {
    // Inisialisasi database dan manager
    $database = new Database();
    $conn = $database->getConnection();
    $loanManager = new Loan($conn);
    $bookManager = new Book($conn);
    $userManager = new User($conn);

    // Get available books (yang tidak sedang dipinjam)
    $availableBooks = $bookManager->getAvailableBooks();

    // Get all users/borrowers
    $borrowers = $userManager->getAllBorrowers();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validasi input
        $errors = [];

        // Required fields
        $required = ['borrower_id', 'book_id', 'loan_date', 'due_date'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " harus diisi.";
            }
        }

        // Validasi tanggal
        $loanDate = new DateTime($_POST['loan_date']);
        $dueDate = new DateTime($_POST['due_date']);
        $today = new DateTime($currentDateTime);

        if ($loanDate > $dueDate) {
            $errors[] = "Tanggal peminjaman tidak boleh lebih besar dari tanggal pengembalian.";
        }

        if ($dueDate <= $loanDate) {
            $errors[] = "Tanggal pengembalian harus lebih besar dari tanggal peminjaman.";
        }

        // Jika tidak ada error, proses peminjaman
        // Di create-borrow.php
        if (empty($errors)) {
            $loanData = [
                'borrower_id' => $_POST['borrower_id'],
                'book_id' => $_POST['book_id'],
                'loan_date' => $_POST['loan_date'],
                'due_date' => $_POST['due_date'],
                'status' => 'active',
                'created_by' => $_SESSION['admin_username'],    // Username admin yang sedang login (misal: "boba")
                'created_at' => date('Y-m-d H:i:s')            // Waktu server saat transaksi
            ];

            if ($loanManager->createLoanWithAdminInfo($loanData)) {
                $_SESSION['success'] = "Peminjaman berhasil ditambahkan.";
                header('Location: dashboard-borrow.php');
                exit;
            }
        }
    }
} catch (Exception $e) {
    error_log("Error in create-borrow.php: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan dalam sistem. Silakan coba lagi nanti.";
    header('Location: dashboard-borrow.php');
    exit;
}
?>

<!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tambah Peminjaman - <?= SITE_NAME ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

        <!-- Custom CSS -->
        <style>
            :root {
                --primary-color: #3498db;
                --secondary-color: #2ecc71;
                --accent-color: #e74c3c;
                --text-color: #2c3e50;
                --background-color: #f4f7f6;
                --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            body {
                font-family: 'Inter', sans-serif;
                line-height: 1.6;
                color: var(--text-color);
                background-color: var(--background-color);
            }

            .card-hover {
                transition: transform 0.2s ease-in-out;
            }

            .card-hover:hover {
                transform: translateY(-5px);
            }

            .form-container {
                animation: slideIn 0.5s ease-out;
            }

            @keyframes slideIn {
                from {
                    transform: translateY(-20px);
                    opacity: 0;
                }

                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }

            /* Custom Form Styling */
            .form-input {
                @apply mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out;
            }

            .form-select {
                @apply mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 rounded-md transition duration-150 ease-in-out;
            }

            .form-label {
                @apply block text-sm font-medium text-gray-700 mb-1;
            }

            .btn-primary {
                @apply bg-blue-500 hover:bg-blue-600 text-white font-semibold px-6 py-2 rounded-lg transition duration-200 flex items-center justify-center space-x-2 hover:shadow-lg active:transform active:scale-95;
            }

            .btn-secondary {
                @apply bg-gray-500 hover:bg-gray-600 text-white font-semibold px-4 py-2 rounded-lg transition duration-200 flex items-center justify-center space-x-2 hover:shadow-lg active:transform active:scale-95;
            }

            /* Error Message Styling */
            .error-container {
                @apply bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded;
                animation: shake 0.5s ease-in-out;
            }

            @keyframes shake {

                0%,
                100% {
                    transform: translateX(0);
                }

                25% {
                    transform: translateX(-10px);
                }

                75% {
                    transform: translateX(10px);
                }
            }

            /* Form Group Hover Effect */
            .form-group {
                @apply mb-6 relative;
                transition: all 0.3s ease;
            }

            .form-group:hover .form-label {
                @apply text-blue-600;
            }

            /* Custom Select Styling */
            select {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
                background-position: right 0.5rem center;
                background-repeat: no-repeat;
                background-size: 1.5em 1.5em;
            }

            /* Date Input Styling */
            input[type="date"] {
                @apply cursor-pointer;
            }

            input[type="date"]::-webkit-calendar-picker-indicator {
                @apply cursor-pointer hover:opacity-75 transition-opacity duration-150;
            }

            /* Responsive Padding */
            @media (max-width: 640px) {
                .form-container {
                    @apply px-4;
                }
            }
        </style>

    <body class="min-h-screen flex flex-col bg-gray-50">
        <nav x-data="{ open: false }" class="bg-gradient-to-r from-blue-600 to-blue-800 shadow-lg sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <a href="/" class="flex items-center group">
                            <i class="fas fa-book-reader text-white text-2xl mr-2 transform group-hover:scale-110 transition-transform"></i>
                            <span class="text-white font-bold text-xl"><?= htmlspecialchars(SITE_NAME) ?></span>
                        </a>
                    </div>

                    <!-- Enhanced Navigation Links with Animations -->
                    <div class="hidden md:block">
                        <div class="flex items-center space-x-4">
                            <a href="/sistem/public/index.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all duration-300 hover:scale-105">
                                <i class="fas fa-home mr-1"></i> Beranda
                            </a>
                            <a href="/sistem/public/books.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all duration-300 hover:scale-105">
                                <i class="fas fa-book mr-1"></i> Buku
                            </a>
                            <a href="/sistem/public/contact.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all duration-300 hover:scale-105">
                                <i class="fas fa-envelope mr-1"></i> Kontak
                            </a>
                            <?php if (empty($_SESSION['user_id'])): ?>
                                <a href="/sistem/public/auth/login.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all duration-300 hover:scale-105">
                                    <i class="fas fa-sign-in-alt mr-1"></i> Login
                                </a>
                            <?php else: ?>
                                <div class="flex items-center space-x-3">
                                    <span class="text-white text-sm">
                                        <i class="fas fa-user-circle mr-1"></i> Admin
                                    </span>
                                    <a href="/sistem/admin/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 hover:scale-105 flex items-center">
                                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Improved Mobile Menu Button -->
                    <div class="md:hidden">
                        <button @click="open = !open" class="text-white hover:bg-blue-700 p-2 rounded-md transition-colors duration-300">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Enhanced Mobile Menu with Smooth Transitions -->
            <div x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform -translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform -translate-y-2"
                class="md:hidden bg-blue-800">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="/sistem/public/index.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                        <i class="fas fa-home mr-1"></i> Beranda
                    </a>
                    <a href="/sistem/public/books.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                        <i class="fas fa-book mr-1"></i> Buku
                    </a>
                    <a href="/sistem/public/contact.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                        <i class="fas fa-envelope mr-1"></i> Kontak
                    </a>
                </div>
            </div>
        </nav>
        <main class="max-w-4xl mx-auto px-4 py-8">
            <div class="form-container bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-plus-circle text-blue-500 mr-2"></i>
                        Tambah Peminjaman Baru
                    </h1>
                    <a href="dashboard-borrow.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="error-container" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <ul class="mt-2 list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-6">
                    <!-- Peminjam -->
                    <div class="form-group">
                        <label for="borrower_id" class="form-label">Peminjam</label>
                        <select name="borrower_id" id="borrower_id" required class="form-select">
                            <option value="">Pilih Peminjam</option>
                            <?php foreach ($borrowers as $borrower): ?>
                                <option value="<?= $borrower['id'] ?>" <?= isset($_POST['borrower_id']) && $_POST['borrower_id'] == $borrower['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($borrower['name']) ?> - <?= htmlspecialchars($borrower['email']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Buku -->
                    <div class="form-group">
                        <label for="book_id" class="form-label">Buku</label>
                        <select name="book_id" id="book_id" required class="form-select">
                            <option value="">Pilih Buku</option>
                            <?php foreach ($availableBooks as $book): ?>
                                <option value="<?= $book['id'] ?>" <?= isset($_POST['book_id']) && $_POST['book_id'] == $book['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($book['title']) ?> - ISBN: <?= htmlspecialchars($book['isbn']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date Inputs Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tanggal Peminjaman -->
                        <div class="form-group">
                            <label for="loan_date" class="form-label">Tanggal Peminjaman</label>
                            <input type="date" name="loan_date" id="loan_date" required
                                value="<?= isset($_POST['loan_date']) ? $_POST['loan_date'] : date('Y-m-d', strtotime($currentDateTime)) ?>"
                                class="form-input">
                        </div>

                        <!-- Tanggal Pengembalian -->
                        <div class="form-group">
                            <label for="due_date" class="form-label">Tanggal Pengembalian</label>
                            <input type="date" name="due_date" id="due_date" required
                                value="<?= isset($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d', strtotime($currentDateTime . ' +7 days')) ?>"
                                class="form-input">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end pt-4">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            <span>Simpan Peminjaman</span>
                        </button>
                    </div>
                </form>
            </div>
        </main>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const loanDateInput = document.getElementById('loan_date');
                const dueDateInput = document.getElementById('due_date');
                const currentDateTime = <?= json_encode($currentDateTime) ?>;

                // Set min date untuk loan_date (tidak boleh kurang dari hari ini)
                const today = new Date(currentDateTime);
                const formattedToday = today.toISOString().split('T')[0];
                loanDateInput.min = formattedToday;

                // Update min date untuk due_date ketika loan_date berubah
                loanDateInput.addEventListener('change', function() {
                    const selectedDate = new Date(this.value);
                    const nextDay = new Date(selectedDate);
                    nextDay.setDate(selectedDate.getDate() + 1);
                    dueDateInput.min = nextDay.toISOString().split('T')[0];

                    // Jika due_date lebih kecil dari loan_date, update due_date
                    if (new Date(dueDateInput.value) <= selectedDate) {
                        dueDateInput.value = nextDay.toISOString().split('T')[0];
                    }
                });

                // Set default min date untuk due_date
                const minDueDate = new Date(loanDateInput.value);
                minDueDate.setDate(minDueDate.getDate() + 1);
                dueDateInput.min = minDueDate.toISOString().split('T')[0];
            });
        </script>
    </body>

    </html>