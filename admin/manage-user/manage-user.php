<?php

session_start();
require_once '../includes/admin-auth.php';
checkAdminAuth();

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../classes/User.php';

$database = new Database();
$conn = $database->getConnection();
$userManager = new User($conn);


// Handle form submissions for creating, updating, and deleting admins
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $data = [
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'nik' => $_POST['nik'],
            'name' => $_POST['name']
        ];
        $result = $userManager->adminRegister($data);
        if ($result['status']) {
            $_SESSION['success'] = "Admin berhasil ditambahkan";
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } elseif (isset($_POST['update'])) {
        $id = $_POST['id'];
        $data = [
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'name' => $_POST['name'],
            'nik' => $_POST['nik']
        ];
        $userManager->updateAdmin($id, $data);
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $userManager->deleteAdmin($id);
    }
}

// Fetch all admins
$admins = $userManager->getAllAdmins();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #e74c3c;
            --text-color: #2c3e50;
            --background-color: #f4f7f6;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
        }

        .card-hover {
            transition: transform 0.2s ease-in-out;
        }

        .card-hover:hover {
            transform: translateY(-5px);
        }

        .gradient-bg {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-gray-50">
    <!-- Enhanced Navigation with Improved Styling -->
    <nav x-data="{ open: false }" class="bg-gradient-to-r from-blue-600 to-blue-800 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/sistem/admin/admin-index.php" class="flex items-center group">
                        <i class="fas fa-book-reader text-white text-2xl mr-2 transform group-hover:scale-110 transition-transform"></i>
                        <span class="text-white font-bold text-xl"><?= htmlspecialchars(SITE_NAME) ?></span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <!-- Navigation Links -->
                <div class="hidden md:block">
                    <div class="flex items-center space-x-4">
                        <a href="/sistem/public/index.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-home mr-1"></i> Beranda
                        </a>
                        <a href="/sistem/public/books.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-book mr-1"></i> Buku
                        </a>
                        <a href="/sistem/public/contact.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-envelope mr-1"></i> Kontak
                        </a>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                            <!-- Jika login sebagai admin -->
                            <div class="flex items-center space-x-3">
                                <span class="text-white text-sm">
                                    <i class="fas fa-user-circle mr-1"></i>
                                    <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                                </span>
                                <a href="/sistem/admin/auth/logout.php"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium">
                                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <!-- Jika belum login -->
                                <a href="/sistem/admin/auth/login.php"
                                    class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                                    <i class="fas fa-sign-in-alt mr-1"></i> Login Admin
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Improved Mobile Menu Button -->
                <div class="md:hidden">
                    <button @click="open = !open" class="text-white hover:bg-blue-700 p-2 rounded-md transition-colors duration-300">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Enhanced Mobile Menu with Smooth Transitions -->
        <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="md:hidden bg-blue-800">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/sistem/public/index.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                    <i class="fas fa-home mr-1"></i> Beranda
                </a>
                <a href="/sistem/public/books.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                    <i class="fas fa-book mr-1"></i> Buku
                </a>
                <a href="/sistem/public/contact.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                    <i class="fas fa-envelope mr-1"></i> Kontak
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content with Enhanced Layout -->
    <!-- Main Content with Enhanced Layout -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <!-- Dashboard Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-bold text-blue-700 flex items-center">
                    <i class="fas fa-users mr-3"></i>
                    Manajemen Pengguna
                </h2>
                <!-- Breadcrumb -->
                <nav class="hidden sm:flex">
                    <ol class="flex items-center space-x-2 text-gray-500 text-sm">
                        <li><a href="/sistem/admin/admin-index.php" class="hover:text-blue-600">Dashboard</a></li>
                        <li><i class="fas fa-chevron-right text-xs"></i></li>
                        <li class="text-blue-600">Manajemen Pengguna</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Form Section -->
            <!-- Form Section -->
            <div class="lg:col-span-4">
                <form method="POST" action="" class="bg-white rounded-2xl shadow-lg p-6 transform hover:shadow-xl transition-all duration-300">
                    <h3 class="text-xl font-bold text-blue-700 mb-6 flex items-center">
                        <i class="fas fa-user-plus mr-2"></i>
                        Tambah Admin Baru
                    </h3>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                            <?= htmlspecialchars($_SESSION['error']) ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                            <?= htmlspecialchars($_SESSION['success']) ?>
                            <?php unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-6">
                        <div class="relative">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-user mr-2"></i>Username
                            </label>
                            <input type="text" name="username" required placeholder="Masukkan username"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                        </div>

                        <div class="relative">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-id-card mr-2"></i>NIK
                            </label>
                            <input type="text" name="nik" required placeholder="Masukkan NIK 16 digit"
                                pattern="\d{16}" maxlength="16"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                        </div>

                        <div class="relative">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-user mr-2"></i>Nama Lengkap
                            </label>
                            <input type="text" name="name" required placeholder="Masukkan nama lengkap"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                        </div>

                        <div class="relative">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-envelope mr-2"></i>Email
                            </label>
                            <input type="email" name="email" required placeholder="contoh@email.com"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                        </div>

                        <div class="relative">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-lock mr-2"></i>Password
                            </label>
                            <input type="password" name="password" required placeholder="Minimal 8 karakter"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                        </div>

                        <button type="submit" name="create"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 flex items-center justify-center">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Tambah Admin
                        </button>
                    </div>
                </form>
            </div>

            <!-- Table Section -->
            <div class="lg:col-span-8">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <!-- Table Header -->
                    <div class="p-6 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-blue-700 flex items-center">
                                <i class="fas fa-list mr-2"></i>
                                Daftar Admin
                            </h3>
                            <div class="flex items-center space-x-3">
                                <div class="relative">
                                    <input type="text" placeholder="Cari admin..."
                                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table Content -->
                    <!-- Table Content -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIK</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($admins as $admin): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4"><?= htmlspecialchars($admin['username']) ?></td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($admin['name']) ?></td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($admin['email']) ?></td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($admin['nik']) ?></td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end space-x-2">
                                                <button class="group bg-yellow-500 hover:bg-yellow-600 text-white p-2 rounded-lg">
                                                    <i class="fas fa-edit"></i>
                                                    <span class="hidden group-hover:inline ml-1">Edit</span>
                                                </button>
                                                <form method="POST" action="" class="inline">
                                                    <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                                                    <button type="submit" name="delete"
                                                        class="group bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg"
                                                        onclick="return confirm('Yakin ingin menghapus admin ini?')">
                                                        <i class="fas fa-trash"></i>
                                                        <span class="hidden group-hover:inline ml-1">Hapus</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Menampilkan <span class="font-medium">1</span> sampai <span class="font-medium">10</span> dari <span class="font-medium">20</span> data
                            </div>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</body>

</html>