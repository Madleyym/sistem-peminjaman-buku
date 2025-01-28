<?php

session_start();
require_once '../includes/admin-auth.php';
checkAdminAuth();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../classes/Loan.php';

try {
    // Inisialisasi database dan loan manager
    $database = new Database();
    $conn = $database->getConnection();
    $loanManager = new Loan($conn);

    // Set current date time dari parameter yang diberikan
    $currentDateTime = "2025-01-27 07:25:36"; // Menggunakan waktu yang diberikan
    $currentUser = "Madleyym"; // Menggunakan user yang diberikan

    // Update semua denda terlebih dahulu
    $loanManager->updateAllFines();

    // Fetch statistics
    $stats = $loanManager->getLoanStatistics();
    $totalLoans = $stats['total_loans'];
    $activeLoans = $stats['active_loans'];
    $overdueLoans = $stats['overdue_loans'];

    // Get recent loans with error handling
    $recentLoans = $loanManager->getRecentLoans(5);
    if (!$recentLoans) {
        $recentLoans = [];
    }

    // Pagination setup
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $limit = 10;

    // Validate pagination parameters
    if ($page < 1) $page = 1;

    // Get total records untuk pagination
    $totalRecords = $loanManager->getTotalLoans($search, $status);
    $totalPages = ceil($totalRecords / $limit);

    // Pastikan page tidak melebihi total pages
    if ($totalPages > 0 && $page > $totalPages) {
        $page = $totalPages;
    }

    // Get loans with pagination
    $allLoans = $loanManager->getAllLoans($page, $limit, $search, $status);

    // Hitung start dan end untuk tampilan
    if ($totalRecords > 0) {
        $start = ($page - 1) * $limit + 1;
        $end = min($page * $limit, $totalRecords);
    } else {
        $start = 0;
        $end = 0;
    }

    // Chart data untuk 7 hari terakhir
    $today = date('Y-m-d', strtotime($currentDateTime));
    $week_ago = date('Y-m-d', strtotime($currentDateTime . ' -7 days'));

    $stmt = $conn->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as total 
        FROM loans 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at) 
        ORDER BY date ASC
    ");
    $stmt->execute([$week_ago, $today]);
    $chart_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize chart data
    $chart_dates = [];
    $chart_data = [];

    // Generate data for all 7 days, even if no loans exist
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime($week_ago . " +$i days"));
        $found = false;

        foreach ($chart_results as $result) {
            if ($result['date'] === $date) {
                $chart_dates[] = date('d M', strtotime($date));
                $chart_data[] = (int)$result['total'];
                $found = true;
                break;
            }
        }

        if (!$found) {
            $chart_dates[] = date('d M', strtotime($date));
            $chart_data[] = 0;
        }
    }
} catch (Exception $e) {
    // Log error
    error_log("Error in dashboard-borrow.php: " . $e->getMessage());

    // Set default values jika terjadi error
    $totalLoans = 0;
    $activeLoans = 0;
    $overdueLoans = 0;
    $recentLoans = [];
    $allLoans = [];
    $totalRecords = 0;
    $totalPages = 1;
    $start = 0;
    $end = 0;
    $chart_dates = [];
    $chart_data = [];

    // Set error message
    $_SESSION['error'] = "Terjadi kesalahan dalam memuat data. Silakan coba lagi nanti.";
}

