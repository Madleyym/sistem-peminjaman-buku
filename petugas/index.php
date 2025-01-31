<?php
session_start();
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Book.php';
require_once '../classes/Borrowing.php';

function isStaff()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === User::getRoles()['STAFF'];
}

// Redirect jika tidak login atau bukan staff
if (!isStaff()) {
    header("Location: " . User::getLoginPath(User::getRoles()['STAFF']));
    exit();
}

// Ambil informasi user dari session
$user_info = [
    'id' => $_SESSION['user_id'] ?? null,
    'name' => $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Unknown',
    'email' => $_SESSION['user_email'] ?? '',
    'login_time' => $_SESSION['login_time'] ?? date('Y-m-d H:i:s')
];

$database = new Database();
$conn = $database->getConnection();
$bookManager = new Book($conn);
$borrowManager = new Borrowing($conn);

// Dapatkan statistik untuk dashboard
$loanStats = $borrowManager->getLoanStatistics();

// Dapatkan daftar peminjaman aktif
$activeLoans = $borrowManager->getActiveLoans(5);

// Dapatkan daftar peminjaman terlambat
$overdueLoans = $borrowManager->getOverdueLoans();

// Fetch statistics
$totalBooks = $bookManager->countTotalBooks();
$activeLoansCount = $borrowManager->countActiveLoans();
$overdueLoansCount = $borrowManager->countOverdueLoans();

// Analytics date ranges - langsung gunakan date()
$today = date('Y-m-d');
$week_ago = date('Y-m-d', strtotime('-7 days'));

// Fetch analytics data
function getAnalyticsData($conn, $today, $week_ago)
{
    // Total transactions in last 7 days
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM loans WHERE created_at >= ?");
    $stmt->execute([$week_ago]);
    $total_transactions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Today's transactions
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM loans WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $today_transactions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pending returns
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM loans WHERE return_date IS NULL");
    $stmt->execute();
    $pending_returns = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    return [
        'total_transactions' => $total_transactions,
        'today_transactions' => $today_transactions,
        'pending_returns' => $pending_returns
    ];
}

$analytics = getAnalyticsData($conn, $today, $week_ago);

