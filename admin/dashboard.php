<?php
// admin/dashboard.php
session_start();
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../classes/AdminDashboard.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$dashboard = new dashboard($conn);

// Fetch dashboard statistics
$stats = [
    'total_books' => $dashboard->getTotalBooks(),
    'total_users' => $dashboard->getTotalUsers(),
    'active_loans' => $dashboard->getActiveLoanCount(),
    'overdue_loans' => $dashboard->getOverdueLoanCount()
];

// Recent activities
$recentActivities = $dashboard->getRecentActivities(10);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../includes/meta.php'; ?>
    <title>Admin Dashboard - <?= SITE_NAME ?></title>
</head>
<body>
    <?php include '../includes/admin-navbar.php'; ?>

    <div class="admin-dashboard">
        <div class="dashboard-header">
            <h1>Selamat datang, Admin!</h1>
            <p>Statistik sistem peminjaman buku</p>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Buku</h3>
                <p><?= $stats['total_books'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Pengguna</h3>
                <p><?= $stats['total_users'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Peminjaman Aktif</h3>
                <p><?= $stats['active_loans'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Peminjaman Terlambat</h3>
                <p><?= $stats['overdue_loans'] ?></p>
            </div>
        </div>

        <div class="recent-activities">
            <h2>Aktivitas Terkini</h2>
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentActivities as $activity): ?>
                    <tr>
                        <td><?= $activity['created_at'] ?></td>
                        <td><?= $activity['user_name'] ?></td>
                        <td><?= $activity['action'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../includes/admin-sidebar.php'; ?>
    <?php include '../includes/footer-scripts.php'; ?>
</body>
</html>