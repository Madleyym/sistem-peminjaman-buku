<?php
// includes/header.php
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Sistem Peminjaman Buku');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Peminjaman Buku Modern dan Efisien">
    <title><?= isset($pageTitle) ? $pageTitle : SITE_NAME ?></title>
    <!-- <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css"> -->
    <style>
        /* Header and Navigation Responsive Styles */
        .main-header {
            background: linear-gradient(135deg, rgb(70, 103, 193), #4f46e5);
            /* Sama dengan gradien yang ada di hero section */
            /* Gradien sesuai dengan body */
            /* Sama dengan gradien di body */
            /* Biru muda ke biru yang lebih gelap */
            color: var(--white);
            padding: 15px 0;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .logo h1 {
            color: var(--white);
            font-size: 1.5rem;
            margin: 0;
        }

        .main-nav {
            display: flex;
            align-items: center;
        }

        .nav-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 1000;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        .nav-menu a {
            color: var(--white);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .nav-menu a:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        /* Responsive Breakpoints */
        @media screen and (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                position: relative;
            }

            .nav-toggle {
                display: block;
                position: absolute;
                top: 10px;
                right: 15px;
            }

            .nav-menu {
                display: none;
                flex-direction: column;
                width: 100%;
                text-align: center;
                background-color: var(--primary-color);
                position: absolute;
                top: 100%;
                left: 0;
                padding: 20px 0;
                gap: 15px;
            }

            .nav-menu.active {
                display: flex;
            }

            .nav-menu li {
                margin: 0;
            }
        }

        /* Extra Small Screens */
        @media screen and (max-width: 480px) {
            .logo h1 {
                font-size: 1.2rem;
            }

            .nav-toggle {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 to-blue-100 min-h-screen font-inter">
    <header class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="text-2xl font-bold">
                    <?= SITE_NAME ?>
                </div>
                <nav class="hidden md:block">
                    <ul class="flex space-x-6">
                        <li><a href="../public/index.php" class="hover:text-blue-200 transition">Beranda</a></li>
                        <li><a href="../public/books.php" class="hover:text-blue-200 transition">Buku</a></li>
                        <li><a href="../public/about.php" class="hover:text-blue-200 transition">Tentang</a></li>
                        <li><a href="../public/contact.php" class="hover:text-blue-200 transition">Kontak</a></li>
                    </ul>
                </nav>
                <!-- Mobile Menu Toggle -->
                <div class="md:hidden">
                    <button id="mobile-menu-toggle" class="text-white focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="md:hidden hidden mt-4">
                <ul class="space-y-4 text-center">
                    <li><a href="../public/index.php" class="block py-2 hover:bg-blue-700 rounded">Beranda</a></li>
                    <li><a href="../public/books.php" class="block py-2 hover:bg-blue-700 rounded">Buku</a></li>
                    <li><a href="../public/about.php" class="block py-2 hover:bg-blue-700 rounded">Tentang</a></li>
                    <li><a href="../public/contact.php" class="block py-2 hover:bg-blue-700 rounded">Kontak</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Mobile Menu JavaScript -->
    <script>
        document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>

</html>