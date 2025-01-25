<?php
session_start();
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../classes/User.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /sistem/public/auth/users/dashboard.php");
    exit();
}

// Inisialisasi variabel
$result = null;
$email = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitasi input
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Validasi input
    $errors = [];
    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
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
        $result = $user->login($email, $password);

        // Jika login berhasil, mulai session
        if ($result['status']) {
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['user_name'] = $result['user']['name'];
            $_SESSION['user_email'] = $result['user']['email'];

            // Redirect ke halaman dashboard
            header("Location: /sistem/public/auth/users/dashboard.php");
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
    <title>Login - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sistem/public/auth/auth-styles.css">
</head>

<body class="bg-gray-50 font-inter min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white shadow-2xl rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-white text-center">
            <h2 class="text-3xl font-bold"><?= htmlspecialchars(SITE_NAME) ?></h2>
            <p class="text-blue-100 mt-2">Masuk ke akun Anda</p>
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
                <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    value="<?= htmlspecialchars($email) ?>"
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
                Login
            </button>

            <div class="text-center mt-4">
                <p class="text-gray-600">
                    Belum punya akun?
                    <a href="register.php" class="text-blue-600 hover:underline">Daftar di sini</a>
                </p>
            </div>
        </form>
    </div>

    <script>
        // Tampilkan popup jika registrasi berhasil
        <?php if (isset($_SESSION['registration_success']) && $_SESSION['registration_success'] === true): ?>
            Swal.fire({
                icon: 'success',
                title: 'Pendaftaran Berhasil!',
                text: 'Silakan login dengan akun Anda',
                confirmButtonText: 'OK'
            });
            <?php unset($_SESSION['registration_success']); ?>
        <?php endif; ?>
    </script>
</body>

</html>