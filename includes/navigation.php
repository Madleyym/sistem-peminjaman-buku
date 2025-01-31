<?php
// Prevent direct access to this file
if (!defined('SITE_NAME')) {
    die('Direct access to this file is not allowed');
}

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/User.php';

// Helper functions
function isActivePage($path)
{
    return strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'bg-blue-600' : '';
}

function getUserRole()
{
    return $_SESSION['role'] ?? null;
}

function isAdmin()
{
    return getUserRole() === User::getRoles()['ADMIN'];
}


?>

<!-- Main Navigation -->
<nav class="bg-blue-700" x-data="{ isOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo and Brand -->
            <div class="flex items-center">
                <a href="/sistem/index.php" class="flex-shrink-0">
                    <span class="text-white font-bold text-xl"><?= htmlspecialchars(SITE_NAME) ?></span>
                </a>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-4">
                <!-- Navigation Links -->
                <a href="/sistem/index.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= isActivePage('/index.php') ?>">
                    <i class="fas fa-home mr-1"></i> Beranda
                </a>
                <a href="/sistem/admin/index.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= isActivePage('/admin/index.php') ?>">
                    <i class="fas fa-chart-bar mr-1"></i> Statistika
                </a>
                <a href="/sistem/public/daftar-buku.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= isActivePage('/daftar-buku.php') ?>">
                    <i class="fas fa-book mr-1"></i> Buku
                </a>
                <a href="/sistem/public/kontak.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= isActivePage('/kontak.php') ?>">
                    <i class="fas fa-envelope mr-1"></i> Kontak
                </a>

                <!-- Auth Buttons Desktop -->
                <?php if (empty($_SESSION['user_id'])): ?>
                    <a href="/sistem/public/auth/login.php" class="text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                    <a href="/sistem/public/auth/register.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-user-plus mr-1"></i> Daftar
                    </a>
                <?php else: ?>
                    <!-- Profile Dropdown -->
                    <div class="relative" x-data="{ dropdownOpen: false }" @click.away="dropdownOpen = false" @keydown.escape.window="dropdownOpen = false">
                        <button @click="dropdownOpen = !dropdownOpen" class="flex items-center text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                            <i class="fas fa-user-circle mr-2"></i>
                            <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                            <i class="fas fa-chevron-down ml-2"></i>
                        </button>
                        <div x-show="dropdownOpen"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50"
                            style="display: none;">
                            <a href="/sistem/public/auth/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i> Profile
                            </a>
                            <a href="/sistem/public/auth/logout.php"
                                class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                                onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden flex items-center">
                <button @click="isOpen = !isOpen" class="inline-flex items-center justify-center p-2 rounded-md text-white hover:bg-blue-600 focus:outline-none">
                    <svg class="h-6 w-6" x-show="!isOpen" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="h-6 w-6" x-show="isOpen" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="isOpen" class="md:hidden bg-blue-600">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <!-- Mobile Navigation Links -->
                <a href="/sistem/index.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500 transition-colors duration-200 <?= isActivePage('/index.php') ?>">
                    <i class="fas fa-home mr-2"></i> Beranda
                </a>
                <a href="/sistem/admin/index.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500 transition-colors duration-200 <?= isActivePage('/admin/index.php') ?>">
                    <i class="fas fa-chart-bar mr-2"></i> Statistika
                </a>
                <a href="/sistem/public/daftar-buku.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500 transition-colors duration-200 <?= isActivePage('/daftar-buku.php') ?>">
                    <i class="fas fa-book mr-2"></i> Buku
                </a>
                <a href="/sistem/public/kontak.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500 transition-colors duration-200 <?= isActivePage('/kontak.php') ?>">
                    <i class="fas fa-envelope mr-2"></i> Kontak
                </a>

                <!-- Mobile Auth Section -->
                <?php if (empty($_SESSION['user_id'])): ?>
                    <div class="pt-4 pb-3 border-t border-blue-500">
                        <a href="/sistem/public/auth/login.php" class="block text-white px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500 transition-colors duration-200">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login
                        </a>
                        <a href="/sistem/public/auth/register.php" class="block text-white px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500 transition-colors duration-200">
                            <i class="fas fa-user-plus mr-2"></i> Daftar
                        </a>
                    </div>
                <?php else: ?>
                    <div class="pt-4 pb-3 border-t border-blue-500">
                        <div class="flex items-center px-3">
                            <div class="text-white font-medium">
                                <i class="fas fa-user-circle mr-2"></i>
                                <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                            </div>
                        </div>
                        <div class="mt-3 px-2 space-y-1">
                            <a href="/sistem/public/auth/profile.php" class="block text-white px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500 transition-colors duration-200">
                                <i class="fas fa-user mr-2"></i> Profile
                            </a>
                            <a href="/sistem/public/auth/logout.php"
                                class="block text-white px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500 transition-colors duration-200"
                                onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Add Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Add Alpine.js -->
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>