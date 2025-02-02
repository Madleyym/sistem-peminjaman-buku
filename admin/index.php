<?php
// Di bagian atas file
require_once '../config/auth-session.php';
require_once '../config/database.php';
require_once '../config/constants.php';

// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('UTC');

// Database connection
try {
    $database = new database();
    $conn = $database->getConnection();
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Koneksi database gagal. Silakan coba lagi nanti.");
}

// Check admin access
function checkAdminAccess()
{
    if (!AuthSession::isAdmin()) {
        $_SESSION['auth_error'] = "Anda harus login sebagai admin untuk mengakses halaman ini.";
        header('Location: ' . AuthSession::getLoginPath(AuthSession::ROLES['ADMIN']));
        exit;
    }
}

checkAdminAccess();

// Fungsi-fungsi lainnya tetap sama
function getAnalyticsData($conn)
{
    try {
        $today = date('Y-m-d');
        $week_ago = date('Y-m-d', strtotime('-7 days'));

        // Get weekly statistics
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_activities,
                SUM(CASE WHEN action_type = 'peminjaman' THEN 1 ELSE 0 END) as total_peminjaman,
                SUM(CASE WHEN action_type = 'pengembalian' THEN 1 ELSE 0 END) as total_pengembalian
            FROM activity_logs 
            WHERE DATE(created_at) BETWEEN :week_ago AND :today
        ");
        $stmt->execute(['week_ago' => $week_ago, 'today' => $today]);
        $weekly = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get today's statistics
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as today_actions,
                COUNT(DISTINCT user_id) as unique_users
            FROM activity_logs 
            WHERE DATE(created_at) = :today
        ");
        $stmt->execute(['today' => $today]);
        $daily = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_activities' => number_format($weekly['total_activities'] ?? 0),
            'total_peminjaman' => number_format($weekly['total_peminjaman'] ?? 0),
            'total_pengembalian' => number_format($weekly['total_pengembalian'] ?? 0),
            'today_actions' => number_format($daily['today_actions'] ?? 0),
            'unique_users' => number_format($daily['unique_users'] ?? 0)
        ];
    } catch (PDOException $e) {
        error_log("Analytics Error: " . $e->getMessage());
        return [
            'total_activities' => '0',
            'total_peminjaman' => '0',
            'total_pengembalian' => '0',
            'today_actions' => '0',
            'unique_users' => '0'
        ];
    }
}

