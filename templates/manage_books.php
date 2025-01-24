<?php
include "../config/config.php";

$query = "SELECT * FROM books";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    echo "<div>";
    echo "<h3>" . $row['title'] . "</h3>";
    echo "<p>Penulis: " . $row['author'] . "</p>";
    echo "<p>Penerbit: " . $row['publisher'] . "</p>";
    echo "</div>";
}
?>
