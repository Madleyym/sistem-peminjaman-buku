<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // Start session for authentication check

require_once($_SERVER['DOCUMENT_ROOT'] . '/sistem/config/constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sistem/config/database.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sistem/classes/book.php');

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
    <!-- Mobile Navigation -->
    <nav x-data="{ mobileMenu: false }" class="bg-blue-600 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/sistem/public/auth/users/home.php" class="text-white font-bold text-xl flex items-center">
                        <i class="fas fa-book-open mr-2"></i>
                        <?= htmlspecialchars(SITE_NAME) ?>
                    </a>
                </div>

                <!-- Mobile Menu Toggle -->
                <div class="md:hidden">
                    <button
                        @click="mobileMenu = !mobileMenu"
                        class="text-white hover:bg-blue-500 p-2 rounded-md">
                        <i x-show="!mobileMenu" class="fas fa-bars"></i>
                        <i x-show="mobileMenu" class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex space-x-4 items-center">
                    <a href="/sistem/public/auth/users/book-loan.php" class="text-white hover:bg-blue-500 px-3 py-2 rounded-md">
                        <i class="fas fa-book-reader mr-2"></i>Pinjam Buku
                    </a>
                    <a href="/sistem/public/auth/users/profile.php" class="text-white hover:bg-blue-500 px-3 py-2 rounded-md">
                        <i class="fas fa-user-circle mr-2"></i>
                    </a>
                    <a href="/sistem/public/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>

            <!-- Mobile Menu Dropdown -->
            <div x-show="mobileMenu" class="md:hidden">
                <div class="px-2 pt-2 pb-3 space-y-1 bg-blue-600">
                    <a href="/sistem/public/auth/users/book-loan.php" class="text-white block px-3 py-2 rounded-md hover:bg-blue-500">
                        <i class="fas fa-book-reader mr-2"></i>Pinjam Buku
                    </a>
                    <a href="/sistem/public/auth/users/profile.php" class="text-white block px-3 py-2 rounded-md hover:bg-blue-500">
                        <i class="fas fa-user-circle mr-2"></i>Profil
                    </a>
                    <a href="/sistem/public/auth/logout.php" class="text-white block px-3 py-2 rounded-md hover:bg-blue-500">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Back to Books List -->
    <div class="container mx-auto px-4 mb-4 mt-6">
        <a href="/sistem/public/auth/users/pinjaman-buku.php?id=<?= $book_id ?>" class="group flex items-center text-blue-600 hover:text-blue-800 transition-all duration-300 ease-in-out">
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

                        <div class="space-y-4">
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

                        <!-- Login Required Section -->
                        <?php if (!$isLoggedIn): ?>
                            <div class="mt-6 bg-gray-100 rounded-lg p-6 text-center">
                                <i class="fas fa-lock text-4xl text-red-500 mb-3
                                <h2 class="text-xl font-bold mb-3">Login Required</h2>
                                <p class="text-gray-600 mb-4">
                                    Silakan login terlebih dahulu untuk melihat detail buku.
                                </p>
                                <a href="/sistem/public/auth/login.php" class="bg-blue-500 text-white px-5 py-2 rounded-lg hover:bg-blue-600 transition">
                                    Login Sekarang
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Description Section -->
                <div class="p-8">
                    <div class="description-section">
                        <h3 class="text-2xl font-semibold text-blue-700 mb-">Deskripsi Buku</h3>
                        <p class="text-gray-700 leading-relaxed">
                            <?= htmlspecialchars($book['description']) ?>
                        </p>
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