<?php
// C:\xampp\htdocs\sistem\includes\header.php
if (!defined('SITE_NAME')) {
    die('Direct access to this file is not allowed');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? SITE_NAME) ?></title>

    <!-- Required CSS and JavaScript for Navigation -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Custom styles -->
    <style>
        /* Smooth transitions for mobile menu */
        .nav-transition {
            transition: all 0.3s ease-in-out;
        }

        /* Improve mobile touch targets */
        @media (max-width: 768px) {
            .nav-item {
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>

<body>