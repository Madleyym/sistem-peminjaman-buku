<?php

session_start();
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
// Kode lainnya...
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../classes/Book.php';

$database = new Database();
$conn = $database->getConnection();
$bookManager = new Book($conn);

// Fetch statistics
$totalBooks = $bookManager->countTotalBooks();
$lowStockBooks = $bookManager->countLowStockBooks();
$recentlyAddedBooks = $bookManager->getRecentlyAddedBooks(5);


// Analytics date ranges
$today = date('Y-m-d');
$week_ago = date('Y-m-d', strtotime('-7 days'));


// Fetch analytics data
function getAnalyticsData($conn, $today, $week_ago)
{
    // Total activities in last 7 days
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM activity_logs WHERE created_at >= ?");
    $stmt->execute([$week_ago]);
    $total_activities = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Unique users today
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as total FROM activity_logs WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $unique_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Today's actions
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM activity_logs WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $today_actions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    return [
        'total_activities' => $total_activities,
        'unique_users' => $unique_users,
        'today_actions' => $today_actions
    ];
}

$analytics = getAnalyticsData($conn, $today, $week_ago);

// Recent activities with user names
$stmt = $conn->prepare("
    SELECT al.*, u.name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Chart data
$stmt = $conn->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as total 
    FROM activity_logs 
    WHERE created_at >= ? 
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
");
$stmt->execute([$week_ago]);
$chart_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chart_dates = array_map(function ($result) {
    return date('d M', strtotime($result['date']));
}, $chart_results);

$chart_data = array_column($chart_results, 'total');
// Tambahkan ini di bagian atas file setelah session_start()
// var_dump($_SESSION); // Untuk debugging, hapus setelah selesai

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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
                        <?php if (isAdmin()): ?>
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
                                <a href="/sistem/admin/auth/login.php"
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
        <!-- Improved Admin Menu Section -->
        <section class="bg-white shadow-lg rounded-2xl p-8 mb-8 transform hover:shadow-xl transition-all duration-300">
            <h2 class="text-3xl font-bold text-blue-700 mb-6 flex items-center">
                <i class="fas fa-tools mr-2"></i>Menu Admin
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php if (isAdmin()): ?>
                    <!-- Menu khusus admin -->
                    <a href="/sistem/admin/manage-books/dashboard-book.php"
                        class="group card-hover bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-xl p-6 text-center transition-all duration-300 transform hover:-translate-y-1">
                        <div class="bg-blue-500 text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 transform group-hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-book-open text-2xl"></i>
                        </div>
                        <span class="font-semibold text-blue-800">Manajemen Buku</span>
                    </a>

                    <a href="/sistem/admin/manage-user/manage-user.php"
                        class="group card-hover bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 rounded-xl p-6 text-center transition-all duration-300 transform hover:-translate-y-1">
                        <div class="bg-green-500 text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 transform group-hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <span class="font-semibold text-green-800">Manajemen Pengguna</span>
                    </a>

                    <a href="/sistem/admin/manage-borrow/dashboard-borrow.php"
                        class="group card-hover bg-gradient-to-br from-yellow-50 to-yellow-100 hover:from-yellow-100 hover:to-yellow-200 rounded-xl p-6 text-center transition-all duration-300 transform hover:-translate-y-1">
                        <div class="bg-yellow-500 text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 transform group-hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-handshake text-2xl"></i>
                        </div>
                        <span class="font-semibold text-yellow-800">Peminjaman</span>
                    </a>

                    <a href="/sistem/admin/reports/dashboard-reports.php"
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
                            <a href="/sistem/admin/auth/login.php"
                                class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all duration-300 transform hover:-translate-y-1">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Login Sebagai Admin
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Enhanced Statistics Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover transform hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-blue-700">Total Aktivitas</h3>
                    <div class="bg-blue-100 rounded-full p-3 transform hover:rotate-12 transition-transform duration-300">
                        <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="text-4xl font-extrabold text-gray-800 mb-2"><?= $analytics['total_activities'] ?></div>
                <p class="text-sm text-gray-600 flex items-center">
                    <i class="fas fa-clock mr-1"></i> 7 hari terakhir
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover transform hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-green-600">Pengguna Aktif</h3>
                    <div class="bg-green-100 rounded-full p-3 transform hover:rotate-12 transition-transform duration-300">
                        <i class="fas fa-users text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="text-4xl font-extrabold text-gray-800 mb-2"><?= $analytics['unique_users'] ?></div>
                <p class="text-sm text-gray-600 flex items-center">
                    <i class="fas fa-calendar-day mr-1"></i> Hari ini
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover transform hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-purple-600">Aktivitas Hari Ini</h3>
                    <div class="bg-purple-100 rounded-full p-3 transform hover:rotate-12 transition-transform duration-300">
                        <i class="fas fa-bolt text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="text-4xl font-extrabold text-gray-800 mb-2"><?= $analytics['today_actions'] ?></div>
                <p class="text-sm text-gray-600 flex items-center">
                    <i class="fas fa-history mr-1"></i> Total hari ini
                </p>
            </div>
        </div>

        <!-- Enhanced Activity Chart -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 transform hover:shadow-xl transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-blue-700 flex items-center">
                    <i class="fas fa-chart-area mr-2"></i>Tren Aktivitas
                </h3>
                <div class="flex space-x-2">
                    <button class="chart-filter active px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors" data-period="week">7 Hari</button>
                    <button class="chart-filter px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" data-period="month">30 Hari</button>
                </div>
            </div>
            <div class="relative" style="height: 400px;">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        <!-- Enhanced Recent Activities Table -->
        <div>
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
                            <?php foreach ($recent_activities as $activity): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($activity['username'] ?? 'Anonymous') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div> <!-- Penutup div terakhir dari tabel aktivitas -->

        <!-- Scripts untuk Chart -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Data for chart from PHP dengan waktu login yang diberikan
                const currentUser = {
                    username: 'Madleyym',
                    loginTime: '2025-01-28 05:28:28'
                };

                // Chart Configuration
                const ctx = document.getElementById('activityChart').getContext('2d');
                const activityChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($chart_dates) ?>,
                        datasets: [{
                                label: 'Total Aktivitas',
                                data: <?= json_encode($chart_data) ?>,
                                fill: true,
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderColor: 'rgb(59, 130, 246)',
                                tension: 0.4,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Peminjaman',
                                data: <?= json_encode($chart_peminjaman ?? []) ?>,
                                fill: true,
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderColor: 'rgb(16, 185, 129)',
                                tension: 0.4,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Pengembalian',
                                data: <?= json_encode($chart_pengembalian ?? []) ?>,
                                fill: true,
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                borderColor: 'rgb(245, 158, 11)',
                                tension: 0.4,
                                pointRadius: 4,
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
                                        size: 12
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
                                    weight: 'bold'
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
                                        label += context.parsed.y + ' aktivitas';
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        family: "'Inter', sans-serif"
                                    },
                                    padding: 10,
                                    callback: function(value) {
                                        return value + ' aktivitas';
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        family: "'Inter', sans-serif"
                                    },
                                    padding: 10
                                }
                            }
                        }
                    }
                });

                // Period Filter Handler
                document.querySelectorAll('.chart-filter').forEach(button => {
                    button.addEventListener('click', async function() {
                        const period = this.dataset.period;

                        // Update active state
                        document.querySelectorAll('.chart-filter').forEach(btn => {
                            btn.classList.remove('active', 'bg-blue-100', 'text-blue-600');
                            btn.classList.add('bg-gray-100', 'text-gray-600');
                        });
                        this.classList.add('active', 'bg-blue-100', 'text-blue-600');
                        this.classList.remove('bg-gray-100', 'text-gray-600');

                        // Simulasi loading
                        activityChart.options.plugins.legend.display = false;
                        activityChart.update('none');

                        await new Promise(resolve => setTimeout(resolve, 300));

                        // Reset display
                        activityChart.options.plugins.legend.display = true;
                        activityChart.update();
                    });
                });
            });
        </script>
    </main>
</body>