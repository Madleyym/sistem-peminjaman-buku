<?php
session_start();
require_once '../includes/admin-auth.php';
checkAdminAuth();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$rootPath = realpath(dirname(__DIR__, 2));
// require_once $rootPath . '/vendor/autoload.php';
require_once $rootPath . '/config/constants.php';
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/classes/Book.php';
require_once $rootPath . '/classes/Category.php';

// Pastikan direktori upload ada
$uploadDir = __DIR__ . "/../../uploads/book_covers/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}


// Inisialisasi objek
$database = new Database();
$conn = $database->getConnection();
$bookManager = new Book($conn);

$categories = $bookManager->getCategory();

// Debug output
$categories = $bookManager->getCategory();
error_log("Found categories: " . print_r($categories, true));

// Pagination dan Filter
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$searchQuery = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        if ($bookManager->deleteBook($_GET['id'])) {
            $_SESSION['message'] = "Buku berhasil dihapus";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}

// Handle Add/Edit Book
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $bookData = [
            'title' => trim($_POST['title'] ?? ''),
            'author' => trim($_POST['author'] ?? ''),
            'publisher' => trim($_POST['publisher'] ?? ''),
            'year_published' => trim($_POST['year_published'] ?? ''),
            'isbn' => trim($_POST['isbn'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'total_quantity' => (int)trim($_POST['total_quantity'] ?? 0),
            'available_quantity' => (int)trim($_POST['available_quantity'] ?? 0),
            'description' => trim($_POST['description'] ?? ''),
            'shelf_location' => trim($_POST['shelf_location'] ?? '')
        ];

        if (!empty($_FILES['cover_image']['name'])) {
            $uploadResult = handleBookCoverUpload($_FILES['cover_image']);
            if ($uploadResult['success']) {
                $bookData['book_cover'] = $uploadResult['filename'];
            }
        }

        $bookId = $_POST['id'] ?? null;
        if ($bookId) {
            $bookManager->updateBook($bookId, $bookData);
            $_SESSION['message'] = "Buku berhasil diperbarui";
        } else {
            $bookManager->create($bookData);
            $_SESSION['message'] = "Buku berhasil ditambahkan";
        }

        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Perbaikan fungsi handleBookCoverUpload
function handleBookCoverUpload($file)
{
    // Definisikan path absolut untuk direktori upload
    $targetDir = __DIR__ . "/../../uploads/book_covers/";

    // Pastikan direktori ada
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Validasi ukuran file (max 5MB)
    if ($file['size'] > 5000000) {
        throw new Exception("Ukuran file terlalu besar. Maksimal 5MB.");
    }

    $fileName = uniqid() . '_' . basename($file['name']);
    $targetPath = $targetDir . $fileName;

    $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception("Tipe file tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diperbolehkan.");
    }

    // Debug information
    error_log("Upload Path: " . $targetPath);
    error_log("Directory exists: " . (file_exists($targetDir) ? 'Yes' : 'No'));
    error_log("Directory writable: " . (is_writable($targetDir) ? 'Yes' : 'No'));

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Gagal mengunggah file: " . error_get_last()['message']);
    }

    return [
        'success' => true,
        'filename' => $fileName
    ];
}
// Get Data
$books = $bookManager->getAllBooks($limit, $offset, $searchQuery, $categoryFilter);
$totalBooks = $bookManager->countTotalBooks($searchQuery, $categoryFilter);
$totalPages = ceil($totalBooks / $limit);