// Remove debug information
// var_dump($allLoans);
// Di bagian atas file setelah mengambil data
// Pindahkan bagian ini ke awal file, setelah inisialisasi variabel
// AJAX handler - letakkan di awal file setelah inisialisasi variabel
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');

    // Set current datetime dari request atau gunakan default
    $currentDateTime = $_GET['datetime'] ?? "2025-01-27 07:47:32";

    $response = [];

    if (!empty($allLoans)) {
        ob_start();
        foreach ($allLoans as $loan) {
            include 'loan-row-template.php';
        }
        $response['html'] = ob_get_clean();
    } else {
        $response['html'] = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">' .
            (!empty($search) || !empty($status) ?
                'Tidak ada data peminjaman yang sesuai dengan kriteria pencarian' :
                'Tidak ada data peminjaman') .
            '</td></tr>';
    }

    $response['totalRecords'] = $totalRecords;
    $response['currentPage'] = $page;
    $response['totalPages'] = $totalPages;
    $response['start'] = $start;
    $response['end'] = $end;
    $response['timestamp'] = $currentDateTime;

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peminjaman - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Gunakan style yang sama dengan admin-index.php -->
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #e74c3c;
            --text-color: #2c3e50;
            --background-color: #f4f7f6;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
        }

        .card-hover {
            transition: transform 0.2s ease-in-out;
        }

        .card-hover:hover {
            transform: translateY(-5px);
        }

        .gradient-bg {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .table-row-hover {
            transition: all 0.2s ease-in-out;
        }

        .table-row-hover:hover {
            background-color: rgba(59, 130, 246, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-gray-50">
    <!-- Enhanced Navigation with Improved Styling -->

    <nav x-data="{ open: false }" class="bg-gradient-to-r from-blue-600 to-blue-800 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center group">
                        <i class="fas fa-book-reader text-white text-2xl mr-2 transform group-hover:scale-110 transition-transform"></i>
                        <span class="text-white font-bold text-xl"><?= htmlspecialchars(SITE_NAME) ?></span>
                    </a>
                </div>

                <!-- Enhanced Navigation Links with Animations -->
                <div class="hidden md:block">
                    <div class="flex items-center space-x-4">
                        <a href="/sistem/public/index.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all duration-300 hover:scale-105">
                            <i class="fas fa-home mr-1"></i> Beranda
                        </a>
                        <a href="/sistem/public/books.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all duration-300 hover:scale-105">
                            <i class="fas fa-book mr-1"></i> Buku
                        </a>
                        <a href="/sistem/public/contact.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all duration-300 hover:scale-105">
                            <i class="fas fa-envelope mr-1"></i> Kontak
                        </a>
                        <?php if (empty($_SESSION['user_id'])): ?>
                            <a href="/sistem/public/auth/login.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all duration-300 hover:scale-105">
                                <i class="fas fa-sign-in-alt mr-1"></i> Login
                            </a>
                        <?php else: ?>
                            <div class="flex items-center space-x-3">
                                <span class="text-white text-sm">
                                    <i class="fas fa-user-circle mr-1"></i> Admin
                                </span>
                                <a href="/sistem/admin/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 hover:scale-105 flex items-center">
                                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Improved Mobile Menu Button -->
                <div class="md:hidden">
                    <button @click="open = !open" class="text-white hover:bg-blue-700 p-2 rounded-md transition-colors duration-300">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Enhanced Mobile Menu with Smooth Transitions -->
        <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="md:hidden bg-blue-800">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/sistem/public/index.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                    <i class="fas fa-home mr-1"></i> Beranda
                </a>
                <a href="/sistem/public/books.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                    <i class="fas fa-book mr-1"></i> Buku
                </a>
                <a href="/sistem/public/contact.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                    <i class="fas fa-envelope mr-1"></i> Kontak
                </a>
            </div>
        </div>
    </nav>
    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <!-- Dashboard Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-bold text-blue-700 flex items-center">
                    <i class="fas fa-handshake mr-3"></i>
                    Dashboard Peminjaman
                </h2>
                <!-- Action Button -->
                <a href="create-borrow.php"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-all duration-300 flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Tambah Peminjaman
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Peminjaman -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-blue-700">Total Peminjaman</h3>
                    <div class="bg-blue-100 rounded-full p-3">
                        <i class="fas fa-book text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="text-4xl font-extrabold text-gray-800 mb-2"><?= $totalLoans ?></div>
                <p class="text-sm text-gray-600">
                    <i class="fas fa-chart-line mr-1"></i> Total keseluruhan
                </p>
            </div>

            <!-- Peminjaman Aktif -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-green-600">Peminjaman Aktif</h3>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="text-4xl font-extrabold text-gray-800 mb-2"><?= $activeLoans ?></div>
                <p class="text-sm text-gray-600">
                    <i class="fas fa-clock mr-1"></i> Sedang berlangsung
                </p>
            </div>

            <!-- Peminjaman Terlambat -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-red-600">Terlambat</h3>
                    <div class="bg-red-100 rounded-full p-3">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                    </div>
                </div>
                <div class="text-4xl font-extrabold text-gray-800 mb-2"><?= $overdueLoans ?></div>
                <p class="text-sm text-gray-600">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Perlu tindakan
                </p>
            </div>
        </div>

        <!-- Chart and Recent Borrows Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Trend Chart -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-blue-700 mb-4 flex items-center">
                    <i class="fas fa-chart-line mr-2"></i>
                    Tren Peminjaman
                </h3>
                <canvas id="borrowChart" height="300"></canvas>
            </div>

            <!-- Recent Borrows -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-blue-700 mb-4 flex items-center">
                    <i class="fas fa-clock mr-2"></i>
                    Peminjaman Terbaru
                </h3>
                <div class="space-y-4">
                    <?php if (!empty($recentLoans)): ?>
                        <?php foreach ($recentLoans as $loan): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                <div class="flex items-center space-x-4">
                                    <div class="bg-blue-100 rounded-full p-2">
                                        <i class="fas fa-book text-blue-600"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold"><?= htmlspecialchars($loan['book_title'] ?? 'Tidak ada judul') ?></h4>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($loan['borrower_name'] ?? 'Tidak ada peminjam') ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                    <?php
                            switch ($loan['status']) {
                                case 'active':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'overdue':
                                    echo 'bg-red-100 text-red-800';
                                    break;
                                case 'returned':
                                    echo 'bg-gray-100 text-gray-800';
                                    break;
                                default:
                                    echo 'bg-blue-100 text-blue-800';
                            }
                    ?>">
                                        <?= htmlspecialchars(ucfirst($loan['status'])) ?>
                                    </span>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?= date('d M Y', strtotime($loan['due_date'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-gray-500">
                            Tidak ada data peminjaman terbaru
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Full Borrow List -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-blue-700 flex items-center">
                        <i class="fas fa-list mr-2"></i>
                        Daftar Peminjaman
                    </h3>
                    <!-- Form Pencarian dengan AJAX -->
                    <form id="searchForm" method="GET" action="" class="flex items-center space-x-3">
                        <div class="relative">
                            <input type="text"
                                id="searchInput"
                                name="search"
                                value="<?= htmlspecialchars($search ?? '') ?>"
                                placeholder="Cari peminjaman..."
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <select id="statusFilter"
                            name="status"
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                            <option value="">Semua Status</option>
                            <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Aktif</option>
                            <option value="overdue" <?= ($status === 'overdue') ? 'selected' : '' ?>>Terlambat</option>
                            <option value="returned" <?= ($status === 'returned') ? 'selected' : '' ?>>Dikembalikan</option>
                        </select>
                        <button type="submit"
                            id="searchButton"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center">
                            <i class="fas fa-search mr-2"></i>
                            <span>Cari</span>
                        </button>
                        <?php if (!empty($search) || !empty($status)): ?>
                            <button type="button"
                                id="resetButton"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center">
                                <i class="fas fa-times mr-2"></i>
                                Reset
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
                <div id="searchInfo" class="mt-4 text-sm text-gray-600">
                    <?php if (!empty($search) || !empty($status)): ?>
                        <?php if (!empty($search)): ?>
                            <span class="mr-2">
                                Pencarian: <span class="font-medium">"<?= htmlspecialchars($search) ?>"</span>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($status)): ?>
                            <span>
                                Status: <span class="font-medium"><?= ucfirst(htmlspecialchars($status)) ?></span>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Peminjam
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Buku
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tanggal Pinjam
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Jatuh Tempo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Denda
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    // Get loans with pagination parameters
                    $allLoans = $loanManager->getAllLoans($page, $limit, $search, $status);

                    if (!empty($allLoans)):
                        foreach ($allLoans as $loan):
                            // Hitung keterlambatan dan status
                            $today = new DateTime($currentDateTime);
                            $dueDate = new DateTime($loan['due_date']);
                            $isOverdue = $today > $dueDate && $loan['status'] != 'returned';

                            // Set status berdasarkan kondisi
                            $status = $loan['status'];
                            if ($isOverdue) {
                                $status = 'overdue';
                            }
                    ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-user text-blue-500"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($loan['borrower_name'] ?? 'N/A') ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($loan['borrower_email'] ?? 'N/A') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($loan['book_title'] ?? 'N/A') ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($loan['book_isbn'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d M Y', strtotime($loan['loan_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="<?= $isOverdue ? 'text-red-600 font-semibold' : '' ?>">
                                        <?= date('d M Y', strtotime($loan['due_date'])) ?>
                                    </span>
                                    <?php if ($isOverdue): ?>
                                        <div class="text-xs text-red-500">
                                            (Terlambat <?= $today->diff($dueDate)->days ?> hari)
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php
                                                                                                                switch ($status) {
                                                                                                                    case 'active':
                                                                                                                        echo 'bg-green-100 text-green-800';
                                                                                                                        break;
                                                                                                                    case 'overdue':
                                                                                                                        echo 'bg-red-100 text-red-800';
                                                                                                                        break;
                                                                                                                    case 'returned':
                                                                                                                        echo 'bg-gray-100 text-gray-800';
                                                                                                                        break;
                                                                                                                    default:
                                                                                                                        echo 'bg-yellow-100 text-yellow-800';
                                                                                                                }
                                                                                                                ?>">
                                        <?= ucfirst(htmlspecialchars($status)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($loan['fine_amount'] > 0): ?>
                                        <span class="text-red-600 font-semibold">
                                            Rp <?= number_format($loan['fine_amount'], 0, ',', '.') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <a href="view-loan.php?id=<?= $loan['id'] ?>"
                                            class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-lg transition-all duration-300">
                                            <i class="fas fa-eye"></i>
                                            <span class="hidden sm:inline ml-1">Detail</span>
                                        </a>

                                        <?php if ($status === 'active' || $status === 'overdue'): ?>
                                            <a href="return-loan.php?id=<?= $loan['id'] ?>"
                                                class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-lg transition-all duration-300">
                                                <i class="fas fa-undo"></i>
                                                <span class="hidden sm:inline ml-1">Kembalikan</span>
                                            </a>

                                            <a href="edit-loan.php?id=<?= $loan['id'] ?>"
                                                class="bg-yellow-500 hover:bg-yellow-600 text-white p-2 rounded-lg transition-all duration-300">
                                                <i class="fas fa-edit"></i>
                                                <span class="hidden sm:inline ml-1">Edit</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                <?php if (!empty($search) || !empty($status)): ?>
                                    Tidak ada data peminjaman yang sesuai dengan kriteria pencarian
                                <?php else: ?>
                                    Tidak ada data peminjaman
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    <?php if ($totalRecords > 0): ?>
                        Menampilkan
                        <span class="font-medium"><?= $start ?></span>
                        sampai
                        <span class="font-medium"><?= $end ?></span>
                        dari
                        <span class="font-medium"><?= $totalRecords ?></span>
                        data
                    <?php else: ?>
                        Tidak ada data
                    <?php endif; ?>
                </div>
                <div class="flex space-x-2">
                    <?php if ($totalPages > 1): ?>
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"
                                class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"
                                class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ambil waktu dan user dari PHP
            const currentDateTime = <?= json_encode($currentDateTime) ?>; // "2025-01-27 07:57:13"
            const currentUser = <?= json_encode($currentUser) ?>; // "Madleyym"

            // Initialize semua fungsi
            initializeSearch();
            initializeTableAnimation();
            initializeChart();

            function initializeSearch() {
                const searchForm = document.getElementById('searchForm');
                const searchInput = document.getElementById('searchInput');
                const statusFilter = document.getElementById('statusFilter');
                const searchButton = document.getElementById('searchButton');
                const resetButton = document.getElementById('resetButton');
                const searchInfo = document.getElementById('searchInfo');
                const tableBody = document.querySelector('tbody');
                const paginationInfo = document.querySelector('.pagination-info');

                // Function to update table content
                function updateTable(response) {
                    if (typeof response === 'object') {
                        tableBody.innerHTML = response.html;
                        updatePaginationInfo(response);
                    } else {
                        tableBody.innerHTML = response;
                    }
                }

                // Function to update pagination info
                function updatePaginationInfo(data) {
                    if (paginationInfo) {
                        if (data.totalRecords > 0) {
                            paginationInfo.innerHTML = `Menampilkan ${data.start} sampai ${data.end} dari ${data.totalRecords} data`;
                        } else {
                            paginationInfo.innerHTML = 'Tidak ada data';
                        }
                    }
                }

                // Function to update search info
                function updateSearchInfo(search, status) {
                    if (!searchInfo) return;

                    let infoHTML = '';
                    if (search || status) {
                        if (search) {
                            infoHTML += `<span class="mr-2">Pencarian: <span class="font-medium">"${search}"</span></span>`;
                        }
                        if (status) {
                            infoHTML += `<span>Status: <span class="font-medium">${status}</span></span>`;
                        }
                    }
                    searchInfo.innerHTML = infoHTML;
                }

                // Handle form submission
                searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const search = searchInput.value;
                    const status = statusFilter.value;

                    // Update button state
                    searchButton.disabled = true;
                    searchButton.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Mencari...';

                    // Create URL with parameters
                    const params = new URLSearchParams({
                        search: search,
                        status: status,
                        ajax: 1,
                        datetime: currentDateTime
                    });

                    // Fetch data
                    fetch(`${window.location.pathname}?${params}`)
                        .then(response => response.json())
                        .then(data => {
                            updateTable(data);
                            updateSearchInfo(search, status);

                            // Update URL
                            const newUrl = `${window.location.pathname}?${new URLSearchParams({
                        search: search,
                        status: status,
                        page: data.currentPage || 1
                    })}`;
                            window.history.pushState({
                                search,
                                status
                            }, '', newUrl);
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            tableBody.innerHTML = `<tr><td colspan="7" class="px-6 py-4 text-center text-red-500">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        Terjadi kesalahan saat mencari data
                    </td></tr>`;
                        })
                        .finally(() => {
                            searchButton.disabled = false;
                            searchButton.innerHTML = '<i class="fas fa-search mr-2"></i> Cari';
                        });
                });

                // Handle reset button
                if (resetButton) {
                    resetButton.addEventListener('click', function() {
                        searchInput.value = '';
                        statusFilter.value = '';
                        searchForm.dispatchEvent(new Event('submit'));
                    });
                }
            }

            function initializeTableAnimation() {
                const tableBody = document.querySelector('tbody');
                if (tableBody) {
                    tableBody.style.opacity = '0';
                    setTimeout(() => {
                        tableBody.style.opacity = '1';
                        tableBody.style.transition = 'opacity 0.3s ease-in-out';
                    }, 300);
                }
            }

            function initializeChart() {
                const ctx = document.getElementById('borrowChart')?.getContext('2d');
                if (!ctx) return;

                const borrowChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($chart_dates) ?>,
                        datasets: [{
                            label: 'Peminjaman per Hari',
                            data: <?= json_encode($chart_data) ?>,
                            fill: true,
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderColor: 'rgb(59, 130, 246)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Tren Peminjaman Harian'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>