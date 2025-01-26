<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$rootPath = realpath(dirname(__DIR__, 2));
require_once '../../config/database.php';
require_once '../../classes/Book.php';

// Prevent direct access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../books.php');
    exit();
}

try {
    // Database connection
    $database = new Database();
    $conn = $database->getConnection();
    $bookManager = new Book($conn);

    // Validate input
    $bookId = $_POST['id'] ?? null;
    if (!$bookId) {
        throw new Exception("ID buku tidak valid");
    }

    // Prepare book data
    $bookData = [
        'title' => $_POST['title'],
        'author' => $_POST['author'],
        'publisher' => $_POST['publisher'],
        'year_published' => $_POST['year_published'],
        'isbn' => $_POST['isbn'],
        'category_id' => $_POST['category_id'],
        'total_quantity' => $_POST['total_quantity'],
        'available_quantity' => $_POST['available_quantity'],
        'shelf_location' => $_POST['shelf_location'],
        'description' => $_POST['description']
    ];

    // Handle book cover upload
    if (!empty($_FILES['cover_image']['name'])) {
        $targetDir = "../uploads/book_covers/";
        $fileName = uniqid() . '_' . basename($_FILES['cover_image']['name']);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $targetFilePath)) {
            $bookData['cover_image'] = $fileName;
        }
    }

    // Update book
    $result = $bookManager->updateBook($bookId, $bookData);

    if ($result) {
        $_SESSION['success_message'] = "Buku berhasil diperbarui";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui buku";
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

// Redirect back to books page
header('Location: ../../books.php');
exit();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Buku</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        /* Modal and Confirmation Styles */
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 0.75rem;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .delete-confirmation-modal {
            text-align: center;
        }

        .delete-confirmation-modal h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .delete-confirmation-modal .book-details {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-delete {
            background-color: #ef4444;
            color: white;
            hover:bg-red-600;
        }

        .btn-cancel {
            background-color: #6b7280;
            color: white;
            hover:bg-gray-700;
        }
    </style>
</head>
<body x-data="{ 
    showDeleteModal: false, 
    showEditModal: false, 
    bookToDelete: null, 
    bookToEdit: null 
}">
    <!-- Main Book Management Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Existing Book List -->
        <div class="grid md:grid-cols-3 gap-6">
            <?php foreach ($books as $book): ?>
                <div class="bg-white shadow rounded-lg p-4">
                    <h3><?= htmlspecialchars($book['title']) ?></h3>
                    <div class="flex justify-between mt-4">
                        <button 
                            @click="bookToEdit = <?= json_encode($book) ?>; showEditModal = true"
                            class="text-blue-500 hover:text-blue-700">
                            Edit
                        </button>
                        <button 
                            @click="bookToDelete = <?= json_encode($book) ?>; showDeleteModal = true"
                            class="text-red-500 hover:text-red-700">
                            Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Delete Confirmation Modal -->
        <div 
            x-show="showDeleteModal" 
            class="modal-overlay"
            x-cloak>
            <div class="modal-content delete-confirmation-modal">
                <h2>Konfirmasi Hapus Buku</h2>
                <div class="book-details">
                    <p x-text="bookToDelete?.title"></p>
                    <p x-text="'Penulis: ' + bookToDelete?.author"></p>
                </div>
                <div class="flex justify-center space-x-4">
                    <a 
                        x-bind:href="'/actions/delete-book.php?id=' + bookToDelete?.id"
                        class="btn btn-delete">
                        Hapus
                    </a>
                    <button 
                        @click="showDeleteModal = false; bookToDelete = null"
                        class="btn btn-cancel">
                        Batal
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Book Modal -->
        <div 
            x-show="showEditModal" 
            class="modal-overlay"
            x-cloak>
            <div class="modal-content">
                <h2>Edit Buku</h2>
                <form 
                    action="/actions/edit-book.php" 
                    method="POST" 
                    enctype="multipart/form-data">
                    <input type="hidden" name="id" x-model="bookToEdit?.id">
                    
                    <div class="space-y-4">
                        <div>
                            <label>Judul</label>
                            <input 
                                type="text" 
                                name="title" 
                                x-model="bookToEdit?.title" 
                                class="w-full border rounded p-2">
                        </div>
                        
                        <div>
                            <label>Penulis</label>
                            <input 
                                type="text" 
                                name="author" 
                                x-model="bookToEdit?.author" 
                                class="w-full border rounded p-2">
                        </div>

                        <!-- Add more fields as needed -->
                    </div>

                    <div class="flex justify-end space-x-4 mt-6">
                        <button 
                            type="button" 
                            @click="showEditModal = false; bookToEdit = null"
                            class="btn btn-cancel">
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            class="btn btn-delete">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bookManagement', () => ({
                showDeleteModal: false,
                showEditModal: false,
                bookToDelete: null,
                bookToEdit: null
            }))
        })
    </script>
</body>
</html>