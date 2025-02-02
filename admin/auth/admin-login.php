<?php
// File: /sistem/admin/auth/admin-login.php

session_start();

// Define constants if not already defined
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Sistem Perpustakaan');
}

// Include necessary files with absolute paths
$baseDir = dirname(dirname(dirname(__FILE__))); // Get root directory
require_once $baseDir . '/config/auth-session.php';
require_once $baseDir . '/config/database.php';

// Initialize variables
$email = $nik = '';
$errors = [];
$flashMessage = '';

try {
    // Initialize AuthSession for admin
    $authSession = AuthSession::getInstance(AuthSession::ROLES['ADMIN']);

    // Check if already logged in
    if (AuthSession::isLoggedIn()) {
        $currentRole = $_SESSION['user_role'] ?? '';
        if ($currentRole === AuthSession::ROLES['ADMIN']) {
            header('Location: /sistem/admin/index.php');
            exit();
        }
    }

    // Process login form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize inputs
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $nik = preg_replace('/[^0-9]/', '', $_POST['nik'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validate inputs
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email tidak valid";
        }
        if (empty($nik) || strlen($nik) !== 16) {
            $errors[] = "NIK harus 16 digit";
        }
        if (empty($password)) {
            $errors[] = "Password harus diisi";
        }

        // Attempt login if no validation errors
        if (empty($errors)) {
            try {
                $loginData = [
                    'email' => $email,
                    'nik' => $nik,
                    'password' => $password
                ];

                if (AuthSession::login($loginData, AuthSession::ROLES['ADMIN'])) {
                    $adminData = AuthSession::getCurrentUser();

                    // Log successful login
                    try {
                        AuthSession::logActivity(
                            'Login berhasil ke sistem admin',
                            AuthSession::ROLES['ADMIN'],
                            $adminData['id']
                        );
                    } catch (Exception $e) {
                        error_log("Failed to log activity: " . $e->getMessage());
                    }

                    // Clear session errors
                    unset($_SESSION['login_errors']);

                    // Redirect to admin dashboard
                    header('Location: /sistem/admin/index.php');
                    exit();
                } else {
                    $errors[] = "Email, NIK, atau password salah";
                    error_log("Failed login attempt - Email: $email, NIK: $nik, IP: " . $_SERVER['REMOTE_ADDR']);
                }
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $errors[] = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
            }
        }
    }

    // Get flash message
    if (isset($_SESSION['message'])) {
        $flashMessage = $_SESSION['message'];
        unset($_SESSION['message']);
    }
} catch (Exception $e) {
    error_log("System error in admin login: " . $e->getMessage());
    $errors[] = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
}

// Store errors in session
if (!empty($errors)) {
    $_SESSION['login_errors'] = $errors;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md animate-fade-in">
        <!-- Logo atau Icon -->
        <div class="text-center mb-4">
            <i class="fas fa-book-reader text-4xl text-blue-600"></i>
        </div>

        <div class="bg-white rounded-lg shadow-xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Login Administrator</h1>
                <p class="text-gray-600 text-sm">Masuk ke panel admin <?= htmlspecialchars(SITE_NAME) ?></p>
            </div>

            <?php if (!empty($flashMessage)): ?>
                <div class="mb-4 bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 animate-fade-in">
                    <?= htmlspecialchars($flashMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 animate-fade-in">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-envelope mr-1 text-gray-400"></i> Email
                    </label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required
                        placeholder="Masukkan email anda"
                        class="mt-1 block w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:ring-1 transition duration-150">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-id-card mr-1 text-gray-400"></i> NIK (16 digit)
                    </label>
                    <input type="text" name="nik" value="<?= htmlspecialchars($nik) ?>" required
                        pattern="\d{16}" maxlength="16" placeholder="Masukkan 16 digit NIK"
                        class="mt-1 block w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:ring-1 transition duration-150">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-lock mr-1 text-gray-400"></i> Password
                    </label>
                    <div class="relative">
                        <input type="password" name="password" required id="password"
                            placeholder="Masukkan password anda"
                            class="mt-1 block w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:ring-1 transition duration-150">
                        <button type="button" onclick="togglePassword()"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="/sistem"
                    class="inline-flex items-center text-sm text-blue-600 hover:text-blue-700 transition duration-150">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Beranda
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 text-sm text-gray-600">
            &copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. All rights reserved.
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (password.type === 'password') {
                password.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Validasi NIK hanya angka
        document.querySelector('input[name="nik"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 16);
        });

        // Animate flash messages fadeout
        setTimeout(() => {
            const flashMessages = document.querySelectorAll('.bg-blue-100, .bg-red-100');
            flashMessages.forEach(message => {
                message.style.transition = 'opacity 0.5s ease-in-out';
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            });
        }, 5000);
    </script>

    <!-- Add SweetAlert2 for better alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>