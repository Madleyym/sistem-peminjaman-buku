<?php
session_start();
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../classes/User.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: /sistem/admin/index.php");
    exit();
}

// Inisialisasi variabel
$username = $email = '';
$errors = [];

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitasi input
    $username = trim(filter_var($_POST['username'] ?? '', FILTER_SANITIZE_STRING));
    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi input
    if (empty($username)) {
        $errors[] = "Username harus diisi";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username minimal 3 karakter";
    } elseif (strlen($username) > 50) {
        $errors[] = "Username maksimal 50 karakter";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username hanya boleh berisi huruf, angka, dan underscore";
    }

    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    } elseif (strlen($email) > 100) {
        $errors[] = "Email terlalu panjang";
    }

    if (empty($password)) {
        $errors[] = "Password harus diisi";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter";
    } elseif (strlen($password) > 255) {
        $errors[] = "Password terlalu panjang";
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/', $password)) {
        $errors[] = "Password harus mengandung huruf & angka";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok";
    }

    // Jika tidak ada error
    if (empty($errors)) {
        try {
            // Koneksi database
            $database = new Database();
            $conn = $database->getConnection();

            // Buat objek user
            $user = new User($conn);

            // Lakukan proses registrasi
            $result = $user->adminRegister([
                'username' => $username,
                'email' => $email,
                'password' => $password
            ]);

            // Jika registrasi berhasil
            if ($result['status']) {
                $_SESSION['registration_success'] = true;
                $_SESSION['success_message'] = "Registrasi admin berhasil. Silakan login.";
                header("Location: login.php");
                exit();
            } else {
                $errors[] = $result['message'];
            }
        } catch (Exception $e) {
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Admin - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sistem/admin/auth/auth-styles.css">
</head>

<body class="bg-gray-50 font-inter min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white shadow-2xl rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-white text-center">
            <h2 class="text-3xl font-bold">Admin <?= htmlspecialchars(SITE_NAME) ?></h2>
            <p class="text-blue-100 mt-2">Registrasi Administrator</p>
        </div>

        <form action="register.php" method="POST" class="p-8">
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label for="username" class="block text-gray-700 font-bold mb-2">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    value="<?= htmlspecialchars($username) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    value="<?= htmlspecialchars($email) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-bold mb-2">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 font-bold mb-2">Konfirmasi Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-300 font-bold">
                Daftar Admin
            </button>
        </form>
    </div>
</body>

</html>