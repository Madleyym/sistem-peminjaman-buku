<?php
// Logging configuration
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/database.php';
require_once '../config/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

$log_file = 'C:\xampp\htdocs\sistem\logs\submit_contact_debug.log';

// Custom logging function
function custom_log($message)
{
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

custom_log("Script dimulai");

// CSRF Token Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    custom_log("CSRF Token dibuat: " . $_SESSION['csrf_token']);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log raw input
    custom_log("POST Data: " . print_r($_POST, true));

    // Validate CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        custom_log("CSRF Token Validation Failed");
        die('CSRF token validation failed');
    }

    // Sanitize and validate input
    $name = trim($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $message = trim($_POST['message']);

    // Validate inputs with the new validation rules
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Nama tidak boleh kosong';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Nama minimal 2 karakter';
    }

    if (empty($email)) {
        $errors[] = 'Email tidak boleh kosong';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    }

    if (empty($message)) {
        $errors[] = 'Pesan tidak boleh kosong';
    } elseif (strlen($message) < 10) {
        $errors[] = 'Pesan minimal 10 karakter';
    }

    // Modifikasi bagian penyimpanan error
    if (!empty($errors)) {
        // Store detailed errors in session
        $_SESSION['errors'] = $errors;
        custom_log("Validation Errors: " . print_r($errors, true));

        // Tambahkan keterangan detail error
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => implode(', ', $errors)
        ];

        header("Location: contact.php");
        exit();
    }

    // If no errors, process the form
    try {
        // Database connection
        $database = new Database();
        $conn = $database->getConnection();
        custom_log("Koneksi database berhasil");

        // Cek apakah tabel contact_messages ada
        $stmt = $conn->query("SHOW TABLES LIKE 'contact_messages'");
        $tableExists = $stmt->rowCount() > 0;

        if (!$tableExists) {
            custom_log("Tabel contact_messages TIDAK ADA");

            // Buat tabel
            $createTableQuery = "CREATE TABLE contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";

            $conn->exec($createTableQuery);
            custom_log("Tabel contact_messages berhasil dibuat");
        }

        // Prepare SQL to insert contact message
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message, created_at) VALUES (:name, :email, :message, NOW())");

        // Bind parameters
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':message', $message);

        // Execute the statement
        $result = $stmt->execute();

        custom_log("Database Insert Result: " . ($result ? "Success" : "Failure"));
        custom_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));

        if ($result) {
            // Redirect with success message
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Pesan berhasil dikirim!'
            ];
            custom_log("Pesan berhasil dikirim");
            header("Location: contact.php");
            exit();
        } else {
            throw new Exception("Database insert failed");
        }
    } catch (Exception $e) {
        // Log full error details
        custom_log("Contact Form Error: " . $e->getMessage());
        custom_log("Full Exception: " . print_r($e, true));

        // Set error message
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => 'Terjadi kesalahan. Silakan coba lagi. ' . $e->getMessage()
        ];
        header("Location: contact.php");
        exit();
    }
} else {
    // Direct access to script is not allowed
    header("Location: contact.php");
    exit();
}
