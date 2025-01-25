<?php
session_start();

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once('../../../config/constants.php');
require_once '../../../config/database.php';
require_once '../../../classes/User.php';
require_once '../../../classes/Book.php';
require_once '../../../classes/Loan.php';  // Add this line

$database = new Database();
$conn = $database->getConnection();
$bookManager = new Book($conn);
$userManager = new User($conn);
$loanManager = new Loan($conn);  // Add this line

// Handle book loan request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_id'])) {
    $book_id = $_POST['book_id'];
    $user_id = $_SESSION['user_id'];

    try {
        $result = $loanManager->createLoan($user_id, $book_id);
        $message = $result['status'] ? "Buku berhasil dipinjam!" : $result['message'];
        $messageType = $result['status'] ? "success" : "error";
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
    }
}

$keyword = $_GET['search'] ?? null;
$filters = [
    'category' => $_GET['category'] ?? null,
    'min_year' => $_GET['min_year'] ?? null,
    'max_year' => $_GET['max_year'] ?? null,
    'language' => $_GET['language'] ?? null
];

$books = $keyword ?
    $bookManager->searchBooks($keyword, $filters) :
    $bookManager->searchBooks('', $filters);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan - Pinjam Buku</title>
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

    /* Navigation Styles */
    .nav-container {
        background-color: var(--primary-color);
        color: var(--white);
    }

    .nav-link {
        color: var(--white);
        transition: background-color 0.3s ease;
    }

    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    /* Main Content Styles */
    .books-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px;
    }

    .search-form {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px var(--shadow-color);
    }

    .search-input {
        border: 2px solid var(--primary-color);
        border-radius: var(--border-radius);
        transition: all 0.3s ease;
    }

    .search-input:focus {
        outline: none;
        border-color: var(--secondary-color);
        box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
    }

    .search-button {
        background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        color: var(--white);
        border-radius: var(--border-radius);
        transition: transform 0.3s ease;
    }

    .search-button:hover {
        transform: translateY(-3px);
    }

    /* Book Card Styles */
    .book-card {
        background: var(--white);
        border-radius: 15px;
        box-shadow: 0 10px 30px var(--shadow-color);
        transition: all 0.4s ease;
        position: relative;
        overflow: hidden;
    }

    .book-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
    }

    .book-card-title {
        color: var(--primary-color);
        font-size: 1.3rem;
    }

    .book-card-btn {
        background: var(--primary-color);
        color: var(--white);
        border-radius: var(--border-radius);
        transition: all 0.3s ease;
    }

    .book-card-btn:hover {
        background: var(--secondary-color);
        transform: translateY(-3px);
    }

    /* Footer Styles */
    .site-footer {
        background-color: var(--text-color);
        color: var(--white);
        padding: 40px 0;
    }

    .footer-link {
        color: var(--white);
        opacity: 0.7;
        transition: opacity 0.3s ease;
    }

    .footer-link:hover {
        opacity: 1;
    }

    /* Responsive Adjustments */
    @media screen and (max-width: 768px) {
        .book-grid {
            grid-template-columns: 1fr 1fr;
        }

        .search-form {
            flex-direction: column;
        }
    }
</style>

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
                        <a href="/sistem/public/dashboard.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <a href="/sistem/public/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <?php if (isset($message)): ?>
            <div class="mb-6 p-4 rounded-lg <?= $messageType == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <section class="bg-white shadow-lg rounded-2xl p-8 mb-12">
            <h1 class="text-4xl font-bold text-center text-blue-700 mb-8">Pinjam Buku Perpustakaan</h1>

            <form action="book-loan.php" method="GET" class="mb-12">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <input
                        type="text"
                        name="search"
                        placeholder="Cari buku..."
                        value="<?= htmlspecialchars($keyword ?? '') ?>"
                        class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <select
                        name="category"
                        class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Kategori</option>
                        <option value="novel">Novel</option>
                        <option value="non-fiksi">Non-Fiksi</option>
                        <option value="pendidikan">Pendidikan</option>
                        <option value="biografi">Biografi</option>
                    </select>
                    <input
                        type="number"
                        name="min_year"
                        placeholder="Tahun Min"
                        value="<?= htmlspecialchars($filters['min_year'] ?? '') ?>"
                        class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input
                        type="number"
                        name="max_year"
                        placeholder="Tahun Maks"
                        value="<?= htmlspecialchars($filters['max_year'] ?? '') ?>"
                        class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button
                        type="submit"
                        class="w-full bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 transition duration-300 ease-in-out">
                        Cari Buku
                    </button>
                </div>
            </form>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <?php foreach ($books as $book): ?>
                    <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-2 transition duration-300 p-4 flex flex-col">
                        <div class="relative mb-4">
                            <img
                                src="<?= !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : '../assets/images/default-book-cover.jpg' ?>"
                                alt="<?= htmlspecialchars($book['title']) ?>"
                                class="w-full h-64 object-cover rounded-xl">
                            <?php if ($book['available_quantity'] <= 3 && $book['available_quantity'] > 0): ?>
                                <span class="absolute top-2 right-2 bg-yellow-500 text-white text-xs px-2 py-1 rounded-full">
                                    Tersisa <?= $book['available_quantity'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow flex flex-col">
                            <h3 class="font-semibold text-lg text-gray-800 mb-1 line-clamp-2">
                                <?= htmlspecialchars($book['title']) ?>
                            </h3>
                            <p class="text-gray-600 mb-1 text-sm">
                                <?= htmlspecialchars($book['author']) ?>
                            </p>
                            <p class="text-gray-500 text-xs mb-2">
                                Tahun Terbit: <?= htmlspecialchars($book['year_published']) ?>
                            </p>
                            <p class="text-gray-500 text-xs mb-3">
                                Kategori: <?= htmlspecialchars($book['category']) ?>
                            </p>
                            <div class="mt-auto">
                                <div class="text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                    </svg>
                                    Tersedia: <?= $book['available_quantity'] ?>
                                </div>
                                <form action="book-loan.php" method="POST">
                                    <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                    <button
                                        type="submit"
                                        <?= $book['available_quantity'] <= 0 ? 'disabled' : '' ?>
                                        class="w-full bg-blue-500 text-white px-4 py-2.5 rounded-full hover:bg-blue-600 transition duration-300 ease-in-out flex items-center justify-center
                                <?= $book['available_quantity'] <= 0 ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                        <?php if ($book['available_quantity'] > 0): ?>
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            Pinjam Buku
                                        <?php else: ?>
                                            Tidak Tersedia
                                        <?php endif; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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