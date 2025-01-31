<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Define all constants first
define('LOGIN_URL', '/sistem/public/auth/login.php');
define('LOGOUT_URL', '/sistem/public/auth/logout.php'); // Tambahkan ini
define('BOOKS_PATH', '/sistem/public/daftar-buku.php');
define('REGISTER_PATH', '/sistem/public/auth/register.php');
define('SITE_NAME', 'Sistem Perpustakaan');
define('CURRENT_URL', $_SERVER['REQUEST_URI']);

// Strict login check
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . LOGIN_URL);
    exit();
}

// Validate session
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // If last activity was more than 30 minutes ago
    session_unset();
    session_destroy();
    header('Location: ' . LOGIN_URL . '?session=expired');
    exit();
}
$_SESSION['last_activity'] = time(); // Update last activity time

// Book cover paths
define('UPLOAD_BOOK_COVERS_PATH', '/sistem/uploads/book_covers/');
define('DEFAULT_BOOK_COVER_PATH', '/sistem/uploads/books/book-default.png');
define('UPLOAD_BOOK_COVERS_URL', '/sistem/uploads/book_covers/');
define('DEFAULT_BOOK_COVER_URL', '/sistem/uploads/books/book-default.png');

// Check if directory exists and create if it doesn't
if (!file_exists(UPLOAD_BOOK_COVERS_PATH)) {
    mkdir(UPLOAD_BOOK_COVERS_PATH, 0777, true);
}

// Include required files
require_once $_SERVER['DOCUMENT_ROOT'] . '/sistem/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/sistem/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/sistem/classes/Book.php';

// Check if user is logged in, if not redirect to login
if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . LOGIN_URL);
    exit();
}

// Initialize database connection
try {
    $database = new Database();
    $conn = $database->getConnection();
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    header('Location: ' . LOGIN_URL . '?error=database');
    exit();
}

// Fetch user data
try {
    $userQuery = $conn->prepare("SELECT id, name, email, phone_number, address FROM users WHERE id = ?");
    $userQuery->execute([$_SESSION['user_id']]);
    $userData = $userQuery->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        session_destroy();
        header('Location: ' . LOGIN_URL . '?error=invalid_user');
        exit();
    }
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    header('Location: ' . LOGIN_URL . '?error=user_data');
    exit();
}

// Validate and fetch book data
$book_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$book_id) {
    header('Location: ' . BOOKS_PATH);
    exit();
}

try {
    $bookManager = new Book($conn);
    $book = $bookManager->getBookById($book_id);

    if (!$book) {
        header('Location: ' . BOOKS_PATH);
        exit();
    }
} catch (Exception $e) {
    error_log("Book Detail Error: " . $e->getMessage());
    header('Location: ' . BOOKS_PATH);
    exit();
}

