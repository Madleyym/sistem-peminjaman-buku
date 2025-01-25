<?php
// includes/sidebar.php
?>

<head>
    <style>
        /* Sidebar Styles with Enhanced Responsiveness */
        .page-wrapper {
            display: flex;
            min-height: calc(100vh - 200px);
        }

        .sidebar {
            background-color: var(--background-color);
            width: 250px;
            padding: 20px;
            flex-shrink: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar-widget {
            margin-bottom: 20px;
        }

        .sidebar h3 {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .category-list li {
            margin-bottom: 10px;
        }

        .category-list a {
            color: var(--text-color);
            text-decoration: none;
            transition: color 0.3s ease, background-color 0.3s ease;
            padding: 5px 10px;
            border-radius: 4px;
            display: block;
        }

        .category-list a:hover {
            color: var(--primary-color);
            background-color: rgba(0, 0, 0, 0.05);
        }

        /* Responsive Breakpoints */
        @media screen and (max-width: 768px) {
            .page-wrapper {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                order: -1;
                margin-bottom: 20px;
            }

            .category-list {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }

            .category-list li {
                flex-grow: 1;
                text-align: center;
            }

            .category-list a {
                background-color: rgba(0, 0, 0, 0.05);
                padding: 10px;
            }
        }

        /* Extra Small Screens */
        @media screen and (max-width: 480px) {
            .sidebar h3 {
                text-align: center;
            }

            .category-list {
                flex-direction: column;
                gap: 10px;
            }

            .category-list li {
                width: 100%;
            }
        }
    </style>
</head>
<aside class="sidebar">
    <div class="sidebar-widget">
        <h3>Kategori Buku</h3>
        <ul class="category-list">
            <li><a href="/public/books.php?category=novel">Novel</a></li>
            <li><a href="/public/books.php?category=nonfiction">Non-Fiksi</a></li>
            <li><a href="/public/books.php?category=education">Pendidikan</a></li>
            <li><a href="/public/books.php?category=biography">Biografi</a></li>
        </ul>
    </div>
</aside>