<?php
session_start();
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../classes/User.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Redirect to dashboard if already logged in


if (isset($_SESSION['user_id'])) {
    // Jika pengguna sudah login, arahkan ke halaman login
    header('Location: /sistem/public/auth/login.php');
    exit();
}


// Inisialisasi variabel
$name = '';
$email = '';
$phone_number = '';
$address = '';
$errors = [];
$result = null;

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitasi input
    $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone_number = filter_var($_POST['phone_number'] ?? '', FILTER_SANITIZE_STRING);
    $address = filter_var($_POST['address'] ?? '', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi input
    $errors = [];
    if (empty($name)) {
        $errors[] = "Nama harus diisi";
    }

    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }

    if (empty($phone_number)) {
        $errors[] = "Nomor telepon harus diisi";
    }

    if (empty($address)) {
        $errors[] = "Alamat harus diisi";
    }

    if (empty($password)) {
        $errors[] = "Password harus diisi";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok";
    }

    // Jika tidak ada error
    if (empty($errors)) {
        // Koneksi database
        $database = new Database();
        $conn = $database->getConnection();

        // Buat objek user
        $user = new User($conn);

        // Lakukan proses registrasi
        $result = $user->register($name, $email, $password, $phone_number, $address);

        // Jika registrasi berhasil
        if ($result['status']) {
            // Langsung login
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['user_name'] = $result['user']['name'];
            $_SESSION['user_email'] = $result['user']['email'];

            // Redirect ke halaman dashboard
            header("Location: /sistem/public/auth/login.php");
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
    <title>Daftar - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sistem/public/auth/auth-styles.css">
</head>

<body class="bg-gray-50 font-inter min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white shadow-2xl rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-emerald-700 p-6 text-white text-center">
            <h2 class="text-3xl font-bold"><?= htmlspecialchars(SITE_NAME) ?></h2>
            <p class="text-green-100 mt-2">Buat Akun Baru</p>
        </div>

        <form action="register.php" method="POST" class="p-8">
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
                <label for="name" class="block text-gray-700 font-bold mb-2">Nama Lengkap</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                    value="<?= htmlspecialchars($name) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    value="<?= htmlspecialchars($email) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div class="mb-4">
                <label for="phone_number" class="block text-gray-700 font-bold mb-2">Nomor Telepon</label>
                <input
                    type="tel"
                    id="phone_number"
                    name="phone_number"
                    required
                    value="<?= htmlspecialchars($phone_number) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div class="mb-4">
                <label for="address" class="block text-gray-700 font-bold mb-2">Alamat</label>
                <textarea
                    id="address"
                    name="address"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"><?= htmlspecialchars($address) ?></textarea>
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-bold mb-2">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 font-bold mb-2">Konfirmasi Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <button
                type="submit"
                class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition duration-300 font-bold">
                Daftar
            </button>

            <div class="text-center mt-4">
                <p class="text-gray-600">
                    Sudah punya akun?
                    <a href="login.php" class="text-green-600 hover:underline">Login di sini</a>
                </p>
            </div>
        </form>
    </div>
</body>

</html>