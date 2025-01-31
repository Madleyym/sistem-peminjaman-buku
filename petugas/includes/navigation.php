<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../../classes/User.php';

// Cek apakah user sudah login sebagai petugas menggunakan konstanta ROLES
if (!isset($_SESSION['role']) || $_SESSION['role'] !== User::getRoles()['STAFF']) {
    header("Location: " . User::getLoginPath(User::getRoles()['STAFF']));
    exit();
}

// Helper function untuk mengecek active menu
function isActiveMenu($path)
{
    return strpos($_SERVER['PHP_SELF'], $path) !== false;
}

// Base URL untuk petugas
$staffBaseUrl = '/sistem/petugas';
?>

<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo/Brand -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="<?= $staffBaseUrl ?>/index.php" class="text-xl font-bold text-green-600">
                        <?= htmlspecialchars(SITE_NAME) ?>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <!-- Navigation Links -->
                    <a href="<?= $staffBaseUrl ?>/index.php"
                        class="<?= isActiveMenu('/index.php') ? 'border-green-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="<?= $staffBaseUrl ?>/peminjaman/index.php"
                        class="<?= isActiveMenu('/peminjaman/') ? 'border-green-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Peminjaman
                    </a>
                    <a href="<?= $staffBaseUrl ?>/buku/index.php"
                        class="<?= isActiveMenu('/buku/') ? 'border-green-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Data Buku
                    </a>
                    <a href="<?= $staffBaseUrl ?>/anggota/index.php"
                        class="<?= isActiveMenu('/anggota/') ? 'border-green-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Data Anggota
                    </a>
                </div>
            </div>

            <!-- User Menu -->
            <div class="flex items-center">
                <div class="hidden sm:flex sm:items-center sm:ml-6">
                    <div class="relative">
                        <div class="flex items-center space-x-4">
                            <div class="text-sm text-gray-600">
                                <span class="block">Selamat datang, <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username']) ?></span>
                                <span class="block text-xs text-gray-500">Login: <?= $_SESSION['login_time'] ?? '' ?></span>
                            </div>
                            <a href="<?= $staffBaseUrl ?>/auth/logout.php"
                                class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-600 transition duration-150 ease-in-out">
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div class="sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <a href="<?= $staffBaseUrl ?>/index.php"
                class="<?= isActiveMenu('/index.php') ? 'bg-green-50 border-green-500 text-green-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700' ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Dashboard
            </a>
            <a href="<?= $staffBaseUrl ?>/peminjaman/index.php"
                class="<?= isActiveMenu('/peminjaman/') ? 'bg-green-50 border-green-500 text-green-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700' ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Peminjaman
            </a>
            <a href="<?= $staffBaseUrl ?>/buku/index.php"
                class="<?= isActiveMenu('/buku/') ? 'bg-green-50 border-green-500 text-green-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700' ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Data Buku
            </a>
            <a href="<?= $staffBaseUrl ?>/anggota/index.php"
                class="<?= isActiveMenu('/anggota/') ? 'bg-green-50 border-green-500 text-green-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700' ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Data Anggota
            </a>
        </div>
    </div>
</nav>