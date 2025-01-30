<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/sistem/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/sistem/classes/Book.php';

// Cek apakah user adalah petugas perpustakaan
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'librarian') {
    header('Location: /sistem/public/auth/login.php');
    exit();
}

// Konstanta untuk site name jika belum didefinisikan
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Sistem Perpustakaan');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemindai QR Code Peminjaman | <?= SITE_NAME ?></title>

    <!-- CSS Dependencies -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        #reader {
            width: 100% !important;
        }

        #reader video {
            border-radius: 0.75rem;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sistem/includes/navigation.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-qrcode text-indigo-600 mr-2"></i>
                    Pemindai QR Code Peminjaman
                </h1>
                <p class="text-gray-600 mt-2">
                    Pindai QR Code dari peminjam untuk memproses peminjaman buku
                </p>
            </div>

            <!-- Main Content -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <!-- Status Scanner -->
                <div id="scanner-status" class="mb-4 text-center">
                    <div class="animate-pulse inline-flex items-center text-sm text-gray-600">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        Scanner aktif - Arahkan ke QR Code
                    </div>
                </div>

                <!-- Area Pemindaian QR -->
                <div id="reader" class="mb-6 overflow-hidden rounded-xl"></div>

                <!-- Area Hasil Pemindaian -->
                <div id="result" class="hidden">
                    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-6">
                        <h2 class="text-xl font-semibold text-indigo-900 mb-4">
                            <i class="fas fa-info-circle mr-2"></i>
                            Detail Peminjaman
                        </h2>

                        <!-- Loading State -->
                        <div id="loading" class="hidden">
                            <div class="flex items-center justify-center py-4">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                                <span class="ml-2 text-indigo-600">Memproses...</span>
                            </div>
                        </div>

                        <!-- Results Container -->
                        <div id="scan-result" class="space-y-3"></div>

                        <!-- Action Buttons -->
                        <div class="mt-6 flex gap-3">
                            <button id="confirmBtn" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-1">
                                <i class="fas fa-check mr-2"></i>
                                Konfirmasi Peminjaman
                            </button>
                            <button id="cancelBtn" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-1">
                                <i class="fas fa-times mr-2"></i>
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructions Card -->
            <div class="mt-6 bg-white rounded-xl shadow-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                    Petunjuk Penggunaan
                </h3>
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                    <li>Pastikan QR Code terlihat jelas dan tidak rusak</li>
                    <li>Arahkan kamera ke QR Code dari peminjam</li>
                    <li>Periksa detail peminjaman sebelum konfirmasi</li>
                    <li>Pastikan buku tersedia sebelum memproses peminjaman</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Konfigurasi Scanner
        const config = {
            fps: 10,
            qrbox: {
                width: 250,
                height: 250
            },
            aspectRatio: 1.0
        };

        // Fungsi untuk memformat tanggal
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function showLoading() {
            document.getElementById('loading').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loading').classList.add('hidden');
        }

        function onScanSuccess(decodedText) {
            try {
                const data = JSON.parse(decodedText);
                const resultDiv = document.getElementById('result');
                const scanResult = document.getElementById('scan-result');

                // Format dan tampilkan hasil
                scanResult.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <p class="font-semibold text-gray-700">Informasi Buku:</p>
                            <p class="text-gray-600"><span class="font-medium">ID:</span> ${data.book_id}</p>
                            <p class="text-gray-600"><span class="font-medium">Judul:</span> ${data.title}</p>
                            <p class="text-gray-600"><span class="font-medium">ISBN:</span> ${data.isbn}</p>
                        </div>
                        <div class="space-y-2">
                            <p class="font-semibold text-gray-700">Informasi Peminjaman:</p>
                            <p class="text-gray-600"><span class="font-medium">ID Peminjam:</span> ${data.user}</p>
                            <p class="text-gray-600"><span class="font-medium">Waktu Request:</span> ${formatDate(data.timestamp)}</p>
                            <p class="text-gray-600"><span class="font-medium">ID Request:</span> ${data.request_id}</p>
                        </div>
                    </div>
                `;

                resultDiv.classList.remove('hidden');
                html5QrcodeScanner.pause();

                // Event handler untuk konfirmasi
                document.getElementById('confirmBtn').onclick = async () => {
                    try {
                        showLoading();
                        const response = await fetch('/sistem/api/process-loan.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                book_id: data.book_id,
                                user_id: data.user,
                                request_id: data.request_id
                            })
                        });

                        const result = await response.json();
                        hideLoading();

                        if (result.success) {
                            alert('✅ Peminjaman berhasil diproses!');
                            location.reload();
                        } else {
                            alert('❌ Error: ' + result.message);
                        }
                    } catch (error) {
                        hideLoading();
                        alert('❌ Terjadi kesalahan saat memproses peminjaman');
                        console.error(error);
                    }
                };

                // Event handler untuk batal
                document.getElementById('cancelBtn').onclick = () => {
                    resultDiv.classList.add('hidden');
                    html5QrcodeScanner.resume();
                };
            } catch (error) {
                console.error('QR Code tidak valid:', error);
                alert('❌ QR Code tidak valid atau rusak');
            }
        }

        function onScanError(errorMessage) {
            // Handle scan error (optional)
            console.warn(errorMessage);
        }

        // Inisialisasi Scanner
        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader",
            config
        );

        html5QrcodeScanner.render(onScanSuccess, onScanError);

        // Tambahkan event listener untuk tombol escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const resultDiv = document.getElementById('result');
                if (!resultDiv.classList.contains('hidden')) {
                    resultDiv.classList.add('hidden');
                    html5QrcodeScanner.resume();
                }
            }
        });
    </script>
</body>

</html>