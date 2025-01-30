<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// require_once $_SERVER['DOCUMENT_ROOT'] . '/sistem/config/database.php';
// require_once $_SERVER['DOCUMENT_ROOT'] . '/sistem/classes/Book.php';

// Cek apakah user adalah petugas perpustakaan
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'librarian') {
//     header('Location: /sistem/petugas/auth/login.php?message=' . urlencode('Anda harus login sebagai petugas perpustakaan'));
//     exit();
// }

// Current timestamp untuk logging
// $currentTimestamp = date('Y-m-d H:i:s');
// $currentUser = $_SESSION['username'] ?? 'Unknown';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Buku | <?= SITE_NAME ?></title>

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

        .tab-active {
            color: #4f46e5;
            border-bottom: 2px solid #4f46e5;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sistem/includes/navigation.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-book-reader text-indigo-600 mr-2"></i>
                    Peminjaman Buku
                </h1>
                <p class="text-gray-600 mt-2">
                    Kelola peminjaman buku dengan mudah menggunakan QR Code atau form manual
                </p>
            </div>

            <!-- Tab Navigation -->
            <div class="flex justify-center mb-6">
                <div class="inline-flex rounded-lg bg-gray-100 p-1">
                    <button id="qrTab" class="px-4 py-2 rounded-lg tab-active">
                        <i class="fas fa-qrcode mr-2"></i>
                        Scan QR Code
                    </button>
                    <button id="manualTab" class="px-4 py-2 rounded-lg">
                        <i class="fas fa-edit mr-2"></i>
                        Input Manual
                    </button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <!-- QR Scanner Section -->
                <div id="qrSection">
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

                <!-- Manual Input Section -->
                <div id="manualSection" class="hidden">
                    <form id="manualForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    ID Buku
                                </label>
                                <input type="text" name="book_id" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    ID Peminjam
                                </label>
                                <input type="text" name="user_id" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-1">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Proses Peminjaman
                        </button>
                    </form>
                </div>
            </div>

            <!-- Instructions Card -->
            <div class="mt-6 bg-white rounded-xl shadow-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                    Petunjuk Penggunaan
                </h3>
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                    <li>Gunakan QR Scanner untuk peminjaman cepat</li>
                    <li>Input manual tersedia jika QR Code tidak bisa dipindai</li>
                    <li>Pastikan data peminjam dan buku sudah benar</li>
                    <li>Sistem akan otomatis mengecek ketersediaan buku</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Tab Switching Logic
        const qrTab = document.getElementById('qrTab');
        const manualTab = document.getElementById('manualTab');
        const qrSection = document.getElementById('qrSection');
        const manualSection = document.getElementById('manualSection');

        qrTab.addEventListener('click', () => {
            qrTab.classList.add('tab-active');
            manualTab.classList.remove('tab-active');
            qrSection.classList.remove('hidden');
            manualSection.classList.add('hidden');
            if (html5QrcodeScanner) {
                html5QrcodeScanner.resume();
            }
        });

        manualTab.addEventListener('click', () => {
            manualTab.classList.add('tab-active');
            qrTab.classList.remove('tab-active');
            manualSection.classList.remove('hidden');
            qrSection.classList.add('hidden');
            if (html5QrcodeScanner) {
                html5QrcodeScanner.pause();
            }
        });

        // QR Scanner Configuration
        const config = {
            fps: 10,
            qrbox: {
                width: 250,
                height: 250
            },
            aspectRatio: 1.0
        };

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

        // QR Code Scanner Handler
        function onScanSuccess(decodedText) {
            try {
                const data = JSON.parse(decodedText);
                const resultDiv = document.getElementById('result');
                const scanResult = document.getElementById('scan-result');

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

                // Handle Confirmation
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

                // Handle Cancel
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
            console.warn(errorMessage);
        }

        // Initialize QR Scanner
        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader",
            config
        );

        html5QrcodeScanner.render(onScanSuccess, onScanError);

        // Manual Form Handler
        document.getElementById('manualForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                showLoading();
                const response = await fetch('/sistem/api/process-loan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        book_id: formData.get('book_id'),
                        user_id: formData.get('user_id'),
                        request_id: 'MANUAL-' + Date.now()
                    })
                });

                const result = await response.json();
                hideLoading();

                if (result.success) {
                    alert('✅ Peminjaman berhasil diproses!');
                    e.target.reset();
                } else {
                    alert('❌ Error: ' + result.message);
                }
            } catch (error) {
                hideLoading();
                alert('❌ Terjadi kesalahan saat memproses peminjaman');
                console.error(error);
            }
        });

        // Escape Key Handler
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