<?php
require 'koneksi.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Migrasi Database Manual</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>body { font-family: sans-serif; padding: 20px; }</style>
</head>
<body>
    <h2>Migrasi Database Manual</h2>
    <ul>
<?php
$queries = [
    "ALTER TABLE tbl_izin_siswa ADD COLUMN `catatan_wali_kelas` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE tbl_izin_siswa ADD COLUMN `catatan_guru_bk` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE tbl_materi ADD COLUMN `waktu_input` TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
];

foreach ($queries as $q) {
    echo "<li>Mengeksekusi: <code>" . htmlspecialchars($q) . "</code><br>";
    if ($conn->query($q)) {
        echo "<span style='color:green;'>Berhasil ditambahkan.</span>";
    } else {
        $err = $conn->error;
        if (strpos(strtolower($err), 'duplicate column') !== false) {
            echo "<span style='color:blue;'>Kolom sudah ada (Aman).</span>";
        } else {
            echo "<span style='color:red;'>Gagal: " . htmlspecialchars($err) . "</span>";
        }
    }
    echo "</li><br>";
}
?>
    </ul>
    <p>Selesai! Silakan kembali ke <a href="home.php">Dashboard</a>.</p>
</body>
</html>
