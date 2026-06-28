<?php
// Test struktur tabel tbl_materi di hosting
include "koneksi.php";

echo "<h2>Test Struktur Tabel tbl_materi</h2>";

// Cek kolom yang ada
$result = mysqli_query($conn, "DESCRIBE tbl_materi");
echo "<h3>Kolom yang ada:</h3>";
echo "<ul>";
while($row = mysqli_fetch_assoc($result)) {
    echo "<li><strong>{$row['Field']}</strong> - {$row['Type']}</li>";
}
echo "</ul>";

// Test query sederhana
echo "<h3>Test Query:</h3>";
$query = "SELECT id_materi, date, tanggal FROM tbl_materi LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "<p>✅ Query berhasil! Kolom yang digunakan:</p>";
    $row = mysqli_fetch_assoc($result);
    echo "<pre>" . print_r($row, true) . "</pre>";
} else {
    echo "<p>❌ Query gagal: " . mysqli_error($conn) . "</p>";
}

// Test query monitoring-guru
echo "<h3>Test Query Monitoring Guru:</h3>";
$tanggal = date("Y-m-d");
$query2 = "SELECT COUNT(*) as total FROM tbl_materi WHERE tanggal = '$tanggal'";
$result2 = mysqli_query($conn, $query2);

if ($result2) {
    $row2 = mysqli_fetch_assoc($result2);
    echo "<p>✅ Query monitoring berhasil! Total record hari ini: {$row2['total']}</p>";
} else {
    echo "<p>❌ Query monitoring gagal: " . mysqli_error($conn) . "</p>";
}
?>