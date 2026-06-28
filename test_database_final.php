<?php
echo "=== TEST KONEKSI DATABASE JURNAL ===\n\n";

// Test koneksi menggunakan metode yang sama seperti koneksi.php
$cfg = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'db' => 'jurnal',
    'port' => 3306,
];

echo "Mencoba koneksi ke database jurnal...\n";
$conn = mysqli_connect($cfg['host'], $cfg['user'], $cfg['password'], $cfg['db'], $cfg['port']);

if (!$conn) {
    echo "❌ KONEKSI GAGAL: " . mysqli_connect_error() . "\n\n";
    echo "SOLUSI:\n";
    echo "1. Pastikan database 'jurnal' sudah dibuat di phpMyAdmin\n";
    echo "2. Import file include/db_appsiswa.sql ke database jurnal\n";
    echo "3. Pastikan MySQL service sedang berjalan\n";
    echo "4. Lihat file INSTRUKSI_SETUP_DATABASE.txt untuk panduan lengkap\n";
    exit(1);
}

echo "✅ KONEKSI BERHASIL!\n\n";

// Cek tabel yang ada
echo "=== CEK TABEL DATABASE ===\n";
$result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
    echo "- " . $row[0] . "\n";
}

echo "\nTotal tabel: " . count($tables) . "\n\n";

// Cek data guru
echo "=== CEK DATA GURU ===\n";
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_guru");
$row = mysqli_fetch_assoc($result);
echo "Total guru: " . $row['total'] . "\n";

if ($row['total'] > 0) {
    $result = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru LIMIT 3");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- NIP: " . $row['no_induk'] . " | Nama: " . $row['nama_guru'] . "\n";
    }
} else {
    echo "❌ Tidak ada data guru. Tambahkan data sample:\n";
    echo "INSERT INTO tbl_guru (no_induk, nama_guru, status_kepegawaian, foto, status)\n";
    echo "VALUES ('123456789', 'Ahmad Surya', 'PNS', '', 'Aktif');\n";
}

echo "\n=== CEK DATA JADWAL ===\n";
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mapel_ampu");
$row = mysqli_fetch_assoc($result);
echo "Total jadwal: " . $row['total'] . "\n";

if ($row['total'] > 0) {
    $result = mysqli_query($conn, "SELECT no_induk, nama_mapel, kelas, hari FROM tbl_mapel_ampu LIMIT 3");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- " . $row['nama_mapel'] . " | " . $row['kelas'] . " | " . $row['hari'] . " | NIP: " . $row['no_induk'] . "\n";
    }
} else {
    echo "❌ Tidak ada data jadwal. Tambahkan data sample:\n";
    echo "INSERT INTO tbl_mapel_ampu (no_induk, nama_mapel, kelas, hari, jam_mulai, jam_selesai)\n";
    echo "VALUES ('123456789', 'Matematika', 'X-A', 'Senin', '07:00', '08:30');\n";
}

echo "\n=== STATUS SISTEM ===\n";
if (count($tables) > 0 && $row['total'] > 0) {
    echo "✅ SISTEM SIAP DIGUNAKAN!\n";
    echo "Silakan akses: http://localhost:8080/pages/guru/cetak_jurnal.php\n";
    echo "Login dengan NIP: 123456789 (jika sudah ada data)\n";
} else {
    echo "⚠️  SISTEM PERLU DATA SAMPLE\n";
    echo "Ikuti instruksi di file INSTRUKSI_SETUP_DATABASE.txt\n";
}

mysqli_close($conn);
?>