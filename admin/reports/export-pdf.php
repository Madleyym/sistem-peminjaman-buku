<?php
session_start();
require_once '../includes/admin-auth.php';
checkAdminAuth();

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../vendor/autoload.php'; // Make sure you have installed FPDF via Composer

// Hapus baris ini: use FPDF\FPDF;

$database = new Database();
$conn = $database->getConnection();

// Fetch report data (reuse the queries from your report.php)
// ...

// Ganti pembuatan objek FPDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(40, 10, 'Library Report');
// Add more content to the PDF using FPDF methods
// ...

$pdf->Output('library_report.pdf', 'D');