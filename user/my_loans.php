<?php
include "../config/config.php";
$user_id = 1; // Ganti dengan ID pengguna yang sedang login

$query = "SELECT loans.*, books.title FROM loans
          INNER JOIN books ON loans.book_id = books.id
          WHERE loans.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "<div>";
    echo "<h3>Buku: " . $row['title'] . "</h3>";
    echo "<p>Tanggal Pinjam: " . $row['loan_date'] . "</p>";
    echo "<p>Status: " . $row['status'] . "</p>";
    echo "</div>";
}
?>
