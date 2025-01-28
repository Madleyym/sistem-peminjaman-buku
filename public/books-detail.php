<?php
// Di bagian atas file
session_start();

// Define constants
define('LOGIN_PATH', '/sistem/public/auth/login.php');
define('BOOKS_PATH', '/sistem/public/books.php');
define('REGISTER_PATH', '/sistem/public/auth/register.php');
define('CURRENT_URL', $_SERVER['REQUEST_URI']);
define('SITE_NAME', 'Sistem Perpustakaan');

// Check login status dari session
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Validate book ID
$book_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$book_id) {
    header('Location: ' . BOOKS_PATH);
    exit();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Book.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    $bookManager = new Book($conn);
    $book = $bookManager->getBookById($book_id);

    if (!$book) {
        throw new Exception('Book not found');
    }
} catch (Exception $e) {
    header('Location: ' . BOOKS_PATH . '?error=' . urlencode($e->getMessage()));
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom CSS */
        :root {
            --primary-color: #2563eb;
            --secondary-color: #16a34a;
            --accent-color: #dc2626;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
            --bg-primary: #f3f4f6;
            --bg-secondary: #ffffff;
            --transition-speed: 300ms;
            --border-radius: 0.75rem;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-primary);
        }

        .card-hover-effect {
            transition: transform var(--transition-speed);
        }

        .card-hover-effect:hover {
            transform: translateY(-4px);
        }

        .gradient-background {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .custom-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .blur-background {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .text-gradient {
            background: linear-gradient(to right, #2563eb, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <nav x-data="{ open: false }" class="bg-blue-700">
        <!-- Mobile & Desktop Header -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/sistem/public/index.php" class="text-white font-bold text-xl mr-8">
                        <?= htmlspecialchars(SITE_NAME) ?>
                    </a>
                    <!-- Desktop Navigation Links -->
                    <div class="hidden md:flex space-x-4">
                        <a href="/sistem/public/index.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Beranda</a>
                        <a href="/sistem/public/books.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Buku</a>
                        <a href="/sistem/public/contact.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Kontak</a>
                    </div>
                </div>

                <!-- Authentication Links -->
                <div class="hidden md:flex space-x-4">
                    <?php if (!$isLoggedIn): ?>
                        <a href="<?= LOGIN_PATH ?>" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="<?= REGISTER_PATH ?>" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-full text-sm font-medium">Daftar</a>
                    <?php else: ?>
                        <div class="flex items-center space-x-4">
                            <!-- Langsung menggunakan $_SESSION['username'] -->
                            <span class="text-white text-sm"><?= htmlspecialchars($_SESSION['username']) ?></span>
                            <a href="/sistem/public/auth/logout.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Logout</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button
                        @click="open = !open"
                        type="button"
                        class="bg-blue-600 inline-flex items-center justify-center p-2 rounded-md text-white hover:bg-blue-500 focus:outline-none"
                        aria-expanded="false">
                        <span class="sr-only">Toggle menu</span>
                        <svg
                            x-show="!open"
                            class="h-6 w-6"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg
                            x-show="open"
                            class="h-6 w-6"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="open" class="md:hidden bg-blue-600">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/sistem/public/index.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Beranda</a>
                <a href="/sistem/public/books.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Buku</a>
                <a href="/sistem/public/contact.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Kontak</a>
                <?php if (!$isLoggedIn): ?>
                    <a href="<?= LOGIN_PATH ?>" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Login</a>
                    <a href="<?= REGISTER_PATH ?>" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Daftar</a>
                <?php else: ?>
                    <div class="px-3 py-2 text-white">
                        <!-- Langsung menggunakan $_SESSION['username'] -->
                        <span class="block text-sm mb-2"><?= htmlspecialchars($_SESSION['username']) ?></span>
                        <a href="/sistem/public/auth/logout.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Logout</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <?php if (!$isLoggedIn): ?>
            <!-- Login Required Modal -->
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 fade-in blur-background">
                <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md w-full mx-4 card-hover-effect">
                    <div class="text-center mb-8">
                        <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-lock text-blue-500 text-3xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Login Diperlukan</h2>
                        <p class="text-gray-600">
                            Anda harus login terlebih dahulu untuk melihat detail buku ini.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <a href="<?= LOGIN_PATH ?>?redirect=<?= urlencode(CURRENT_URL) ?>"
                            class="block w-full bg-blue-500 text-white py-3 rounded-lg text-center hover:bg-blue-600 
                                  transition-colors duration-200 font-medium">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login
                        </a>
                        <a href="<?= REGISTER_PATH ?>?redirect=<?= urlencode(CURRENT_URL) ?>"
                            class="block w-full bg-green-500 text-white py-3 rounded-lg text-center hover:bg-green-600 
                                  transition-colors duration-200 font-medium">
                            <i class="fas fa-user-plus mr-2"></i> Daftar Akun Baru
                        </a>
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">atau</span>
                            </div>
                        </div>
                        <a href="<?= BOOKS_PATH ?>"
                            class="block w-full text-gray-600 text-center py-2 hover:text-gray-900 transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Buku
                        </a>
                    </div>
                </div>
            </div>

            <!-- Blurred Background Content -->
            <div class="filter blur-sm">
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <h1 class="text-3xl font-bold text-gray-800 mb-6"><?= htmlspecialchars($book['title']) ?></h1>
                    <div class="grid md:grid-cols-2 gap-8">
                        <img src="<?= !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : '../assets/images/default-book-cover.jpg' ?>"
                            alt="Cover Buku"
                            class="w-full h-[500px] object-cover rounded-xl shadow-lg">
                        <div class="space-y-6 opacity-50">
                            <div class="bg-gray-100 p-6 rounded-xl">
                                <p class="text-lg text-center">Detail buku akan tersedia setelah login...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 mt-auto">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4"><?= htmlspecialchars(SITE_NAME) ?></h3>
                    <p class="text-gray-400">Sistem perpustakaan digital untuk memudahkan akses ke pengetahuan.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="/sistem/public/index.php" class="text-gray-400 hover:text-white transition-colors duration-200">Beranda</a></li>
                        <li><a href="/sistem/public/books.php" class="text-gray-400 hover:text-white transition-colors duration-200">Katalog Buku</a></li>
                        <li><a href="/sistem/public/contact.php" class="text-gray-400 hover:text-white transition-colors duration-200">Hubungi Kami</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact Info</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><i class="fas fa-envelope mr-2"></i> perpustakaan@example.com</li>
                        <li><i class="fas fa-phone mr-2"></i> (021) 1234-5678</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i> Jl. Perpustakaan No. 123</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400">&copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
</body>

</html>