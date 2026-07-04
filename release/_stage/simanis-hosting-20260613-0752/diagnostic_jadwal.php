<?php
echo "=== DIAGNOSTIC: CEK DATA GURU DAN JADWAL ===\n\n";

// Coba koneksi dengan berbagai cara
$connections = [
    ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'db' => 'jurnal'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'db' => null],
];

$conn = null;
foreach ($connections as $i => $config) {
    echo "Mencoba koneksi " . ($i + 1) . "... ";
    if ($config['db']) {
        $conn = @mysqli_connect($config['host'], $config['user'], $config['pass'], $config['db']);
        echo "dengan database\n";
    } else {
        $conn = @mysqli_connect($config['host'], $config['user'], $config['pass']);
        echo "tanpa database\n";
    }

    if ($conn) {
        echo "✅ BERHASIL\n\n";
        break;
    } else {
        echo "❌ GAGAL: " . mysqli_connect_error() . "\n\n";
    }
}

if (!$conn) {
    echo "❌ TIDAK BISA KONEKSI KE DATABASE\n";
    echo "Pastikan:\n";
    echo "1. XAMPP MySQL sudah start\n";
    echo "2. Database 'jurnal' sudah dibuat\n";
    echo "3. User root memiliki akses\n";
    exit(1);
}

// Jika belum select database, coba select
if (!mysqli_select_db($conn, 'jurnal')) {
    echo "❌ DATABASE 'jurnal' TIDAK DITEMUKAN\n\n";
    echo "Database yang tersedia:\n";
    $result = mysqli_query($conn, "SHOW DATABASES");
    while ($row = mysqli_fetch_row($result)) {
        echo "- {$row[0]}\n";
    }
    echo "\nSolusi: Buat database 'jurnal' di phpMyAdmin\n";
    mysqli_close($conn);
    exit(1);
}

echo "✅ DATABASE 'jurnal' BERHASIL DIPILIH\n\n";

// Cek tabel yang ada
echo "=== CEK TABEL DATABASE ===\n";
$result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
    echo "- {$row[0]}\n";
}

$requiredTables = ['tbl_guru', 'tbl_mapel_ampu', 'tbl_materi'];
$missingTables = array_diff($requiredTables, $tables);

if (!empty($missingTables)) {
    echo "\n❌ TABEL YANG HILANG:\n";
    foreach ($missingTables as $table) {
        echo "- $table\n";
    }
    echo "\nSolusi: Import file include/db_appsiswa.sql\n";
    mysqli_close($conn);
    exit(1);
}

echo "\n✅ SEMUA TABEL PENTING DITEMUKAN\n\n";

// Cek data guru
echo "=== CEK DATA GURU ===\n";
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_guru");
$row = mysqli_fetch_assoc($result);
$guruCount = $row['total'];
echo "Total guru: $guruCount\n";

if ($guruCount == 0) {
    echo "❌ TIDAK ADA DATA GURU\n\n";
    echo "TAMBAHKAN DATA GURU:\n";
    echo "INSERT INTO tbl_guru (no_induk, nama_guru, status_kepegawaian, foto, status) VALUES\n";
    echo "('123456789', 'Ahmad Surya', 'PNS', '', 'Aktif');\n\n";
} else {
    echo "✅ ADA DATA GURU\n";
    $result = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- NIP: {$row['no_induk']} | Nama: {$row['nama_guru']}\n";
    }
    echo "\n";
}

// Cek data jadwal
echo "=== CEK DATA JADWAL ===\n";
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mapel_ampu");
$row = mysqli_fetch_assoc($result);
$jadwalCount = $row['total'];
echo "Total jadwal: $jadwalCount\n";

if ($jadwalCount == 0) {
    echo "❌ TIDAK ADA DATA JADWAL\n\n";
    echo "TAMBAHKAN DATA JADWAL:\n";
    echo "INSERT INTO tbl_mapel_ampu (no_induk, nama_mapel, kelas, hari, jam_mulai, jam_selesai) VALUES\n";
    echo "('123456789', 'Matematika', 'X-A', 'Senin', '07:00', '08:30'),\n";
    echo "('123456789', 'Bahasa Indonesia', 'X-B', 'Selasa', '08:30', '10:00');\n\n";
} else {
    echo "✅ ADA DATA JADWAL\n";
    $result = mysqli_query($conn, "SELECT no_induk, nama_mapel, kelas, hari FROM tbl_mapel_ampu");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- NIP: {$row['no_induk']} | {$row['nama_mapel']} | {$row['kelas']} | {$row['hari']}\n";
    }
    echo "\n";
}

// Analisis masalah
echo "=== ANALISIS MASALAH ===\n";

if ($guruCount == 0) {
    echo "❌ MASALAH: Tidak ada data guru di database\n";
    echo "SOLUSI: Tambahkan data guru terlebih dahulu\n\n";
} elseif ($jadwalCount == 0) {
    echo "❌ MASALAH: Tidak ada data jadwal di database\n";
    echo "SOLUSI: Tambahkan data jadwal untuk guru\n\n";
} else {
    echo "✅ DATA TERSEDIA\n";
    echo "Jika masih muncul 'Tidak ada jadwal', kemungkinan:\n";
    echo "1. NIP guru di session tidak sesuai dengan data jadwal\n";
    echo "2. Filter kelas aktif tapi tidak ada jadwal untuk kelas tersebut\n";
    echo "3. Periode tanggal tidak sesuai\n\n";

    // Cek apakah ada jadwal untuk NIP tertentu
    echo "CEK JADWAL PER NIP:\n";
    $result = mysqli_query($conn, "SELECT no_induk, COUNT(*) as count FROM tbl_mapel_ampu GROUP BY no_induk");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- NIP {$row['no_induk']}: {$row['count']} jadwal\n";
    }
    echo "\n";
}

echo "=== REKOMENDASI SOLUSI ===\n";
if ($guruCount == 0 || $jadwalCount == 0) {
    echo "1. Buka phpMyAdmin: http://localhost/phpmyadmin\n";
    echo "2. Klik database 'jurnal'\n";
    echo "3. Klik tab 'SQL'\n";
    echo "4. Jalankan query di atas untuk menambah data\n";
    echo "5. Refresh halaman cetak jurnal\n";
} else {
    echo "1. Cek NIP guru yang sedang login\n";
    echo "2. Pastikan NIP tersebut ada di tbl_mapel_ampu\n";
    echo "3. Coba hapus filter kelas jika aktif\n";
    echo "4. Lihat debug info di halaman cetak jurnal\n";
}

echo "\n=== TEST CEPAT ===\n";
echo "Jalankan: php cek_status_database.php\n";
echo "Untuk verifikasi cepat status database\n";

mysqli_close($conn);
?>