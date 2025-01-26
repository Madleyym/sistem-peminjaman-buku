<?php
session_start();
require_once('../../../config/constants.php');
require_once '../../../config/database.php';
require_once '../../../classes/User.php';
require_once '../../../classes/Book.php';
require_once __DIR__ . '/../../../vendor/autoload.php';


// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$userModel = new User($conn);
$user = $userModel->getUserById($_SESSION['user_id']);

// Profile update handling
$updateSuccess = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Input sanitization
    $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone_number'] ?? '', FILTER_SANITIZE_STRING);
    $address = filter_var($_POST['address'] ?? '', FILTER_SANITIZE_STRING);

    // Validation
    if (empty($name)) $errors[] = "Nama tidak boleh kosong";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid";

    // Profile image handling
    $profileImage = $user['profile_image'];
    if (!empty($_FILES['profile_image']['name'])) {
        $uploadDir = realpath(__DIR__ . '/../../../uploads/profiles') . '/'; // Path absolut
        $filename = uniqid() . '_' . basename($_FILES['profile_image']['name']);
        $uploadPath = $uploadDir . $filename;

        // Debugging path
        if (!file_exists($uploadDir)) {
            $errors[] = "Direktori tujuan tidak ditemukan: $uploadDir";
        } else if (!file_exists($_FILES['profile_image']['tmp_name'])) {
            $errors[] = "File sementara tidak ditemukan.";
        } else {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                // Ganti path untuk penyimpanan di database
                $profileImage = str_replace(realpath(__DIR__ . '/../../../'), '', $uploadPath);
                $profileImage = str_replace('\\', '/', $profileImage); // Normalisasi path
            } else {
                $errors[] = "Gagal mengunggah gambar profil ke $uploadPath.";
            }
        }
    }

    // Update profile
    if (empty($errors)) {
        $updateResult = $userModel->updateProfile(
            $_SESSION['user_id'],
            $name,
            $email,
            $phone,
            $address,
            $profileImage
        );


        if ($updateResult) {
            $updateSuccess = true;
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f6f8f9 0%, #e5ebee 100%);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col font-inter">
    <!-- Navigation -->
    <nav x-data="{ mobileMenu: false }" class="bg-gradient-to-r from-blue-600 to-blue-700 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="/sistem/public/auth/users/dashboard.php" class="text-white font-bold text-xl flex items-center">
                    <i class="fas fa-book-open mr-2"></i>
                    <?= htmlspecialchars(SITE_NAME) ?>
                </a>

                <!-- Mobile Menu Toggle -->
                <div class="md:hidden">
                    <button
                        @click="mobileMenu = !mobileMenu"
                        class="text-white hover:bg-blue-500 p-2 rounded-md">
                        <i x-show="!mobileMenu" class="fas fa-bars"></i>
                        <i x-show="mobileMenu" class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex space-x-4 items-center">
                    <a href="/sistem/public/auth/users/book-loan.php" class="text-white hover:bg-blue-500 px-3 py-2 rounded-md">
                        <i class="fas fa-book-reader mr-2"></i>Pinjam Buku
                    </a>
                    <a href="/sistem/public/auth/users/profile.php" class="text-white hover:bg-blue-500 px-3 py-2 rounded-md">
                        <i class="fas fa-user-circle mr-2"></i>Profil
                    </a>
                    <a href="/sistem/public/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div x-show="mobileMenu" class="md:hidden">
                <div class="px-2 pt-2 pb-3 space-y-1 bg-blue-700">
                    <a href="/sistem/public/auth/users/book-loan.php" class="text-white block px-3 py-2 rounded-md hover:bg-blue-500">
                        <i class="fas fa-book-reader mr-2"></i>Pinjam Buku
                    </a>
                    <a href="/sistem/public/auth/users/profile.php" class="text-white block px-3 py-2 rounded-md hover:bg-blue-500">
                        <i class="fas fa-user-circle mr-2"></i>Profil
                    </a>
                    <a href="/sistem/public/auth/logout.php" class="text-white block px-3 py-2 rounded-md hover:bg-blue-500">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-center text-white">
                <h2 class="text-3xl font-bold">Edit Profil</h2>
                <p class="text-blue-100 mt-2">Perbarui informasi pribadi Anda</p>
            </div>

            <!-- Notifications -->
            <div class="px-6 py-4">
                <?php if ($updateSuccess): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
                        <p class="font-bold">Sukses</p>
                        <p>Profil berhasil diperbarui</p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                        <p class="font-bold">Error</p>
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Profile Form -->
            <form action="profile.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                <!-- Profile Image Upload -->
                <div class="flex flex-col items-center mb-6">
                    <input type="file" name="profile_image" id="profile_image" class="hidden" accept="image/*" onchange="previewImage(event)">
                    <label for="profile_image" class="cursor-pointer relative">
                        <img
                            id="profile_preview"
                            src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '../assets/images/default-profile.png' ?>"
                            alt="Profil Pengguna"
                            class="w-40 h-40 rounded-full object-cover border-4 border-blue-200 hover:border-blue-400 transition">
                        <div class="absolute bottom-0 right-0 bg-blue-500 text-white rounded-full p-2 hover:bg-blue-600 transition">
                            <i class="fas fa-camera"></i>
                        </div>
                    </label>
                    <p class="mt-2 text-sm text-gray-500">Klik foto untuk mengganti</p>
                </div>

                <!-- Input Fields -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Nama Lengkap</label>
                        <input
                            type="text"
                            name="name"
                            value="<?= htmlspecialchars($user['name']) ?>"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Email</label>
                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($user['email']) ?>"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Nomor Telepon</label>
                        <input
                            type="tel"
                            name="phone_number"
                            value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Alamat</label>
                        <textarea
                            name="address"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-4 rounded-lg hover:from-blue-600 hover:to-blue-700 transition duration-300 transform hover:scale-105">
                    <i class="fas fa-save mr-2"></i>Perbarui Profil
                </button>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. All Rights Reserved.</p>
            <div class="flex justify-center space-x-4 mt-4">
                <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook"></i></a>
                <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
            </div>
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