function getRecentActivities($conn, $limit = 5)
{
    try {
        $stmt = $conn->prepare("
            SELECT 
                al.*,
                COALESCE(u.username, a.username, s.username, 'Anonymous') as username
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN admin a ON al.user_id = a.id
            LEFT JOIN staff s ON al.user_id = s.id
            ORDER BY al.created_at DESC
            LIMIT :limit
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Recent Activities Error: " . $e->getMessage());
        return [];
    }
}

function getChartData($conn, $period = 7)
{
    try {
        date_default_timezone_set('UTC');
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-$period days"));

        $stmt = $conn->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN action_type = 'peminjaman' THEN 1 ELSE 0 END) as peminjaman,
                SUM(CASE WHEN action_type = 'pengembalian' THEN 1 ELSE 0 END) as pengembalian
            FROM activity_logs 
            WHERE DATE(created_at) BETWEEN :start_date AND :end_date
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");

        $stmt->execute([
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dates = [];
        $totals = [];
        $peminjaman = [];
        $pengembalian = [];

        // Fill in all dates including those with no activity
        $current = strtotime($start_date);
        $end = strtotime($end_date);

        while ($current <= $end) {
            $currentDate = date('Y-m-d', $current);
            $found = false;

            foreach ($results as $row) {
                if ($row['date'] === $currentDate) {
                    $dates[] = date('d M', $current);
                    $totals[] = (int)$row['total'];
                    $peminjaman[] = (int)$row['peminjaman'];
                    $pengembalian[] = (int)$row['pengembalian'];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $dates[] = date('d M', $current);
                $totals[] = 0;
                $peminjaman[] = 0;
                $pengembalian[] = 0;
            }

            $current = strtotime('+1 day', $current);
        }

        return [
            'dates' => $dates,
            'totals' => $totals,
            'peminjaman' => $peminjaman,
            'pengembalian' => $pengembalian
        ];
    } catch (PDOException $e) {
        error_log("Chart Data Error: " . $e->getMessage());
        return [
            'dates' => [],
            'totals' => [],
            'peminjaman' => [],
            'pengembalian' => []
        ];
    }
}

try {
    $analytics = getAnalyticsData($conn);
    $recentActivities = getRecentActivities($conn);
    $chartData = getChartData($conn);

    // Get current user data from session
    $currentUser = AuthSession::getCurrentUser();

    // Log dashboard access
    AuthSession::logActivity(
        'Mengakses dashboard admin',
        'admin',
        $currentUser['id'] ?? 0
    );
} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan. Silakan coba lagi nanti.";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #e74c3c;
            --text-color: #2c3e50;
            --background-color: #f4f7f6;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .card-hover {
            transition: all 0.3s ease;
            transition: transform 0.2s ease-in-out;
            /* transition: all 0.3s ease; */
        }

        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        .chart-filter {
            transition: all 0.3s ease;
        }

        .chart-filter:hover {
            transform: translateY(-1px);
        }

        .chart-filter.active {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
        }

        .card-hover:hover {
            transform: translateY(-5px);
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .gradient-bg {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-gray-50">
    <!-- Enhanced Navigation with Improved Styling -->
    <nav x-data="{ open: false }" class="bg-gradient-to-r from-blue-600 to-blue-800 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/sistem/admin/index.php" class="flex items-center group">
                        <i class="fas fa-book-reader text-white text-2xl mr-2 transform group-hover:scale-110 transition-transform"></i>
                        <span class="text-white font-bold text-xl"><?= htmlspecialchars(SITE_NAME) ?></span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:block">
                    <div class="flex items-center space-x-4">
                        <a href="/sistem/index.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-home mr-1"></i> Beranda
                        </a>
                        <a href="/sistem/public/daftar-buku.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-book mr-1"></i> Buku
                        </a>
                        <a href="/sistem/public/kontak.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-envelope mr-1"></i> Kontak
                        </a>
                        <?php if (AuthSession::isAdmin()): ?>
                            <div class="flex items-center space-x-3">
                                <span class="text-white text-sm">
                                    <i class="fas fa-user-circle mr-1"></i>
                                    <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                                </span>
                                <a href="/sistem/admin/index.php"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium">
                                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <!-- Jika belum login -->
                                <a href="/sistem/admin/auth/admin-login.php"
                                    class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                                    <i class="fas fa-sign-in-alt mr-1"></i> Login Admin
                                </a>
                            <?php endif; ?>
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
    <!-- Main Content with Enhanced Layout -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <!-- Admin Menu Section -->
        <section class="bg-white shadow-lg rounded-2xl p-8 mb-8 transform hover:shadow-xl transition-all duration-300">
            <h2 class="text-3xl font-bold text-blue-700 mb-6 flex items-center">
                <i class="fas fa-tools mr-2"></i>Menu Admin
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php if (AuthSession::isAdmin()): ?>
                    <!-- Menu khusus admin -->
                    <a href="manage-books/dashboard-book.php"
                        class="group card-hover bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-xl p-6 text-center transition-all duration-300 transform hover:-translate-y-1">
                        <div class="bg-blue-500 text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 transform group-hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-book-open text-2xl"></i>
                        </div>
                        <span class="font-semibold text-blue-800">Manajemen Buku</span>
                    </a>

                    <a href="manage-user/manage-user.php"
                        class="group card-hover bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 rounded-xl p-6 text-center transition-all duration-300 transform hover:-translate-y-1">
                        <div class="bg-green-500 text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 transform group-hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <span class="font-semibold text-green-800">Manajemen Pengguna</span>
                    </a>

                    <a href="manage-borrow/dashboard-borrow.php"
                        class="group card-hover bg-gradient-to-br from-yellow-50 to-yellow-100 hover:from-yellow-100 hover:to-yellow-200 rounded-xl p-6 text-center transition-all duration-300 transform hover:-translate-y-1">
                        <div class="bg-yellow-500 text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 transform group-hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-handshake text-2xl"></i>
                        </div>
                        <span class="font-semibold text-yellow-800">Peminjaman</span>
                    </a>

                    <a href="reports/dashboard-reports.php"
                        class="group card-hover bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 rounded-xl p-6 text-center transition-all duration-300 transform hover:-translate-y-1">
                        <div class="bg-purple-500 text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 transform group-hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-chart-bar text-2xl"></i>
                        </div>
                        <span class="font-semibold text-purple-800">Laporan</span>
                    </a>
                <?php else: ?>
                    <!-- Pesan untuk non-admin -->
                    <div class="col-span-4 text-center p-8 bg-white rounded-xl shadow-lg border border-gray-100">
                        <div class="flex flex-col items-center space-y-4">
                            <div class="bg-blue-100 p-4 rounded-full">
                                <i class="fas fa-lock text-blue-600 text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800">Area Administrator</h3>
                            <p class="text-gray-600 mb-4">
                                Menu admin hanya dapat diakses oleh administrator yang telah login.
                            </p>
                            <a href="auth/admin-login.php"
                                class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all duration-300 transform hover:-translate-y-1">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Login Sebagai Admin
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Statistics Dashboard -->
        <div class="space-y-6">
            <!-- Weekly Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Aktivitas Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover transform hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-blue-700">Total Aktivitas</h3>
                        <div class="bg-blue-100 rounded-full p-3 transform hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="text-4xl font-extrabold text-gray-800 mb-2">
                        <?= htmlspecialchars($analytics['total_activities']) ?>
                    </div>
                    <p class="text-sm text-gray-600 flex items-center">
                        <i class="fas fa-clock mr-1"></i> 7 hari terakhir
                    </p>
                </div>

                <!-- Total Peminjaman Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover transform hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-green-600">Total Peminjaman</h3>
                        <div class="bg-green-100 rounded-full p-3 transform hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-book text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="text-4xl font-extrabold text-gray-800 mb-2">
                        <?= htmlspecialchars($analytics['total_peminjaman']) ?>
                    </div>
                    <p class="text-sm text-gray-600 flex items-center">
                        <i class="fas fa-calendar-day mr-1"></i> 7 hari terakhir
                    </p>
                </div>

                <!-- Total Pengembalian Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover transform hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-purple-600">Total Pengembalian</h3>
                        <div class="bg-purple-100 rounded-full p-3 transform hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-undo text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="text-4xl font-extrabold text-gray-800 mb-2">
                        <?= htmlspecialchars($analytics['total_pengembalian']) ?>
                    </div>
                    <p class="text-sm text-gray-600 flex items-center">
                        <i class="fas fa-history mr-1"></i> 7 hari terakhir
                    </p>
                </div>
            </div>

            <!-- Daily Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Pengguna Aktif Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover transform hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-green-600">Pengguna Aktif</h3>
                        <div class="bg-green-100 rounded-full p-3 transform hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-users text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="text-4xl font-extrabold text-gray-800 mb-2">
                        <?= htmlspecialchars($analytics['unique_users']) ?>
                    </div>
                    <p class="text-sm text-gray-600 flex items-center">
                        <i class="fas fa-calendar-day mr-1"></i> Hari ini
                    </p>
                </div>

                <!-- Aktivitas Hari Ini Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover transform hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-purple-600">Aktivitas Hari Ini</h3>
                        <div class="bg-purple-100 rounded-full p-3 transform hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-bolt text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="text-4xl font-extrabold text-gray-800 mb-2">
                        <?= htmlspecialchars($analytics['today_actions']) ?>
                    </div>
                    <p class="text-sm text-gray-600 flex items-center">
                        <i class="fas fa-history mr-1"></i> Total hari ini
                    </p>
                </div>
            </div>

            <!-- Activity Chart -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 transform hover:shadow-xl transition-all duration-300">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-blue-700 flex items-center">
                        <i class="fas fa-chart-area mr-2"></i>Tren Aktivitas
                    </h3>
                    <div class="flex space-x-2">
                        <button class="chart-filter active px-4 py-2 rounded-lg text-sm font-medium bg-blue-500 text-white hover:bg-blue-600 transition-colors" data-period="7">7 Hari</button>
                        <button class="chart-filter px-4 py-2 rounded-lg text-sm font-medium bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors" data-period="30">30 Hari</button>
                    </div>
                </div>
                <div class="relative h-[400px]">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>

            <!-- Recent Activities Table -->
            <div class="bg-white rounded-2xl shadow-lg p-6 transform hover:shadow-xl transition-all duration-300">
                <h3 class="text-xl font-bold text-blue-700 mb-4 flex items-center">
                    <i class="fas fa-history mr-2"></i>Aktivitas Terbaru
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($recentActivities)): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($activity['username']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $activity['action_type'] === 'peminjaman' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                                <?= htmlspecialchars($activity['action_type']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($activity['ip_address']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('d M Y H:i', strtotime($activity['created_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                        Tidak ada aktivitas terbaru
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts untuk Chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('activityChart').getContext('2d');

            // Create gradient fills
            const blueGradient = ctx.createLinearGradient(0, 0, 0, 400);
            blueGradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
            blueGradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

            const greenGradient = ctx.createLinearGradient(0, 0, 0, 400);
            greenGradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
            greenGradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

            const orangeGradient = ctx.createLinearGradient(0, 0, 0, 400);
            orangeGradient.addColorStop(0, 'rgba(245, 158, 11, 0.2)');
            orangeGradient.addColorStop(1, 'rgba(245, 158, 11, 0)');

            let activityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chartData['dates'] ?? []) ?>,
                    datasets: [{
                            label: 'Total Aktivitas',
                            data: <?= json_encode($chartData['totals'] ?? []) ?>,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: blueGradient,
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: 'rgb(59, 130, 246)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Peminjaman',
                            data: <?= json_encode($chartData['peminjaman'] ?? []) ?>,
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: greenGradient,
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: 'rgb(16, 185, 129)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Pengembalian',
                            data: <?= json_encode($chartData['pengembalian'] ?? []) ?>,
                            borderColor: 'rgb(245, 158, 11)',
                            backgroundColor: orangeGradient,
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: 'rgb(245, 158, 11)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 12,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#1f2937',
                            bodyColor: '#1f2937',
                            borderColor: '#e5e7eb',
                            borderWidth: 1,
                            padding: 12,
                            bodySpacing: 8,
                            bodyFont: {
                                family: "'Inter', sans-serif"
                            },
                            titleFont: {
                                family: "'Inter', sans-serif",
                                weight: '600'
                            },
                            callbacks: {
                                title: function(tooltipItems) {
                                    return 'Tanggal: ' + tooltipItems[0].label;
                                },
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y + ' aktivitas';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 12
                                },
                                color: '#6b7280'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 12
                                },
                                color: '#6b7280',
                                padding: 10,
                                callback: function(value) {
                                    return value + ' aktivitas';
                                }
                            }
                        }
                    }
                }
            });

            // Handle period changes
            document.querySelectorAll('.chart-filter').forEach(button => {
                button.addEventListener('click', async function() {
                    const period = this.dataset.period;

                    // Update button styles
                    document.querySelectorAll('.chart-filter').forEach(btn => {
                        btn.classList.remove('bg-blue-500', 'text-white');
                        btn.classList.add('bg-gray-200', 'text-gray-700');
                    });
                    this.classList.remove('bg-gray-200', 'text-gray-700');
                    this.classList.add('bg-blue-500', 'text-white');

                    try {
                        const response = await fetch(`includes/get-chart-data.php?period=${period}`);
                        if (!response.ok) throw new Error('Network response was not ok');

                        const result = await response.json();

                        if (result.status === 'success') {
                            // Update chart with animation
                            activityChart.data.labels = result.data.dates;
                            activityChart.data.datasets[0].data = result.data.totals;
                            activityChart.data.datasets[1].data = result.data.peminjaman;
                            activityChart.data.datasets[2].data = result.data.pengembalian;

                            activityChart.update('active');
                        } else {
                            console.error('Error:', result.message);
                        }
                    } catch (error) {
                        console.error('Error fetching chart data:', error);
                    }
                });
            });
        });
    </script>

    </main>
</body>