<?php
// File: C:\xampp\htdocs\sistem\admin\includes\admin-nav.php

// Check if accessed directly
if (!defined('SITE_NAME')) {
    header('HTTP/1.1 403 Forbidden');
    die('Direct access not allowed');
}

require_once __DIR__ . '/../../config/auth-session.php';

try {
    // Initialize admin session
    $authSession = AuthSession::getInstance(AuthSession::ROLES['ADMIN']);

    // Validate admin session and role
    AuthSession::requireRole(AuthSession::ROLES['ADMIN']);

    // Get current admin data
    $adminData = AuthSession::getCurrentUser();
    if (!$adminData) {
        throw new Exception('Invalid admin session data');
    }

    // Generate CSRF token for logout
    $csrf_token = AuthSession::generateCSRFToken();

    // Define navigation items with additional metadata
    $navItems = [
        [
            'path' => '/sistem/admin/index.php',
            'icon' => 'fa-chart-line',
            'text' => 'Dashboard',
            'description' => 'Dashboard Pengguna'
        ],
        [
            'path' => '/sistem/admin/manage-books/dashboard-book.php',
            'icon' => 'fa-book',
            'text' => 'Kelola Buku',
            'description' => 'Manajemen Buku'
        ],
        [
            'path' => '/sistem/admin/manage-users/index.php',
            'icon' => 'fa-users',
            'text' => 'Kelola User',
            'description' => 'Manajemen Pengguna'
        ]
    ];

    // Get current path for active state
    $currentPath = $_SERVER['REQUEST_URI'];
} catch (Exception $e) {
    error_log("Admin Nav Error: " . $e->getMessage());
    AuthSession::logout(AuthSession::ROLES['ADMIN']);
    header('Location: ' . AuthSession::getLoginPath(AuthSession::ROLES['ADMIN']));
    exit;
}
?>

<!-- Modern Navigation Bar -->
<nav class="bg-gradient-to-r from-blue-700 to-indigo-800 shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand Logo -->
            <div class="flex-shrink-0 flex items-center">
                <a href="<?= htmlspecialchars(AuthSession::getRedirectPath(AuthSession::ROLES['ADMIN'])) ?>"
                    class="flex items-center group">
                    <div class="bg-white p-2 rounded-full shadow-md transform group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-book-reader text-blue-700 text-xl"></i>
                    </div>
                    <span class="ml-3 text-white font-bold text-lg tracking-wider">
                        Admin <?= htmlspecialchars(SITE_NAME) ?>
                    </span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex md:items-center md:space-x-4">
                <?php foreach ($navItems as $item):
                    $isActive = $currentPath === $item['path'];
                ?>
                    <a href="<?= htmlspecialchars($item['path']) ?>"
                        class="relative group flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300 
                              <?= $isActive ? 'bg-white text-blue-700' : 'text-white hover:bg-blue-600' ?>">
                        <i class="fas <?= $item['icon'] ?> <?= $isActive ? 'text-blue-700' : 'text-white' ?> mr-2"></i>
                        <span><?= $item['text'] ?></span>

                        <!-- Tooltip -->
                        <div class="absolute hidden group-hover:block top-full left-1/2 transform -translate-x-1/2 mt-2 px-3 py-1 
                                  bg-gray-900 text-white text-xs rounded-md whitespace-nowrap">
                            <?= $item['description'] ?>
                        </div>
                    </a>
                <?php endforeach; ?>

                <!-- User Profile & Logout Section -->
                <div class="flex items-center space-x-4 ml-4 pl-4 border-l border-blue-600">
                    <!-- User Info -->
                    <div class="group relative">
                        <button class="flex items-center space-x-2 bg-blue-600 rounded-lg px-3 py-2 text-white hover:bg-blue-500 transition-colors duration-300">
                            <i class="fas fa-user-circle text-lg"></i>
                            <span class="text-sm font-medium"><?= htmlspecialchars($adminData['name']) ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div class="hidden group-hover:block absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5">
                            <div class="px-4 py-2 text-sm text-gray-700 border-b">
                                <div class="font-medium"><?= htmlspecialchars($adminData['email']) ?></div>
                                <div class="text-xs text-gray-500">Administrator</div>
                            </div>
                            <a href="/sistem/admin/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user-cog mr-2"></i> Pengaturan Profil
                            </a>
                        </div>
                    </div>

                    <form action="/sistem/admin/auth/admin-logout.php"
                        method="POST"
                        class="logout-form"
                        onsubmit="return confirmLogout(event)">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit"
                            class="flex items-center px-4 py-2 rounded-lg text-sm font-medium text-white bg-red-500 
                   hover:bg-red-600 transition-colors duration-300">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden">
                <button type="button"
                    onclick="toggleMobileMenu()"
                    class="inline-flex items-center justify-center p-2 rounded-md text-white hover:bg-blue-600 
                               focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Panel -->
        <div id="mobileMenu" class="hidden md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <?php foreach ($navItems as $item):
                    $isActive = $currentPath === $item['path'];
                ?>
                    <a href="<?= htmlspecialchars($item['path']) ?>"
                        class="<?= $isActive ? 'bg-blue-600 text-white' : 'text-blue-100 hover:bg-blue-600' ?> 
                              block px-3 py-2 rounded-md text-base font-medium transition-colors duration-300">
                        <i class="fas <?= $item['icon'] ?> mr-2"></i>
                        <?= $item['text'] ?>
                    </a>
                <?php endforeach; ?>

                <!-- Mobile User Info -->
                <div class="mt-4 pt-4 border-t border-blue-600">
                    <div class="flex items-center px-3 py-2 text-white">
                        <i class="fas fa-user-circle text-2xl mr-2"></i>
                        <div>
                            <div class="font-medium"><?= htmlspecialchars($adminData['name']) ?></div>
                            <div class="text-sm opacity-75"><?= htmlspecialchars($adminData['email']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        menu.classList.toggle('hidden');
    }

    function confirmLogout(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Konfirmasi Logout',
            text: 'Apakah Anda yakin ingin keluar?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Logout',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                event.target.submit();
            }
        });
        return false;
    }

    // Highlight active menu
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname;
        const menuItems = document.querySelectorAll('nav a');
        menuItems.forEach(item => {
            if (item.getAttribute('href') === currentPath) {
                item.classList.add('bg-blue-600');
            }
        });
    });
</script>

<!-- Add SweetAlert2 for better alerts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>