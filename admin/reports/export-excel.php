<?php
session_start();
require_once '../includes/admin-auth.php';
checkAdminAuth();

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../vendor/autoload.php'; // Make sure you have installed PhpSpreadsheet via Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$database = new Database();
$conn = $database->getConnection();

// Fetch report data (reuse the queries from your report.php)
// ...

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Library Report');
// Add more data to the spreadsheet
// ...

$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="library_report.xlsx"');
$writer->save('php://output');