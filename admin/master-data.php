<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Master Data Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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
        --danger-color: #e74c3c;
        --success-color: #2ecc71;
        --warning-color: #f39c12;
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
        border-radius: 8px;
    }

    input:focus,
    select:focus,
    textarea:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    }

    /* Dashboard card styles */
    .dashboard-card {
        background-color: var(--white);
        border-radius: var(--border-radius);
        box-shadow: 0 4px 6px var(--shadow-color);
        padding: 1.5rem;
        transition: transform 0.3s ease;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
    }

    /* Status indicator styles */
    .status-indicator {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-weight: 600;
    }

    .status-active {
        background-color: rgba(46, 204, 113, 0.1);
        color: var(--success-color);
    }

    .status-inactive {
        background-color: rgba(231, 76, 60, 0.1);
        color: var(--danger-color);
    }

    .status-warning {
        background-color: rgba(243, 156, 18, 0.1);
        color: var(--warning-color);
    }

    /* Button styles */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: var(--white);
    }

    .btn-primary:hover {
        background-color: darken(var(--primary-color), 10%);
    }

    .btn-secondary {
        background-color: var(--secondary-color);
        color: var(--white);
    }

    .btn-danger {
        background-color: var(--danger-color);
        color: var(--white);
    }

    /* Responsive table styles */
    .responsive-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: var(--border-radius);
        overflow: hidden;
    }

    .responsive-table thead {
        background-color: #f1f5f9;
    }

    .responsive-table th,
    .responsive-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .responsive-table tbody tr:hover {
        background-color: rgba(52, 152, 219, 0.05);
    }

    /* Modal styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-content {
        background-color: var(--white);
        border-radius: var(--border-radius);
        box-shadow: 0 10px 25px var(--shadow-color);
        max-width: 90%;
        width: 600px;
        padding: 2rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .responsive-table {
            font-size: 0.875rem;
        }
    }
</style>

<body class="bg-gray-100">
    <div x-data="masterDataDashboard()" class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">Master Data Dashboard</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Categories Card -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">Kategori Buku</h2>
                <div class="space-y-2">
                    <p>Total Kategori: <span x-text="summaryData.categories.total_categories">0</span></p>
                    <p>Total Buku: <span x-text="summaryData.categories.total_books">0</span></p>
                    <p>Rata-rata Buku per Kategori: <span x-text="summaryData.categories.avg_books_per_category.toFixed(2)">0</span></p>
                    <button @click="showCategoryModal = true" class="btn btn-primary">Kelola Kategori</button>
                </div>
            </div>

            <!-- Users Card -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">Manajemen Pengguna</h2>
                <div class="space-y-2">
                    <p>Total Pengguna: <span x-text="summaryData.users.total_users">0</span></p>
                    <p>Admin: <span x-text="summaryData.users.admin_count">0</span></p>
                    <p>Librarian: <span x-text="summaryData.users.librarian_count">0</span></p>
                    <p>Member: <span x-text="summaryData.users.member_count">0</span></p>
                    <button @click="showUserModal = true" class="btn btn-primary">Kelola Pengguna</button>
                </div>
            </div>

            <!-- Book Loans Card -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">Peminjaman Buku</h2>
                <div class="space-y-2">
                    <p>Total Peminjaman: <span x-text="summaryData.book_loans.total_loans">0</span></p>
                    <p>Peminjaman Aktif: <span x-text="summaryData.book_loans.active_loans">0</span></p>
                    <p>Peminjaman Terlambat: <span x-text="summaryData.book_loans.overdue_loans">0</span></p>
                    <button class="btn btn-primary">Lihat Detail Peminjaman</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function masterDataDashboard() {
            return {
                summaryData: {
                    categories: {
                        total_categories: 0,
                        total_books: 0
                    },
                    users: {
                        total_users: 0
                    },
                    book_loans: {
                        total_loans: 0
                    }
                },
                showCategoryModal: false,
                showUserModal: false,

                init() {
                    this.fetchDashboardData();
                },

                fetchDashboardData() {
                    // Implementasi AJAX untuk mengambil data
                    // Misalnya menggunakan fetch() atau axios
                }
            }
        }
    </script>
</body>

</html>