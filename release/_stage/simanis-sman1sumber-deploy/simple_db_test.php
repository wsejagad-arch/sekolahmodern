<?php
/**
 * Simple Database Connection Test & Data Viewer
 * For tbl_mapel_ampu table
 */

// Konfigurasi database lokal (XAMPP default)
$host = 'localhost';
$user = 'root';
$password = '';
$db = 'jurnal';
$port = 3307;

echo "=== TEST KONEKSI & DATA TBL_MAPEL_AMPU ===\n\n";

// Test koneksi
$conn = mysqli_connect($host, $user, $password, $db, $port);

if (!$conn) {
    echo "❌ KONEKSI GAGAL!\n";
    echo "Error: " . mysqli_connect_error() . "\n\n";

    echo "🔧 TROUBLESHOOTING:\n";
    echo "1. Pastikan XAMPP MySQL sedang berjalan\n";
    echo "2. Buka XAMPP Control Panel → Start MySQL\n";
    echo "3. Buat database 'jurnal' di phpMyAdmin\n";
    echo "4. Import struktur database dari include/db_appsiswa.sql\n";
    exit(1);
}

echo "✅ KONEKSI BERHASIL!\n";
echo "Host: $host:$port\n";
echo "Database: $db\n";
echo "User: $user\n\n";

// Cek apakah tabel tbl_mapel_ampu ada
$result = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_mapel_ampu'");
if (mysqli_num_rows($result) == 0) {
    echo "❌ TABEL tbl_mapel_ampu TIDAK DITEMUKAN!\n\n";

    echo "🔧 SOLUSI - Buat tabel manual:\n";
    echo "Buka phpMyAdmin → Database 'jurnal' → SQL → Jalankan query berikut:\n\n";

    $create_table = "CREATE TABLE tbl_mapel_ampu (
        id_mapel INT PRIMARY KEY AUTO_INCREMENT,
        no_induk VARCHAR(20) NOT NULL,
        nama_mapel VARCHAR(100) NOT NULL,
        kelas VARCHAR(20) NOT NULL,
        hari VARCHAR(20) NOT NULL,
        jam_mulai TIME NOT NULL,
        jam_selesai TIME NOT NULL,
        INDEX idx_no_induk (no_induk),
        INDEX idx_hari (hari)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    echo $create_table . "\n\n";

    echo "📝 Kemudian tambahkan data sample:\n";
    $sample_data = "INSERT INTO tbl_mapel_ampu (no_induk, nama_mapel, kelas, hari, jam_mulai, jam_selesai) VALUES
        ('123456789', 'Matematika', 'X-A', 'Senin', '07:00:00', '08:30:00'),
        ('123456789', 'Bahasa Indonesia', 'X-B', 'Selasa', '08:30:00', '10:00:00'),
        ('123456789', 'Fisika', 'XI-A', 'Rabu', '10:00:00', '11:30:00'),
        ('987654321', 'Kimia', 'XI-B', 'Kamis', '07:00:00', '08:30:00'),
        ('987654321', 'Biologi', 'XI-A', 'Jumat', '09:00:00', '10:30:00');";

    echo $sample_data . "\n\n";
    exit(1);
}

echo "✅ TABEL tbl_mapel_ampu DITEMUKAN!\n\n";

// Ambil semua data dari tbl_mapel_ampu
$query = "SELECT * FROM tbl_mapel_ampu ORDER BY no_induk, hari, jam_mulai";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "❌ ERROR QUERY: " . mysqli_error($conn) . "\n";
    exit(1);
}

$total_records = mysqli_num_rows($result);
echo "📊 TOTAL DATA: $total_records records\n\n";

if ($total_records == 0) {
    echo "⚠️  TABEL KOSONG - Tidak ada data jadwal mengajar\n\n";
    echo "🔧 SOLUSI: Tambahkan data melalui aplikasi atau query SQL\n";
    exit(0);
}

// Tampilkan data dalam format tabel
echo "📋 DATA TBL_MAPEL_AMPU:\n";
echo str_repeat("=", 100) . "\n";
printf("%-3s %-10s %-15s %-20s %-8s %-10s %-10s\n",
       "No", "ID Mapel", "No Induk", "Nama Mapel", "Kelas", "Hari", "Jam Mulai");
echo str_repeat("-", 100) . "\n";

$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    printf("%-3d %-10s %-15s %-20s %-8s %-10s %-10s\n",
           $no++,
           $row['id_mapel'] ?? '',
           $row['no_induk'] ?? '',
           substr($row['nama_mapel'] ?? '', 0, 18),
           $row['kelas'] ?? '',
           $row['hari'] ?? '',
           $row['jam_mulai'] ?? '');
}
echo str_repeat("=", 100) . "\n\n";

// Statistik
echo "📈 STATISTIK:\n";

// Hitung per guru
$guru_stats = [];
mysqli_data_seek($result, 0);
while ($row = mysqli_fetch_assoc($result)) {
    $guru_stats[$row['no_induk']] = ($guru_stats[$row['no_induk']] ?? 0) + 1;
}

echo "👨‍🏫 Jumlah jadwal per guru:\n";
foreach ($guru_stats as $nip => $count) {
    echo "  • $nip: $count jadwal\n";
}
echo "\n";

// Hitung per hari
$hari_stats = [];
mysqli_data_seek($result, 0);
while ($row = mysqli_fetch_assoc($result)) {
    $hari_stats[$row['hari']] = ($hari_stats[$row['hari']] ?? 0) + 1;
}

echo "📅 Distribusi per hari:\n";
foreach ($hari_stats as $hari => $count) {
    echo "  • $hari: $count jadwal\n";
}

mysqli_close($conn);
echo "\n✅ SELESAI!\n";
?>