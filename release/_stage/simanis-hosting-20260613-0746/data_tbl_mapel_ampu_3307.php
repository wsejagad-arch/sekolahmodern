<?php
/**
 * Data Viewer untuk tbl_mapel_ampu - Menggunakan Port 3307
 */

// Konfigurasi database dengan port 3307
$host = 'localhost';
$user = 'root';
$password = '';
$db = 'jurnal';
$port = 3307;

echo "=== DATA TBL_MAPEL_AMPU (Port 3307) ===\n\n";

// Koneksi ke database
$conn = mysqli_connect($host, $user, $password, $db, $port);

if (!$conn) {
    echo "❌ KONEKSI GAGAL!\n";
    echo "Error: " . mysqli_connect_error() . "\n\n";
    echo "🔧 TROUBLESHOOTING:\n";
    echo "1. Pastikan MySQL berjalan di port 3307\n";
    echo "2. Cek XAMPP Control Panel → Config → my.ini\n";
    echo "3. Cari 'port = 3307' di file konfigurasi\n";
    exit(1);
}

echo "✅ KONEKSI BERHASIL!\n";
echo "Host: $host:$port\n";
echo "Database: $db\n\n";

// Cek tabel tbl_mapel_ampu
$result = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_mapel_ampu'");
if (mysqli_num_rows($result) == 0) {
    echo "❌ TABEL tbl_mapel_ampu TIDAK DITEMUKAN!\n\n";
    echo "🔧 SOLUSI: Buat tabel dengan query berikut:\n\n";
    echo "CREATE TABLE tbl_mapel_ampu (\n";
    echo "    id_mapel INT PRIMARY KEY AUTO_INCREMENT,\n";
    echo "    no_induk VARCHAR(20) NOT NULL,\n";
    echo "    nama_mapel VARCHAR(100) NOT NULL,\n";
    echo "    kelas VARCHAR(20) NOT NULL,\n";
    echo "    hari VARCHAR(20) NOT NULL,\n";
    echo "    jam_mulai TIME NOT NULL,\n";
    echo "    jam_selesai TIME NOT NULL\n";
    echo ");\n\n";
    mysqli_close($conn);
    exit(1);
}

echo "✅ TABEL tbl_mapel_ampu DITEMUKAN!\n\n";

// Ambil data
$query = "SELECT * FROM tbl_mapel_ampu ORDER BY no_induk, hari, jam_mulai";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "❌ ERROR QUERY: " . mysqli_error($conn) . "\n";
    mysqli_close($conn);
    exit(1);
}

$total_records = mysqli_num_rows($result);
echo "📊 TOTAL DATA: $total_records records\n\n";

if ($total_records == 0) {
    echo "⚠️  TABEL KOSONG - Tidak ada data jadwal mengajar\n\n";
    echo "🔧 SOLUSI: Tambahkan data dengan query berikut:\n\n";
    echo "INSERT INTO tbl_mapel_ampu (no_induk, nama_mapel, kelas, hari, jam_mulai, jam_selesai) VALUES\n";
    echo "    ('123456789', 'Matematika', 'X-A', 'Senin', '07:00:00', '08:30:00'),\n";
    echo "    ('123456789', 'Bahasa Indonesia', 'X-B', 'Selasa', '08:30:00', '10:00:00'),\n";
    echo "    ('987654321', 'Fisika', 'XI-A', 'Rabu', '10:00:00', '11:30:00');\n\n";
    mysqli_close($conn);
    exit(0);
}

// Tampilkan data
echo "📋 DATA JADWAL MENGAJAR:\n";
echo str_repeat("=", 90) . "\n";
printf("%-3s %-12s %-18s %-15s %-8s %-8s %-12s\n",
       "No", "ID Mapel", "NIP Guru", "Mata Pelajaran", "Kelas", "Hari", "Jam Mulai");
echo str_repeat("-", 90) . "\n";

$no = 1;
$all_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $all_data[] = $row;
    printf("%-3d %-12s %-18s %-15s %-8s %-8s %-12s\n",
           $no++,
           $row['id_mapel'] ?? '',
           $row['no_induk'] ?? '',
           substr($row['nama_mapel'] ?? '', 0, 13),
           $row['kelas'] ?? '',
           $row['hari'] ?? '',
           $row['jam_mulai'] ?? '');
}
echo str_repeat("=", 90) . "\n\n";

// Statistik detail
echo "📈 STATISTIK DETAIL:\n\n";

// Hitung per guru
$guru_stats = [];
foreach ($all_data as $row) {
    $guru_stats[$row['no_induk']] = ($guru_stats[$row['no_induk']] ?? 0) + 1;
}

echo "👨‍🏫 JUMLAH JADWAL PER GURU:\n";
foreach ($guru_stats as $nip => $count) {
    echo "  • $nip: $count jadwal\n";
}
echo "\n";

// Hitung per mata pelajaran
$mapel_stats = [];
foreach ($all_data as $row) {
    $mapel_stats[$row['nama_mapel']] = ($mapel_stats[$row['nama_mapel']] ?? 0) + 1;
}

echo "📚 DISTRIBUSI MATA PELAJARAN:\n";
foreach ($mapel_stats as $mapel => $count) {
    echo "  • $mapel: $count jadwal\n";
}
echo "\n";

// Hitung per hari
$hari_stats = [];
foreach ($all_data as $row) {
    $hari_stats[$row['hari']] = ($hari_stats[$row['hari']] ?? 0) + 1;
}

echo "📅 JADWAL PER HARI:\n";
foreach ($hari_stats as $hari => $count) {
    echo "  • $hari: $count jadwal\n";
}
echo "\n";

// Hitung per kelas
$kelas_stats = [];
foreach ($all_data as $row) {
    $kelas_stats[$row['kelas']] = ($kelas_stats[$row['kelas']] ?? 0) + 1;
}

echo "🏫 JADWAL PER KELAS:\n";
foreach ($kelas_stats as $kelas => $count) {
    echo "  • $kelas: $count jadwal\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ DATA BERHASIL DIAMBIL!\n";
echo "📊 Total Records: $total_records\n";
echo "👨‍🏫 Total Guru: " . count($guru_stats) . "\n";
echo "📚 Total Mapel: " . count($mapel_stats) . "\n";
echo "🏫 Total Kelas: " . count($kelas_stats) . "\n";
echo str_repeat("=", 50) . "\n";

mysqli_close($conn);
?>