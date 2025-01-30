<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../classes/Book.php';

// Pastikan direktori upload ada
// Pastikan direktori dan file default ada
$defaultImageDir = __DIR__ . "/../../uploads/books/";
if (!file_exists($defaultImageDir)) {
    mkdir($defaultImageDir, 0777, true);
}
// Copy file default-book-cover.jpg ke folder tersebut
// Default book cover path
$defaultBookCover = '/sistem/uploads/books/book-default.png';

try {
    $database = new Database();
    $conn = $database->getConnection();
    $bookManager = new Book($conn);

    // Get search parameters with proper validation
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';

    // Perbaikan validasi tahun
    $minYear = !empty($_GET['min_year']) ? (int)$_GET['min_year'] : '';
    $maxYear = !empty($_GET['max_year']) ? (int)$_GET['max_year'] : '';

    // Tambahan validasi untuk memastikan tahun valid
    if ($minYear !== '' && ($minYear < 1900 || $minYear > date('Y'))) {
        $minYear = '';
    }
    if ($maxYear !== '' && ($maxYear < 1900 || $maxYear > date('Y'))) {
        $maxYear = '';
    }
    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Get books with all filters
    $books = $bookManager->getAllBooks(
        $limit,
        $offset,
        $search,
        $category,
        $minYear,
        $maxYear
    );

    // Get total for pagination
    $total = $bookManager->countTotalBooks(
        $search,
        $category,
        $minYear,
        $maxYear
    );

    $totalPages = ceil($total / $limit);

    // Get categories for dropdown
    $stmt = $conn->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("Daftar Buku Error: " . $e->getMessage());
    $books = [];
    $total = 0;
    $totalPages = 0;
    $categories = [];
}

// Helper function untuk path gambar
function getBookCoverPath($coverImage)
{
    if (!empty($coverImage)) {
        return "/sistem/uploads/book_covers/" . htmlspecialchars($coverImage);
    }
    return "/sistem/uploads/books/book-default.png";
}

// Helper function untuk URL pagination
function buildPaginationUrl($pageNum, $search, $filters)
{
    return "?page=" . $pageNum .
        "&search=" . urlencode($search) .
        "&category=" . urlencode($filters['category']) .
        "&min_year=" . urlencode($filters['min_year'] ?? '') .
        "&max_year=" . urlencode($filters['max_year'] ?? '');
}

