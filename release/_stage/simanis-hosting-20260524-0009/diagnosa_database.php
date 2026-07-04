<?php
echo "=== ALTERNATIF SOLUSI DATABASE ===\n\n";

// Mari kita coba pendekatan yang berbeda
echo "1. Mengecek apakah MySQL service berjalan...\n";

// Coba koneksi tanpa database dulu
$conn = @mysqli_connect('localhost', 'root', '');

if (!$conn) {
    echo "❌ TIDAK BISA KONEKSI KE MYSQL SERVER\n";
    echo "Error: " . mysqli_connect_error() . "\n\n";

    echo "SOLUSI:\n";
    echo "1. Buka XAMPP Control Panel\n";
    echo "2. Klik 'Start' pada MySQL (harus berwarna hijau)\n";
    echo "3. Jika masih error, coba restart XAMPP\n";
    echo "4. Atau gunakan WAMP/XAMPP versi lain\n\n";

    echo "ALTERNATIF LAIN:\n";
    echo "- Gunakan Laragon (https://laragon.org/)\n";
    echo "- Atau install MySQL secara terpisah\n";
    exit(1);
}

echo "✅ KONEKSI KE MYSQL SERVER BERHASIL\n\n";

// Cek apakah database jurnal ada
echo "2. Mengecek database 'jurnal'...\n";
$result = mysqli_query($conn, "SHOW DATABASES");
$databases = [];
while ($row = mysqli_fetch_row($result)) {
    $databases[] = $row[0];
}

if (!in_array('jurnal', $databases)) {
    echo "❌ DATABASE 'jurnal' TIDAK DITEMUKAN\n\n";
    echo "Database yang tersedia:\n";
    foreach ($databases as $db) {
        echo "- $db\n";
    }
    echo "\n";

    echo "LANGKAH MEMBUAT DATABASE:\n";
    echo "1. Buka phpMyAdmin: http://localhost/phpmyadmin\n";
    echo "2. Login dengan user: root, password: (kosong)\n";
    echo "3. Klik 'New' untuk buat database baru\n";
    echo "4. Nama database: jurnal\n";
    echo "5. Collation: utf8mb4_general_ci\n";
    echo "6. Klik 'Create'\n\n";

    echo "SETELAH DATABASE DIBUAT:\n";
    echo "1. Klik database 'jurnal' di sidebar\n";
    echo "2. Klik tab 'Import'\n";
    echo "3. Pilih file: include/db_appsiswa.sql\n";
    echo "4. Klik 'Go' untuk import\n\n";

    mysqli_close($conn);
    exit(1);
}

echo "✅ DATABASE 'jurnal' DITEMUKAN\n\n";

// Pilih database jurnal
if (!mysqli_select_db($conn, 'jurnal')) {
    echo "❌ GAGAL MEMILIH DATABASE 'jurnal'\n";
    mysqli_close($conn);
    exit(1);
}

echo "✅ DATABASE 'jurnal' BERHASIL DIPILIH\n\n";

// Cek tabel yang ada
echo "3. Mengecek tabel dalam database...\n";
$result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
    echo "- $row[0]\n";
}

echo "\nTotal tabel: " . count($tables) . "\n\n";

// Cek tabel penting
$requiredTables = ['tbl_guru', 'tbl_mapel_ampu', 'tbl_materi'];
$missingTables = [];

foreach ($requiredTables as $table) {
    if (!in_array($table, $tables)) {
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    echo "❌ TABEL YANG HILANG:\n";
    foreach ($missingTables as $table) {
        echo "- $table\n";
    }
    echo "\nSOLUSI: Import ulang file include/db_appsiswa.sql\n\n";
} else {
    echo "✅ SEMUA TABEL PENTING DITEMUKAN\n\n";
}

// Cek data guru
echo "4. Mengecek data guru...\n";
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_guru");
$row = mysqli_fetch_assoc($result);
$guruCount = $row['total'];
echo "Total guru: $guruCount\n";

if ($guruCount == 0) {
    echo "❌ TIDAK ADA DATA GURU\n\n";
    echo "TAMBAHKAN DATA SAMPLE:\n";
    echo "INSERT INTO tbl_guru (no_induk, nama_guru, status_kepegawaian, foto, status) VALUES\n";
    echo "('123456789', 'Ahmad Surya', 'PNS', '', 'Aktif');\n\n";
} else {
    echo "✅ ADA DATA GURU\n";
    $result = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru LIMIT 3");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- NIP: {$row['no_induk']} | Nama: {$row['nama_guru']}\n";
    }
    echo "\n";
}

// Cek data jadwal
echo "5. Mengecek data jadwal...\n";
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mapel_ampu");
$row = mysqli_fetch_assoc($result);
$jadwalCount = $row['total'];
echo "Total jadwal: $jadwalCount\n";

if ($jadwalCount == 0) {
    echo "❌ TIDAK ADA DATA JADWAL\n\n";
    echo "TAMBAHKAN DATA SAMPLE:\n";
    echo "INSERT INTO tbl_mapel_ampu (no_induk, nama_mapel, kelas, hari, jam_mulai, jam_selesai) VALUES\n";
    echo "('123456789', 'Matematika', 'X-A', 'Senin', '07:00', '08:30'),\n";
    echo "('123456789', 'Bahasa Indonesia', 'X-B', 'Selasa', '08:30', '10:00');\n\n";
} else {
    echo "✅ ADA DATA JADWAL\n";
    $result = mysqli_query($conn, "SELECT nama_mapel, kelas, hari FROM tbl_mapel_ampu LIMIT 3");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- {$row['nama_mapel']} | {$row['kelas']} | {$row['hari']}\n";
    }
    echo "\n";
}

// Kesimpulan
echo "=== KESIMPULAN ===\n";
if (count($tables) >= 3 && $guruCount > 0 && $jadwalCount > 0) {
    echo "✅ SISTEM SIAP DIGUNAKAN!\n\n";
    echo "SELANJUTNYA:\n";
    echo "1. Buka browser: http://localhost:8080\n";
    echo "2. Login sebagai guru dengan NIP yang tersedia\n";
    echo "3. Akses halaman cetak jurnal untuk testing\n\n";

    echo "DEBUG INFORMATION:\n";
    echo "- Database: jurnal ✅\n";
    echo "- Tabel: " . count($tables) . " tabel ✅\n";
    echo "- Guru: $guruCount data ✅\n";
    echo "- Jadwal: $jadwalCount data ✅\n";
    echo "- Sistem cetak jurnal akan berfungsi normal\n";

} else {
    echo "⚠️  MASIH PERLU SETUP\n\n";
    echo "LANGKAH YANG PERLU DILAKUKAN:\n";
    if (count($tables) < 3) echo "- Import file include/db_appsiswa.sql\n";
    if ($guruCount == 0) echo "- Tambahkan data guru sample\n";
    if ($jadwalCount == 0) echo "- Tambahkan data jadwal sample\n";
    echo "\nLihat file INSTRUKSI_SETUP_DATABASE.txt untuk panduan lengkap\n";
}

mysqli_close($conn);
?>