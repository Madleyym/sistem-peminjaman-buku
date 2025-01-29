<?php
session_start();
require_once('../../../config/constants.php');
require_once '../../../config/database.php';
require_once '../../../classes/User.php';
require_once '../../../classes/Book.php';
// require_once __DIR__ . '/../../../vendor/autoload.php';


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
                <a href="/sistem/public/auth/users/home.php" class="text-white font-bold text-xl flex items-center">
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
                    <a href="/sistem/public/auth/users/pinjaman-buku.php" class="text-white hover:bg-blue-500 px-3 py-2 rounded-md">
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
                    <a href="/sistem/public/auth/users/pinjaman-buku.php" class="text-white block px-3 py-2 rounded-md hover:bg-blue-500">
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
    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <!-- Profile Card -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden mb-8">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-white">Edit Profil</h2>
                        <!-- User Info & Time -->
                        <div class="text-right">
                            <div class="text-sm text-blue-100">
                                Current User's Login: <span class="font-semibold"><?= htmlspecialchars($user['name']) ?></span>
                            </div>
                            <div class="text-sm text-blue-100 mt-1">
                                <span x-data="{ time: '<?= date('Y-m-d H:i:s', strtotime('UTC')) ?>' }"
                                    x-init="setInterval(() => time = new Date().toISOString().slice(0, 19).replace('T', ' '), 1000)"
                                    x-text="time"></span> UTC
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="px-6 py-4">
                    <?php if ($updateSuccess): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                            <p class="font-bold">Sukses!</p>
                            <p>Profil berhasil diperbarui</p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                            <p class="font-bold">Error!</p>
                            <?php foreach ($errors as $error): ?>
                                <p><?= htmlspecialchars($error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Profile Image Upload -->
                <div class="flex flex-col items-center p-6 bg-gray-50">
                    <div class="relative">
                        <input type="file" name="profile_image" id="profile_image" class="hidden" accept="image/*" onchange="previewImage(event)">
                        <label for="profile_image" class="cursor-pointer block">
                            <div class="p-1 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600">
                                <img id="profile_preview"
                                    src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '../assets/images/default-profile.png' ?>"
                                    alt="Profile picture"
                                    class="w-32 h-32 rounded-full object-cover border-4 border-white">
                            </div>
                            <div class="absolute bottom-0 right-0 bg-blue-500 text-white rounded-full p-2 shadow-lg hover:bg-blue-600 transition">
                                <i class="fas fa-camera"></i>
                            </div>
                        </label>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">Klik untuk mengganti foto profil</p>
                </div>

                <!-- Profile Form -->
                <!-- Profile Form -->
                <form action="profile.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                    <!-- User Details Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user-edit text-blue-500 mr-2"></i>
                            Informasi Dasar
                        </h3>
                        <div class="max-w-xl mx-auto space-y-6"> <!-- Tambahkan max-w-xl dan mx-auto untuk centering -->
                            <div class="space-y-2">
                                <label class="block text-gray-700 font-medium">
                                    <i class="fas fa-user text-blue-500 mr-2"></i>Nama Lengkap
                                </label>
                                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-gray-700 font-medium">
                                    <i class="fas fa-envelope text-blue-500 mr-2"></i>Email
                                </label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                            </div>
                        </div>
                    </div>

                    <!-- Contact Details Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-address-card text-blue-500 mr-2"></i>
                            Informasi Kontak
                        </h3>
                        <div class="max-w-xl mx-auto space-y-6"> <!-- Tambahkan max-w-xl dan mx-auto untuk centering -->
                            <div class="space-y-2">
                                <label class="block text-gray-700 font-medium">
                                    <i class="fas fa-phone text-blue-500 mr-2"></i>Nomor Telepon
                                </label>
                                <input type="tel" name="phone_number" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-gray-700 font-medium">
                                    <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>Alamat
                                </label>
                                <textarea name="address"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 resize-none h-24"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-center pt-4">
                        <button type="submit"
                            class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-8 py-3 rounded-full hover:from-blue-600 hover:to-indigo-700 transition duration-300 flex items-center transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save mr-2"></i>
                            Perbarui Profil
                        </button>
                    </div>
                </form>
            </div>
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
</body>

</html>
<script>
    // Profile Image Preview with enhancement
    function previewImage(event) {
        const input = event.target;
        const preview = document.getElementById('profile_preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                // Add animation
                preview.classList.add('scale-105');
                setTimeout(() => preview.classList.remove('scale-105'), 200);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Enhanced Real-time UTC Clock
    document.addEventListener('alpine:init', () => {
        Alpine.data('clock', () => ({
            time: '',
            init() {
                this.updateTime();
                setInterval(() => this.updateTime(), 1000);
            },
            updateTime() {
                const now = new Date();
                // Format: YYYY-MM-DD HH:MM:SS
                this.time = now.toISOString().slice(0, 19).replace('T', ' ');
            }
        }));
    });
</script>