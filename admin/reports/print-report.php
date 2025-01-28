<?php
session_start();
require_once '../includes/admin-auth.php';
checkAdminAuth();

require_once '../../config/constants.php';
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Fetch report data (reuse the queries from your report.php)
// ...

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Report - <?= htmlspecialchars(SITE_NAME) ?></title>
    <style>
        /* Add print-friendly styles here */
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Library Report</h1>
    <!-- Add your report content here, formatted for printing -->
    <!-- ... -->
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>