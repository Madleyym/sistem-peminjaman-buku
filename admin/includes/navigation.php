<?php
// Cek apakah fungsi sudah ada sebelum mendefinisikan
if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

// Ambil informasi user dari session untuk ditampilkan
$current_user = [
    'username' => $_SESSION['username'] ?? 'Admin',
    'login_time' => $_SESSION['login_time'] ?? '2025-01-31 18:29:41'
];
?>

<!-- Enhanced Navigation with Improved Styling -->
<nav x-data="{ open: false }" class="bg-gradient-to-r from-blue-600 to-blue-800 shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <a href="/sistem/admin/index.php" class="flex items-center group">
                    <i class="fas fa-book-reader text-white text-2xl mr-2 transform group-hover:scale-110 transition-transform"></i>
                    <span class="text-white font-bold text-xl"><?= htmlspecialchars(SITE_NAME) ?></span>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:block">
                <div class="flex items-center space-x-4">
                    <a href="/sistem/admin/index.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-home mr-1"></i> Dashboard
                    </a>
                    <!-- <a href="/sistem/admin/reports/dashboard-reports.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-chart-bar mr-1"></i> Statistika
                    </a> -->
                    <a href="/sistem/admin/books.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-book mr-1"></i> Kelola Buku
                    </a>
                    <?php if (isAdmin()): ?>
                        <div class="flex items-center space-x-3">
                            <span class="text-white text-sm">
                                <i class="fas fa-user-circle mr-1"></i>
                                <?= htmlspecialchars($current_user['username']) ?>
                                <span class="ml-1 text-xs bg-yellow-500 text-white px-2 py-0.5 rounded">Admin</span>
                            </span>
                            <a href="/sistem/admin/auth/logout.php"
                                onclick="return confirm('Apakah Anda yakin ingin keluar?')"
                                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium">
                                <i class="fas fa-sign-out-alt mr-1"></i> Logout
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden">
                <button @click="open = !open" class="text-white hover:bg-blue-700 p-2 rounded-md transition-colors duration-300">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-2"
        class="md:hidden bg-blue-800">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="/sistem/admin/index.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                <i class="fas fa-home mr-1"></i> Dashboard
            </a>
            <!-- <a href="/sistem/admin/reports/dashboard-reports.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                <i class="fas fa-chart-bar mr-1"></i> Statistika
            </a> -->
            <a href="/sistem/admin/books.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                <i class="fas fa-book mr-1"></i> Kelola Buku
            </a>
            <?php if (isAdmin()): ?>
                <div class="border-t border-blue-700 my-2"></div>
                <div class="px-3 py-2 text-white">
                    <i class="fas fa-user-circle mr-1"></i>
                    <?= htmlspecialchars($current_user['username']) ?>
                    <span class="ml-1 text-xs bg-yellow-500 text-white px-2 py-0.5 rounded">Admin</span>
                </div>
                <a href="/sistem/admin/auth/logout.php"
                    onclick="return confirm('Apakah Anda yakin ingin keluar?')"
                    class="text-white block px-3 py-2 rounded-md text-base font-medium bg-red-500 hover:bg-red-600">
                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>