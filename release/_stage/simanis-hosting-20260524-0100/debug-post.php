<?php
// Debug POST request untuk batch delete
echo "<h2>Debug POST Request - Batch Delete</h2>";

echo "<h3>Data POST yang diterima:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>Data GET yang diterima:</h3>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h3>HTTP Method:</h3>";
echo $_SERVER['REQUEST_METHOD'] ?? 'Tidak ada';

echo "<br><br>";
echo "<a href='home.php?page=input-kelas'>🔙 Kembali ke Input Kelas</a>";
?>