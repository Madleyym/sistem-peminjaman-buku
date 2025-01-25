<?php
session_start();
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../classes/User.php';

// Redirect to admin dashboard if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: /sistem/admin/admin-index.php");
    exit();
}

// Inisialisasi variabel
$result = null;
$email = $nik = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitasi input
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $nik = filter_var($_POST['nik'] ?? '', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';

    // Validasi input
    $errors = [];
    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }

    if (empty($nik)) {
        $errors[] = "NIK harus diisi";
    } elseif (!preg_match('/^\d{16}$/', $nik)) {
        $errors[] = "NIK harus terdiri dari 16 digit angka";
    }

    if (empty($password)) {
        $errors[] = "Password harus diisi";
    }

    // Jika tidak ada error
    if (empty($errors)) {
        // Koneksi database
        $database = new Database();
        $conn = $database->getConnection();

        // Buat objek user
        $user = new User($conn);

        // Lakukan proses login
        $result = $user->adminLoginWithNIK($email, $nik, $password);

        // Jika login berhasil, mulai session
        if ($result['status']) {
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['user_name'] = $result['user']['name'];
            $_SESSION['user_email'] = $result['user']['email'];
            $_SESSION['role'] = 'admin';

            // Redirect ke halaman admin dashboard
            header("Location: /sistem/admin/admin-index.php");
            exit();
        }
    }
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
    <link rel="stylesheet" href="/sistem/admin/auth/auth-styles.css">
</head>

<body class="bg-gray-50 font-inter min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white shadow-2xl rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-white text-center">
            <h2 class="text-3xl font-bold">Admin <?= htmlspecialchars(SITE_NAME) ?></h2>
            <p class="text-blue-100 mt-2">Login Area Administrator</p>
        </div>

        <form action="login.php" method="POST" class="p-8">
            <?php if ($result && !$result['status']): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <?= htmlspecialchars($result['message']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-bold mb-2">Email Admin</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    value="<?= htmlspecialchars($email) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="nik" class="block text-gray-700 font-bold mb-2">NIK Admin</label>
                <input
                    type="text"
                    id="nik"
                    name="nik"
                    required
                    pattern="\d{16}"
                    maxlength="16"
                    value="<?= htmlspecialchars($nik) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label for="password" class="block text-gray-700 font-bold mb-2">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-300 font-bold">
                Login Admin
            </button>
        </form>
    </div>
</body>

</html>