<?php
session_start();
require_once('../../../config/constants.php');
require_once '../../../config/database.php';
require_once '../../../classes/User.php';
require_once '../../../classes/Book.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$userModel = new User($conn);
$user = $userModel->getUserById($_SESSION['user_id']);

// Handle profile update
$updateSuccess = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone_number'] ?? '', FILTER_SANITIZE_STRING);
    $address = filter_var($_POST['address'] ?? '', FILTER_SANITIZE_STRING);

    // Validate inputs
    if (empty($name)) $errors[] = "Nama tidak boleh kosong";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid";

    // Handle profile image upload
    $profileImage = $user['profile_image'];
    if (!empty($_FILES['profile_image']['name'])) {
        $uploadDir = '../uploads/profiles/';
        $filename = uniqid() . '_' . basename($_FILES['profile_image']['name']);
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
            $profileImage = str_replace('../', '/', $uploadPath);
        } else {
            $errors[] = "Gagal mengunggah gambar profil";
        }
    }

    // Update profile if no errors
    if (empty($errors)) {
        $updateResult = $user->updateProfile(
            $_SESSION['user_id'],
            $name,
            $email,
            $phone,
            $address,
            $profileImage
        );

        if ($updateResult) {
            $updateSuccess = true;
            // Refresh user data
            $user = $userModel->getUserById($_SESSION['user_id']);
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
        } else {
            $errors[] = "Gagal memperbarui profil";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-50 font-inter min-h-screen flex flex-col">
    <!-- Mobile Navigation -->
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
                    <a href="/sistem/public/auth/users/dashboard.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Home</a>
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
                        <a href="/sistem/public/auth/users/dashboard.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Home</a>
                        <a href="/sistem/public/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <body class="bg-gray-50 font-inter min-h-screen flex flex-col">
        <main class="flex-grow container mx-auto px-4 py-8">
            <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-3xl font-bold text-center text-blue-700 mb-8">Edit Profil</h2>

                <?php if ($updateSuccess): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        Profil berhasil diperbarui
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-6 text-center">
                        <input
                            type="file"
                            name="profile_image"
                            id="profile_image"
                            class="hidden"
                            accept="image/*"
                            onchange="previewImage(event)">
                        <label for="profile_image" class="cursor-pointer">
                            <img
                                id="profile_preview"
                                src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '../assets/images/default-profile.png' ?>"
                                alt="Profil Pengguna"
                                class="w-32 h-32 rounded-full mx-auto mb-4 object-cover border-4 border-blue-200 hover:border-blue-400 transition">
                            <span class="text-blue-600 hover:text-blue-800">Ubah Foto Profil</span>
                        </label>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-gray-700 font-bold mb-2">Nama Lengkap</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="<?= htmlspecialchars($user['name']) ?>"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($user['email']) ?>"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="phone_number" class="block text-gray-700 font-bold mb-2">Nomor Telepon</label>
                            <input
                                type="tel"
                                id="phone_number"
                                name="phone_number"
                                value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="address" class="block text-gray-700 font-bold mb-2">Alamat</label>
                            <textarea
                                id="address"
                                name="address"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="w-full mt-6 bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 transition duration-300">
                        Perbarui Profil
                    </button>
                </form>
            </div>
        </main>

        <!-- Footer -->
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
        </footer>

        <script>
            function previewImage(event) {
                const input = event.target;
                const preview = document.getElementById('profile_preview');
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }
        </script>
    </body>

</html>