<?php
session_start();
require_once('../../../config/constants.php');
require_once '../../../config/database.php';
require_once '../../../classes/User.php';
require_once '../../../classes/Book.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get user details
$users = new User($conn);
$user = $users->getUserById($_SESSION['user_id']);

// Get recent borrowed books with full details
$book = new Book($conn);
$borrowedBooks = $book->getBorrowedBooksByUserId($_SESSION['user_id']);
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #10b981;
            --text-color: #1f2937;
            --background-color: #f3f4f6;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Gradient Navigation -->
    <nav x-data="{ mobileMenu: false }" class="bg-blue-600 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/sistem/public/auth/users/dashboard.php" class="text-white font-bold text-xl flex items-center">
                        <i class="fas fa-book-open mr-2"></i>
                        <?= htmlspecialchars(SITE_NAME) ?>
                    </a>
                </div>

                <!-- Mobile Menu Toggle -->
                <div class="md:hidden">
                    <button
                        @click="mobileMenu = !mobileMenu"
                        class="text-white hover:bg-blue-500 p-2 rounded-full transition duration-300">
                        <i x-show="!mobileMenu" class="fas fa-bars"></i>
                        <i x-show="mobileMenu" class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex space-x-4 items-center">
                    <a href="/sistem/public/auth/users/book-loan.php" class="text-white hover:bg-blue-500 px-4 py-2 rounded-lg transition duration-300 flex items-center">
                        <i class="fas fa-book-reader mr-2"></i>Pinjam Buku
                    </a>
                    <a href="/sistem/public/auth/users/profile.php" class="text-white hover:bg-blue-500 px-4 py-2 rounded-lg transition duration-300 flex items-center">
                        <i class="fas fa-user-circle mr-2"></i>Profil
                    </a>
                    <a href="/sistem/public/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-5 py-2 rounded-full transition duration-300 flex items-center">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>

            <!-- Mobile Menu Dropdown -->
            <div x-show="mobileMenu" class="md:hidden">
                <div class="px-2 pt-2 pb-3 space-y-1 bg-gradient-to-r from-blue-600 to-indigo-700">
                    <a href="/sistem/public/auth/users/book-loan.php" class="text-white block px-3 py-2 rounded-md hover:bg-blue-500 transition duration-300">
                        <i class="fas fa-book-reader mr-2"></i>Pinjam Buku
                    </a>
                    <a href="/sistem/public/auth/users/profile.php" class="text-white block px-3 py-2 rounded-md hover:bg-blue-500 transition duration-300">
                        <i class="fas fa-user-circle mr-2"></i>Profil
                    </a>
                    <a href="/sistem/public/auth/logout.php" class="text-white block px-3 py-2 rounded-md hover:bg-blue-500 transition duration-300">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Dashboard Content -->
    <main class="container mx-auto px-4 py-8 flex-grow">
        <div class="grid md:grid-cols-3 gap-8">
            <!-- User Profile Card with Enhanced Design -->
            <div class="bg-white rounded-2xl shadow-2xl p-6 text-center transform transition hover:scale-105 hover:shadow-3xl">
                <div class="relative inline-block mb-6">
                    <div class="p-1 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600">
                        <img
                            src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '../assets/images/default-profile.png' ?>"
                            alt="Profil Pengguna"
                            class="w-36 h-36 rounded-full object-cover border-4 border-white">
                    </div>
                    <span class="absolute bottom-1 right-1 bg-green-500 text-white p-1.5 rounded-full text-xs">
                        <i class="fas fa-check"></i>
                    </span>
                </div>
                <h2 class="text-3xl font-bold text-indigo-700 mb-2"><?= htmlspecialchars($user['name']) ?></h2>
                <p class="text-gray-600 mb-6"><?= htmlspecialchars($user['email']) ?></p>
                <div class="flex justify-center">
                    <a href="/sistem/public/auth/users/profile.php" class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-6 py-3 rounded-full hover:from-blue-600 hover:to-indigo-700 transition duration-300 flex items-center">
                        <i class="fas fa-edit mr-2"></i>Edit Profil
                    </a>
                </div>
            </div>

            <!-- Borrowed Books Section with Grid Layout and Card Effects -->
            <div class="md:col-span-2 bg-white rounded-2xl shadow-2xl p-8">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-3xl font-bold text-indigo-700 flex items-center">
                        <i class="fas fa-book mr-4 text-yellow-500"></i>Buku yang Dipinjam
                    </h3>
                    <span class="bg-indigo-100 text-indigo-800 px-4 py-2 rounded-full text-sm font-semibold">
                        <?= count($borrowedBooks) ?> Buku
                    </span>
                </div>
                <?php if (!empty($borrowedBooks)): ?>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <?php foreach ($borrowedBooks as $book): ?>
                            <div class="bg-gray-100 rounded-xl p-4 text-center transform transition hover:scale-105 hover:shadow-2xl relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-indigo-600"></div>
                                <img
                                    src="<?= !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : '../assets/images/default-book-cover.jpg' ?>"
                                    alt="<?= htmlspecialchars($book['title']) ?>"
                                    class="w-full h-48 object-cover rounded-lg mb-4 shadow-md">
                                <h4 class="font-bold text-gray-800 mb-2 truncate text-lg">
                                    <?= htmlspecialchars($book['title']) ?>
                                </h4>
                                <div class="space-y-2">
                                    <p class="text-sm text-gray-600 flex items-center justify-center">
                                        <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                        Dipinjam: <?= htmlspecialchars($book['loan_date']) ?>
                                    </p>
                                    <p class="text-sm text-gray-600 flex items-center justify-center">
                                        <i class="fas fa-calendar-check mr-2 text-green-500"></i>
                                        Harus Kembali: <?= htmlspecialchars($book['due_date']) ?>
                                    </p>
                                    <p class="text-sm text-gray-600 flex items-center justify-center">
                                        <i class="fas fa-hourglass-half mr-2 text-yellow-500"></i>
                                        Status: <?= htmlspecialchars($book['status']) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-book-open text-6xl mb-4 text-gray-300"></i>
                        <p>Anda belum meminjam buku apapun.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modern Gradient Footer -->
    <footer class="bg-gradient-to-r from-gray-900 to-black text-white py-10">
        <div class="container mx-auto px-4 text-center">
            <p class="mb-4">&copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. All Rights Reserved.</p>
            <div class="flex justify-center space-x-6">
                <a href="#" class="text-gray-400 hover:text-white transition duration-300"><i class="fab fa-facebook text-2xl"></i></a>
                <a href="#" class="text-gray-400 hover:text-white transition duration-300"><i class="fab fa-twitter text-2xl"></i></a>
                <a href="#" class="text-gray-400 hover:text-white transition duration-300"><i class="fab fa-instagram text-2xl"></i></a>
            </div>
        </div>
    </footer>
</body>

</html>