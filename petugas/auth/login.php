<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_start();

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../classes/User.php';

// Cek jika sudah login
if (isset($_SESSION['role'])) {
    // Gunakan getRedirectPath untuk mengarahkan ke halaman yang sesuai
    header("Location: " . User::getRedirectPath($_SESSION['role']));
    exit();
}

// Inisialisasi variabel
$result = null;
$email = '';
$errors = [];

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitasi input
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Validasi input
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email tidak valid";
    }
    if (empty($password)) {
        $errors[] = "Password harus diisi";
    }

    // Jika tidak ada error
    if (empty($errors)) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            $user = new User($conn);

            // Gunakan method login yang baru
            $result = $user->login($email, $password);

            if ($result['status']) {
                // Verifikasi bahwa yang login adalah staff
                if ($result['role'] === User::getRoles()['STAFF']) {
                    // Regenerate session ID for security
                    session_regenerate_id(true);

                    // Set session variables
                    $_SESSION['user_id'] = $result['user']['id'];
                    $_SESSION['user_name'] = $result['user']['name'] ?? $result['user']['username'];
                    $_SESSION['user_email'] = $result['user']['email'];
                    $_SESSION['role'] = $result['role'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = date('Y-m-d H:i:s');

                    // Redirect ke halaman staff menggunakan getRedirectPath
                    header("Location: " . User::getRedirectPath($_SESSION['role']));
                    exit();
                } else {
                    // Jika bukan staff, arahkan ke halaman login yang sesuai
                    header("Location: " . User::getLoginPath($result['role']));
                    exit();
                }
            } else {
                $errors[] = $result['message'] ?? 'Email atau password salah';
            }
        } catch (Exception $e) {
            $errors[] = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Petugas - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sistem/petugas/auth/auth-styles.css">
</head>

<body class="bg-gray-50 font-inter min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white shadow-2xl rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-teal-700 p-6 text-white text-center">
            <h2 class="text-3xl font-bold">Area Petugas <?= htmlspecialchars(SITE_NAME) ?></h2>
            <p class="text-green-100 mt-2">Login Petugas Perpustakaan</p>
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
                <label for="email" class="block text-gray-700 font-bold mb-2">Email Petugas</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    value="<?= htmlspecialchars($email) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div class="mb-6">
                <label for="password" class="block text-gray-700 font-bold mb-2">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <button
                type="submit"
                class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition duration-300 font-bold">
                Login Petugas
            </button>
        </form>
    </div>

    <script>
        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>