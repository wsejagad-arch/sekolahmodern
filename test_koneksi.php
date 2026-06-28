<?php
// Test koneksi database
include 'koneksi.php';

echo "<h3>Test Koneksi Database</h3>";

if ($conn) {
    echo "<div style='color: green;'>✅ Koneksi database berhasil!</div>";
    echo "<p>Host: $host<br>User: $user<br>Database: $db<br>Port: $port</p>";
    
    // Test query sederhana
    $result = mysqli_query($conn, "SHOW TABLES");
    if ($result) {
        echo "<h4>Tabel dalam database:</h4><ul>";
        while ($row = mysqli_fetch_array($result)) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<div style='color: orange;'>⚠️ Database terhubung tapi tidak ada tabel atau error query</div>";
    }
} else {
    echo "<div style='color: red;'>❌ Koneksi database gagal!</div>";
    echo "<p>Error: " . mysqli_connect_error() . "</p>";
}
?>