<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Define all constants first
define('LOGIN_URL', '/sistem/public/auth/login.php');
define('BOOKS_PATH', '/sistem/public/daftar-buku.php');
define('REGISTER_PATH', '/sistem/public/auth/register.php');
define('SITE_NAME', 'Sistem Perpustakaan');
define('CURRENT_URL', $_SERVER['REQUEST_URI']);

// Book cover paths
define('UPLOAD_BOOK_COVERS_PATH', '/sistem/uploads/book_covers/');
define('DEFAULT_BOOK_COVER_PATH', '/sistem/uploads/books/book-default.png');
define('UPLOAD_BOOK_COVERS_URL', '/sistem/uploads/book_covers/');
define('DEFAULT_BOOK_COVER_URL', '/sistem/uploads/books/book-default.png');

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_BOOK_COVERS_PATH)) {
    mkdir(UPLOAD_BOOK_COVERS_PATH, 0777, true);
}

// Include required files
require_once $_SERVER['DOCUMENT_ROOT'] . '/sistem/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/sistem/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/sistem/classes/Book.php';

// Rest of your code...

// Check login status
$isLoggedIn = !empty($_SESSION['user_id']);

// Validate book ID
$book_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$book_id) {
    header('Location: ' . BOOKS_PATH);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
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

    <style>
        :root {
            --primary-color: #3498db;
            /* Indigo-600 menggantikan blue */
            --secondary-color: #2ecc71;
            --accent-color: #e74c3c;
            --text-color: #2c3e50;
            --background-color: #f4f7f6;
            --white: #ffffff;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --border-radius: 16px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
            min-height: 100vh;
        }

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

        .button-primary {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .button-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

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
        }

        .metadata-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-color);
            color: var(--white);
            border-radius: 50%;
        }

        .book-cover-container img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 20px var(--shadow-color);
            transition: transform 0.3s ease;
        }

        .book-cover-container img:hover {
            transform: scale(1.02);
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
                                <a href="/sistem/public/pinjam-buku.php?id=<?= $book_id ?>"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white py-3 px-6 rounded-lg block w-full text-center transition-all duration-300 transform hover:-translate-y-1">
                                    <i class="fas fa-book-reader mr-2"></i>
                                    Pinjam Buku
                                </a>
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
                qrCodeContainer.innerHTML = ''; // Clear previous QR code
                QRCode.toCanvas(qrCodeContainer, qrData, {
                    width: 256,
                    height: 256,
                    margin: 2,
                    color: {
                        dark: '#4338ca', // Indigo-700 untuk konsistensi dengan tema
                        light: '#ffffff'
                    }
                }, function(error) {
                    if (error) console.error(error);
                    console.log('QR Code generated successfully');
                });
            }

            // Show Modal
            if (showQRCodeBtn) {
                showQRCodeBtn.addEventListener('click', function() {
                    qrModal.classList.remove('hidden');
                    generateQRCode();
                    // Tambahkan animasi fade in
                    requestAnimationFrame(() => {
                        qrModal.style.opacity = '1';
                    });
                });
            }

            // Close Modal
            if (closeQRModal) {
                closeQRModal.addEventListener('click', function() {
                    // Tambahkan animasi fade out
                    qrModal.style.opacity = '0';
                    setTimeout(() => {
                        qrModal.classList.add('hidden');
                    }, 300);
                });
            }

            // Close Modal when clicking outside
            if (qrModal) {
                qrModal.addEventListener('click', function(e) {
                    if (e.target === qrModal) {
                        // Tambahkan animasi fade out
                        qrModal.style.opacity = '0';
                        setTimeout(() => {
                            qrModal.classList.add('hidden');
                        }, 300);
                    }
                });
            }

            // Close Modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && qrModal && !qrModal.classList.contains('hidden')) {
                    // Tambahkan animasi fade out
                    qrModal.style.opacity = '0';
                    setTimeout(() => {
                        qrModal.classList.add('hidden');
                    }, 300);
                }
            });
        });
    </script>
</body>

</html>