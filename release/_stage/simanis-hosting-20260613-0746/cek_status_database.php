<?php
echo "=== CEK STATUS DATABASE SETELAH SETUP ===\n\n";

echo "Script ini akan mengecek apakah database sudah siap digunakan.\n\n";

// Coba koneksi ke database jurnal
echo "1. Testing koneksi database...\n";
$conn = mysqli_connect('localhost', 'root', '', 'jurnal');

if (!$conn) {
    echo "❌ KONEKSI GAGAL\n";
    echo "Error: " . mysqli_connect_error() . "\n\n";
    echo "SOLUSI:\n";
    echo "- Pastikan XAMPP MySQL sudah start\n";
    echo "- Pastikan database 'jurnal' sudah dibuat\n";
    echo "- Lihat PANDUAN_SETUP_LENGKAP.txt\n";
    exit(1);
}

echo "✅ KONEKSI BERHASIL\n\n";

// Cek tabel
echo "2. Mengecek tabel...\n";
$result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
    echo "   - $row[0]\n";
}

$requiredTables = ['tbl_guru', 'tbl_mapel_ampu', 'tbl_materi'];
$missing = array_diff($requiredTables, $tables);

if (!empty($missing)) {
    echo "\n❌ TABEL YANG HILANG:\n";
    foreach ($missing as $table) {
        echo "   - $table\n";
    }
    echo "\nSOLUSI: Import ulang file db_appsiswa.sql\n";
} else {
    echo "\n✅ SEMUA TABEL PENTING ADA\n";
}

// Cek data guru
echo "\n3. Mengecek data guru...\n";
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_guru");
$row = mysqli_fetch_assoc($result);
echo "   Total guru: {$row['total']}\n";

if ($row['total'] > 0) {
    $result = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "   - NIP: {$row['no_induk']} | {$row['nama_guru']}\n";
    }
} else {
    echo "   ❌ TIDAK ADA DATA GURU\n";
}

// Cek data jadwal
echo "\n4. Mengecek data jadwal...\n";
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mapel_ampu");
$row = mysqli_fetch_assoc($result);
echo "   Total jadwal: {$row['total']}\n";

if ($row['total'] > 0) {
    $result = mysqli_query($conn, "SELECT nama_mapel, kelas, hari FROM tbl_mapel_ampu");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "   - {$row['nama_mapel']} | {$row['kelas']} | {$row['hari']}\n";
    }
} else {
    echo "   ❌ TIDAK ADA DATA JADWAL\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "STATUS AKHIR:\n";

$ready = (count($tables) >= 3 && $row['total'] > 0);

if ($ready) {
    echo "✅ SISTEM SIAP DIGUNAKAN!\n\n";
    echo "LANGKAH SELANJUTNYA:\n";
    echo "1. Buka: http://localhost:8080\n";
    echo "2. Login sebagai guru dengan NIP yang tersedia\n";
    echo "3. Akses halaman cetak jurnal\n";
    echo "4. Sistem akan menampilkan data jadwal + jurnal\n\n";

    echo "🎉 SELAMAT! Sistem cetak jurnal sudah berfungsi!\n";
} else {
    echo "⚠️  MASIH PERLU SETUP\n\n";
    echo "LIHAT FILE: PANDUAN_SETUP_LENGKAP.txt\n";
}

mysqli_close($conn);
?>