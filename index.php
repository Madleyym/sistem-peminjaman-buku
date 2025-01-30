<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Book.php';

// Validate constants are defined
if (!defined('SITE_NAME')) {
    die('Site configuration error: SITE_NAME is not defined.');
}

// Pastikan direktori upload ada
$uploadDir = __DIR__ . "/uploads/book_covers/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Default book cover path
$defaultBookCover = '/sistem/uploads/books/book-default.png';

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
    <!-- Navigation -->
    <?php include './includes/header.php'; ?>

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
                                <img src="<?= !empty($book['cover_image'])
                                                ? '/sistem/uploads/book_covers/' . htmlspecialchars($book['cover_image'])
                                                : $defaultBookCover ?>"
                                    alt="<?= htmlspecialchars($book['title']) ?>"
                                    class="w-full h-64 object-cover rounded-xl"
                                    onerror="this.src='<?= $defaultBookCover ?>'">
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

                            <a href="#"
                                onclick="checkLoginStatus(<?= $book['id'] ?>); return false;"
                                class="w-full py-2.5 rounded-full text-sm font-semibold text-center 
                          bg-blue-500 text-white hover:bg-blue-600 
                          transition duration-300 ease-in-out mt-3">
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
    <?php include './includes/footer.php'; ?>
</body>
<script>
    // Default image path
    const DEFAULT_BOOK_COVER = '/sistem/uploads/books/book-default.png';

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Enhanced Login status check with loading state
    function checkLoginStatus(bookId) {
        const button = event.currentTarget;

        // Add loading state with spinner
        button.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading...
        `;
        button.disabled = true;
        button.classList.add('opacity-75', 'cursor-not-allowed');

        <?php if (empty($_SESSION['user_id'])): ?>
            setTimeout(() => {
                window.location.href = '/sistem/public/auth/login.php?redirect=' +
                    encodeURIComponent(window.location.pathname + '?book=' + bookId);
            }, 500);
        <?php else: ?>
            setTimeout(() => {
                window.location.href = '/sistem/public/detail-buku.php?id=' + bookId;
            }, 500);
        <?php endif; ?>
    }

    // Enhanced lazy loading for images with fallback
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.onerror = function() {
                        this.src = DEFAULT_BOOK_COVER;
                        this.onerror = null;
                    }
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    });

    // Handle image errors globally
    document.addEventListener('error', function(e) {
        if (e.target.tagName.toLowerCase() === 'img') {
            e.target.src = DEFAULT_BOOK_COVER;
        }
    }, true);
</script>

</html>