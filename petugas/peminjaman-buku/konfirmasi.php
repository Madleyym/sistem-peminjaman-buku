<?php
session_start();

// Validasi login petugas
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: /sistem/petugas/auth/login.php");  // Updated login path
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';

// Update timestamp dan username
$current_time = '2025-01-31 16:51:35';
$staff_username = 'Madleyym';

try {
    $database = new Database();
    $conn = $database->getConnection();

    $query = "SELECT 
        b.id as borrow_id,
        b.user_id,
        b.book_id,
        b.borrow_date,
        b.due_date,
        b.status,
        b.notes,
        bk.title,          
        bk.cover_image,
        bk.author,
        bk.publisher,
        bk.isbn,
        bk.category,
        u.name as user_name,
        u.email,
        u.phone_number,
        u.address
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.id
    JOIN users u ON b.user_id = u.id
    WHERE LOWER(b.status) = LOWER('pending')
    ORDER BY b.borrow_date ASC";

    $stmt = $conn->query($query);
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan saat mengambil data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Peminjaman | <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include __DIR__ . '/../../petugas/includes/navigation.php'; ?>

    <main class="flex-grow container mx-auto px-4 py-8">
        <!-- Main Content -->
        <div class="bg-white rounded-xl p-8 shadow-lg">
            <!-- Header Section with Title and Staff Info -->
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-clipboard-check text-indigo-600 mr-2"></i>
                        Konfirmasi Peminjaman
                    </h1>
                    <p class="text-gray-600 mt-2">Kelola permintaan peminjaman buku yang menunggu persetujuan</p>
                </div>
                <div class="text-sm text-gray-600">
                    <p class="flex items-center">
                        <i class="fas fa-clock text-indigo-600 mr-2"></i>
                        Waktu Server: <?= date('d M Y H:i:s', strtotime('2025-01-31 16:06:00')) ?>
                    </p>
                    <p class="flex items-center mt-1">
                        <i class="fas fa-user text-indigo-600 mr-2"></i>
                        Petugas: <?= htmlspecialchars('Madleyym') ?>
                    </p>
                </div>
            </div>

            <?php if (empty($pending_requests)): ?>
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 mb-4">
                        <i class="fas fa-check text-2xl text-indigo-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Tidak Ada Permintaan Pending</h3>
                    <p class="text-gray-600">Semua permintaan peminjaman telah diproses.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($pending_requests as $request): ?>
                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <div class="flex items-start gap-6">
                                <!-- Book Cover -->
                                <div class="w-[120px] h-[160px] flex-shrink-0">
                                    <img
                                        src="<?= !empty($request['cover_image'])
                                                    ? '/sistem/uploads/book_covers/' . htmlspecialchars($request['cover_image'])
                                                    : '/sistem/uploads/books/book-default.png' ?>"
                                        alt="<?= htmlspecialchars($request['title']) ?>"
                                        class="w-full h-full object-cover rounded-lg shadow-md"
                                        onerror="this.src='/sistem/uploads/books/book-default.png'">
                                </div>

                                <!-- Request Details -->
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-xl font-semibold text-gray-800 mb-2">
                                                <?= htmlspecialchars($request['title']) ?>
                                            </h3>
                                            <p class="text-gray-600">oleh <?= htmlspecialchars($request['author']) ?></p>
                                        </div>
                                        <span class="px-3 py-1 text-sm font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                            Menunggu Persetujuan
                                        </span>
                                    </div>

                                    <!-- Borrower Info -->
                                    <div class="mt-4 grid grid-cols-2 gap-6">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 mb-2">Informasi Peminjam:</h4>
                                            <div class="space-y-1">
                                                <p class="text-gray-600"><?= htmlspecialchars($request['user_name']) ?></p>
                                                <p class="text-gray-600"><?= htmlspecialchars($request['email']) ?></p>
                                                <p class="text-gray-600"><?= htmlspecialchars($request['phone_number']) ?></p>
                                                <div class="mt-2">
                                                    <p class="text-sm font-medium text-gray-700">Alamat:</p>
                                                    <p class="text-gray-600"><?= nl2br(htmlspecialchars($request['address'])) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 mb-2">Tanggal Peminjaman:</h4>
                                            <p class="text-gray-600"><?= date('d M Y', strtotime($request['borrow_date'])) ?></p>
                                            <h4 class="text-sm font-medium text-gray-700 mt-4 mb-2">Tanggal Pengembalian:</h4>
                                            <p class="text-gray-600"><?= date('d M Y', strtotime($request['due_date'])) ?></p>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="mt-6 flex gap-4">
                                        <button
                                            onclick="handleRequest(<?= $request['borrow_id'] ?>, 'approve')"
                                            class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-300 transform hover:-translate-y-1">
                                            <i class="fas fa-check mr-2"></i>
                                            Setujui
                                        </button>
                                        <button
                                            onclick="handleRequest(<?= $request['borrow_id'] ?>, 'reject')"
                                            class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-300 transform hover:-translate-y-1">
                                            <i class="fas fa-times mr-2"></i>
                                            Tolak
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script>
        async function handleRequest(borrowId, action) {
            try {
                const title = action === 'approve' ? 'Setujui Peminjaman' : 'Tolak Peminjaman';
                const text = action === 'approve' ?
                    'Apakah Anda yakin ingin menyetujui peminjaman ini?' :
                    'Apakah Anda yakin ingin menolak peminjaman ini?';
                const confirmButtonColor = action === 'approve' ? '#059669' : '#DC2626';

                const result = await Swal.fire({
                    title: title,
                    text: text,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: action === 'approve' ? 'Ya, Setujui' : 'Ya, Tolak',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: confirmButtonColor,
                    cancelButtonColor: '#6B7280',
                });

                if (result.isConfirmed) {
                    const response = await fetch('/sistem/petugas/proses-konfirmasi.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            borrow_id: borrowId,
                            action: action,
                            processed_at: '2025-01-31 16:09:04' // Updated timestamp
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        location.reload();
                    } else {
                        throw new Error(data.message);
                    }
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Terjadi kesalahan saat memproses permintaan'
                });
            }
        }
    </script>
</body>

</html>