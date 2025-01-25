<?php
session_start();
// Enhanced Authentication

error_reporting(E_ALL);
ini_set('display_errors', 1);


// Rest of your existing code...

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../classes/Book.php';
// require_once '../classes/Category.php';

$database = new Database();
$conn = $database->getConnection();
$bookManager = new Book($conn);
// $categoryManager = new Category($conn);

// Handle form submissions
$action = $_GET['action'] ?? '';
$bookId = $_GET['id'] ?? null;

// Add or Update Book
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookData = [
        'title' => $_POST['title'],
        'author' => $_POST['author'],
        'isbn' => $_POST['isbn'],
        'category_id' => $_POST['category_id'],
        'publication_year' => $_POST['publication_year'],
        'total_copies' => $_POST['total_copies'],
        'description' => $_POST['description']
    ];

    // Handle file upload for book cover
    if (!empty($_FILES['book_cover']['name'])) {
        $targetDir = "../uploads/book_covers/";
        $fileName = uniqid() . '_' . basename($_FILES['book_cover']['name']);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['book_cover']['tmp_name'], $targetFilePath)) {
            $bookData['book_cover'] = $fileName;
        }
    }

    if ($action === 'edit' && $bookId) {
        $result = $bookManager->updateBook($bookId, $bookData);
        $message = $result ? "Buku berhasil diperbarui" : "Gagal memperbarui buku";
    } else {
        $result = $bookManager->addBook($bookData);
        $message = $result ? "Buku berhasil ditambahkan" : "Gagal menambahkan buku";
    }
}

// Delete Book
if ($action === 'delete' && $bookId) {
    $result = $bookManager->deleteBook($bookId);
    $message = $result ? "Buku berhasil dihapus" : "Gagal menghapus buku";
}

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch books with search and filter
$searchQuery = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$books = $bookManager->getAllBooks($limit, $offset, $searchQuery, $categoryFilter);
$totalBooks = $bookManager->countTotalBooks($searchQuery, $categoryFilter);
$totalPages = ceil($totalBooks / $limit);

// Fetch categories for dropdown
// $categories = $categoryManager->getAllCategories();
// [Previous PHP code remains the same]
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Buku - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Custom CSS for enhanced visual hierarchy and interaction */
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

        /* Enhanced form input styles */
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

        /* Zebra striping for table */
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Hover effect for table rows */
        tbody tr:hover {
            background-color: #f1f5f9;
            transition: background-color 0.3s ease;
        }

        /* Modal backdrop with blur effect */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .md\:grid-cols-2 {
                grid-template-columns: 1fr;
            }
        }

        /* Button and action styles */
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        /* Status indicator styles */
        .status-low-stock {
            color: #e74c3c;
            font-weight: bold;
        }

        .status-good-stock {
            color: #2ecc71;
            font-weight: bold;
        }
    </style>
</head>
<!-- Rest of the previous code remains the same -->

<body class="bg-gray-50 font-inter min-h-screen flex flex-col">
    <!-- Navigation (Same as admin dashboard) -->
    <nav class="bg-blue-700">
        <!-- [Navigation code from admin dashboard] -->
    </nav>

    <main class="flex-grow container mx-auto px-4 py-8">
        <div x-data="{ openModal: false, currentBook: null }" class="space-y-6">
            <?php if (isset($message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

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
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <form method="get" class="flex space-x-4">
                    <input
                        type="text"
                        name="search"
                        placeholder="Cari buku..."
                        value="<?= htmlspecialchars($searchQuery) ?>"
                        class="flex-grow px-4 py-2 border rounded-lg">
                    <select name="category" class="px-4 py-2 border rounded-lg">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= $category['id'] ?>"
                                <?= $categoryFilter == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Cari</button>
                </form>
            </div>

            <!-- Books Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left">Cover</th>
                            <th class="px-4 py-3 text-left">Judul</th>
                            <th class="px-4 py-3 text-left">Penulis</th>
                            <th class="px-4 py-3 text-left">Kategori</th>
                            <th class="px-4 py-3 text-left">Stok</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <img
                                        src="/uploads/book_covers/<?= htmlspecialchars($book['book_cover'] ?? 'default.jpg') ?>"
                                        alt="Cover Buku"
                                        class="w-16 h-24 object-cover rounded">
                                </td>
                                <td class="px-4 py-3"><?= htmlspecialchars($book['title']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($book['author']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($book['category_name']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="<?= $book['total_copies'] < 5 ? 'text-red-500' : 'text-green-500' ?>">
                                        <?= $book['total_copies'] ?> Eksemplar
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center space-x-2">
                                        <button
                                            @click="openModal = true; currentBook = <?= json_encode($book) ?>"
                                            class="text-blue-500 hover:text-blue-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <a
                                            href="?action=delete&id=<?= $book['id'] ?>"
                                            onclick="return confirm('Yakin ingin menghapus buku ini?')"
                                            class="text-red-500 hover:text-red-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-center space-x-2 mt-6">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($searchQuery) ?>&category=<?= $categoryFilter ?>"
                        class="px-4 py-2 <?= $page == $i ? 'bg-blue-500 text-white' : 'bg-white text-blue-500' ?> border rounded-lg">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>

            <!-- Modal for Add/Edit Book -->
            <div
                x-show="openModal"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div
                    @click.away="openModal = false"
                    class="bg-white rounded-lg w-full max-w-2xl p-8 max-h-[90vh] overflow-y-auto">
                    <h2 x-text="currentBook ? 'Edit Buku' : 'Tambah Buku Baru'" class="text-2xl font-bold mb-6 text-blue-700"></h2>

                    <form
                        method="post"
                        enctype="multipart/form-data"
                        x-ref="bookForm">
                        <input type="hidden" name="action" :value="currentBook ? 'edit' : 'add'">
                        <input type="hidden" name="id" x-model="currentBook?.id">

                        <div class="grid md:grid-cols-2 gap-6">
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
                                    name="category_id"
                                    x-model="currentBook?.category_id"
                                    required
                                    class="w-full px-4 py-2 border rounded-lg">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>">
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block mb-2">Tahun Publikasi</label>
                                <input
                                    type="number"
                                    name="publication_year"
                                    x-model="currentBook?.publication_year"
                                    required
                                    class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block mb-2">Total Eksemplar</label>
                                <input
                                    type="number"
                                    name="total_copies"
                                    x-model="currentBook?.total_copies"
                                    required
                                    class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block mb-2">Deskripsi Buku</label>
                                <textarea
                                    name="description"
                                    x-model="currentBook?.description"
                                    rows="4"
                                    class="w-full px-4 py-2 border rounded-<?php
                                                                            // [Previous PHP code remains the same]
                                                                            ?>
                            <div class=" md:col-span-2">
                                <label class="block mb-2">Sampul Buku</label>
                                <input 
                                    type="file" 
                                    name="book_cover" 
                                    accept="image/*" 
                                    class="w-full px-4 py-2 border rounded-lg"
                                >
                                <p x-show="currentBook?.book_cover" class="text-sm text-gray-500 mt-2">
                                    Sampul saat ini: <span x-text="currentBook?.book_cover"></span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-4">
                            <button 
                                type="button" 
                                @click="openModal = false" 
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg"
                            >
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white py-12">
        <!-- [Footer code from admin dashboard] -->
    </footer>
</body>
</html>