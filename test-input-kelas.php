<?php
// Test sederhana untuk memverifikasi query di input-kelas.php
include "koneksi.php";

// Simulasi session dan hakakses untuk test
$_SESSION["username"] = "test";
$hakakses = 1;

echo "<h3>Test Query input-kelas.php</h3>";

try {
    // Test query yang sudah diperbaiki
    $sql = "SELECT k.*, w.nip_wali, g.nama_guru 
           FROM tbl_kelas k 
           LEFT JOIN tbl_wali_kelas w ON k.id_kelas = w.id_kelas 
           LEFT JOIN tbl_guru g ON w.nip_wali = g.no_induk 
           ORDER BY k.kelas ASC 
           LIMIT 3";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Query berhasil dijalankan tanpa error!</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Kelas</th><th>NIP Wali</th><th>Nama Guru</th></tr>";
        
        while ($data = mysqli_fetch_array($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($data['kelas']) . "</td>";
            echo "<td>" . ($data['nip_wali'] ?? 'Tidak ada') . "</td>";
            echo "<td>" . ($data['nama_guru'] ?? 'Tidak ada') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>✗ Query gagal: " . mysqli_error($conn) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='home.php'>← Kembali ke Home</a></p>";
?>