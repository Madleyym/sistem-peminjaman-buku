<?php
session_start(); // Add this line before the authentication check

// Add authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /sistem/admin/auth/login.php");
    exit();
}

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../classes/Book.php';

$database = new Database();
$conn = $database->getConnection();
$bookManager = new Book($conn);

// Fetch some statistics
$totalBooks = $bookManager->countTotalBooks();
$lowStockBooks = $bookManager->countLowStockBooks();
$recentlyAddedBooks = $bookManager->getRecentlyAddedBooks(5);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
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
    </style>
</head>
<!-- Mobile Navigation -->
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
                <a href="/sistem/admin/auth/logout.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Logout</a>
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
                    <a href="/sistem/admin/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<body class="bg-gray-50 font-inter min-h-screen flex flex-col">
    <!-- Mobile Navigation (Identical to books.php) -->
    <nav x-data="{ open: false }" class="bg-blue-700 md:hidden">
        <!-- [Mobile navigation code from books.php remains the same] -->
    </nav>

    <!-- Desktop Navigation (Identical to books.php) -->
    <nav class="bg-blue-700 hidden md:block">
        <!-- [Desktop navigation code from books.php remains the same] -->
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Dashboard Cards -->
            <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col">
                <h3 class="text-xl font-bold text-blue-700 mb-4">Total Buku</h3>
                <div class="text-4xl font-extrabold text-gray-800"><?= $totalBooks ?></div>
                <a href="/sistem/admin/auth/mana" class="mt-4 text-blue-500 hover:text-blue-700">Kelola Buku</a>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col">
                <h3 class="text-xl font-bold text-yellow-600 mb-4">Buku Stok Rendah</h3>
                <div class="text-4xl font-extrabold text-gray-800"><?= $lowStockBooks ?></div>
                <p class="mt-2 text-sm text-yellow-600">Perlu Segera Ditambah</p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col">
                <h3 class="text-xl font-bold text-green-600 mb-4">Buku Terbaru</h3>
                <ul class="space-y-2">
                    <?php foreach ($recentlyAddedBooks as $book): ?>
                        <li class="flex justify-between items-center">
                            <span class="truncate"><?= htmlspecialchars($book['title']) ?></span>
                            <span class="text-xs text-gray-500"><?= htmlspecialchars($book['year_published']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <section class="bg-white shadow-lg rounded-2xl p-8">
            <h2 class="text-3xl font-bold text-blue-700 mb-6">Menu Admin</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <a href="/sistem/admin/manage-books/dashboard-book.php" class="bg-blue-100 hover:bg-blue-200 rounded-xl p-6 text-center transition duration-300">
                    <svg class="w-12 h-12 mx-auto mb-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span class="font-semibold text-blue-800">Manajemen Buku</span>
                </a>

                <a href="/sistem/admin/manage-users/dashboard-user.php" class="bg-green-100 hover:bg-green-200 rounded-xl p-6 text-center transition duration-300">
                    <svg class="w-12 h-12 mx-auto mb-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="font-semibold text-green-800">Manajemen Pengguna</span>
                </a>

                <a href="/sistem/admin/manage-borrow/dashboard-borrow.php" class="bg-yellow-100 hover:bg-yellow-200 rounded-xl p-6 text-center transition duration-300">
                    <svg class="w-12 h-12 mx-auto mb-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-semibold text-yellow-800">Peminjaman</span>
                </a>

                <a href="/sistem/admin/reports/dashboard-reports.php" class="bg-purple-100 hover:bg-purple-200 rounded-xl p-6 text-center transition duration-300">
                    <svg class="w-12 h-12 mx-auto mb-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h8l4 4v10a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="font-semibold text-purple-800">Laporan</span>
                </a>
            </div>
        </section>
    </main>

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
                <p class="text-gray-400 mb-2">Telepon: +62 888 1234 5678</p>
                <p class="text-gray-400">Alamat: Jl. Perpustakaan No. 123, Kota</p>
            </div>
        </div>
        <div class="text-center text-gray-500 mt-8 pt-4 border-t border-gray-700">
            &copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. Hak Cipta Dilindungi.
        </div>
    </footer>
</body>

</html>