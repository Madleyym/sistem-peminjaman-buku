<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$rootPath = realpath(dirname(__DIR__, 2));
require_once $rootPath . '/vendor/autoload.php';
require_once $rootPath . '/config/constants.php';
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/classes/Book.php';
require_once $rootPath . '/classes/Category.php';

$database = new Database();
$conn = $database->getConnection();
$bookManager = new Book($conn);

$message = '';
$errorDetails = null;

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$searchQuery = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';


// Handle form submissions
$action = $_GET['action'] ?? '';
$bookId = $_GET['id'] ?? null;

// Add or Update Book
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $bookData = [
            'title' => $_POST['title'] ?? '',
            'author' => $_POST['author'] ?? '',
            'publisher' => $_POST['publisher'] ?? '',
            'publication_year' => $_POST['year_published'] ?? '',
            'isbn' => $_POST['isbn'] ?? '',
            'category_id' => $_POST['category'] ?? '',
            'total_copies' => $_POST['total_quantity'] ?? '',
            'description' => $_POST['description'] ?? '',
            'shelf_location' => $_POST['shelf_location'] ?? ''
        ];

        // Validasi data
        $errors = [];
        foreach (['title', 'author', 'publisher', 'publication_year', 'category', 'total_copies', 'shelf_location'] as $field) {
            if (empty($bookData[$field])) {
                $errors[] = "Field $field wajib diisi";
            }
        }

        // Handle cover image upload
        if (!empty($_FILES['cover_image']['name'])) {
            $targetDir = "../uploads/book_covers/";
            $fileName = uniqid() . '_' . basename($_FILES['cover_image']['name']);
            $targetFilePath = $targetDir . $fileName;

            // Validasi file
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Tipe file tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
            }

            if (empty($errors) && move_uploaded_file($_FILES['cover_image']['tmp_name'], $targetFilePath)) {
                $bookData['book_cover'] = $fileName;
            } else {
                $errors[] = "Gagal mengunggah gambar sampul";
            }
        }

        // Jika ada error, lempar exception
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        // Proses tambah/edit buku
        if ($action === 'edit' && $bookId) {
            $result = $bookManager->updateBook($bookId, $bookData);
            $message = $result ? "Buku berhasil diperbarui" : "Gagal memperbarui buku";
        } else {
            $result = $bookManager->create($bookData);
            $message = $result ? "Buku berhasil ditambahkan" : "Gagal menambahkan buku";
        }

        // Jika gagal, simpan detail error
        if (!$result) {
            $errorDetails = $bookData;
            throw new Exception($message);
        }
    } catch (Exception $e) {
        // Log error
        error_log($e->getMessage());

        // Redirect dengan pesan error
        $errorParam = urlencode(json_encode($bookData));
        header("Location: ?error=" . $errorParam);
        exit();
    }
}

// Ambil daftar buku
$categoryManager = new Category($conn);
$categories = $categoryManager->getAllCategories();

$books = $bookManager->getAllBooks($limit, $offset, $searchQuery, $categoryFilter);
$totalBooks = $bookManager->countTotalBooks($searchQuery, $categoryFilter);
$totalPages = ceil($totalBooks / $limit);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Buku - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
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
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        input,
        select,
        textarea {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .book-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .responsive-grid {
                grid-template-columns: 1fr;
            }

            .mobile-stack>* {
                margin-bottom: 1rem;
            }
        }

        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
    </style>
</head>

