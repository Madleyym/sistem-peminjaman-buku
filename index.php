<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Security headers
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Load dependencies
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth-session.php';
require_once __DIR__ . '/classes/Book.php';

// Check session status
if (session_status() === PHP_SESSION_DISABLED) {
    die('Sessions are disabled on this server.');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$currentUser = null;
$isLoggedIn = false;
$errors = [];
$currentDateTime = date('Y-m-d H:i:s');

try {
    // Initialize AuthSession
    $authSession = AuthSession::getInstance(AuthSession::ROLES['USERS']);

    // Check login status
    if (AuthSession::isLoggedIn()) {
        $currentUser = AuthSession::getCurrentUser();
        $isLoggedIn = true;

        // Jika user baru login (redirect dari login page)
        if (
            isset($_SERVER['HTTP_REFERER']) &&
            strpos($_SERVER['HTTP_REFERER'], 'user-login.php') !== false
        ) {
            unset($_SESSION['welcome_shown']); // Reset welcome message
        }

        error_log("User logged in: " . $currentUser['email'] . " at " . $currentDateTime);
    }

    // Validate constants
    if (!defined('SITE_NAME')) {
        throw new Exception('Site configuration error: SITE_NAME is not defined.');
    }

    // Setup upload directory
    $uploadDir = __DIR__ . "/uploads/book_covers/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Set default paths
    $defaultBookCover = '/sistem/uploads/books/book-default.png';
    $defaultAvatar = '/sistem/assets/images/default-avatar.png';

    // Database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Page configuration
    $pageTitle = SITE_NAME . " - Selamat Datang";
    $pageDescription = "Sistem Peminjaman Buku Modern dan Efisien";

    // Fetch new books
    $bookManager = new Book($conn);
    $newBooks = $bookManager->getNewBooks(6);
} catch (Exception $e) {
    error_log("System Error: " . $e->getMessage());
    $errors[] = "Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.";
    $newBooks = [];
}

// Session timeout check
$session_lifetime = 1800; // 30 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_lifetime)) {
    session_unset();
    session_destroy();
    header("Location: /sistem/public/auth/user-login.php?expired=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="theme-color" content="#3498db">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/sistem/assets/favicon.ico">

    <!-- CSS Dependencies -->
    <script src="https://cdn.tailwindcss.com?v=<?= filemtime(__FILE__) ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Structured Data for SEO -->
    <script type="application/ld+json">
        {
            "@context": "http://schema.org",
            "@type": "WebSite",
            "name": "<?= htmlspecialchars(SITE_NAME) ?>",
            "description": "<?= htmlspecialchars($pageDescription) ?>",
            "url": "<?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>"
        }
    </script>

</head>
<style>
    :root {
        /* Color Palette */
        --primary-color: #3498db;
        --secondary-color: #2ecc71;
        --text-color: #2c3e50;
        --background-color: #f4f7f6;
        --white: #ffffff;
        --shadow-color: rgba(0, 0, 0, 0.1);
    }

    /* Reset and Base Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', 'Arial', sans-serif;
        line-height: 1.6;
        color: var(--text-color);
        background-color: var(--background-color);
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* Container */
    .container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
        flex: 1;
    }

    /* Hero Section */
    .hero {
        background: linear-gradient(135deg, var(--primary-color), #2980b9);
        color: var(--white);
        text-align: center;
        padding: 60px 20px;
        border-radius: 15px;
        margin-top: 30px;
        box-shadow: 0 10px 30px var(--shadow-color);
        position: relative;
        overflow: hidden;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.1);
        z-index: 1;
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    .hero h1 {
        font-size: 2.8rem;
        margin-bottom: 20px;
        font-weight: 700;
    }

    .hero p {
        font-size: 1.3rem;
        margin-bottom: 30px;
        opacity: 0.9;
    }

    /* Buttons */
    .btn {
        display: inline-block;
        padding: 12px 30px;
        text-decoration: none;
        border-radius: 50px;
        transition: all 0.3s ease;
        font-weight: 600;
        letter-spacing: 1px;
    }

    .btn-primary {
        background-color: var(--white);
        color: var(--primary-color);
    }

    .btn-secondary {
        background-color: var(--secondary-color);
        color: var(--white);
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    /* Book Cards */
    .book-card-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 25px;
        justify-content: center;
    }

    .book-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        background-color: var(--white);
        border-radius: 15px;
        box-shadow: 0 8px 20px var(--shadow-color);
        padding: 20px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .book-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
    }

    .book-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
    }

    .book-card img {
        max-width: 180px;
        height: 250px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }

    .book-card:hover img {
        transform: scale(1.05);
    }

    .book-card h3 {
        font-size: 1.3rem;
        margin-bottom: 10px;
        color: var(--text-color);
    }

    .book-card p {
        color: #6c757d;
        margin-bottom: 15px;
    }

    .book-card .btn-small {
        background-color: var(--primary-color);
        color: var(--white);
        padding: 8px 20px;
        border-radius: 30px;
        font-size: 0.9rem;
    }

    /* Responsive Design */
    @media screen and (max-width: 768px) {
        .hero h1 {
            font-size: 2.2rem;
        }

        .hero p {
            font-size: 1.1rem;
        }

        .book-card-container {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
    }

    @media screen and (max-width: 480px) {
        .hero {
            padding: 40px 15px;
        }

        .hero h1 {
            font-size: 1.8rem;
        }

        .book-card-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<body class="bg-gray-50 font-inter min-h-screen flex flex-col">
    <noscript>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
            <p class="font-bold">JavaScript Diperlukan</p>
            <p>Situs ini membutuhkan JavaScript untuk pengalaman yang optimal.</p>
        </div>
    </noscript>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <!-- Navigation -->
    <?php
    $navFile = __DIR__ . '/public/auth/users/includes/user-nav.php';
    if (file_exists($navFile)) {
        include $navFile;
    } else {
        error_log("Navigation file not found: $navFile");
    }
    ?>
    <main class="flex-grow container mx-auto px-4 py-8">
        <?php try { ?>
            <!-- Hero Section -->
            <section class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-2xl shadow-2xl overflow-hidden mb-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 grid md:grid-cols-2 gap-10 items-center">
                    <div class="space-y-6">
                        <h1 class="text-4xl md:text-5xl font-bold leading-tight">
                            <?php if ($isLoggedIn): ?>
                                Selamat Datang, <?= htmlspecialchars($currentUser['name']) ?>
                            <?php else: ?>
                                Selamat Datang di <?= htmlspecialchars(SITE_NAME) ?>
                            <?php endif; ?>
                        </h1>
                        <p class="text-xl opacity-90">
                            <?= htmlspecialchars($pageDescription) ?>
                        </p>
                        <div class="flex space-x-4">
                            <?php if (!$isLoggedIn): ?>
                                <a href="/sistem/public/auth/user-login.php"
                                    class="bg-white text-blue-600 px-6 py-3 rounded-full font-semibold hover:bg-blue-50 transition">
                                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                                </a>
                                <a href="/sistem/public/auth/register.php"
                                    class="bg-green-500 text-white px-6 py-3 rounded-full font-semibold hover:bg-green-600 transition">
                                    <i class="fas fa-user-plus mr-2"></i>Daftar
                                </a>
                            <?php else: ?>
                                <a href="/sistem/public/daftar-buku.php"
                                    class="bg-white text-blue-600 px-6 py-3 rounded-full font-semibold hover:bg-blue-50 transition">
                                    <i class="fas fa-book mr-2"></i>Lihat Buku
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        <img src="/sistem/assets/images/Library Illustration1.png"
                            alt="Library Illustration"
                            class="rounded-2xl shadow-lg transform hover:scale-105 transition duration-300"
                            loading="lazy">
                    </div>
                </div>
            </section>

            <!-- Books Section -->
            <section class="mt-12">
                <h2 class="text-3xl font-bold text-center text-blue-700 mb-10">Buku Terbaru</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                    <?php if (!empty($newBooks)): ?>
                        <?php foreach ($newBooks as $book): ?>
                            <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-2 transition duration-300 p-4 flex flex-col">
                                <div class="relative mb-4">
                                    <img src="<?= !empty($book['cover_image']) && file_exists(__DIR__ . '/uploads/book_covers/' . $book['cover_image'])
                                                    ? '/sistem/uploads/book_covers/' . htmlspecialchars($book['cover_image'])
                                                    : $defaultBookCover ?>"
                                        alt="<?= htmlspecialchars($book['title']) ?>"
                                        class="w-full h-64 object-cover rounded-xl"
                                        loading="lazy"
                                        onerror="this.src='<?= $defaultBookCover ?>'">
                                    <span class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                                        Baru
                                    </span>
                                </div>

                                <div class="flex-grow flex flex-col">
                                    <h3 class="font-semibold text-lg text-gray-800 mb-1 line-clamp-2 h-12">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </h3>
                                    <p class="text-gray-600 text-sm mb-1">
                                        <?= htmlspecialchars($book['author']) ?>
                                    </p>
                                    <div class="text-gray-500 text-xs mb-2 flex justify-between">
                                        <span>Tahun: <?= htmlspecialchars($book['year_published']) ?></span>
                                    </div>
                                </div>

                                <a href="#"
                                    onclick="checkLoginStatus(<?= $book['id'] ?>); return false;"
                                    class="w-full py-2.5 rounded-full text-sm font-semibold text-center 
                                          bg-blue-500 text-white hover:bg-blue-600 
                                          transition duration-300 ease-in-out mt-3">
                                    <i class="fas fa-info-circle mr-2"></i>Detail
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-full text-center text-gray-600 py-8">
                            <i class="fas fa-book-open mr-2"></i>Belum ada buku terbaru.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php } catch (Exception $e) {
            error_log("Error rendering content: " . $e->getMessage());
        ?>
            <div class="text-center text-red-600">
                <p>Maaf, terjadi kesalahan dalam menampilkan konten.</p>
            </div>
        <?php } ?>
    </main>
    <?php
    $footerFile = __DIR__ . '/public/auth/users/includes/footer.php';
    if (file_exists($footerFile)) {
        include $footerFile;
    } else {
        error_log("Footer file not found: $footerFile");
    }
    ?>
</body>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Default image path
    const DEFAULT_BOOK_COVER = '/sistem/uploads/books/book-default.png';

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Enhanced Login status check with loading state and SweetAlert
    function checkLoginStatus(bookId) {
        const button = event.currentTarget;

        // Add loading state with spinner
        button.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading...
        `;
        button.disabled = true;
        button.classList.add('opacity-75', 'cursor-not-allowed');

        <?php if (!AuthSession::isLoggedIn()): ?>
            Swal.fire({
                title: 'Login Diperlukan',
                text: 'Silakan login terlebih dahulu untuk melihat detail buku',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Login Sekarang',
                cancelButtonText: 'Nanti Saja'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/sistem/public/auth/user-login.php?redirect=' +
                        encodeURIComponent('/sistem/public/detail-buku.php?id=' + bookId);
                } else {
                    // Reset button state if user cancels
                    button.innerHTML = '<i class="fas fa-info-circle mr-2"></i>Detail';
                    button.disabled = false;
                    button.classList.remove('opacity-75', 'cursor-not-allowed');
                }
            });
        <?php else: ?>
            setTimeout(() => {
                window.location.href = '/sistem/public/detail-buku.php?id=' + bookId;
            }, 500);
        <?php endif; ?>
    }

    // Enhanced lazy loading for images with fallback
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.onerror = function() {
                        this.src = DEFAULT_BOOK_COVER;
                        this.onerror = null;
                    }
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    });

    // Handle image errors globally
    document.addEventListener('error', function(e) {
        if (e.target.tagName.toLowerCase() === 'img') {
            e.target.src = DEFAULT_BOOK_COVER;
        }
    }, true);

    // Ubah bagian script welcome message
    <?php if (AuthSession::isLoggedIn()): ?>
        // Tambahkan pengecekan first login menggunakan session
        <?php if (!isset($_SESSION['welcome_shown'])): ?>
            const currentUser = <?= json_encode(AuthSession::getCurrentUser()) ?>;
            const currentTime = new Date();
            let greeting = 'Selamat Datang';

            if (currentTime.getHours() < 12) {
                greeting = 'Selamat Pagi';
            } else if (currentTime.getHours() < 15) {
                greeting = 'Selamat Siang';
            } else if (currentTime.getHours() < 19) {
                greeting = 'Selamat Sore';
            } else {
                greeting = 'Selamat Malam';
            }

            Swal.fire({
                title: `${greeting}, ${currentUser.name}!`,
                text: 'Senang melihat Anda kembali',
                icon: 'success',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            });

            <?php
            // Set session bahwa welcome message sudah ditampilkan
            $_SESSION['welcome_shown'] = true;
            ?>
        <?php endif; ?>
    <?php endif; ?>

    // Show session timeout warning
    let sessionTimeout;

    function checkSessionStatus() {
        clearTimeout(sessionTimeout);
        sessionTimeout = setTimeout(() => {
            Swal.fire({
                title: 'Sesi Akan Berakhir',
                text: 'Sesi Anda akan berakhir dalam 5 menit. Ingin tetap login?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Tetap Login',
                cancelButtonText: 'Logout'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Refresh session
                    fetch('/sistem/public/auth/refresh-session.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                checkSessionStatus();
                            }
                        });
                } else {
                    window.location.href = '/sistem/public/auth/user-logout.php';
                }
            });
        }, 25 * 60 * 1000); // 25 minutes
    }

    <?php if (AuthSession::isLoggedIn()): ?>
        checkSessionStatus();
    <?php endif; ?>
</script>

</html>