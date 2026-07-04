<?php
echo "=== MEMBUAT DATABASE JURNAL ===\n";

// Coba koneksi ke MySQL server tanpa database
$host = 'localhost';
$user = 'root';
$pass = '';

echo "Mencoba koneksi ke MySQL server...\n";
$conn = mysqli_connect($host, $user, $pass);

if (!$conn) {
    echo "❌ GAGAL: " . mysqli_connect_error() . "\n";
    echo "\nSolusi:\n";
    echo "1. Pastikan XAMPP MySQL service sedang berjalan\n";
    echo "2. Coba restart XAMPP\n";
    echo "3. Atau gunakan phpMyAdmin untuk membuat database manual\n";
    exit(1);
}

echo "✅ Berhasil koneksi ke MySQL server\n";

// Buat database jurnal
echo "\nMembuat database 'jurnal'...\n";
$sql = "CREATE DATABASE IF NOT EXISTS jurnal CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";

if (mysqli_query($conn, $sql)) {
    echo "✅ Database 'jurnal' berhasil dibuat atau sudah ada\n";
} else {
    echo "❌ GAGAL membuat database: " . mysqli_error($conn) . "\n";
    mysqli_close($conn);
    exit(1);
}

// Pilih database
if (mysqli_select_db($conn, 'jurnal')) {
    echo "✅ Database 'jurnal' berhasil dipilih\n";
} else {
    echo "❌ GAGAL memilih database: " . mysqli_error($conn) . "\n";
    mysqli_close($conn);
    exit(1);
}

// Import struktur tabel dari file SQL
$sqlFile = __DIR__ . '/include/db_appsiswa.sql';
if (file_exists($sqlFile)) {
    echo "\nMengimport struktur tabel dari file SQL...\n";
    $sqlContent = file_get_contents($sqlFile);

    // Split SQL commands
    $commands = array_filter(array_map('trim', explode(';', $sqlContent)));

    $successCount = 0;
    $errorCount = 0;

    foreach ($commands as $command) {
        if (!empty($command) && !preg_match('/^(SET|START|COMMIT|--)/i', $command)) {
            if (mysqli_query($conn, $command)) {
                $successCount++;
            } else {
                $errorCount++;
                echo "❌ Error: " . mysqli_error($conn) . "\n";
                echo "Query: " . substr($command, 0, 100) . "...\n";
            }
        }
    }

    echo "✅ Import selesai: $successCount berhasil, $errorCount gagal\n";
} else {
    echo "❌ File SQL tidak ditemukan: $sqlFile\n";
}

// Cek tabel yang berhasil dibuat
echo "\n=== CEK TABEL YANG BERHASIL DIBUAT ===\n";
$result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
    echo "- " . $row[0] . "\n";
}

// Insert data sample untuk testing
echo "\n=== MEMBUAT DATA SAMPLE ===\n";

// Insert sample guru
$insertGuru = "INSERT INTO tbl_guru (no_induk, nama_guru, status_kepegawaian, foto, status) VALUES
('123456789', 'Ahmad Surya', 'PNS', '', 'Aktif'),
('987654321', 'Siti Aminah', 'PNS', '', 'Aktif')";

if (mysqli_query($conn, $insertGuru)) {
    echo "✅ Sample guru berhasil ditambahkan\n";
} else {
    echo "❌ Gagal menambah sample guru: " . mysqli_error($conn) . "\n";
}

// Insert sample mata pelajaran
$insertMapel = "INSERT INTO tbl_mapel_ampu (no_induk, nama_mapel, kelas, hari, jam_mulai, jam_selesai) VALUES
('123456789', 'Matematika', 'X-A', 'Senin', '07:00', '08:30'),
('123456789', 'Bahasa Indonesia', 'X-B', 'Selasa', '08:30', '10:00'),
('987654321', 'Fisika', 'XI-A', 'Rabu', '10:00', '11:30')";

if (mysqli_query($conn, $insertMapel)) {
    echo "✅ Sample jadwal berhasil ditambahkan\n";
} else {
    echo "❌ Gagal menambah sample jadwal: " . mysqli_error($conn) . "\n";
}

echo "\n=== RINGKASAN ===\n";
echo "Database: jurnal ✅\n";
echo "Tabel: " . count($tables) . " tabel berhasil dibuat\n";
echo "Sample Data: Guru dan jadwal sample ditambahkan\n";
echo "\n🎉 Database siap digunakan!\n";

mysqli_close($conn);
?>