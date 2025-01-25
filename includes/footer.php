<?php
// includes/footer.php
?>

<head></head>
<style>
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        margin: 0;
    }

    .page-wrapper {
        flex: 1 0 auto;
    }

    .main-footer {
        flex-shrink: 0;
        background-color: var(--primary-color);
        color: var(--white);
        padding: 20px 0;
        width: 100%;
    }

    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }

    .footer-links {
        display: flex;
        align-items: center;
    }

    .footer-links a {
        color: var(--white);
        text-decoration: none;
        margin-left: 15px;
        transition: opacity 0.3s ease;
        white-space: nowrap;
    }

    .footer-links a:hover {
        opacity: 0.8;
    }

    /* Responsive Adjustments */
    @media screen and (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }

        .footer-links {
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 10px;
        }

        .footer-links a {
            margin: 0 10px 10px;
            display: inline-block;
        }
    }

    @media screen and (max-width: 480px) {
        .footer-content {
            padding: 0 10px;
        }

        .footer-links {
            flex-direction: column;
            gap: 10px;
        }

        .footer-links a {
            margin: 5px 0;
        }
    }
</style>
</div> <!-- Penutup page-wrapper dari header -->
<footer class="main-footer">
    <div class="container">
        <div>
            <div class="footer-content">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
                <div class="footer-links">
                    <a href="/public/privacy.php">Kebijakan Privasi</a>
                    <a href="/public/terms.php">Syarat & Ketentuan</a>
                </div>
            </div>
        </div>
    </div>
</footer>
<script src="/assets/js/main.js"></script>
</body>

</html>