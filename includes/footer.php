<?php
// includes/footer.php
?>

<head></head>
<style>
    footer {
        display: flex;
        /* Mengatur footer menggunakan flexbox */
        flex-direction: column;
        /* Susunan vertikal */
        justify-content: center;
        /* Konten di tengah secara vertikal */
        align-items: center;
        /* Konten di tengah secara horizontal */
        text-align: center;
        /* Memusatkan teks */
    }

    footer .container {
        display: grid;
        /* Gunakan grid untuk tata letak kolom */
        grid-template-columns: 1fr;
        /* Semua kolom menumpuk di layar kecil */
        gap: 20px;
        /* Jarak antar item */
    }

    @media (min-width: 768px) {
        footer .container {
            grid-template-columns: repeat(3, 1fr);
            /* 3 kolom di layar besar */
        }
    }
</style>

<footer class="bg-gray-800 text-white py-12">
    <div class="container mx-auto px-4 grid md:grid-cols-3 gap-8">
        <div>
            <h4 class="text-xl font-bold mb-4"><?= htmlspecialchars(SITE_NAME) ?></h4>
            <p class="text-gray-400">Platform peminjaman buku digital modern dan efisien</p>
            <div class="flex space-x-4 mt-4">
                <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-facebook"></i></a>
                <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
        <div>
            <h4 class="text-xl font-bold mb-4">Tautan Cepat</h4>
            <ul class="space-y-2">
                <li><a href="/" class="text-gray-300 hover:text-white">Beranda</a></li>
                <li><a href="/books" class="text-gray-300 hover:text-white">Buku</a></li>
                <li><a href="/contact" class="text-gray-300 hover:text-white">Kontak</a></li>
            </ul>
        </div>
        <div>
            <h4 class="text-xl font-bold mb-4">Hubungi Kami</h4>
            <p class="text-gray-400 mb-2">Email: support@perpustakaan.com</p>
            <p class="text-gray-400 mb-2">Telepon: +62 888 1234 5678</p>
            <p class="text-gray-400">Alamat: Jl. Perpustakaan No. 123, Kota</p>
        </div>
    </div>
    <div class="text-center text-gray-500 mt-8 pt-4 border-t border-gray-700">
        &copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. Hak Cipta Dilindungi.
    </div>
</footer>