// Create filters array for pagination
$filters = [
    'category' => $category,
    'min_year' => $minYear,
    'max_year' => $maxYear
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan - Daftar Buku</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<style>
    :root {
        --primary-color: #3498db;
        --secondary-color: #2ecc71;
        --text-color: #2c3e50;
        --background-color: #f4f7f6;
        --white: #ffffff;
        --shadow-color: rgba(0, 0, 0, 0.1);
        --border-radius: 12px;
    }

    body {
        font-family: 'Inter', 'Arial', sans-serif;
        line-height: 1.6;
        color: var(--text-color);
        background-color: var(--background-color);
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .books-container {
        flex: 1;
        padding: 30px;
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
    }

    .book-header h1 {
        color: var(--primary-color);
        text-align: center;
        margin-bottom: 30px;
        font-size: 2.5rem;
    }

    .search-form {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px var(--shadow-color);
        display: flex;
        flex-direction: column;
        gap: 20px;
        align-items: center;
    }

    .search-inputs {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
        width: 100%;
    }

    .search-form input,
    .search-form select {
        flex: 1;
        min-width: 150px;
        padding: 12px;
        border: 2px solid var(--primary-color);
        border-radius: var(--border-radius);
        transition: all 0.3s ease;
    }

    .search-form input:focus,
    .search-form select:focus {
        outline: none;
        border-color: var(--secondary-color);
        box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
    }

    .search-form button {
        padding: 12px 25px;
        background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        color: var(--white);
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .search-form button:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px var(--shadow-color);
    }

    .book-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 25px;
        padding: 20px;
    }

    .book-card {
        background: var(--white);
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transition: all 0.4s ease;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .book-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
    }

    .book-card h3 {
        color: var(--primary-color);
        margin-bottom: 15px;
        font-size: 1.3rem;
    }

    .book-card p {
        color: var(--text-color);
        margin-bottom: 10px;
        opacity: 0.8;
    }

    .book-card .btn {
        display: inline-block;
        margin-top: 15px;
        padding: 10px 20px;
        background: var(--primary-color);
        color: var(--white);
        text-decoration: none;
        border-radius: var(--border-radius);
        transition: all 0.3s ease;
    }

    .book-card .btn:hover {
        background: var(--secondary-color);
        transform: translateY(-3px);
    }

    /* Responsive Adjustments */
    @media screen and (max-width: 768px) {
        .search-inputs {
            flex-direction: column;
            gap: 10px;
        }

        .book-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<body class="bg-gray-50 font-inter min-h-screen flex flex-col">
    <!-- Mobile Navigation -->
    <nav x-data="{ open: false }" class="bg-blue-700 md:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/sistem/index.php" class="text-white font-bold text-xl">
                        <?= htmlspecialchars(SITE_NAME) ?>
                    </a>
                </div>
                <div class="-mr-2 flex md:hidden">
                    <button
                        @click="open = !open"
                        type="button"
                        class="bg-blue-600 inline-flex items-center justify-center p-2 rounded-md text-white hover:bg-blue-500 focus:outline-none">
                        <span class="sr-only">Open main menu</span>
                        <svg x-show="!open" class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg x-show="open" class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="open" class="md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-blue-600">
                <a href="/sistem/index.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Beranda</a>
                <a href="/sistem/public/daftar-buku.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Buku</a>
                <?php if (empty($_SESSION['user_id'])): ?>
                    <a href="../../auth/login.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Login</a>
                    <a href="../../auth/register.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Daftar</a>
                <?php endif; ?>
                <a href="/kontak" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Kontak</a>
            </div>
        </div>
    </nav>

    <!-- Desktop Navigation (Copied from index.php) -->
    <nav class="bg-blue-700 hidden md:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/sistem/index.php" class="text-white font-bold text-xl mr-8">
                        <?= htmlspecialchars(SITE_NAME) ?>
                    </a>
                    <div class="flex space-x-4">
                        <a href="/sistem/index.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Beranda</a>
                        <a href="/sistem/public/daftar-buku.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Buku</a>
                        <a href="/sistem/public/kontak.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Kontak</a>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <?php if (empty($_SESSION['user_id'])): ?>
                        <a href="/sistem/public/auth/login.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="/sistem/public/auth/register.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-full text-sm font-medium">Daftar</a>
                    <?php else: ?>
                        <a href="/sistem/public/auth/login.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="/sistem/public/auth/register.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-full text-sm font-medium">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <section class="bg-white shadow-lg rounded-2xl p-8 mb-12">
            <h1 class="text-4xl font-bold text-center text-blue-700 mb-8">Perpustakaan Buku</h1>

            <form action="" method="GET" class="mb-12">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Search input -->
                    <input
                        type="text"
                        name="search"
                        placeholder="Cari buku..."
                        value="<?= htmlspecialchars($search) ?>"
                        class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                    <!-- Category dropdown -->
                    <select name="category" class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Year range inputs -->
                    <input
                        type="number"
                        name="min_year"
                        placeholder="Tahun Min"
                        value="<?= $minYear !== '' ? htmlspecialchars($minYear) : '' ?>"
                        min="1900"
                        max="<?= date('Y') ?>"
                        class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                    <input
                        type="number"
                        name="max_year"
                        placeholder="Tahun Maks"
                        value="<?= $maxYear !== '' ? htmlspecialchars($maxYear) : '' ?>"
                        min="1900"
                        max="<?= date('Y') ?>"
                        class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                    <button
                        type="submit"
                        class="w-full bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 transition duration-300 ease-in-out">
                        Cari Buku
                    </button>
                </div>
            </form>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <?php if (!empty($books)): ?>
                    <?php foreach ($books as $book): ?>
                        <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-2 transition duration-300 p-4 flex flex-col">
                            <div class="relative mb-4">
                                <?php
                                // Construct correct path to existing book_covers directory
                                $imagePath = !empty($book['cover_image'])
                                    ? "/sistem/uploads/book_covers/" . htmlspecialchars($book['cover_image'])
                                    : "/sistem/uploads/books/default-book-cover.jpg";
                                ?>
                                <img
                                    src="<?= $imagePath ?>"
                                    alt="<?= htmlspecialchars($book['title']) ?>"
                                    class="w-full h-64 object-cover rounded-xl"
                                    onerror="this.src='/sistem/uploads/books/book-default.png'; this.onerror=null;">
                                <?php if ($book['available_quantity'] <= 3 && $book['available_quantity'] > 0): ?>
                                    <span class="absolute top-2 right-2 bg-yellow-500 text-white text-xs px-2 py-1 rounded-full">
                                        Tersisa <?= $book['available_quantity'] ?>
                                    </span>
                                <?php endif; ?>
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
                                    <span class="flex items-center">
                                        <svg class="w-3.5 h-3.5 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                        </svg>
                                        <?= $book['available_quantity'] ?>
                                    </span>
                                </div>
                            </div>

                            <a
                                href="/sistem/public/detail-buku.php?id=<?= $book['id'] ?>"
                                class="w-full py-2.5 rounded-full text-sm font-semibold text-center bg-blue-500 text-white hover:bg-blue-600 transition duration-300 ease-in-out mt-3">
                                Detail Buku
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center text-gray-600 py-8">
                        Tidak ada buku yang ditemukan.
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex justify-center items-center space-x-2 mt-8">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= ($page - 1) ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($filters['category']) ?>&min_year=<?= urlencode($filters['min_year']) ?>&max_year=<?= urlencode($filters['max_year']) ?>"
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        &laquo; Previous
                    </a>
                <?php endif; ?>

                <div class="flex space-x-1">
                    <?php
                    // Show up to 5 pages
                    $start = max(1, min($page - 2, $totalPages - 4));
                    $end = min($start + 4, $totalPages);

                    for ($i = $start; $i <= $end; $i++):
                        $isActive = $i == $page;
                    ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($filters['category']) ?>&min_year=<?= urlencode($filters['min_year']) ?>&max_year=<?= urlencode($filters['max_year']) ?>"
                            class="px-4 py-2 <?= $isActive ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?> rounded">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= ($page + 1) ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($filters['category']) ?>&min_year=<?= urlencode($filters['min_year']) ?>&max_year=<?= urlencode($filters['max_year']) ?>"
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Next &raquo;
                    </a>
                <?php endif; ?>
            </div>

        </section>
    </main>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>

</html>