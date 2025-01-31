<?php
session_start();
require_once '../includes/admin-auth.php';  // sesuaikan dengan nama file yang menggunakan dash
checkAdminAuth();


require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../classes/Book.php';


$database = new Database();
$conn = $database->getConnection();

// Fetch report statistics
$thisMonth = date('Y-m');

// Get monthly statistics - Menggunakan tabel loans
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned_books,
        SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as active_borrows,
        SUM(CASE WHEN CURRENT_TIMESTAMP > due_date AND status = 'borrowed' THEN 1 ELSE 0 END) as overdue_books,
        SUM(fine_amount) as total_fines
    FROM loans 
    WHERE DATE_FORMAT(loan_date, '%Y-%m') = ?
");
$stmt->execute([$thisMonth]);
$currentStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get most borrowed books - Join dengan tabel loans
$stmt = $conn->prepare("
    SELECT b.title, COUNT(*) as borrow_count 
    FROM loans l 
    JOIN books b ON l.book_id = b.id 
    GROUP BY b.id, b.title 
    ORDER BY borrow_count DESC 
    LIMIT 5
");
$stmt->execute();
$popularBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user activity - Join dengan tabel loans
$stmt = $conn->prepare("
    SELECT u.name, COUNT(*) as loan_count 
    FROM loans l 
    JOIN users u ON l.user_id = u.id 
    GROUP BY u.id, u.name 
    ORDER BY loan_count DESC 
    LIMIT 5
");
$stmt->execute();
$activeUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get overdue loans
$stmt = $conn->prepare("
    SELECT 
        u.name as user_name,
        b.title as book_title,
        l.due_date,
        l.fine_amount
    FROM loans l
    JOIN users u ON l.user_id = u.id
    JOIN books b ON l.book_id = b.id
    WHERE l.status = 'borrowed' 
    AND CURRENT_TIMESTAMP > l.due_date
    ORDER BY l.due_date ASC
    LIMIT 5
");
$stmt->execute();
$overdueLoan = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="min-h-screen flex flex-col bg-gray-50">
    <?php
    // Define SITE_NAME if not already defined
    if (!defined('SITE_NAME')) {
        define('SITE_NAME', 'Perpustakaan');
    }

    // Include navigation
    require_once '../includes/navigation.php';
    ?>


    <main class="flex-grow container mx-auto px-4 py-8">
        <!-- Dashboard Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-bold text-blue-700 flex items-center">
                    <i class="fas fa-chart-bar mr-3"></i>
                    Dashboard Laporan
                </h2>
                <nav class="hidden sm:flex">
                    <ol class="flex items-center space-x-2 text-gray-500 text-sm">
                        <li><a href="/sistem/admin/index.php" class="hover:text-blue-600">Dashboard</a></li>
                        <li><i class="fas fa-chevron-right text-xs"></i></li>
                        <li class="text-blue-600">Laporan</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Transactions -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-blue-700">Total Transaksi</h3>
                    <div class="bg-blue-100 rounded-full p-3">
                        <i class="fas fa-exchange-alt text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="text-4xl font-extrabold text-gray-800 mb-2">
                    <?= $currentStats['total_transactions'] ?>
                </div>
                <p class="text-sm text-gray-600">Bulan ini</p>
            </div>

            <!-- Returned Books -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-green-600">Buku Dikembalikan</h3>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="text-4xl font-extrabold text-gray-800 mb-2">
                    <?= $currentStats['returned_books'] ?>
                </div>
                <p class="text-sm text-gray-600">Bulan ini</p>
            </div>

            <!-- Active Borrows -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-yellow-600">Sedang Dipinjam</h3>
                    <div class="bg-yellow-100 rounded-full p-3">
                        <i class="fas fa-book-reader text-yellow-600 text-xl"></i>
                    </div>
                </div>
                <div class="text-4xl font-extrabold text-gray-800 mb-2">
                    <?= $currentStats['active_borrows'] ?>
                </div>
                <p class="text-sm text-gray-600">Saat ini</p>
            </div>

            <!-- Overdue Books -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-red-600">Terlambat</h3>
                    <div class="bg-red-100 rounded-full p-3">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                    </div>
                </div>
                <div class="text-4xl font-extrabold text-gray-800 mb-2">
                    <?= $currentStats['overdue_books'] ?>
                </div>
                <p class="text-sm text-gray-600">Perlu tindakan</p>
            </div>
        </div>

        <!-- Popular Books and Active Users -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <!-- Popular Books -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-blue-700 mb-4 flex items-center">
                    <i class="fas fa-star mr-2"></i>
                    Buku Terpopuler
                </h3>
                <div class="space-y-4">
                    <?php foreach ($popularBooks as $book): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <span class="font-medium"><?= htmlspecialchars($book['title']) ?></span>
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                                <?= $book['borrow_count'] ?> peminjaman
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Active Users -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-blue-700 mb-4 flex items-center">
                    <i class="fas fa-users mr-2"></i>
                    Pengguna Teraktif
                </h3>
                <div class="space-y-4">
                    <?php foreach ($activeUsers as $user): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <span class="font-medium"><?= htmlspecialchars($user['name']) ?></span>
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">
                                <?= $user['loan_count'] ?> peminjaman
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-blue-700 mb-6 flex items-center">
                <i class="fas fa-file-export mr-2"></i>
                Ekspor Laporan
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="/sistem/admin/reports/export-pdf.php" class="flex items-center justify-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors duration-200">
                    <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                    Ekspor PDF
                </a>
                <a href="/sistem/admin/reports/export-excel.php" class="flex items-center justify-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors duration-200">
                    <i class="fas fa-file-excel text-green-500 mr-2"></i>
                    Ekspor Excel
                </a>
                <a href="/sistem/admin/reports/print-report.php" target="_blank" class="flex items-center justify-center p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors duration-200">
                    <i class="fas fa-print text-gray-500 mr-2"></i>
                    Cetak Laporan
                </a>
            </div>
        </div>

    </main>
</body>

</html>