<?php
// File: C:\xampp\htdocs\sistem\admin\includes\get-chart-data.php

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/auth-session.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

function getChartData($conn, $period = 7)
{
    try {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-$period days"));

        // Debug: Print query parameters
        error_log("Start Date: " . $start_date);
        error_log("End Date: " . $end_date);

        $query = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN action_type = 'peminjaman' THEN 1 ELSE 0 END) as peminjaman,
                SUM(CASE WHEN action_type = 'pengembalian' THEN 1 ELSE 0 END) as pengembalian
            FROM activity_logs 
            WHERE DATE(created_at) BETWEEN :start_date AND :end_date
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";

        // Debug: Print query
        error_log("Query: " . $query);

        $stmt = $conn->prepare($query);
        $stmt->execute([
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debug: Print raw results
        error_log("Raw Results: " . print_r($results, true));

        // Initialize arrays
        $dates = [];
        $totals = [];
        $peminjaman = [];
        $pengembalian = [];

        // Fill in all dates including those with no activity
        $current = strtotime($start_date);
        $end = strtotime($end_date);

        while ($current <= $end) {
            $currentDate = date('Y-m-d', $current);
            $found = false;

            foreach ($results as $row) {
                if ($row['date'] === $currentDate) {
                    $dates[] = date('d M', $current);
                    $totals[] = (int)$row['total'];
                    $peminjaman[] = (int)$row['peminjaman'];
                    $pengembalian[] = (int)$row['pengembalian'];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $dates[] = date('d M', $current);
                $totals[] = 0;
                $peminjaman[] = 0;
                $pengembalian[] = 0;
            }

            $current = strtotime('+1 day', $current);
        }

        // Debug: Print formatted data
        error_log("Formatted Data: " . print_r([
            'dates' => $dates,
            'totals' => $totals,
            'peminjaman' => $peminjaman,
            'pengembalian' => $pengembalian
        ], true));

        return [
            'status' => 'success',
            'data' => [
                'dates' => $dates,
                'totals' => $totals,
                'peminjaman' => $peminjaman,
                'pengembalian' => $pengembalian
            ]
        ];
    } catch (PDOException $e) {
        error_log("Chart Data Error: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage(),
            'data' => [
                'dates' => [],
                'totals' => [],
                'peminjaman' => [],
                'pengembalian' => []
            ]
        ];
    }
}

try {
    // Initialize database connection
    $database = new database();
    $conn = $database->getConnection();

    // Get period from request
    $period = isset($_GET['period']) ? (int)$_GET['period'] : 7;

    // Validate period
    if ($period <= 0 || $period > 365) {
        throw new Exception('Invalid period value');
    }

    // Get chart data
    $response = getChartData($conn, $period);

    // Add debug information in response
    $response['debug'] = [
        'period' => $period,
        'current_time' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get()
    ];

    // Send response
    echo json_encode($response);
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => [
            'error_type' => get_class($e),
            'error_message' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString()
        ]
    ]);
}

// Debug: Log all activity_logs entries
try {
    $stmt = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10");
    error_log("Recent Activity Logs: " . print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true));
} catch (PDOException $e) {
    error_log("Debug Query Error: " . $e->getMessage());
}
