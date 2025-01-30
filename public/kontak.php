<?php
session_start();
require_once '../config/constants.php';
require_once '../config/database.php';

// Regenerate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Retrieve and clear messages/errors
$messages = isset($_SESSION['message']) ? $_SESSION['message'] : null;
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : null;
unset($_SESSION['message'], $_SESSION['errors']);

// Page configuration
$pageTitle = SITE_NAME . " - Kontak";
$pageDescription = "Hubungi Kami untuk Pertanyaan dan Dukungan";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="/sistem/includes/styles.css" rel="stylesheet">
    <style>
        /* Custom styles specific to contact page */
        .contact-form input:focus,
        .contact-form textarea:focus {
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
        }
    </style>
</head>

<body class="bg-gray-50 font-inter min-h-screen flex flex-col">
    <?php include __DIR__ . '/../includes/navigation.php'; ?>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-2xl shadow-2xl overflow-hidden mb-12 p-8">
            <div class="max-w-4xl mx-auto grid md:grid-cols-2 gap-8">
                <div>
                    <h1 class="text-4xl font-bold mb-4">Hubungi Kami</h1>
                    <p class="text-xl opacity-90 mb-6"><?= htmlspecialchars($pageDescription) ?></p>
                    <div class="space-y-4 text-white">
                        <p class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Jalan Perpustakaan No. 123, Kota Buku
                        </p>
                        <p class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            (022) 1234 5678
                        </p>
                        <p class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            info@<?= strtolower(str_replace(' ', '', SITE_NAME)) ?>.com
                        </p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-8 text-gray-800">
                    <h2 class="text-3xl font-bold text-blue-700 mb-6">Kirim Pesan</h2>
                    <form action="submit_contact.php" method="POST" class="space-y-4 contact-form">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <?php if ($messages): ?>
                            <div class="bg-<?= $messages['type'] === 'error' ? 'red' : 'green' ?>-100 border border-<?= $messages['type'] === 'error' ? 'red' : 'green' ?>-400 text-<?= $messages['type'] === 'error' ? 'red' : 'green' ?>-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <strong class="font-bold"><?= $messages['type'] === 'error' ? 'Error!' : 'Success!' ?></strong>
                                <span class="block sm:inline"><?= htmlspecialchars($messages['text']) ?></span>
                            </div>
                        <?php endif; ?>

                        <div>
                            <input type="text" name="name" placeholder="Nama Anda" required
                                class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <input type="email" name="email" placeholder="Email Anda" required
                                class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <textarea name="message" placeholder="Pesan Anda" rows="5" required
                                class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        <div>
                            <button type="submit"
                                class="w-full bg-green-500 text-white py-3 rounded-lg hover:bg-green-600 transition duration-300 transform hover:-translate-y-1">
                                Kirim Pesan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Additional JavaScript if needed
    </script>
</body>
</html>