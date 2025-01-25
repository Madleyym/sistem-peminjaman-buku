<?php
session_start();
require_once '../config/constants.php';
require_once '../config/database.php';

// Regenerate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Retrieve and clear messages/errors
$messages = isset($_SESSION['message']) ? $_SESSION['message'] : null;
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : null;
unset($_SESSION['message'], $_SESSION['errors']);

// Page configuration
$pageTitle = SITE_NAME . " - Kontak";
$pageDescription = "Hubungi Kami untuk Pertanyaan dan Dukungan";

if (isset($_SESSION['message'])) {
    echo "<script>console.log(" . json_encode($_SESSION['message']) . ");</script>";
}
if (isset($_SESSION['errors'])) {
    echo "<script>console.log(" . json_encode($_SESSION['errors']) . ");</script>";
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<style>
    /* Use Tailwind for most styles, this is for any additional custom styles */
    :root {
        --primary-color: #3498db;
        --secondary-color: #2ecc71;
        --text-color: #2c3e50;
    }

    /* Add any additional custom styles that aren't easily achievable with Tailwind */
    .contact-form input:focus,
    .contact-form textarea:focus {
        box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
    }
</style>



<body class="bg-gray-50 font-inter min-h-screen flex flex-col">

    <!-- Mobile Navigation (Copied from index.php) -->
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
                        <a href="/sistem/public/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-2xl shadow-2xl overflow-hidden mb-12 p-8">
            <div class="max-w-4xl mx-auto grid md:grid-cols-2 gap-8">
                <div>
                    <h1 class="text-4xl font-bold mb-4">Hubungi Kami</h1>
                    <p class="text-xl opacity-90 mb-6"><?= htmlspecialchars($pageDescription) ?></p>
                    <div class="space-y-4 text-white">
                        <p class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Jalan Perpustakaan No. 123, Kota Buku
                        </p>
                        <p class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            (022) 1234 5678
                        </p>
                        <p class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            info@<?= strtolower(str_replace(' ', '', SITE_NAME)) ?>.com
                        </p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-8 text-gray-800">
                    <h2 class="text-3xl font-bold text-blue-700 mb-6">Kirim Pesan</h2>
                    <form action="submit_contact.php" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div>
                            <input
                                type="text"
                                name="name"
                                placeholder="Nama Anda"
                                required
                                class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <input
                                type="email"
                                name="email"
                                placeholder="Email Anda"
                                required
                                class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <textarea
                                name="message"
                                placeholder="Pesan Anda"
                                rows="5"
                                required
                                class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        <div>
                            <button
                                type="submit"
                                class="w-full bg-green-500 text-white py-3 rounded-lg hover:bg-green-600 transition duration-300 transform hover:-translate-y-1">
                                Kirim Pesan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer (Copied from index.php) -->
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
        <form action="submit_contact.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Tampilan error -->
            <?php if (isset($_SESSION['message']) && $_SESSION['message']['type'] === 'error'): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?= htmlspecialchars($_SESSION['message']['text']) ?></span>
                </div>
            <?php
                // Hapus pesan error setelah ditampilkan
                unset($_SESSION['message']);
            endif;
            ?>

            <!-- Tampilan sukses -->
            <?php if (isset($_SESSION['message']) && $_SESSION['message']['type'] === 'success'): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Sukses!</strong>
                    <span class="block sm:inline"><?= htmlspecialchars($_SESSION['message']['text']) ?></span>
                </div>
            <?php
                // Hapus pesan sukses setelah ditampilkan
                unset($_SESSION['message']);
            endif;
            ?>
        </form>

    </footer>
</body>

</html>