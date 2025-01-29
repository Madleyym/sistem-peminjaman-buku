<?php

declare(strict_types=1);
session_start();

// Security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Required files
require_once(__DIR__ . '/../../../config/constants.php');
require_once(__DIR__ . '/../../../config/database.php');
require_once(__DIR__ . '/../../../classes/User.php');
require_once(__DIR__ . '/../../../classes/Book.php');
// require_once(__DIR__ . '/../../../vendor/autoload.php');

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Initialize variables
$error_message = '';
$user = null;
$borrowedBooks = [];

// Database connection and data fetching
try {
    $database = new Database();
    $conn = $database->getConnection();

    $users = new User($conn);
    $user = $users->getUserById($_SESSION['user_id']);

    $book = new Book($conn);
    $borrowedBooks = $book->getBorrowedBooksByUserId($_SESSION['user_id']);
} catch (Exception $e) {
    error_log($e->getMessage());
    $error_message = 'Terjadi kesalahan. Silakan coba lagi nanti.';
}

// Get current UTC time
$utcTime = gmdate('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars(SITE_NAME) ?></title>

    <!-- Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="bg-gradient-to-r from-blue-600 to-indigo-700 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="home.php" class="flex items-center">
                        <i class="fas fa-book-open text-white text-2xl mr-2"></i>
                        <span class="text-white font-bold text-xl"><?= htmlspecialchars(SITE_NAME) ?></span>
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="/sistem/public/auth/users/pinjaman-buku.php" class="text-white hover:bg-blue-500 px-4 py-2 rounded-lg transition">
                        <i class="fas fa-book-reader mr-2"></i>Pinjam Buku
                    </a>
                    <a href="profile.php" class="text-white hover:bg-blue-500 px-4 py-2 rounded-lg transition">
                        <i class="fas fa-user-circle mr-2"></i>Profil
                    </a>
                    <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white px-5 py-2 rounded-full transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>  

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button @click="mobileMenu = !mobileMenu" class="text-white hover:bg-blue-500 p-2 rounded-lg">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8" role="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Grid -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            <div class="md:col-span-4 h-full"> <!-- Tambahkan h-full -->
                <div class="bg-white rounded-2xl shadow-xl p-6 h-full"> <!-- Tambahkan h-full dan hilangkan sticky -->
                    <div class="flex justify-center mb-6">
                        <div class="p-1 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600">
                            <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '../../assets/images/default-profile.png' ?>"
                                alt="Profile picture"
                                class="w-32 h-32 rounded-full object-cover border-4 border-white">
                        </div>
                    </div>

                    <!-- User Info -->
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($user['name']) ?></h2>
                        <p class="text-gray-600 mt-1"><?= htmlspecialchars($user['email']) ?></p>
                    </div>

                    <!-- Login & Time Info -->
                    <div class="bg-gray-50 rounded-xl p-4 mb-6">
                        <!-- User Login Info -->
                        <div class="flex items-center space-x-3 text-gray-700 mb-4">
                            <i class="fas fa-user-circle text-blue-500 text-xl w-8"></i>
                            <div class="flex-1">
                                <div class="text-sm text-gray-500">Current User's Login</div>
                                <div class="font-semibold"><?= htmlspecialchars($user['name']) ?></div>
                            </div>
                        </div>

                        <!-- UTC Time -->
                        <div class="flex items-center space-x-3 text-gray-700">
                            <i class="fas fa-clock text-blue-500 text-xl w-8"></i>
                            <div class="flex-1">
                                <div class="text-sm text-gray-500">Current Date and Time (UTC)</div>
                                <div class="font-mono text-sm" x-text="currentTime">
                                    <?= date('Y-m-d H:i:s', strtotime('UTC')) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Details -->
                    <div class="bg-gray-50 rounded-xl p-4 mb-6">
                        <div class="space-y-4">
                            <?php if (!empty($user['phone_number'])): ?>
                                <div class="flex items-center space-x-3 text-gray-700">
                                    <i class="fas fa-phone text-blue-500 text-xl w-8"></i>
                                    <span class="text-sm flex-1"><?= htmlspecialchars($user['phone_number']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($user['address'])): ?>
                                <div class="flex items-center space-x-3 text-gray-700">
                                    <i class="fas fa-map-marker-alt text-blue-500 text-xl w-8"></i>
                                    <span class="text-sm flex-1"><?= htmlspecialchars($user['address']) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center space-x-3 text-gray-700">
                                <i class="fas fa-circle text-blue-500 text-xl w-8"></i>
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?= $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= htmlspecialchars(ucfirst($user['status'])) ?>
                                </span>
                            </div>

                            <div class="flex items-center space-x-3 text-gray-700">
                                <i class="fas fa-calendar text-blue-500 text-xl w-8"></i>
                                <div class="flex-1">
                                    <div class="text-xs text-gray-500">Member since</div>
                                    <span class="text-sm">
                                        <?= date('d M Y', strtotime($user['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="flex justify-center">
                        <a href="profile.php"
                            class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-6 py-3 rounded-full hover:from-blue-600 hover:to-indigo-700 transition flex items-center">
                            <i class="fas fa-edit mr-2"></i>Edit Profil
                        </a>
                    </div>
                </div>
            </div>

            <!-- Borrowed Books Section - 8 columns di desktop -->
            <div class="md:col-span-8 h-full"> <!-- Tambahkan h-full -->
                <div class="bg-white rounded-2xl shadow-xl p-8 h-full" ->
                    <div class="flex justify-between items-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-book text-blue-500 mr-3"></i>
                            Buku yang Dipinjam
                        </h3>
                        <span class="bg-blue-100 text-blue-800 px-4 py-2 rounded-full text-sbg-white rounded-2xl shadow-xl p-6 sticky top-8m font-semibold">
                            <?= count($borrowedBooks) ?> Buku
                        </span>
                    </div>

                    <?php if (!empty($borrowedBooks)): ?>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <?php foreach ($borrowedBooks as $book): ?>
                                <div class="bg-gray-50 rounded-xl p-4 transform transition duration-300 hover:scale-[1.02] hover:shadow-lg">
                                    <div class="flex space-x-4">
                                        <img src="<?= !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : '../../assets/images/default-book-cover.jpg' ?>"
                                            alt="<?= htmlspecialchars($book['title']) ?>"
                                            class="w-24 h-32 object-cover rounded-lg shadow-md">
                                        <div class="flex-1 min-w-0"> <!-- prevent text overflow -->
                                            <h4 class="font-bold text-gray-800 mb-2 truncate">
                                                <?= htmlspecialchars($book['title']) ?>
                                            </h4>
                                            <div class="space-y-2 text-sm">
                                                <p class="text-gray-600 flex items-center">
                                                    <i class="fas fa-calendar-alt text-blue-500 mr-2 w-4"></i>
                                                    <span class="truncate"><?= htmlspecialchars($book['loan_date']) ?></span>
                                                </p>
                                                <p class="text-gray-600 flex items-center">
                                                    <i class="fas fa-calendar-check text-green-500 mr-2 w-4"></i>
                                                    <span class="truncate"><?= htmlspecialchars($book['due_date']) ?></span>
                                                </p>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                                <?php
                                                $status = strtolower($book['status']);
                                                if ($status === 'active') echo 'bg-green-100 text-green-800';
                                                elseif ($status === 'overdue') echo 'bg-red-100 text-red-800';
                                                else echo 'bg-yellow-100 text-yellow-800';
                                                ?>">
                                                    <?= htmlspecialchars($book['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-book-open text-gray-300 text-6xl mb-4"></i>
                            <p class="text-gray-500 mb-4">Anda belum meminjam buku apapun.</p>
                            <a href="pinjaman-buku.ph"
                                class="inline-flex items-center px-6 py-3 bg-blue-500 text-white rounded-full hover:bg-blue-600 transition">
                                <i class="fas fa-plus mr-2"></i>
                                Pinjam Buku Sekarang
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-gray-900 to-black text-white py-8 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p class="mb-4">&copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. All Rights Reserved.</p>
            <div class="flex justify-center space-x-6">
                <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-facebook text-xl"></i></a>
                <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-twitter text-xl"></i></a>
                <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-instagram text-xl"></i></a>
            </div>
        </div>
    </footer>

    <!-- Alpine.js Scripts -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('home', () => ({
                books: [],
                filteredBooks: [],
                searchQuery: '',
                sortBy: 'title',
                currentPage: 1,
                itemsPerPage: 9,
                selectedBook: null,
                darkMode: false,
                currentTime: '',
                mobileMenu: false,

                init() {
                    this.filteredBooks = this.books;
                    this.darkMode = localStorage.getItem('darkMode') === 'true';
                    this.watchDarkMode();
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);
                },

                updateTime() {
                    const now = new Date();
                    this.currentTime = now.toISOString().replace('T', ' ').substr(0, 19);
                },

                watchDarkMode() {
                    this.$watch('darkMode', val => {
                        document.documentElement.classList.toggle('dark', val);
                        localStorage.setItem('darkMode', val);
                    });
                },

                filterBooks() {
                    const query = this.searchQuery.toLowerCase();
                    this.filteredBooks = this.books.filter(book =>
                        book.title.toLowerCase().includes(query) ||
                        book.author.toLowerCase().includes(query)
                    );
                    this.currentPage = 1;
                },

                sortBooks() {
                    this.filteredBooks.sort((a, b) => {
                        if (this.sortBy === 'title') {
                            return a.title.localeCompare(b.title);
                        } else if (this.sortBy === 'due_date') {
                            return new Date(a.due_date) - new Date(b.due_date);
                        } else {
                            return a.status.localeCompare(b.status);
                        }
                    });
                },

                formatDate(dateString) {
                    return new Date(dateString).toLocaleDateString('id-ID', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                },

                get totalPages() {
                    return Math.ceil(this.filteredBooks.length / this.itemsPerPage);
                },

                get paginatedBooks() {
                    const start = (this.currentPage - 1) * this.itemsPerPage;
                    const end = start + this.itemsPerPage;
                    return this.filteredBooks.slice(start, end);
                },

                previousPage() {
                    if (this.currentPage > 1) {
                        this.currentPage--;
                    }
                },

                nextPage() {
                    if (this.currentPage < this.totalPages) {
                        this.currentPage++;
                    }
                },

                goToPage(page) {
                    this.currentPage = page;
                },

                showBookDetails(book) {
                    this.selectedBook = book;
                }
            }));
        });
    </script>

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then(registration => {
                    console.log('ServiceWorker registration successful');
                }).catch(err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
            Alpine.data('home', () => ({
                currentTime: '',

                init() {
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);
                },

                updateTime() {
                    const now = new Date();
                    this.currentTime = now.toISOString().slice(0, 19).replace('T', ' ');
                }
            }));
        }
    </script>
</body>

</html>