// Set login status after all checks pass
$isLoggedIn = true;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Buku - <?= htmlspecialchars($book['title']) ?> | <?= SITE_NAME ?></title>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.4.4/build/qrcode.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#4F46E5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #e74c3c;
            --text-color: #2c3e50;
            --background-color: #f4f7f6;
            --white: #ffffff;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --border-radius: 16px;
            --modal-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
            min-height: 100vh;
        }

        /* Existing styles */
        .book-detail-container {
            background: linear-gradient(135deg, #f5f7fa 0%, #f4f7f6 100%);
            box-shadow: 0 15px 30px var(--shadow-color);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
        }

        /* Enhanced Modal Styles */
        #userDataModal {
            perspective: 1000px;
        }

        #userDataModal .bg-white {
            backdrop-filter: blur(16px);
            box-shadow: var(--modal-shadow);
            transform-origin: center;
            animation: modalOpen 0.4s ease-out;
        }

        @keyframes modalOpen {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-10px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Form Elements Styling */
        .form-input {
            @apply w-full px-4 py-3 rounded-lg border transition-all duration-200;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-input:focus {
            @apply ring-2 ring-blue-500 border-transparent;
            transform: translateY(-1px);
        }

        .form-input:disabled,
        .form-input[readonly] {
            background: rgba(243, 244, 246, 0.9);
        }

        /* Button Styles */
        .button-primary {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.25);
        }

        .button-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(52, 152, 219, 0.3);
        }

        /* Book Metadata Styling */
        .book-metadata {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .metadata-item {
            display: flex;
            align-items: start;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .metadata-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .metadata-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-center: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, #2980b9 100%);
            color: var(--white);
            border-radius: 50%;
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.2);
        }

        /* Book Cover Styling */
        .book-cover-container img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: var(--border-radius);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .book-cover-container img:hover {
            transform: scale(1.02) translateY(-5px);
            box-shadow: 0 25px 30px -5px rgba(0, 0, 0, 0.15),
                0 15px 15px -5px rgba(0, 0, 0, 0.08);
        }

        /* Modal Animation */
        .modal-enter-active,
        .modal-leave-active {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .modal-enter,
        .modal-leave-to {
            opacity: 0;
            transform: scale(0.95);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #2980b9;
        }

        /* Input Focus States */
        input:focus,
        textarea:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        /* Responsive Typography */
        @media (max-width: 640px) {
            body {
                font-size: 14px;
            }

            .book-cover-container img {
                height: 300px;
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include __DIR__ . '/../includes/navigation.php'; ?>

    <main class="flex-grow container mx-auto px-4 py-8">
        <!-- Login Modal for Non-logged Users -->
        <?php if (!$isLoggedIn): ?>
            <div class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm">
                <div class="bg-white rounded-xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 shadow-2xl">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-lock text-indigo-600 text-3xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">Login Diperlukan</h2>
                        <p class="text-gray-600 mb-8">
                            Silakan login terlebih dahulu untuk melihat detail buku.
                        </p>
                        <div class="space-y-4">
                            <a href="<?= LOGIN_URL ?>?redirect=<?= urlencode(CURRENT_URL) ?>"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg block w-full text-center transition-all duration-300 transform hover:-translate-y-1">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Login Sekarang
                            </a>
                            <a href="<?= REGISTER_PATH ?>"
                                class="bg-gray-100 text-gray-700 py-2 px-4 rounded-lg block w-full text-center hover:bg-gray-200 transition-all duration-300 transform hover:-translate-y-1">
                                <i class="fas fa-user-plus mr-2"></i>
                                Daftar Akun Baru
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Book Content -->
        <div class="<?= !$isLoggedIn ? 'blur-sm' : '' ?> bg-white rounded-xl p-8 shadow-lg">
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Book Cover -->
                <div class="book-cover-container">
                    <img src="<?= !empty($book['cover_image'])
                                    ? UPLOAD_BOOK_COVERS_URL . htmlspecialchars($book['cover_image'])
                                    : DEFAULT_BOOK_COVER_URL ?>"
                        alt="<?= htmlspecialchars($book['title']) ?>"
                        class="w-full h-[500px] object-cover rounded-xl shadow-lg transition-transform duration-300 hover:scale-105"
                        onerror="this.src='<?= DEFAULT_BOOK_COVER_URL ?>'">
                </div>

                <!-- Book Details -->
                <div class="space-y-6">
                    <h1 class="text-3xl font-bold text-gray-800 border-b border-gray-200 pb-4">
                        <?= htmlspecialchars($book['title']) ?>
                    </h1>

                    <!-- Book Metadata -->
                    <div class="grid grid-cols-2 gap-6">
                        <?php
                        $metadata = [
                            ['icon' => 'user-edit', 'label' => 'Penulis', 'value' => $book['author']],
                            ['icon' => 'building', 'label' => 'Penerbit', 'value' => $book['publisher']],
                            ['icon' => 'calendar-alt', 'label' => 'Tahun Terbit', 'value' => $book['year_published']],
                            ['icon' => 'barcode', 'label' => 'ISBN', 'value' => $book['isbn']],
                            ['icon' => 'tag', 'label' => 'Kategori', 'value' => $book['category']],
                            ['icon' => 'map-marker-alt', 'label' => 'Lokasi Rak', 'value' => $book['shelf_location']]
                        ];

                        foreach ($metadata as $item): ?>
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-<?= $item['icon'] ?> text-indigo-600"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm text-gray-500"><?= $item['label'] ?></h4>
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($item['value']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Availability Info -->
                    <div class="bg-indigo-50 p-6 rounded-xl">
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div>
                                <h4 class="text-sm text-gray-500">Total Buku</h4>
                                <p class="text-2xl font-bold text-indigo-600">
                                    <?= htmlspecialchars($book['total_quantity']) ?>
                                </p>
                            </div>
                            <div>
                                <h4 class="text-sm text-gray-500">Tersedia</h4>
                                <p class="text-2xl font-bold <?= $book['available_quantity'] > 0 ?
                                                                    'text-emerald-600' : 'text-red-600' ?>">
                                    <?= htmlspecialchars($book['available_quantity']) ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code Button -->
                    <button id="showQRCode"
                        class="bg-indigo-500 hover:bg-indigo-600 text-white py-3 px-6 rounded-lg block w-full text-center transition-all duration-300 transform hover:-translate-y-1 mb-3">
                        <i class="fas fa-qrcode mr-2"></i>
                        Tampilkan QR Code
                    </button>

                    <!-- QR Code Modal -->
                    <div id="qrModal" class="fixed inset-0 z-50 hidden">
                        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
                        <div class="absolute inset-0 flex items-center justify-center p-4">
                            <div class="bg-white rounded-xl p-6 max-w-sm w-full mx-auto shadow-2xl transform transition-all duration-300">
                                <div class="text-center">
                                    <h3 class="text-xl font-bold text-gray-800 mb-4">QR Code Peminjaman</h3>
                                    <div id="qrCodeContainer" class="flex justify-center mb-4">
                                        <!-- QR code will be generated here -->
                                    </div>
                                    <p class="text-sm text-gray-600 mb-4">
                                        Tunjukkan QR code ini kepada petugas perpustakaan untuk meminjam buku
                                    </p>
                                    <div class="text-sm text-gray-600 mb-4">
                                        <p class="font-semibold">Detail Buku:</p>
                                        <p><?= htmlspecialchars($book['title']) ?></p>
                                        <p>ISBN: <?= htmlspecialchars($book['isbn']) ?></p>
                                    </div>
                                    <button id="closeQRModal"
                                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg transition-all duration-300">
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if ($isLoggedIn): ?>
                        <div class="mt-6">
                            <?php if ($book['available_quantity'] > 0): ?>
                                <!-- Di bagian tombol Pinjam Buku -->
                                <button type="button"
                                    class="pinjamBukuBtn bg-indigo-600 hover:bg-indigo-700 text-white py-3 px-6 rounded-lg block w-full text-center transition-all duration-300 transform hover:-translate-y-1">
                                    <i class="fas fa-book-reader mr-2"></i>
                                    Pinjam Buku
                                </button>
                            <?php else: ?>
                                <button disabled
                                    class="bg-gray-400 text-white w-full py-3 px-6 rounded-lg cursor-not-allowed">
                                    <i class="fas fa-times-circle mr-2"></i>
                                    Stok Tidak Tersedia
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal Form for User Data -->
            <div id="userDataModal" class="fixed inset-0 z-50 hidden opacity-0 transition-opacity duration-300">
                <!-- Backdrop with blur effect -->
                <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-filter backdrop-blur-sm"></div>

                <!-- Modal Container -->
                <div class="absolute inset-0 flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-auto shadow-2xl transform transition-all duration-300 space-y-6">
                        <!-- Header -->
                        <div class="text-center mb-6">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 mb-4">
                                <i class="fas fa-user-edit text-2xl text-indigo-600"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800">Konfirmasi Data Peminjaman</h3>
                            <p class="text-gray-500 mt-2">Silakan periksa kembali data Anda sebelum melanjutkan</p>
                        </div>

                        <!-- Form -->
                        <form id="userDataForm" method="POST" action="/sistem/user/proses-user-data.php" class="space-y-6">
                            <!-- Personal Information Section -->
                            <div class="space-y-4">
                                <!-- Name Field -->
                                <div class="relative">
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-user text-indigo-600 mr-2"></i>Nama Lengkap
                                    </label>
                                    <input type="text" id="name" name="name"
                                        value="<?= htmlspecialchars($userData['name']) ?>"
                                        required
                                        class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-50 text-gray-800 font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200"
                                        readonly>
                                </div>

                                <!-- Email Field -->
                                <div class="relative">
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-envelope text-indigo-600 mr-2"></i>Email
                                    </label>
                                    <input type="email" id="email" name="email"
                                        value="<?= htmlspecialchars($userData['email']) ?>"
                                        required
                                        class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-50 text-gray-800 font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200"
                                        readonly>
                                </div>

                                <!-- Phone Number Field -->
                                <div class="relative">
                                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-phone text-indigo-600 mr-2"></i>Nomor Telepon
                                    </label>
                                    <input type="tel" id="phone_number" name="phone_number"
                                        value="<?= htmlspecialchars($userData['phone_number']) ?>"
                                        required pattern="[0-9]{10,13}"
                                        title="Masukkan nomor telepon valid (10-13 digit)"
                                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200"
                                        placeholder="Contoh: 08123456789">
                                </div>

                                <!-- Address Field -->
                                <div class="relative">
                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-map-marker-alt text-indigo-600 mr-2"></i>Alamat Lengkap
                                    </label>
                                    <textarea id="address" name="address" required
                                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 resize-none"
                                        rows="3"
                                        placeholder="Masukkan alamat lengkap Anda"><?= htmlspecialchars($userData['address']) ?></textarea>
                                </div>
                            </div>

                            <!-- Hidden Fields -->
                            <input type="hidden" name="book_id" value="<?= htmlspecialchars($book_id) ?>">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($userData['id']) ?>">
                            <input type="hidden" name="borrow_date" value="<?= date('Y-m-d H:i:s') ?>">
                            <input type="hidden" name="status" value="pending">

                            <!-- Action Buttons -->
                            <div class="flex flex-col space-y-3">
                                <button type="submit"
                                    class="flex items-center justify-center w-full px-6 py-3 text-base font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transform transition-all duration-200 hover:-translate-y-1">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    Konfirmasi Peminjaman
                                </button>

                                <button type="button" id="closeUserDataModal"
                                    class="flex items-center justify-center w-full px-6 py-3 text-base font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transform transition-all duration-200">
                                    <i class="fas fa-times-circle mr-2"></i>
                                    Batal
                                </button>
                            </div>
                        </form>

                        <!-- Current Date Info -->
                        <div class="text-center text-sm text-gray-500 mt-4">
                            <p>Tanggal Peminjaman: <?= date('d F Y H:i') ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Description Section -->
            <div class="mt-8">
                <h3 class="text-2xl font-semibold text-gray-800 mb-4">
                    <i class="fas fa-info-circle text-indigo-600 mr-2"></i>
                    Deskripsi Buku
                </h3>
                <div class="bg-gray-50 p-6 rounded-xl">
                    <p class="text-gray-700 leading-relaxed">
                        <?= nl2br(htmlspecialchars($book['description'])) ?>
                    </p>
                </div>
            </div>

        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Default image path
        const DEFAULT_BOOK_COVER = '<?= DEFAULT_BOOK_COVER_URL ?>';

        // Handle image loading errors
        document.addEventListener('error', function(e) {
            if (e.target.tagName.toLowerCase() === 'img') {
                e.target.src = DEFAULT_BOOK_COVER;
            }
        }, true);

        // Function to check if image exists
        function checkImage(url) {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => resolve(true);
                img.onerror = () => resolve(false);
                img.src = url;
            });
        }

        // Form validation functions
        function validatePhoneNumber(phone) {
            const phoneRegex = /^[0-9]{10,13}$/;
            return phoneRegex.test(phone);
        }

        function validateAddress(address) {
            return address.trim().length >= 10;
        }

        // Main initialization when DOM is loaded
        document.addEventListener('DOMContentLoaded', async function() {
            // Enable smooth scrolling
            document.documentElement.style.scrollBehavior = 'smooth';

            // Handle modal animation
            const modal = document.querySelector('.modal-backdrop');
            if (modal) {
                modal.style.opacity = '0';
                requestAnimationFrame(() => {
                    modal.style.transition = 'opacity 0.3s ease';
                    modal.style.opacity = '1';
                });
            }

            // Add enhanced hover effects
            const interactiveElements = document.querySelectorAll('a:not([disabled]), button:not([disabled])');
            interactiveElements.forEach(element => {
                element.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('cursor-not-allowed')) {
                        this.style.transform = 'translateY(-2px)';
                        this.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1)';
                    }
                });
                element.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('cursor-not-allowed')) {
                        this.style.transform = 'translateY(0)';
                        this.style.boxShadow = '';
                    }
                });
            });

            // Check book cover image
            const bookCover = document.querySelector('.book-cover-container img');
            if (bookCover) {
                const imageExists = await checkImage(bookCover.src);
                if (!imageExists) {
                    bookCover.src = DEFAULT_BOOK_COVER;
                }
            }

            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetElement = document.querySelector(this.getAttribute('href'));
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // QR Code Modal Functionality
            const showQRCodeBtn = document.getElementById('showQRCode');
            const qrModal = document.getElementById('qrModal');
            const closeQRModal = document.getElementById('closeQRModal');
            const qrCodeContainer = document.getElementById('qrCodeContainer');

            // Data untuk QR Code
            const qrData = JSON.stringify({
                book_id: '<?= $book_id ?>',
                isbn: '<?= htmlspecialchars($book['isbn']) ?>',
                title: '<?= htmlspecialchars($book['title']) ?>',
                user: '<?= htmlspecialchars($_SESSION['user_id']) ?>',
                timestamp: '<?= date('Y-m-d H:i:s') ?>',
                request_id: Math.random().toString(36).substring(2, 15)
            });

            // Generate QR Code
            function generateQRCode() {
                qrCodeContainer.innerHTML = '';
                QRCode.toCanvas(qrCodeContainer, qrData, {
                    width: 256,
                    height: 256,
                    margin: 2,
                    color: {
                        dark: '#4338ca',
                        light: '#ffffff'
                    }
                }, function(error) {
                    if (error) console.error(error);
                    console.log('QR Code generated successfully');
                });
            }

            // QR Code Modal Event Listeners
            if (showQRCodeBtn) {
                showQRCodeBtn.addEventListener('click', function() {
                    qrModal.classList.remove('hidden');
                    generateQRCode();
                    requestAnimationFrame(() => {
                        qrModal.style.opacity = '1';
                    });
                });
            }

            if (closeQRModal) {
                closeQRModal.addEventListener('click', function() {
                    qrModal.style.opacity = '0';
                    setTimeout(() => {
                        qrModal.classList.add('hidden');
                    }, 300);
                });
            }

            // User Data Modal Functionality
            const pinjamBukuBtn = document.querySelector('.pinjamBukuBtn');
            const userDataModal = document.getElementById('userDataModal');
            const closeUserDataModal = document.getElementById('closeUserDataModal');
            const userDataForm = document.getElementById('userDataForm');

            // Form submission handling
            if (userDataForm) {
                userDataForm.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    try {
                        // Show loading state
                        Swal.fire({
                            title: 'Memproses...',
                            text: 'Mohon tunggu sebentar',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            willOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        const formData = new FormData(this);

                        // Debug: Log form data
                        for (let pair of formData.entries()) {
                            console.log(pair[0] + ': ' + pair[1]);
                        }

                        const response = await fetch('/sistem/user/proses-user-data.php', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        // Debug: Log raw response
                        const rawResponse = await response.text();
                        console.log('Raw response:', rawResponse);

                        let result;
                        try {
                            result = JSON.parse(rawResponse);
                        } catch (e) {
                            console.error('JSON Parse Error:', e);
                            throw new Error('Server returned invalid JSON response');
                        }

                        if (result.success) {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: result.message || 'Permintaan peminjaman berhasil diajukan',
                                confirmButtonColor: '#4F46E5'
                            });
                            window.location.href = '/sistem/user/pinjaman.php';
                        } else {
                            throw new Error(result.message || 'Terjadi kesalahan saat memproses permintaan');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: error.message || 'Terjadi kesalahan sistem. Silakan coba lagi nanti.',
                            confirmButtonColor: '#4F46E5'
                        });
                    }
                });

                // Real-time phone number validation
                const phoneInput = document.getElementById('phone_number');
                if (phoneInput) {
                    phoneInput.addEventListener('input', function() {
                        this.value = this.value.replace(/[^0-9]/g, '');
                        if (this.value.length > 13) {
                            this.value = this.value.slice(0, 13);
                        }
                    });
                }
            }

            // Modal Event Listeners
            if (pinjamBukuBtn) {
                pinjamBukuBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    userDataModal.classList.remove('hidden');
                    requestAnimationFrame(() => {
                        userDataModal.style.opacity = '1';
                    });
                });
            }

            if (closeUserDataModal) {
                closeUserDataModal.addEventListener('click', function() {
                    userDataModal.style.opacity = '0';
                    setTimeout(() => {
                        userDataModal.classList.add('hidden');
                    }, 300);
                });
            }

            // Close modals when clicking outside
            [qrModal, userDataModal].forEach(modal => {
                if (modal) {
                    modal.addEventListener('click', function(e) {
                        if (e.target === this) {
                            this.style.opacity = '0';
                            setTimeout(() => {
                                this.classList.add('hidden');
                            }, 300);
                        }
                    });
                }
            });

            // Global escape key handler
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    [qrModal, userDataModal].forEach(modal => {
                        if (modal && !modal.classList.contains('hidden')) {
                            modal.style.opacity = '0';
                            setTimeout(() => {
                                modal.classList.add('hidden');
                            }, 300);
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>