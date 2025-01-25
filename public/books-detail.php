<?php
session_start(); // Start session for authentication check

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../classes/Book.php';

// Check if user is logged in
$isLoggedIn = !empty($_SESSION['user_id']);

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<style>
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
        font-family: 'Inter', 'Arial', sans-serif;
        line-height: 1.6;
        color: var(--text-color);
        background-color: var(--background-color);
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* Navigation styles from books.php remain the same */
    /* Footer styles from books.php remain the same */

    /* Specific book detail styles */
    .book-detail-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px;
    }

    .book-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 30px;
        box-shadow: 0 10px 30px var(--shadow-color);
    }

    .book-cover img {
        width: 100%;
        border-radius: var(--border-radius);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    .book-info h1 {
        color: var(--primary-color);
        margin-bottom: 20px;
    }

    .book-meta p {
        margin-bottom: 10px;
        color: var(--text-color);
        opacity: 0.8;
    }

    .loan-button {
        display: inline-block;
        width: 100%;
        padding: 15px;
        background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        color: var(--white);
        border: none;
        border-radius: var(--border-radius);
        text-align: center;
        transition: all 0.3s ease;
    }

    .loan-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px var(--shadow-color);
    }

    @media screen and (max-width: 768px) {
        .book-detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<body class="bg-gray-50 font-inter min-h-screen flex flex-col">
    <!-- Mobile Navigation (same as books.php) -->
    <nav x-data="{ open: false }" class="bg-blue-700 md:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-white font-bold text-xl">
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

        <!-- Mobile Menu -->
        <div x-show="open" class="md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-blue-600">
                <a href="/" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Beranda</a>
                <a href="books.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Buku</a>
                <?php if (empty($_SESSION['user_id'])): ?>
                    <a href="../../auth/login.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Login</a>
                    <a href="../../auth/register.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Daftar</a>
                <?php else: ?>
                    <a href="/index.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Dashboard</a>
                    <a href="/auth/logout.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Logout</a>
                <?php endif; ?>
                <a href="/contact" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Kontak</a>
            </div>
        </div>
    </nav>

    <!-- Desktop Navigation (same as books.php) -->
    <nav class="bg-blue-700 hidden md:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-white font-bold text-xl mr-8">
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
                        <a href="/sistem/public/dashboard.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <a href="/sistem/public/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <section class="bg-white shadow-lg rounded-2xl p-8 mb-12">
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Book Cover -->
                <div>
                    <img
                        src="<?= !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : '../assets/images/default-book-cover.jpg' ?>"
                        alt="<?= htmlspecialchars($book['title']) ?>"
                        class="w-full h-96 object-cover rounded-xl shadow-lg">
                </div>

                <!-- Book Details -->
                <div>
                    <h1 class="text-3xl font-bold text-blue-700 mb-4"><?= htmlspecialchars($book['title']) ?></h1>

                    <div class="mb-6">
                        <p class="text-gray-700 mb-2"><strong>Penulis:</strong> <?= htmlspecialchars($book['author']) ?></p>
                        <p class="text-gray-700 mb-2"><strong>Penerbit:</strong> <?= htmlspecialchars($book['publisher']) ?></p>
                        <p class="text-gray-700 mb-2"><strong>Tahun Terbit:</strong> <?= htmlspecialchars($book['year_published']) ?></p>
                        <p class="text-gray-700 mb-2"><strong>Kategori:</strong> <?= htmlspecialchars($book['category']) ?></p>
                        <p class="text-gray-700 mb-2"><strong>ISBN:</strong> <?= htmlspecialchars($book['isbn']) ?></p>
                        <p class="text-gray-700 mb-2"><strong>Lokasi Rak:</strong> <?= htmlspecialchars($book['shelf_location']) ?></p>
                    </div>

                    <div class="mb-6">
                        <p class="text-gray-700 font-semibold">
                            <strong>Tersedia:</strong> <?= $book['available_quantity'] ?> buku
                        </p>
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

    <!-- Footer (same as books.php) -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4 grid md:grid-cols-3 gap-8">
            <div>
                <h4 class="text-xl font-bold mb-4"><?= htmlspecialchars(SITE_NAME) ?></h4>
                <p class="text-gray-400">Platform peminjaman buku digital modern dan efisien</p>
                <div class="flex space-x-4 mt-4">
                    <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div>
                <h4 class="text-xl font-bold mb-4">Tautan Cepat</h4>
                <ul class="space-y-2">
                    <li><a href="/" class="text-gray-300 hover:text-white">Beranda</a></li>
                    <li><a href="/books" class="text-gray-300 hover:text-white">Buku</a></li>
                    <li><a href="/contact" class="text-gray-300 hover:text-white">Kontak</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-xl font-bold mb-4">Hubungi Kami</h4>
                <p class="text-gray-400 mb-2">Email: support@perpustakaan.com</p>