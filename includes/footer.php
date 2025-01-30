<?php
if (!defined('SITE_NAME')) {
    die('Direct access to this file is not allowed');
}
?>

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <!-- Tambahkan Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="/sistem/includes/styles.css" rel="stylesheet">
</head>

<!-- Footer dengan gradient background dan efek hover yang lebih menarik -->
<footer class="bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <!-- Main Footer Content -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
            <!-- Brand Section -->
            <div class="col-span-1 md:col-span-2">
                <h3 class="text-2xl font-bold mb-6 text-white">
                    <?= htmlspecialchars(SITE_NAME) ?>
                </h3>
                <p class="text-gray-300 mb-6 leading-relaxed">
                    Sistem perpustakaan modern yang memudahkan proses peminjaman dan pengelolaan buku.
                    Kami berkomitmen untuk memberikan pengalaman membaca terbaik bagi semua pengguna.
                </p>
                <!-- Social Media Links -->
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                        <i class="fab fa-facebook-f text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                        <i class="fab fa-instagram text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                        <i class="fab fa-linkedin-in text-xl"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-lg font-semibold mb-6 text-white">Link Cepat</h4>
                <ul class="space-y-3">
                    <li>
                        <a href="/sistem/index.php" class="text-gray-300 hover:text-white transition-colors duration-300 flex items-center">
                            <i class="fas fa-chevron-right mr-2 text-xs"></i>
                            Beranda
                        </a>
                    </li>
                    <li>
                        <a href="/sistem/public/daftar-buku.php" class="text-gray-300 hover:text-white transition-colors duration-300 flex items-center">
                            <i class="fas fa-chevron-right mr-2 text-xs"></i>
                            Daftar Buku
                        </a>
                    </li>
                    <li>
                        <a href="/sistem/public/kontak.php" class="text-gray-300 hover:text-white transition-colors duration-300 flex items-center">
                            <i class="fas fa-chevron-right mr-2 text-xs"></i>
                            Kontak
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div>
                <h4 class="text-lg font-semibold mb-6 text-white">Informasi Kontak</h4>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <i class="fas fa-envelope mt-1.5 mr-3 text-gray-400"></i>
                        <span class="text-gray-300">perpustakaan@example.com</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-phone mt-1.5 mr-3 text-gray-400"></i>
                        <span class="text-gray-300">(021) 123-4567</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-map-marker-alt mt-1.5 mr-3 text-gray-400"></i>
                        <span class="text-gray-300">Jl. Contoh No. 123, Jakarta</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Newsletter Section -->
        <div class="border-t border-gray-700 mt-12 pt-8">
            <div class="max-w-md mx-auto text-center">
                <h5 class="text-lg font-semibold mb-4">Berlangganan Book Lending System</h5>
                <div class="flex">
                    <input type="email" placeholder="Masukkan email Anda"
                        class="flex-1 p-2 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-700 text-white">
                    <button class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded-r-lg transition-colors duration-300">
                        Daftar
                    </button>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="border-t border-gray-700 mt-12 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. All rights reserved.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors duration-300">Kebijakan Privasi</a>
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors duration-300">Syarat & Ketentuan</a>
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors duration-300">FAQ</a>
                </div>
            </div>
        </div>
    </div>
</footer>