$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Buku - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --text-color: #2c3e50;
            --background-color: #f4f7f6;
            --white: #ffffff;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        input,
        select,
        textarea {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideInUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Enhanced Table Styles */
        .book-table-container {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeIn 0.3s ease-in-out;
        }

        .book-table {
            width: 100%;
            border-collapse: collapse;
        }

        .book-table th {
            padding: 1.5rem;
            background: linear-gradient(to right, #2563eb, #1d4ed8);
            color: white;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        .book-table tr {
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.2s;
        }

        .book-table tr:hover {
            background-color: #eff6ff;
        }

        /* Enhanced Card Styles */
        .book-card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            animation: slideInUp 0.3s ease-out;
        }

        .book-card:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
        }

        /* Enhanced Button Styles */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: linear-gradient(to right, #2563eb, #1d4ed8);
            color: white;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.15s ease-in-out;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #1d4ed8, #1e40af);
        }

        .btn-primary:focus {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.4);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.15s ease-in-out;
        }

        .btn-secondary:hover {
            background-color: #e5e7eb;
        }

        /* Modal Styles */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 50;
            animation: fadeIn 0.2s ease-out;
        }

        .modal-content {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(1);
            transition: all 0.3s ease-out;
            animation: slideInUp 0.3s ease-out;
        }

        /* Form Styles */
        .form-input {
            width: 100%;
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            transition: all 0.15s ease-in-out;
        }

        .form-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .form-select {
            width: 100%;
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            background-color: white;
            transition: all 0.15s ease-in-out;
        }

        /* Loading Spinner */
        .loading-spinner {
            animation: spin 1s linear infinite;
            height: 1.25rem;
            width: 1.25rem;
            color: white;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive Styles */
        @media (max-width: 640px) {
            .responsive-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .mobile-stack {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }
        }

        /* Notification Styles */
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 50;
            max-width: 28rem;
            animation: slideInUp 0.3s ease-out;
        }

        .notification-content {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .notification-success {
            background-color: #22c55e;
            color: white;
        }

        .notification-error {
            background-color: #ef4444;
            color: white;
        }

        /* Tambahkan di bagian style */
        .book-cover-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .book-cover-preview-container {
            aspect-ratio: 2/3;
            overflow: hidden;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<!-- START: MAIN LAYOUT -->

<body x-data="bookManager">
    <!-- Navigation Component -->
    <nav x-data="{ open: false }" class="bg-gradient-to-r from-blue-600 to-blue-800 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/sistem/admin/index.php" class="flex items-center group">
                        <i class="fas fa-book-reader text-white text-2xl mr-2 transform group-hover:scale-110 transition-transform"></i>
                        <span class="text-white font-bold text-xl"><?= htmlspecialchars(SITE_NAME) ?></span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:block">
                    <div class="flex items-center space-x-4">
                        <a href="/sistem/beranda-pengguna.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-home mr-1"></i> Beranda
                        </a>
                        <a href="/sistem/public/books.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-book mr-1"></i> Buku
                        </a>
                        <a href="/sistem/public/contact.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-envelope mr-1"></i> Kontak
                        </a>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                            <!-- Jika login sebagai admin -->
                            <div class="flex items-center space-x-3">
                                <span class="text-white text-sm">
                                    <i class="fas fa-user-circle mr-1"></i>
                                    <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                                </span>
                                <a href="/sistem/admin/auth/logout.php"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium">
                                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <!-- Jika belum login -->
                                <a href="/sistem/admin/auth/login.php"
                                    class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                                    <i class="fas fa-sign-in-alt mr-1"></i> Login Admin
                                </a>
                            <?php endif; ?>
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
                <a href="/sistem/beranda-pengguna.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
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
    <!-- Notification Component -->
    <?php if ($message || $error): ?>
        <div class="fixed top-4 right-4 z-50 notification">
            <div class="<?= $error ? 'bg-red-500' : 'bg-green-500' ?> text-white px-6 py-4 rounded-lg shadow-lg">
                <div class="flex items-center">
                    <i class="fas <?= $error ? 'fa-exclamation-circle' : 'fa-check-circle' ?> mr-2"></i>
                    <p><?= htmlspecialchars($error ?: $message) ?></p>
                </div>
            </div>
        </div>
        <script>
            setTimeout(() => {
                document.querySelector('.notification')?.remove();
            }, 5000);
        </script>
    <?php endif; ?>
    <!-- START: MAIN CONTENT -->
    <main x-data="bookManager" class="container mx-auto px-4 py-8">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                <h1 class="text-2xl md:text-3xl font-bold text-blue-700">Manajemen Buku</h1>
                <button
                    @click="openAddModal()"
                    class="w-full md:w-auto bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Tambah Buku Baru</span>
                </button>
            </div>

            <!-- Search and Filter Section -->
            <!-- Search and Filter Section -->
            <div class="bg-white shadow rounded-lg p-4 md:p-6">
                <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input
                        type="text"
                        name="search"
                        placeholder="Cari buku..."
                        value="<?= htmlspecialchars($searchQuery) ?>"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <select
                        name="category"
                        class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Pilih Kategori</option>
                        <?php
                        $stmt = $conn->prepare("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category");
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($categoryFilter == $row['category']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['category']) . "' $selected>" .
                                htmlspecialchars($row['category']) . "</option>";
                        }
                        ?>
                    </select>
                    <div class="flex items-center space-x-2">
                        <button type="submit"
                            class="w-full md:w-auto bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-150 ease-in-out">
                            Cari
                        </button>
                        <button type="button" id="resetButton"
                            class="w-full md:w-auto bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-150 ease-in-out">
                            Reset
                        </button>
                    </div>
                </form>
            </div>

            <!-- JavaScript untuk Tombol Reset -->
            <script>
                document.getElementById('resetButton').addEventListener('click', function() {
                    window.location.href = window.location.pathname;
                });
            </script>

            <!-- Books List -->
            <div class="bg-white shadow rounded-lg overflow-hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Buku</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Detail</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Lokasi</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($books as $book): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <img src="<?= $book['cover_image'] ? '/sistem/uploads/book_covers/' . htmlspecialchars($book['cover_image']) : '/sistem/uploads/books/book-default.png' ?>"
                                            alt="Cover Buku"
                                            class="h-16 w-12 object-cover rounded hidden md:block"
                                            onerror="this.src='/sistem/uploads/books/book-default.png'">
                                        <div class="ml-0 md:ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($book['title']) ?></div>
                                            <div class="text-sm text-gray-500 md:hidden">
                                                <?= htmlspecialchars($book['author']) ?><br>
                                                ISBN: <?= htmlspecialchars($book['isbn']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500 hidden md:block"><?= htmlspecialchars($book['author']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($book['publisher']) ?> (<?= $book['year_published'] ?>)</div>
                                    <div class="text-sm text-gray-500">ISBN: <?= htmlspecialchars($book['isbn']) ?></div>
                                    <div class="text-sm text-gray-500">Kategori: <?= htmlspecialchars($book['category']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm <?= $book['available_quantity'] < 5 ? 'text-red-500' : 'text-green-500' ?>">
                                        <?= $book['available_quantity'] ?> / <?= $book['total_quantity'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($book['shelf_location']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            type="button"
                                            class="text-blue-500 hover:text-blue-700"
                                            @click="openEditModal(<?= htmlspecialchars(json_encode($book)) ?>)">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            class="text-red-500 hover:text-red-700"
                                            @click="confirmDelete(<?= $book['id'] ?>)">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Pagination -->
                <div class="mt-6 flex justify-center">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <!-- Previous Page -->
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?>"
                                class="relative inline-flex items-center px-4 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=<?= $i ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?>"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?= $i === $page ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-700' ?> text-sm font-medium hover:bg-gray-50">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?>"
                                class="relative inline-flex items-center px-4 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>


            <!-- Modal -->
            <div x-show="isModalOpen" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                <div @click.away="isModalOpen = false" class="bg-white rounded-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-blue-700" x-text="editingBook.id ? 'Edit Buku' : 'Tambah Buku Baru'"></h2>
                            <button @click="isModalOpen = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Form -->
                        <form method="post" enctype="multipart/form-data" @submit.prevent="submitForm">
                            <input type="hidden" name="id" x-model="editingBook.id">

                            <!-- Main Grid Layout -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Basic Book Information -->
                                <div>
                                    <label class="block text-sm font-medium mb-2">Judul Buku <span class="text-red-500">*</span></label>
                                    <input type="text" name="title" x-model="editingBook.title" required
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Penulis <span class="text-red-500">*</span></label>
                                    <input type="text" name="author" x-model="editingBook.author" required
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Penerbit <span class="text-red-500">*</span></label>
                                    <input type="text" name="publisher" x-model="editingBook.publisher" required
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Tahun Publikasi <span class="text-red-500">*</span></label>
                                    <input type="number" name="year_published" x-model="editingBook.year_published" required
                                        min="1900" :max="new Date().getFullYear()"
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">ISBN</label>
                                    <input type="text" name="isbn" x-model="editingBook.isbn" placeholder="Format: XXX-XXX-XXX"
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Kategori <span class="text-red-500">*</span></label>
                                    <select
                                        name="category"
                                        class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Semua Kategori</option>
                                        <?php
                                        $stmt = $conn->prepare("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category");
                                        $stmt->execute();
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $selected = (isset($_GET['category']) && $_GET['category'] == $row['category']) ? 'selected' : '';
                                            echo "<option value='" . htmlspecialchars($row['category']) . "' {$selected}>" .
                                                htmlspecialchars($row['category']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Quantity Information -->
                                <div>
                                    <label class="block text-sm font-medium mb-2">Total Kuantitas <span class="text-red-500">*</span></label>
                                    <input type="number" name="total_quantity" x-model="editingBook.total_quantity" required min="0"
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Kuantitas Tersedia <span class="text-red-500">*</span></label>
                                    <input type="number" name="available_quantity" x-model="editingBook.available_quantity" required
                                        min="0" :max="editingBook.total_quantity"
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Lokasi Rak <span class="text-red-500">*</span></label>
                                    <input type="text" name="shelf_location" x-model="editingBook.shelf_location" required
                                        placeholder="Contoh: A-01"
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>

                                <!-- Fields yang mengambil full width -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-2">Deskripsi Buku</label>
                                    <textarea name="description"
                                        x-model="editingBook.description"
                                        rows="4"
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                        placeholder="Masukkan deskripsi buku..."></textarea>
                                </div>
                                <!-- Ganti bagian preview image pada modal -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-2">Sampul Buku</label>
                                    <div class="flex items-center space-x-4">
                                        <!-- Preview gambar dengan x-cloak untuk mencegah flicker -->
                                        <div x-cloak x-show="editingBook.cover_image || editingBook.cover_image_preview"
                                            class="w-24 h-32 border rounded overflow-hidden">
                                            <img :src="getImageSource()"
                                                alt="Preview"
                                                class="w-full h-full object-cover"
                                                @error="handleImageError"
                                                x-ref="previewImage">
                                        </div>
                                        <div class="flex-1">
                                            <input type="file"
                                                name="cover_image"
                                                accept="image/*"
                                                @change="handleImagePreview"
                                                x-ref="fileInput"
                                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <p class="text-sm text-gray-500 mt-1">
                                                Format yang didukung: JPG, JPEG, PNG. Maksimal 5MB.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button (you might want to add this) -->
                            <div class="mt-6 flex justify-end">
                                <div class="mt-6 flex justify-end">
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                        Simpan
                                    </button>
                                </div>
                        </form>
                    </div>
                </div>
            </div>

    <!-- Alpine.js Script -->
    <script>
        document.getElementById('resetButton').addEventListener('click', function() {
            window.location.href = window.location.pathname;
        });

        document.addEventListener('alpine:init', () => {
            Alpine.data('bookManager', () => ({
                isModalOpen: false,
                isLoading: false,
                defaultImagePath: '/sistem/uploads/books/book-default.png', // Path default image
                notification: {
                    show: false,
                    message: '',
                    type: 'success',
                    timeout: null
                },
                editingBook: {
                    id: null,
                    title: '',
                    author: '',
                    publisher: '',
                    year_published: '',
                    isbn: '',
                    category: '',
                    total_quantity: '',
                    available_quantity: '',
                    shelf_location: '',
                    description: '',
                    cover_image: '',
                    cover_image_preview: null,
                    created_at: '', // Tambahkan timestamp
                    updated_by: 'Madleyym' // Tambahkan user yang update
                },

                showNotification(message, type = 'success') {
                    if (this.notification.timeout) {
                        clearTimeout(this.notification.timeout);
                    }

                    this.notification = {
                        show: true,
                        message,
                        type,
                        timeout: setTimeout(() => {
                            this.notification.show = false;
                        }, 3000)
                    };
                },

                validateForm() {
                    const requiredFields = ['title', 'author', 'publisher', 'year_published', 'total_quantity', 'shelf_location'];
                    for (const field of requiredFields) {
                        if (!this.editingBook[field]) {
                            throw new Error(`Kolom ${field.replace('_', ' ')} harus diisi`);
                        }
                    }

                    const currentYear = new Date().getFullYear();
                    if (this.editingBook.year_published < 1900 || this.editingBook.year_published > currentYear) {
                        throw new Error(`Tahun harus antara 1900 dan ${currentYear}`);
                    }

                    if (parseInt(this.editingBook.total_quantity) < 0) {
                        throw new Error('Jumlah total tidak boleh negatif');
                    }

                    if (parseInt(this.editingBook.available_quantity) > parseInt(this.editingBook.total_quantity)) {
                        throw new Error('Jumlah tersedia tidak boleh melebihi jumlah total');
                    }
                },

                openAddModal() {
                    this.clearForm();
                    this.editingBook.year_published = new Date().getFullYear();
                    this.editingBook.created_at = new Date().toISOString();
                    this.editingBook.updated_by = 'Madleyym';
                    this.isModalOpen = true;
                },

                openEditModal(book) {
                    this.clearForm();
                    this.editingBook = {
                        ...book,
                        cover_image_preview: book.cover_image ? `/sistem/uploads/book_covers/${book.cover_image}` : null,
                        updated_by: 'Madleyym'
                    };
                    this.isModalOpen = true;
                },

                clearForm() {
                    this.editingBook = {
                        id: null,
                        title: '',
                        author: '',
                        publisher: '',
                        year_published: '',
                        isbn: '',
                        category: '',
                        total_quantity: '',
                        available_quantity: '',
                        shelf_location: '',
                        description: '',
                        cover_image: '',
                        cover_image_preview: null,
                        created_at: '',
                        updated_by: ''
                    };
                    this.resetImagePreview();
                },

                async confirmDelete(id) {
                    try {
                        if (await this.showConfirmDialog('Apakah Anda yakin ingin menghapus buku ini?')) {
                            this.isLoading = true;
                            window.location.href = `?action=delete&id=${id}`;
                        }
                    } catch (error) {
                        this.showNotification(error.message, 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                showConfirmDialog(message) {
                    return new Promise((resolve) => {
                        const result = confirm(message);
                        resolve(result);
                    });
                },

                getImageSource() {
                    if (this.editingBook.cover_image_preview) {
                        return this.editingBook.cover_image_preview;
                    }
                    if (this.editingBook.cover_image) {
                        return `/sistem/uploads/book_covers/${this.editingBook.cover_image}`;
                    }
                    return this.defaultImagePath;
                },

                handleImagePreview(event) {
                    const file = event.target.files[0];
                    if (file) {
                        // Validasi ukuran file (5MB)
                        if (file.size > 5 * 1024 * 1024) {
                            this.showNotification('Ukuran file tidak boleh lebih dari 5MB', 'error');
                            this.resetImagePreview();
                            return;
                        }

                        // Validasi tipe file
                        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        if (!allowedTypes.includes(file.type)) {
                            this.showNotification('Hanya file JPG, PNG, dan GIF yang diperbolehkan', 'error');
                            this.resetImagePreview();
                            return;
                        }

                        // Buat preview
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.editingBook.cover_image_preview = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    } else {
                        this.resetImagePreview();
                    }
                },

                handleImageError(event) {
                    event.target.src = this.defaultImagePath;
                },

                resetImagePreview() {
                    this.editingBook.cover_image_preview = null;
                    const fileInput = this.$refs.fileInput;
                    if (fileInput) fileInput.value = '';
                },

                async submitForm(e) {
                    try {
                        e.preventDefault();
                        this.isLoading = true;

                        this.validateForm();

                        const form = e.target;
                        const formData = new FormData(form);

                        // Tambah timestamp dan user
                        formData.append('updated_by', this.editingBook.updated_by);
                        formData.append('updated_at', new Date().toISOString());

                        // Submit form
                        form.submit();

                        this.showNotification(
                            this.editingBook.id ? 'Buku berhasil diperbarui' : 'Buku berhasil ditambahkan'
                        );
                        this.isModalOpen = false;

                    } catch (error) {
                        this.showNotification(error.message, 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                init() {
                    // Watcher untuk total quantity
                    this.$watch('editingBook.total_quantity', (value) => {
                        if (parseInt(this.editingBook.available_quantity) > parseInt(value)) {
                            this.editingBook.available_quantity = value;
                        }
                    });

                    // Watcher untuk modal
                    this.$watch('isModalOpen', (value) => {
                        if (!value) {
                            this.clearForm();
                        }
                    });
                }
            }));
        });
    </script>

</html>