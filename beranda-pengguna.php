<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); 

// require_once __DIR__ . '/vendor/autoload.php';

require_once './config/constants.php';
require_once './config/database.php';
require_once './classes/Book.php';

// Validate constants are defined
if (!defined('SITE_NAME')) {
    die('Site configuration error: SITE_NAME is not defined.');
}

try {
    // Database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Page configuration
    $pageTitle = SITE_NAME . " - Selamat Datang";
    $pageDescription = "Sistem Peminjaman Buku Modern dan Efisien";

    // Fetch new books
    $bookManager = new Book($conn);
    $newBooks = $bookManager->getNewBooks(6);
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log("Index Page Error: " . $e->getMessage());
    // Initialize $newBooks to an empty array to avoid undefined variable errors in the HTML section
    $newBooks = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></title>
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<style>
    :root {
        /* Color Palette */
        --primary-color: #3498db;
        --secondary-color: #2ecc71;
        --text-color: #2c3e50;
        --background-color: #f4f7f6;
        --white: #ffffff;
        --shadow-color: rgba(0, 0, 0, 0.1);
    }

    /* Reset and Base Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', 'Arial', sans-serif;
        line-height: 1.6;
        color: var(--text-color);
        background-color: var(--background-color);
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* Container */
    .container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
        flex: 1;
    }

    /* Hero Section */
    .hero {
        background: linear-gradient(135deg, var(--primary-color), #2980b9);
        color: var(--white);
        text-align: center;
        padding: 60px 20px;
        border-radius: 15px;
        margin-top: 30px;
        box-shadow: 0 10px 30px var(--shadow-color);
        position: relative;
        overflow: hidden;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.1);
        z-index: 1;
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    .hero h1 {
        font-size: 2.8rem;
        margin-bottom: 20px;
        font-weight: 700;
    }

    .hero p {
        font-size: 1.3rem;
        margin-bottom: 30px;
        opacity: 0.9;
    }

    /* Buttons */
    .btn {
        display: inline-block;
        padding: 12px 30px;
        text-decoration: none;
        border-radius: 50px;
        transition: all 0.3s ease;
        font-weight: 600;
        letter-spacing: 1px;
    }

    .btn-primary {
        background-color: var(--white);
        color: var(--primary-color);
    }

    .btn-secondary {
        background-color: var(--secondary-color);
        color: var(--white);
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    /* Book Cards */
    .book-card-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 25px;
        justify-content: center;
    }

    .book-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        background-color: var(--white);
        border-radius: 15px;
        box-shadow: 0 8px 20px var(--shadow-color);
        padding: 20px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .book-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
    }

    .book-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
    }

    .book-card img {
        max-width: 180px;
        height: 250px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }

    .book-card:hover img {
        transform: scale(1.05);
    }

    .book-card h3 {
        font-size: 1.3rem;
        margin-bottom: 10px;
        color: var(--text-color);
    }

    .book-card p {
        color: #6c757d;
        margin-bottom: 15px;
    }

    .book-card .btn-small {
        background-color: var(--primary-color);
        color: var(--white);
        padding: 8px 20px;
        border-radius: 30px;
        font-size: 0.9rem;
    }

    /* Navigation */
    .navbar {
        background-color: var(--primary-color);
        color: var(--white);
        padding: 15px 0;
    }

    .navbar-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .navbar-links {
        display: flex;
        gap: 20px;
    }

    .navbar-links a {
        color: var(--white);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .navbar-links a:hover {
        color: var(--secondary-color);
    }

    /* Footer */
    

    /* Responsive Design */
    @media screen and (max-width: 768px) {
        .hero h1 {
            font-size: 2.2rem;
        }

        .hero p {
            font-size: 1.1rem;
        }

        .book-card-container {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .footer-container {
            grid-template-columns: 1fr;
        }

        .navbar-links {
            display: none;
            /* For mobile, you'll use JS/CSS to toggle */
        }
    }

    @media screen and (max-width: 480px) {
        .hero {
            padding: 40px 15px;
        }

        .hero h1 {
            font-size: 1.8rem;
        }

        .book-card-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<body class="bg-gray-50 font-inter min-h-screen flex flex-col">
    <!-- Mobile Navigation -->
    <nav x-data="{ open: false }" class="bg-blue-700 md:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/sistem/beranda-pengguna.php" class="text-white font-bold text-xl">
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
                <a href="/sistem/beranda-pengguna.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Beranda</a>
                <a href="/sistem/public/auth/users/book-loan.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Buku</a>
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

    <!-- Desktop Navigation (Copied from index.php) -->
    <nav class="bg-blue-700 hidden md:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/sistem/beranda-pengguna.php" class="text-white font-bold text-xl mr-8">
                        <?= htmlspecialchars(SITE_NAME) ?>
                    </a>
                    <div class="flex space-x-4">
                        <a href="/sistem/beranda-pengguna.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Beranda</a>
                        <a href="/sistem/public/daftar-buku.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Buku</a>
                        <a href="/sistem/public/kontak.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Kontak</a>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <?php if (empty($_SESSION['user_id'])): ?>
                        <a href="/sistem/public/auth/login.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="/sistem/public/auth/register.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-full text-sm font-medium">Daftar</a>
                    <?php else: ?>
                        <a href="/sistem/public/index.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <a href="/sistem/public/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <!-- Your existing page content goes here -->
        <!-- Hero Section -->
        <section class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-2xl shadow-2xl overflow-hidden mb-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 grid md:grid-cols-2 gap-10 items-center">
                <div class="space-y-6">
                    <h1 class="text-4xl md:text-5xl font-bold leading-tight">
                        Selamat Datang di <?= htmlspecialchars(SITE_NAME) ?>
                    </h1>
                    <p class="text-xl opacity-90">
                        <?= htmlspecialchars($pageDescription) ?>
                    </p>
                    <div class="flex space-x-4">
                        <?php if (empty($_SESSION['user_id'])): ?>
                            <a href="/sistem/public/auth/login.php" class="bg-white text-blue-600 px-6 py-3 rounded-full font-semibold hover:bg-blue-50 transition">
                                Login
                            </a>
                            <a href="/sistem/public/auth/register.php" class="bg-green-500 text-white px-6 py-3 rounded-full font-semibold hover:bg-green-600 transition">
                                Daftar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hidden md:block">
                    <img src="assets/images/Library Illustration1.png" alt="Library Illustration" class="rounded-2xl shadow-lg transform hover:scale-105 transition duration-300">
                </div>
            </div>
        </section>

        <!-- Buku Terbaru Section -->
        <section class="mt-12">
            <h2 class="text-3xl font-bold text-center text-blue-700 mb-10">Buku Terbaru</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <?php if (!empty($newBooks)): ?>
                    <?php foreach ($newBooks as $book): ?>
                        <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-2 transition duration-300 p-4 flex flex-col">
                            <div class="relative mb-4">
                                <img
                                    src="<?= !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : '../assets/images/default-book-cover.jpg' ?>"
                                    alt="<?= htmlspecialchars($book['title']) ?>"
                                    class="w-full h-64 object-cover rounded-xl">
                                <span class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                                    Baru
                                </span>
                            </div>

                            <div class="flex-grow flex flex-col">
                                <h3 class="font-semibold text-lg text-gray-800 mb-1 line-clamp-2 h-12">
                                    <?= htmlspecialchars($book['title']) ?>
                                </h3>
                                <p class="text-gray-600 text-sm mb-1">
                                    <?= htmlspecialchars($book['author']) ?>
                                </p>
                                <div class="text-gray-500 text-xs mb-2 flex justify-between">
                                    <span>Tahun: <?= htmlspecialchars($book['year_published']) ?></span>
                                </div>
                            </div>

                            <a
                                href="#"
                                onclick="checkLoginStatus(<?= $book['id'] ?>); return false;"
                                class="w-full py-2.5 rounded-full text-sm font-semibold text-center bg-blue-500 text-white hover:bg-blue-600 transition duration-300 ease-in-out mt-3">
                                Detail
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center text-gray-600 py-8">
                        Belum ada buku terbaru.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include './includes/footer.php'; ?>


</body>
<script>
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Login status check with loading state
    function checkLoginStatus(bookId) {
        const button = event.currentTarget;
        button.classList.add('loading');

        <?php if (empty($_SESSION['user_id'])): ?>
            setTimeout(() => {
                button.classList.remove('loading');
                window.location.href = '/sistem/public/auth/login.php?redirect=' + encodeURIComponent(window.location.pathname + '?book=' + bookId);
            }, 500);
        <?php else: ?>
            setTimeout(() => {
                button.classList.remove('loading');
                window.location.href = 'detail-buku.php?id=' + bookId;
            }, 500);
        <?php endif; ?>
    }

    // Intersection Observer for lazy loading images
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    });
</script>

</html>