// Recent transactions with user names
$stmt = $conn->prepare("
    SELECT l.*, u.name as user_name, b.title as book_title
    FROM loans l 
    LEFT JOIN users u ON l.user_id = u.id 
    LEFT JOIN books b ON l.book_id = b.id
    ORDER BY l.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Chart data for the last 7 days
$stmt = $conn->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as total 
    FROM loans 
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


?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - <?= htmlspecialchars(SITE_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #2ecc71;
            --secondary-color: #27ae60;
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
    <?php include 'includes/navigation.php'; ?>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <!-- Staff Menu Section -->
        <section class="bg-white shadow-lg rounded-2xl p-8 mb-8">
            <h2 class="text-3xl font-bold text-green-700 mb-6 flex items-center">
                <i class="fas fa-tasks mr-2"></i>Menu Petugas
            </h2>

            <!-- Menu Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Peminjaman Buku Card -->
                <a href="/sistem/petugas/peminjaman-buku/"
                    class="group card-hover bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 rounded-xl p-6 text-center transition-all duration-300">
                    <div class="bg-green-500 text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 transform group-hover:rotate-12 transition-transform duration-300">
                        <i class="fas fa-book-reader text-2xl"></i>
                    </div>
                    <span class="font-semibold text-green-800">Peminjaman Buku</span>
                </a>

                <!-- Pengembalian Buku Card -->
                <a href="/sistem/petugas/pengembalian/"
                    class="group card-hover bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-xl p-6 text-center transition-all duration-300">
                    <div class="bg-blue-500 text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 transform group-hover:rotate-12 transition-transform duration-300">
                        <i class="fas fa-undo text-2xl"></i>
                    </div>
                    <span class="font-semibold text-blue-800">Pengembalian Buku</span>
                </a>

                <!-- Data Anggota Card -->
                <a href="/sistem/petugas/anggota/"
                    class="group card-hover bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 rounded-xl p-6 text-center transition-all duration-300">
                    <div class="bg-purple-500 text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 transform group-hover:rotate-12 transition-transform duration-300">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <span class="font-semibold text-purple-800">Data Anggota</span>
                </a>

                <!-- QR Code Scanning Card -->
                <a href="/sistem/petugas/qr-kode/pindai-qr.php"
                    class="group card-hover bg-gradient-to-br from-yellow-50 to-yellow-100 hover:from-yellow-100 hover:to-yellow-200 rounded-xl p-6 text-center transition-all duration-300">
                    <div class="bg-yellow-500 text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 transform group-hover:rotate-12 transition-transform duration-300">
                        <i class="fas fa-qrcode text-2xl"></i>
                    </div>
                    <span class="font-semibold text-yellow-800">Pinjam dengan QR</span>
                </a>
            </div>
        </section>

        <!-- Statistics Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Transactions Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-green-700">Total Transaksi</h3>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-exchange-alt text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="text-4xl font-extrabold text-gray-800 mb-2"><?= $analytics['total_transactions'] ?></div>
                <p class="text-sm text-gray-600">7 hari terakhir</p>
            </div>

            <!-- Today's Transactions Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-blue-600">Transaksi Hari Ini</h3>
                    <div class="bg-blue-100 rounded-full p-3">
                        <i class="fas fa-calendar-day text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="text-4xl font-extrabold text-gray-800 mb-2"><?= $analytics['today_transactions'] ?></div>
                <p class="text-sm text-gray-600">Total hari ini</p>
            </div>

            <!-- Pending Returns Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-red-600">Menunggu Pengembalian</h3>
                    <div class="bg-red-100 rounded-full p-3">
                        <i class="fas fa-clock text-red-600 text-xl"></i>
                    </div>
                </div>
                <div class="text-4xl font-extrabold text-gray-800 mb-2"><?= $analytics['pending_returns'] ?></div>
                <p class="text-sm text-gray-600">Buku yang belum dikembalikan</p>
            </div>
        </div>

        <!-- Transaction Chart Section -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-green-700 flex items-center">
                    <i class="fas fa-chart-line mr-2"></i>Tren Transaksi
                </h3>
                <div class="flex space-x-2">
                    <button class="chart-filter active px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-600 hover:bg-green-200"
                        data-period="week">7 Hari</button>
                    <button class="chart-filter px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200"
                        data-period="month">30 Hari</button>
                </div>
            </div>
            <div class="relative" style="height: 400px;">
                <canvas id="transactionChart"></canvas>
            </div>
        </div>

        <!-- Recent Transactions Section -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-green-700 mb-4 flex items-center">
                <i class="fas fa-history mr-2"></i>Transaksi Terbaru
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peminjam</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Buku</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($transaction['user_name']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= htmlspecialchars($transaction['book_title']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                       <?= $transaction['return_date'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                        <?= $transaction['return_date'] ? 'Dikembalikan' : 'Dipinjam' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d M Y H:i', strtotime($transaction['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('transactionChart').getContext('2d');
            const transactionChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chart_dates) ?>,
                    datasets: [{
                        label: 'Total Transaksi',
                        data: <?= json_encode($chart_data) ?>,
                        fill: true,
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        borderColor: 'rgb(46, 204, 113)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
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
                                    return value + ' transaksi';
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

            // Filter periode
            document.querySelectorAll('.chart-filter').forEach(button => {
                button.addEventListener('click', async function() {
                    const period = this.dataset.period;

                    // Update status aktif
                    document.querySelectorAll('.chart-filter').forEach(btn => {
                        btn.classList.remove('active', 'bg-green-100', 'text-green-600');
                        btn.classList.add('bg-gray-100', 'text-gray-600');
                    });
                    this.classList.add('active', 'bg-green-100', 'text-green-600');
                    this.classList.remove('bg-gray-100', 'text-gray-600');

                    // Simulasi loading
                    transactionChart.options.plugins.legend.display = false;
                    transactionChart.update('none');

                    // Simulasi delay
                    await new Promise(resolve => setTimeout(resolve, 300));

                    // Reset tampilan
                    transactionChart.options.plugins.legend.display = true;
                    transactionChart.update();
                });
            });

            // Menambahkan informasi user yang sedang login
            // const currentUser = {
            //     username: 'Madleyym',
            //     loginTime: '2025-01-31 12:09:38'
            // };

            // Bisa ditambahkan logika untuk menampilkan info user jika diperlukan
            console.log(`Logged in as: ${currentUser.username}`);
            console.log(`Login time: ${currentUser.loginTime}`);
        });
    </script>
</body>

</html>