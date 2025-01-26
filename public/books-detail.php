<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // Start session for authentication check

// Check if user is logged in AFTER starting the session
// $isLoggedIn = !empty($_SESSION['user_id']);

// // Authentication check
// if (!$isLoggedIn) {
//     // Redirect to login page or show a modal/alert
//     $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
//     header('Location: sistem/public/auth/login.php');
//     exit();
// }

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../classes/Book.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Check if user is logged in
$isLoggedIn = !empty($_SESSION['user_id']);

if (!class_exists('Endroid\QrCode\QrCode')) {
    die('Endroid QR Code class not found. Check Composer installation.');
}

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;

// Check if book ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: books.php');
    exit();
}

$book_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$book_id) {
    header('Location: books.php');
    exit();
}

try {
    // Move QR code generation AFTER $book_id is validated
    $bookQrUrl = SITE_URL . '/auth/users/book-loan.php?id=' . $book_id;

    $qrCode = QrCode::create($bookQrUrl)
        ->setSize(300)
        ->setMargin(10)
        ->setForegroundColor(new Color(0, 0, 0))
        ->setBackgroundColor(new Color(255, 255, 255));

    $writer = new PngWriter();
    $result = $writer->write($qrCode);
    $qrCodeDataUri = $result->getDataUri();
} catch (Exception $e) {
    error_log("QR Code Error: " . $e->getMessage());
    $qrCodeDataUri = '';
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    $bookManager = new Book($conn);

    // Fetch book details
    $book = $bookManager->getBookById($book_id);

    if (!$book) {
        // Book not found
        header('Location: books.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Book Detail Error: " . $e->getMessage());
    header('Location: books.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Buku - <?= htmlspecialchars($book['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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

        .book-detail-container {
            background: linear-gradient(135deg, #f5f7fa 0%, #f4f7f6 100%);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.05);
        }

        .book-cover-container {
            position: relative;
            perspective: 1000px;
        }

        .book-cover {
            transition: all 0.5s ease;
            border-radius: var(--border-radius);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            transform-style: preserve-3d;
        }

        .book-cover:hover {
            transform: rotateY(-10deg) scale(1.05);
        }

        .book-info-container {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            border-left: 5px solid var(--primary-color);
        }

        .book-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .book-meta-item {
            background-color: #f9f9f9;
            padding: 1rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
        }

        .book-meta-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .book-meta-item i {
            margin-right: 1rem;
            color: var(--primary-color);
            font-size: 1.5rem;
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

        .description-section {
            background-color: #f0f4f8;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            position: relative;
        }

        .description-section::before {
            content: '"';
            position: absolute;
            top: -20px;
            left: 10px;
            font-size: 4rem;
            color: var(--primary-color);
            opacity: 0.2;
        }
    </style>


<body class="bg-gray-50 font-inter min-h-screen flex flex-col">
    <!-- Mobile Navigation (same as books.php) -->
    <nav x-data="{ open: false }" class="bg-blue-700 md:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/sistem/public/index.php" class="text-white font-bold text-xl">
                        <?= htmlspecialchars(SITE_NAME) ?>
                    </a>
                </div>
                <div class="-mr-2 flex md:hidden">
                    <button
                        @click="open = !open"
                        type="button"
                        class="bg-blue-600 inline-flex items-center justify-center p-2 rounded-md text-white hover:bg-blue-500 focus:outline-none">
                        <span class="sr-only">Open main menu</span>
                        <svg x-show="!open" class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg x-show="open" class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div x-show="open" class="md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-blue-600">
                <a href="/" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Beranda</a>
                <a href="books.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Buku</a>
                <?php if (empty($_SESSION['user_id'])): ?>
                    <a href="../../auth/login.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Login</a>
                    <a href="../../auth/register.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Daftar</a>
                <?php else: ?>
                    <a href="/sistem/public/auth/login.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                    <a href="/sistem/public/auth/register.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-full text-sm font-medium">Daftar</a>
                <?php endif; ?>
                <a href="/contact" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Kontak</a>
            </div>
        </div>
    </nav>

    <!-- Desktop Navigation (Copied from index.php) -->
    <nav class="bg-blue-700 hidden md:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/sistem/public/index.php" class="text-white font-bold text-xl mr-8">
                        <?= htmlspecialchars(SITE_NAME) ?>
                    </a>
                    <div class="flex space-x-4">
                        <a href="/sistem/public/index.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Beranda</a>
                        <a href="/sistem/public/books.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Buku</a>
                        <a href="/sistem/public/contact.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Kontak</a>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <?php if (empty($_SESSION['user_id'])): ?>
                        <a href="/sistem/public/auth/login.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="/sistem/public/auth/register.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-full text-sm font-medium">Daftar</a>
                    <?php else: ?>
                        <a href="/sistem/public/auth/login.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="/sistem/public/auth/register.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-full text-sm font-medium">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <div class="container mx-auto px-4 mb-4 mt-6">
        <a href="/sistem/public/books.php?id=<?= $book_id ?>" class="group flex items-center text-blue-600 hover:text-blue-800 transition-all duration-300 ease-in-out">
            <div class="mr-3 p-2 bg-blue-50 group-hover:bg-blue-100 rounded-full transition-all duration-300 ease-in-out">
                <svg class="w-5 h-5 text-blue-600 group-hover:translate-x-[-4px] transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </div>
            <span class="font-medium group-hover:pl-1 transition-all duration-300 ease-in-out">Kembali ke Daftar Buku</span>
        </a>
    </div>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <section class="book-detail-container rounded-2xl overflow-hidden">
            <div class="grid md:grid-cols-2 gap-8 p-8">
                <!-- Book Cover -->
                <div class="book-cover-container">
                    <img
                        src="<?= !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : '../assets/images/default-book-cover.jpg' ?>"
                        alt="<?= htmlspecialchars($book['title']) ?>"
                        class="book-cover w-full h-[500px] object-cover rounded-xl">
                </div>

                <!-- Book Details -->
                <div class="container mx-auto px-4 py-8">
                    <div class="book-info-container max-w-3xl mx-auto bg-white shadow-md rounded-lg p-6">
                        <!-- Book Title -->
                        <h1 class="text-3xl font-bold text-blue-700 mb-6 pb-3 border-b border-blue-100">
                            <?= htmlspecialchars($book['title']) ?>
                        </h1>

                        <div class="grid md:grid-cols-3 gap-6">
                            <!-- Book Details Column -->
                            <div class="md:col-span-2 space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-user text-blue-500"></i>
                                        <div>
                                            <h4 class="text-xs text-gray-500">Penulis</h4>
                                            <p><?= htmlspecialchars($book['author']) ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-building text-blue-500"></i>
                                        <div>
                                            <h4 class="text-xs text-gray-500">Penerbit</h4>
                                            <p><?= htmlspecialchars($book['publisher']) ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-calendar text-blue-500"></i>
                                        <div>
                                            <h4 class="text-xs text-gray-500">Tahun Terbit</h4>
                                            <p><?= htmlspecialchars($book['year_published']) ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-tags text-blue-500"></i>
                                        <div>
                                            <h4 class="text-xs text-gray-500">Kategori</h4>
                                            <p><?= htmlspecialchars($book['category']) ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mt-4">
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-book text-blue-500"></i>
                                        <div>
                                            <h4 class="text-xs text-gray-500">ISBN</h4>
                                            <p><?= htmlspecialchars($book['isbn']) ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-map-marker-alt text-blue-500"></i>
                                        <div>
                                            <h4 class="text-xs text-gray-500">Lokasi Rak</h4>
                                            <p><?= htmlspecialchars($book['shelf_location']) ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-blue-50 p-3 rounded-lg text-center mt-4">
                                    <p class="text-blue-700 font-semibold">
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                        Tersedia: <?= $book['available_quantity'] ?> buku
                                    </p>
                                </div>
                            </div>

                            <!-- QR Code Column -->
                            <div class="text-center">
                                <h3 class="text-lg font-semibold mb-3">Scan untuk Pinjam Buku</h3>
                                <img src="<?= $qrCodeDataUri ?>"
                                    alt="QR Code Peminjaman Buku"
                                    class="mx-auto w-48 h-48 object-contain rounded-lg shadow-md">
                                <p class="text-xs text-gray-500 mt-2">
                                    Pindai QR Code dengan aplikasi perpustakaan
                                </p>
                            </div>
                        </div>

                        <?php if (!$isLoggedIn): ?>
                            <button
                                x-data="{ showLoginModal: false }"
                                @click="showLoginModal = true"
                                class="w-full bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 transition duration-300 ease-in-out">
                                Pinjam Buku
                            </button>

                            <!-- Login Modal -->
                            <div
                                x-show="showLoginModal"
                                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 scale-90"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-300"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-90">
                                <div class="bg-white p-8 rounded-xl shadow-2xl max-w-md w-full">
                                    <h2 class="text-2xl font-bold text-blue-700 mb-4 text-center">Masuk Terlebih Dahulu</h2>
                                    <p class="text-gray-600 mb-6 text-center">
                                        Anda perlu masuk untuk meminjam buku. Silakan login atau daftar akun baru.
                                    </p>
                                    <div class="flex space-x-4">
                                        <a
                                            href="/sistem/public/auth/login.php"
                                            class="w-full bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 text-center transition duration-300 ease-in-out">
                                            Login
                                        </a>
                                        <a
                                            href="/sistem/public/auth/register.php"
                                            class="w-full bg-green-500 text-white py-3 rounded-lg hover:bg-green-600 text-center transition duration-300 ease-in-out">
                                            Daftar
                                        </a>
                                    </div>
                                    <button
                                        @click="showLoginModal = false"
                                        class="w-full mt-4 text-gray-600 hover:text-gray-900 transition duration-300 ease-in-out">
                                        Batal
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <a
                                href="loan.php?book_id=<?= $book_id ?>"
                                class="w-full bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 text-center inline-block transition duration-300 ease-in-out">
                                Pinjam Buku
                            </a>
                        <?php endif; ?>

                        <div class="mt-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Deskripsi</h3>
                            <p class="text-gray-700"><?= htmlspecialchars($book['description']) ?></p>
                        </div>
                    </div>
                </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-white py-8">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. All Rights Reserved.</p>
            <div class="flex justify-center space-x-4 mt-4">
                <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook"></i></a>
                <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>