<body x-data="{ mobileMenuOpen: false }">
    <!-- Navigation Component -->
    <nav class="bg-blue-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center justify-between w-full">
                    <a href="/sistem/public/index.php" class="font-bold text-xl">
                        <?= htmlspecialchars(SITE_NAME) ?>
                    </a>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-4">
                        <a href="/sistem/public/index.php" class="hover:bg-blue-600 px-3 py-2 rounded-md text-sm">Beranda</a>
                        <a href="/sistem/public/books.php" class="hover:bg-blue-600 px-3 py-2 rounded-md text-sm">Buku</a>
                        <a href="/sistem/public/admin/master-data.php" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded-full text-sm">Master Data</a>
                        <a href="/sistem/admin/auth/logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-full text-sm">Keluar</a>
                    </div>

                    <!-- Mobile Menu Toggle -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden text-white focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu Dropdown -->
            <div
                x-show="mobileMenuOpen"
                x-transition
                class="md:hidden bg-blue-600 absolute left-0 right-0">
                <div class="px-4 pt-2 pb-4 space-y-2">
                    <a href="/sistem/public/index.php" class="block px-3 py-2 rounded-md hover:bg-blue-500">Beranda</a>
                    <a href="/sistem/public/books.php" class="block px-3 py-2 rounded-md hover:bg-blue-500">Buku</a>
                    <a href="/sistem/public/admin/master-data.php" class="block px-3 py-2 bg-green-500 hover:bg-green-600 rounded-md">Master Data</a>
                    <a href="/sistem/admin/auth/logout.php" class="block px-3 py-2 bg-red-600 hover:bg-red-700 rounded-md">Keluar</a>
                </div>
            </div>
        </div>
    </nav>

    <main x-data="{ openModal: false, currentBook: null }" class="container mx-auto px-4 py-8">
        <div class="space-y-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-blue-700">Manajemen Buku</h1>
                <button
                    @click="openModal = true; currentBook = null"
                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Buku Baru
                </button>
            </div>

            <!-- Search and Filter Section -->
            <div class="bg-white shadow rounded-lg p-6 mb-6 mobile-stack">
                <form method="get" class="grid md:grid-cols-3 gap-4">
                    <input
                        type="text"
                        name="search"
                        placeholder="Cari buku..."
                        value="<?= htmlspecialchars($searchQuery) ?>"
                        class="px-4 py-2 border rounded-lg">
                    <select name="category" class="px-4 py-2 border rounded-lg">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categoryList as $cat): ?>
                            <option
                                value="<?= $cat['id'] ?>"
                                <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Cari</button>
                </form>
            </div>

            <!-- Books Grid -->
            <div class="grid md:grid-cols-3 gap-6 responsive-grid">
                <?php foreach ($books as $book): ?>
                    <div class="book-card bg-white shadow rounded-lg p-4">
                        <div class="flex space-x-4 mb-4">
                            <img
                                src="/uploads/book_covers/<?= htmlspecialchars($book['cover_image'] ?? 'default.jpg') ?>"
                                alt="Cover Buku"
                                class="w-24 h-36 object-cover rounded-lg">
                            <div>
                                <h3 class="text-lg font-bold"><?= htmlspecialchars($book['title']) ?></h3>
                                <p class="text-sm text-gray-600">
                                    <?= htmlspecialchars($book['author']) ?> |
                                    <?= htmlspecialchars($book['publisher']) ?> (<?= $book['year_published'] ?>)
                                </p>
                                <p class="text-sm text-gray-500 mt-2">
                                    Kategori: <?= htmlspecialchars($book['category']) ?> |
                                    Rak: <?= htmlspecialchars($book['shelf_location']) ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="<?= $book['available_quantity'] < 5 ? 'text-red-500' : 'text-green-500' ?>">
                                    Stok: <?= $book['total_quantity'] ?> / <?= $book['available_quantity'] ?>
                                </span>
                                <p class="text-xs text-gray-500">ISBN: <?= htmlspecialchars($book['isbn']) ?></p>
                            </div>
                            <div class="flex space-x-2">
                                <a
                                    href="?action=edit&id=<?= $book['id'] ?>"
                                    class="text-blue-500 hover:text-blue-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <button
                                    onclick="confirmDelete(<?= $book['id'] ?>)"
                                    class="text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Modal for Add/Edit Book -->
            <div
                x-show="openModal"
                class="fixed inset-0 modal-backdrop flex items-center justify-center z-50">
                <div
                    @click.away="openModal = false"
                    class="bg-white rounded-lg w-full max-w-3xl p-8 max-h-[90vh] overflow-y-auto">
                    <h2 x-text="currentBook ? 'Edit Buku' : 'Tambah Buku Baru'" class="text-2xl font-bold mb-6 text-blue-700"></h2>

                    <form
                        method="post"
                        enctype="multipart/form-data"
                        x-ref="bookForm">
                        <input type="hidden" name="action" :value="currentBook ? 'edit' : 'add'">
                        <input type="hidden" name="id" x-model="currentBook?.id">

                        <div class="grid md:grid-cols-2 gap-6 mobile-stack">
                            <div>
                                <label class="block mb-2">Judul Buku</label>
                                <input
                                    type="text"
                                    name="title"
                                    x-model="currentBook?.title"
                                    required
                                    class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block mb-2">Penulis</label>
                                <input
                                    type="text"
                                    name="author"
                                    x-model="currentBook?.author"
                                    required
                                    class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block mb-2">Penerbit</label>
                                <input
                                    type="text"
                                    name="publisher"
                                    x-model="currentBook?.publisher"
                                    required
                                    class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block mb-2">Tahun Publikasi</label>
                                <input
                                    type="number"
                                    name="year_published"
                                    x-model="currentBook?.year_published"
                                    required
                                    class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block mb-2">ISBN</label>
                                <input
                                    type="text"
                                    name="isbn"
                                    x-model="currentBook?.isbn"
                                    class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block mb-2">Kategori</label>
                                <select
                                    name="category"
                                    x-model="currentBook?.category"
                                    required
                                    class="w-full px-4 py-2 border rounded-lg">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>">
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block mb-2">Total Kuantitas</label>
                                <input
                                    type="number"
                                    name="total_quantity"
                                    x-model="currentBook?.total_quantity"
                                    required
                                    class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block mb-2">Kuantitas Tersedia</label>
                                <input
                                    type="number"
                                    name="available_quantity"
                                    x-model="currentBook?.available_quantity"
                                    required
                                    class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block mb-2">Lokasi Rak</label>
                                <input
                                    type="text"
                                    name="shelf_location"
                                    x-model="currentBook?.shelf_location"
                                    required
                                    class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block mb-2">Deskripsi Buku</label>
                                <textarea
                                    name="description"
                                    x-model="currentBook?.description"
                                    rows="4"
                                    class="w-full px-4 py-2 border rounded-lg"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block mb-2">Sampul Buku</label>
                                <input
                                    type="file"
                                    name="cover_image"
                                    accept="image/*"
                                    class="w-full px-4 py-2 border rounded-lg">
                                <p x-show="currentBook?.cover_image" class="text-sm text-gray-500 mt-2">
                                    Sampul saat ini: <span x-text="currentBook?.cover_image"></span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-4">
                            <button
                                type="button"
                                @click="openModal = false"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </main>
