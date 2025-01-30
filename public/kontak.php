<?php
session_start();
require_once '../config/constants.php';
require_once '../config/database.php';
// require_once __DIR__ . '/../vendor/autoload.php';

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

    /* Add any additional custom styles that aren't easily achievable with Tailwind */
    .contact-form input:focus,
    .contact-form textarea:focus {
        box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
    }
    footer {
        display: flex;
        /* Mengatur footer menggunakan flexbox */
        flex-direction: column;
        /* Susunan vertikal */
        justify-content: center;
        /* Konten di tengah secara vertikal */
        align-items: center;
        /* Konten di tengah secara horizontal */
        text-align: center;
        /* Memusatkan teks */
    }

    .footer {
        background-color: #2c3e50;
        color: var(--white);
        padding: 40px 0;
    }

    .footer-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }

    .footer-section h4 {
        margin-bottom: 15px;
        font-size: 1.2rem;
    }

    .footer-links a {
        color: #bdc3c7;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .footer-links a:hover {
        color: var(--white);
    }

    @media (min-width: 768px) {
        footer .container {
            grid-template-columns: repeat(3, 1fr);
            /* 3 kolom di layar besar */
        }
    }
</style>

<body class="bg-gray-50 font-inter min-h-screen flex flex-col">
    <!-- Navigation -->
    <?php include '../includes/header.php'; ?>
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