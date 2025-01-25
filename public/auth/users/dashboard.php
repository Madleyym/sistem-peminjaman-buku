<?php
session_start();
require_once('../../../config/constants.php');
require_once '../../../config/database.php';
require_once '../../../classes/User.php';
require_once '../../../classes/Book.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get user details
$users = new User($conn);
$user = $users->getUserById($_SESSION['user_id']); // Corrected line

// Get recent borrowed books
$book = new Book($conn);
$borrowedBooks = $book->getBorrowedBooksByUserId($_SESSION['user_id']); // Corrected line

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars(SITE_NAME) ?></title>
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
        }
    </style>
</head>

<body class="bg-gray-50 font-inter min-h-screen flex flex-col">
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
                <a href="/sistem/public/auth/users/book-loan.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Pinjam Buku</a>
                <?php if (empty($_SESSION['user_id'])): ?>
                    <a href="../../auth/login.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Login</a>
                    <a href="../../auth/register.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Daftar</a>
                <?php else: ?>
                    <a href="/sistem/public/auth/users/profile.ph" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Profil</a>
                    <a href="/auth/logout.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Logout</a>
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
                        <a href="/sistem/public/auth/users/book-loan.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Pinjam Buku</a>
                        <a href="/sistem/public/contact.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Kontak</a>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <?php if (empty($_SESSION['user_id'])): ?>
                        <a href="/sistem/public/auth/login.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="/sistem/public/auth/register.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-full text-sm font-medium">Daftar</a>
                    <?php else: ?>
                        <a href="/sistem/public/auth/users/profile.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Profil Saya</a>
                        <a href="/sistem/public/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow container mx-auto px-4 py-8">
        <section class="bg-white shadow-lg rounded-2xl p-8 mb-12">
            <div class="grid md:grid-cols-3 gap-8">
                <!-- User Profile Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center hover:shadow-xl transform hover:-translate-y-2 transition duration-300">
                    <img
                        src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '../assets/images/default-profile.png' ?>"
                        alt="Profil Pengguna"
                        class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
                    <h2 class="text-2xl font-bold text-blue-700"><?= htmlspecialchars($user['name']) ?></h2>
                    <p class="text-gray-600 mb-4"><?= htmlspecialchars($user['email']) ?></p>
                    <a href="/sistem/public/auth/users/profile.php" class="bg-blue-500 text-white px-4 py-2 rounded-full hover:bg-blue-600 transition">
                        Edit Profil
                    </a>
                </div>

                <!-- Borrowed Books -->
                <div class="bg-white rounded-2xl shadow-lg p-6 md:col-span-2">
                    <h3 class="text-3xl font-bold text-blue-700 text-center mb-8">Buku yang Dipinjam</h3>
                    <?php if (!empty($borrowedBooks)): ?>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            <?php foreach ($borrowedBooks as $book): ?>
                                <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-2 transition duration-300 p-4 text-center">
                                    <img
                                        src="<?= !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : '../assets/images/default-book-cover.jpg' ?>"
                                        alt="<?= htmlspecialchars($book['title']) ?>"
                                        class="w-full h-48 object-cover rounded-xl mb-4">
                                    <h4 class="font-semibold text-lg text-gray-800 mb-2">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </h4>
                                    <p class="text-gray-600 mb-2">
                                        Batas Kembali: <?= htmlspecialchars($book['return_date']) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-gray-600 py-8">
                            Anda belum meminjam buku apapun.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <!-- Footer -->
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
                    <li><a href="/sistem/public/auth/users/book-loan.php" class="text-gray-300 hover:text-white">Pinjam Buku</a></li>
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