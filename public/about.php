<?php
require_once '../config/constants.php';
require_once '../includes/header.php';
require_once __DIR__ . '/../vendor/autoload.php';

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - <?= SITE_NAME ?></title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --text-color: #2c3e50;
            --background-color: #f4f7f6;
            --white: #ffffff;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }

        .about-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 50px 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            align-items: center;
        }

        .about-content {
            flex: 1;
            min-width: 300px;
        }

        .about-image {
            flex: 1;
            text-align: center;
        }

        .about-image img {
            max-width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 30px var(--shadow-color);
        }

        .about-content h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 2.5rem;
        }

        .about-content p {
            color: var(--text-color);
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .mission-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 30px;
            border-radius: 15px;
            margin-top: 30px;
        }

        .mission-section h2 {
            margin-bottom: 15px;
        }

        @media screen and (max-width: 768px) {
            .about-container {
                flex-direction: column;
            }

            .about-content,
            .about-image {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <main class="about-container">
        <div class="about-content">
            <h1>Tentang <?= SITE_NAME ?></h1>
            <p>Kami adalah platform peminjaman buku modern yang bertujuan memudahkan akses literasi untuk semua kalangan. Dengan teknologi canggih, kami membangun ekosistem perpustakaan digital yang efisien dan user-friendly.</p>

            <div class="mission-section">
                <h2>Misi Kami</h2>
                <p>Memberdayakan masyarakat melalui akses mudah dan cepat terhadap pengetahuan, menghubungkan pembaca dengan buku-buku berkualitas, serta mendorong budaya literasi yang inklusif.</p>
            </div>
        </div>
        <div class="about-image">
            <img src="/assets/images/library-image.jpg" alt="Perpustakaan Digital">
        </div>
    </main>
    <?php include '../includes/footer.php'; ?>
</body>

</html>