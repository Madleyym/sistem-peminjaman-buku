<?php
// Pengaturan session
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_start();

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../classes/User.php';

// Only admin can register new staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /sistem/petugas/auth/login.php");
    exit();
}

// Inisialisasi variabel
$result = null;
$name = $email = $username = '';
$errors = [];

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitasi input
    $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $username = filter_var($_POST['username'] ?? '', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi input
    if (empty($name)) {
        $errors[] = "Nama harus diisi";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email tidak valid";
    }
    if (empty($username)) {
        $errors[] = "Username harus diisi";
    }
    if (empty($password)) {
        $errors[] = "Password harus diisi";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Password tidak cocok";
    }

    // Jika tidak ada error
    if (empty($errors)) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            $user = new User($conn);

            $result = $user->registerStaff([
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'password' => $password,
                'role' => 'staff'
            ]);

            if ($result['status']) {
                header("Location: /sistem/admin/staff-list.php?success=1");
                exit();
            } else {
                $errors[] = "Registrasi gagal: " . ($result['message'] ?? 'Terjadi kesalahan');
            }
        } catch (Exception $e) {
            $errors[] = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Petugas Baru - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sistem/petugas/auth/auth-styles.css">
</head>

<body class="bg-gray-50 font-inter min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white shadow-2xl rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-teal-700 p-6 text-white text-center">
            <h2 class="text-3xl font-bold">Registrasi Petugas Baru</h2>
            <p class="text-green-100 mt-2">Tambah Petugas Perpustakaan</p>
        </div>

        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" class="p-8">
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-bold mb-2">Nama Lengkap</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($name) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div class="mb-4">
                <label for="username" class="block text-gray-700 font-bold mb-2">Username</label>
                <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-bold mb-2">Password</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 font-bold mb-2">Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <button type="submit"
                class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition duration-300 font-bold">
                Daftarkan Petugas
            </button>
        </form>
    </div>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>