</body>
<script>
    function confirmDelete(bookId) {
        if (confirm('Yakin ingin menghapus buku ini?')) {
            window.location.href = `?action=delete&id=${bookId}`;
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        // Tangani pesan dari server
        const message = "<?= isset($message) ? htmlspecialchars($message) : '' ?>";
        if (message) {
            alert(message);
        }

        // Form submission handling
        const form = document.querySelector('form[x-ref="bookForm"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Basic client-side validation
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('border-red-500');
                        isValid = false;
                    } else {
                        field.classList.remove('border-red-500');
                    }
                });

                // Validasi ISBN
                const isbnField = form.querySelector('input[name="isbn"]');
                if (isbnField && isbnField.value.trim()) {
                    const isbnRegex = /^(?=(?:\D*\d){10}(?:(?:\D*\d){3})?$)[\d-]+$/;
                    if (!isbnRegex.test(isbnField.value)) {
                        isbnField.classList.add('border-red-500');
                        isValid = false;
                    } else {
                        isbnField.classList.remove('border-red-500');
                    }
                }

                // Prevent form submission if validation fails
                if (!isValid) {
                    e.preventDefault();
                    alert('Harap isi semua kolom yang wajib dengan benar.');
                    return false;
                }

                // Disable submit button to prevent multiple submissions
                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');

                // Tambahkan logging untuk debugging
                console.log('Form submitted', Object.fromEntries(new FormData(form)));
            });
        }

        // Tambahkan event listener untuk membuka modal kembali jika ada error
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // Halaman di-load dari cache browser
                const urlParams = new URLSearchParams(window.location.search);
                const errorParam = urlParams.get('error');

                if (errorParam) {
                    // Gunakan Alpine.js untuk membuka modal
                    if (window.Alpine) {
                        window.Alpine.store('bookModal', {
                            open: true,
                            currentBook: JSON.parse(decodeURIComponent(errorParam))
                        });
                    }
                }
            }
        });
    });
</script>

</html>
<footer class="bg-gray-900 text-white py-8">
    <div class="container mx-auto px-4 text-center">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. All Rights Reserved.</p>
        <div class="flex justify-center space-x-4 mt-4">
            <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook"></i></a>
            <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
            <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
        </div>
    </div>
</footer>