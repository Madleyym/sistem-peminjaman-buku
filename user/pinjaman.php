<?php
session_start();
// Add these status constants
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_BORROWED', 'borrowed');
define('STATUS_RETURNED', 'returned');
define('STATUS_OVERDUE', 'overdue');
define('STATUS_REJECTED', 'rejected');
// Definisi konstanta
define('UPLOAD_BOOK_COVERS_URL', '/sistem/uploads/book_covers/');
define('DEFAULT_BOOK_COVER_URL', '/sistem/uploads/books/book-default.png');

require_once '../config/database.php';
require_once '../config/constants.php';

// Validasi login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . LOGIN_URL);
    exit();
}

// Helper function untuk URL gambar
function getBookCoverUrl($coverImage)
{
    if (!empty($coverImage) && file_exists($_SERVER['DOCUMENT_ROOT'] . UPLOAD_BOOK_COVERS_URL . $coverImage)) {
        return UPLOAD_BOOK_COVERS_URL . $coverImage;
    }
    return DEFAULT_BOOK_COVER_URL;
}

// Database connection
try {
    $database = new Database();
    $conn = $database->getConnection();
    $conn->query("SELECT 1");
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Koneksi database gagal. Silakan coba beberapa saat lagi.");
}

function getStatusBadgeClass($status)
{
    $classes = [
        STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
        STATUS_APPROVED => 'bg-blue-100 text-blue-800',
        STATUS_BORROWED => 'bg-green-100 text-green-800',
        STATUS_RETURNED => 'bg-gray-100 text-gray-800',
        STATUS_OVERDUE => 'bg-red-100 text-red-800',
        STATUS_REJECTED => 'bg-red-100 text-red-800'
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}

function getStatusLabel($status)
{
    $labels = [
        STATUS_PENDING => 'Menunggu Persetujuan',
        STATUS_APPROVED => 'Disetujui',
        STATUS_BORROWED => 'Sedang Dipinjam',
        STATUS_RETURNED => 'Dikembalikan',
        STATUS_OVERDUE => 'Terlambat',
        STATUS_REJECTED => 'Ditolak'
    ];
    return $labels[$status] ?? 'Unknown';
}

// Get user's borrowing history
$user_id = $_SESSION['user_id'];
$borrowings = [];

$query = "
    SELECT 
        b.id as borrow_id,
        b.book_id,
        b.borrow_date,
        b.due_date,
        b.return_date,
        b.status,
        b.notes,
        bk.title as book_title,
        bk.cover_image,
        bk.author,
        bk.isbn
    FROM borrowings b
    LEFT JOIN books bk ON b.book_id = bk.id
    WHERE b.user_id = :user_id
    ORDER BY b.borrow_date DESC
";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Query error: " . $e->getMessage());
    die("Terjadi kesalahan saat mengambil data peminjaman.");
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman | <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        /* Status Badge Styles */
        .status-badge {
            @apply px-2.5 py-1 text-xs font-medium rounded-full whitespace-nowrap;
        }

        .status-pending {
            @apply bg-yellow-100 text-yellow-800;
        }

        .status-approved {
            @apply bg-blue-100 text-blue-800;
        }

        .status-borrowed {
            @apply bg-green-100 text-green-800;
        }

        .status-returned {
            @apply bg-gray-100 text-gray-800;
        }

        .status-overdue {
            @apply bg-red-100 text-red-800;
        }

        .status-rejected {
            @apply bg-red-100 text-red-800;
        }

        /* Card Styles */
        .grid>div {
            @apply bg-white rounded-xl shadow-md overflow-hidden transition-shadow duration-300;
            height: 280px !important;
        }

        .grid>div:hover {
            @apply shadow-lg;
        }

        /* Book Cover Styles */
        .book-cover {
            width: 140px !important;
            height: 100%;
            flex-shrink: 0;
            background-color: #f3f4f6;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Content Layout */
        .book-content {
            @apply flex-1 p-4 flex flex-col justify-between overflow-hidden;
        }

        /* Text Truncation */
        .truncate-2-lines {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Button Styles */
        .action-button {
            @apply inline-flex items-center justify-center px-3 py-1.5 text-sm transition-colors rounded-md flex-1;
        }

        .action-button-blue {
            @apply text-blue-600 hover:text-blue-800 border border-blue-600 hover:bg-blue-50;
        }

        .action-button-green {
            @apply text-green-600 hover:text-green-800 border border-green-600 hover:bg-green-50;
        }

        /* Info Icons */
        .info-icon {
            @apply w-5 text-gray-400 flex-shrink-0;
        }

        /* Navigation & Time Display */
        .nav-container {
            @apply sticky top-0 z-50 bg-white shadow-md;
        }

        .time-display {
            @apply fixed bottom-4 right-4 bg-white px-4 py-2 rounded-lg shadow-lg border border-gray-200 text-sm text-gray-700 z-50;
        }

        .time-display i {
            @apply text-blue-500;
        }

        /* Debug Info */
        .debug-info {
            @apply bg-gray-100 p-4 rounded-lg text-sm text-gray-600 mb-4;
        }

        /* Loading State */
        .loading {
            @apply opacity-70 pointer-events-none;
        }

        /* Card Styles */
        .grid>div {
            @apply bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all duration-300;
            height: 280px !important;
        }

        .grid>div:hover {
            @apply shadow-md transform -translate-y-1;
        }

        /* Book Cover Styles */
        .book-cover {
            @apply w-[140px] h-full flex-shrink-0 bg-gray-100 relative overflow-hidden;
        }

        .book-cover img {
            @apply w-full h-full object-cover transition-transform duration-300;
        }

        .book-cover:hover img {
            @apply transform scale-110;
        }

        /* Content Layout */
        .book-content {
            @apply flex-1 p-5 flex flex-col justify-between;
        }

        /* Status Badge */
        .status-badge {
            @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-medium transition-colors;
        }

        /* Action Buttons */
        .action-button {
            @apply inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md transition-all duration-200;
        }

        .action-button:hover {
            @apply transform scale-105;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .grid>div {
                height: auto !important;
                min-height: 280px;
            }

            .book-cover {
                width: 120px !important;
            }

            .book-content {
                @apply p-3;
            }

            .action-button {
                @apply px-2 py-1 text-xs;
            }
        }

        /* Animation Effects */
        .animate-fade {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-screen-2xl mx-auto flex-grow w-full">
        <main class="container mx-auto px-4 py-8">
            <!-- Page Header -->
            <div class="mb-8 bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Riwayat Peminjaman</h1>
                        <p class="text-gray-600 mt-2">Daftar buku yang Anda pinjam</p>
                    </div>
                    <div class="text-right">
                        <span class="bg-blue-100 text-blue-800 text-sm font-medium mr-2 px-3 py-1 rounded-full">
                            Total: <?= count($borrowings) ?> Buku
                        </span>
                    </div>
                </div>
            </div>

            <!-- Status Filter Buttons -->
            <div class="mb-6 flex flex-wrap gap-2">
                <button class="status-badge px-4 py-2 rounded-lg hover:shadow-md transition-all duration-300 bg-white active" data-filter="all">
                    <i class="fas fa-layer-group mr-1"></i>
                    Semua
                </button>
                <button class="status-badge px-4 py-2 rounded-lg hover:shadow-md transition-all duration-300 bg-white" data-filter="pending">
                    <i class="fas fa-clock mr-1"></i>
                    Menunggu
                </button>
                <button class="status-badge px-4 py-2 rounded-lg hover:shadow-md transition-all duration-300 bg-white" data-filter="approved">
                    <i class="fas fa-check mr-1"></i>
                    Disetujui
                </button>
                <button class="status-badge px-4 py-2 rounded-lg hover:shadow-md transition-all duration-300 bg-white" data-filter="borrowed">
                    <i class="fas fa-book-reader mr-1"></i>
                    Dipinjam
                </button>
                <button class="status-badge px-4 py-2 rounded-lg hover:shadow-md transition-all duration-300 bg-white" data-filter="overdue">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Terlambat
                </button>
                <button class="status-badge px-4 py-2 rounded-lg hover:shadow-md transition-all duration-300 bg-white" data-filter="returned">
                    <i class="fas fa-check-double mr-1"></i>
                    Dikembalikan
                </button>
            </div>

            <!-- Status Message Container -->
            <div id="status-message" class="mb-6 p-4 rounded-lg text-center hidden"></div>

            <!-- Borrowings Container -->
            <div class="borrowings-container">
                <!-- Grid Layout -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($borrowings as $borrow): ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1"
                            data-status="<?= strtolower($borrow['status']) ?>">
                            <div class="flex h-[300px]">
                                <!-- Book Cover -->
                                <div class="w-[140px] flex-shrink-0 bg-gray-50 overflow-hidden">
                                    <div class="h-full relative group">
                                        <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                            src="<?= !empty($borrow['cover_image'])
                                                        ? '/sistem/uploads/book_covers/' . htmlspecialchars($borrow['cover_image'])
                                                        : '/sistem/uploads/books/book-default.png' ?>"
                                            alt="<?= htmlspecialchars($borrow['book_title']) ?>"
                                            onerror="this.onerror=null; this.src='/sistem/uploads/books/book-default.png';">
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 p-6 flex flex-col justify-between">
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-start gap-3">
                                            <div class="flex-1">
                                                <h3 class="text-lg font-semibold text-gray-900 leading-tight mb-1 line-clamp-2">
                                                    <?= htmlspecialchars($borrow['book_title']) ?>
                                                </h3>
                                                <p class="text-sm text-gray-600">
                                                    oleh <?= htmlspecialchars($borrow['author']) ?>
                                                </p>
                                            </div>

                                            <!-- Status Badge -->
                                            <?php
                                            $statusClasses = [
                                                'borrowed' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                'returned' => 'bg-green-50 text-green-700 border-green-200',
                                                'overdue' => 'bg-red-50 text-red-700 border-red-200',
                                                'pending' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                                'approved' => 'bg-purple-50 text-purple-700 border-purple-200'
                                            ];
                                            $statusClass = $statusClasses[strtolower($borrow['status'])] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                                            ?>
                                            <span class="px-3 py-1.5 text-xs font-medium rounded-full border shadow-sm <?= $statusClass ?>">
                                                <?= ucfirst($borrow['status']) ?>
                                            </span>
                                        </div>

                                        <!-- Borrowing Details -->
                                        <div class="space-y-2.5">
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-calendar-plus w-5 text-gray-400"></i>
                                                <span>Dipinjam: <?= date('d M Y', strtotime($borrow['borrow_date'])) ?></span>
                                            </div>
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-calendar-check w-5 text-gray-400"></i>
                                                <span>Tenggat: <?= date('d M Y', strtotime($borrow['due_date'])) ?></span>
                                            </div>
                                            <?php if ($borrow['return_date']): ?>
                                                <div class="flex items-center text-sm text-gray-600">
                                                    <i class="fas fa-calendar-minus w-5 text-gray-400"></i>
                                                    <span>Dikembalikan: <?= date('d M Y', strtotime($borrow['return_date'])) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex gap-3 mt-4">
                                        <a href="/sistem/public/detail-buku.php?id=<?= $borrow['book_id'] ?>"
                                            class="flex-1 inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors duration-300">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            Detail Buku
                                        </a>
                                        <?php if ($borrow['status'] === 'borrowed'): ?>
                                            <button data-action="return" data-borrow-id="<?= $borrow['borrow_id'] ?>"
                                                class="flex-1 return-book-btn inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100 transition-colors duration-300">
                                                <i class="fas fa-undo mr-2"></i>
                                                Kembalikan
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($borrow['status'] === 'pending'): ?>
                                            <button data-action="cancel" data-borrow-id="<?= $borrow['borrow_id'] ?>"
                                                class="flex-1 cancel-book-btn inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors duration-300">
                                                <i class="fas fa-times mr-2"></i>
                                                Batal
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Empty State -->
                <div id="empty-state" class="hidden text-center py-16 bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-6">
                        <i class="fas fa-search text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2" id="empty-state-message">
                        Tidak ada data
                    </h3>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto" id="empty-state-description">
                        Tidak ada peminjaman dengan status tersebut.
                    </p>
                    <?php if (empty($borrowings)): ?>
                        <a href="/sistem/public/daftar-buku.php"
                            class="inline-flex items-center px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-300 transform hover:-translate-y-1">
                            <i class="fas fa-search mr-2"></i>
                            Cari Buku
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.status-badge');
            const borrowingCards = document.querySelectorAll('[data-status]');
            const borrowingsContainer = document.querySelector('.grid');
            const emptyState = document.getElementById('empty-state');
            const statusMessage = document.getElementById('status-message');

            function filterBorrowings(status) {
                // Reset semua button styling
                filterButtons.forEach(btn => {
                    btn.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50');
                });

                // Update active button
                const activeButton = document.querySelector(`[data-filter="${status}"]`);
                if (activeButton) {
                    activeButton.classList.add('ring-2', 'ring-blue-500', 'bg-blue-50');
                }

                // Update status message
                const messages = {
                    'all': 'Menampilkan semua riwayat peminjaman',
                    'pending': 'Menampilkan peminjaman yang sedang menunggu persetujuan',
                    'approved': 'Menampilkan peminjaman yang telah disetujui',
                    'borrowed': 'Menampilkan buku yang sedang dipinjam',
                    'overdue': 'Menampilkan peminjaman yang telah melewati batas waktu',
                    'returned': 'Menampilkan buku yang telah dikembalikan'
                };

                statusMessage.textContent = messages[status];
                statusMessage.classList.remove('hidden');

                // Filter cards
                let visibleCount = 0;
                borrowingCards.forEach(card => {
                    const cardStatus = card.dataset.status;
                    const shouldShow = status === 'all' || cardStatus === status;
                    card.style.display = shouldShow ? 'block' : 'none';
                    if (shouldShow) visibleCount++;
                });

                // Update empty state visibility
                if (visibleCount === 0) {
                    borrowingsContainer.classList.add('hidden');
                    emptyState.classList.remove('hidden');

                    const emptyStateMessage = document.getElementById('empty-state-message');
                    const emptyStateDescription = document.getElementById('empty-state-description');

                    if (status === 'all') {
                        emptyStateMessage.textContent = 'Belum Ada Peminjaman';
                        emptyStateDescription.textContent = 'Anda belum meminjam buku apapun. Mulai pinjam buku untuk mengisi riwayat peminjaman Anda.';
                    } else {
                        emptyStateMessage.textContent = `Tidak ada peminjaman ${status}`;
                        emptyStateDescription.textContent = `Tidak ada peminjaman dengan status ${status} saat ini.`;
                    }
                } else {
                    borrowingsContainer.classList.remove('hidden');
                    emptyState.classList.add('hidden');
                }
            }

            // Event listeners untuk filter buttons
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const status = this.dataset.filter;
                    filterBorrowings(status);
                });
            });

            async function handleBorrowing(borrowId, action) {
                try {
                    let endpoint = '';
                    let title = '';
                    let text = '';
                    let confirmButtonColor = '#4F46E5';

                    switch (action) {
                        case 'confirm':
                            endpoint = '/sistem/user/konfirmasi-peminjaman.php';
                            title = 'Konfirmasi Pengambilan Buku';
                            text = 'Apakah Anda sudah mengambil buku ini?';
                            break;
                        case 'return':
                            endpoint = '/sistem/user/proses-pengembalian.php';
                            title = 'Konfirmasi Pengembalian';
                            text = 'Apakah Anda yakin ingin mengembalikan buku ini?';
                            break;
                        case 'cancel':
                            endpoint = '/sistem/user/batalkan-pinjaman.php'; // Sesuaikan dengan nama file yang benar
                            title = 'Batalkan Peminjaman';
                            text = 'Apakah Anda yakin ingin membatalkan peminjaman buku ini?';
                            confirmButtonColor = '#DC2626';
                            break;
                    }

                    const result = await Swal.fire({
                        title: title,
                        text: text,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: action === 'cancel' ? 'Ya, Batalkan' : 'Ya',
                        cancelButtonText: 'Tidak',
                        confirmButtonColor: confirmButtonColor,
                        cancelButtonColor: '#6B7280',
                        showLoaderOnConfirm: true,
                        preConfirm: async () => {
                            try {
                                console.log('Sending request to:', endpoint); // Tambahkan log untuk debugging
                                const response = await fetch(endpoint, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        borrow_id: borrowId,
                                        action_date: new Date().toISOString(),
                                        user_id: '<?= $_SESSION['user_id'] ?>',
                                        action: action
                                    })
                                });

                                if (!response.ok) {
                                    const errorData = await response.json().catch(() => ({}));
                                    throw new Error(errorData.message || 'Gagal memproses permintaan');
                                }

                                return await response.json();
                            } catch (error) {
                                console.error('Error:', error); // Tambahkan log untuk debugging
                                Swal.showValidationMessage(`Request failed: ${error.message}`);
                                throw error;
                            }
                        }
                    });

                    if (result.isConfirmed && result.value?.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: result.value.message || 'Peminjaman berhasil dibatalkan',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    }
                } catch (error) {
                    console.error('Error in handleBorrowing:', error); // Tambahkan log untuk debugging
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Terjadi kesalahan saat memproses permintaan'
                    });
                }
            }

            // Fungsi untuk menampilkan waktu
            function initializeTimeDisplay() {
                const timeDisplay = document.createElement('div');
                timeDisplay.className = 'fixed bottom-4 right-4 bg-white p-2 rounded-lg shadow-md text-sm text-gray-600 z-50';

                function updateTime() {
                    const now = new Date();
                    const options = {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    };

                    timeDisplay.innerHTML = `
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-clock"></i>
                            <span>${now.toLocaleString('id-ID', options)}</span>
                        </div>
                    `;
                }

                updateTime();
                setInterval(updateTime, 60000);
                document.body.appendChild(timeDisplay);
            }

            // Event listeners untuk tombol aksi
            document.querySelectorAll('[data-action]').forEach(button => {
                button.addEventListener('click', function() {
                    const action = this.dataset.action;
                    const borrowId = this.dataset.borrowId;
                    handleBorrowing(borrowId, action);
                });
            });

            // Inisialisasi timeDisplay
            initializeTimeDisplay();

            // Set filter 'all' sebagai default saat halaman dimuat
            filterBorrowings('all');
        });
    </script>
</body>