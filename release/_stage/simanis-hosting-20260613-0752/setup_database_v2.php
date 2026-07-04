<?php
echo "=== SETUP DATABASE MENGGUNAKAN METODE KONEKSI ASLI ===\n";

// Gunakan metode koneksi yang sama seperti di koneksi.php
$cfg = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'db' => 'jurnal',
    'port' => 3306,
];

// Deteksi environment lokal
$serverName = $_SERVER['SERVER_NAME'] ?? 'cli';
$isLocal = in_array($serverName, ['localhost', '127.0.0.1']) || (strpos(php_uname('n'), 'DESKTOP') === 0);

if ($isLocal) {
    echo "Environment: LOCAL ✅\n";
    $cfg['user'] = 'root';
    $cfg['password'] = '';
    $cfg['db'] = 'jurnal';
} else {
    echo "Environment: PRODUCTION\n";
}

echo "Konfigurasi koneksi:\n";
echo "- Host: {$cfg['host']}\n";
echo "- User: {$cfg['user']}\n";
echo "- Database: {$cfg['db']}\n";
echo "- Port: {$cfg['port']}\n\n";

// Coba koneksi dengan berbagai kombinasi
$connectionAttempts = [
    // 1. Dengan database
    ['host' => $cfg['host'], 'user' => $cfg['user'], 'password' => $cfg['password'], 'db' => $cfg['db'], 'port' => $cfg['port']],
    // 2. Tanpa database
    ['host' => $cfg['host'], 'user' => $cfg['user'], 'password' => $cfg['password'], 'db' => null, 'port' => $cfg['port']],
    // 3. Dengan password kosong eksplisit
    ['host' => $cfg['host'], 'user' => $cfg['user'], 'password' => '', 'db' => null, 'port' => $cfg['port']],
];

$conn = null;
foreach ($connectionAttempts as $i => $attempt) {
    echo "Percobaan " . ($i + 1) . ": ";
    if ($attempt['db']) {
        $conn = @mysqli_connect($attempt['host'], $attempt['user'], $attempt['password'], $attempt['db'], $attempt['port']);
        echo "dengan database '{$attempt['db']}'";
    } else {
        $conn = @mysqli_connect($attempt['host'], $attempt['user'], $attempt['password'], null, $attempt['port']);
        echo "tanpa database";
    }

    if ($conn) {
        echo " ✅ BERHASIL\n";
        break;
    } else {
        echo " ❌ GAGAL: " . mysqli_connect_error() . "\n";
    }
}

if (!$conn) {
    echo "\n❌ SEMUA PERCOBAAN KONEKSI GAGAL\n";
    echo "\n=== SOLUSI YANG BISA DICUBA ===\n";
    echo "1. Pastikan XAMPP MySQL service sedang berjalan\n";
    echo "2. Buka XAMPP Control Panel → Start MySQL\n";
    echo "3. Atau gunakan phpMyAdmin untuk setup database manual:\n";
    echo "   - Buka http://localhost/phpmyadmin\n";
    echo "   - Buat database 'jurnal'\n";
    echo "   - Import file include/db_appsiswa.sql\n";
    echo "4. Atau restart XAMPP completely\n";
    exit(1);
}

echo "\n✅ KONEKSI BERHASIL\n";

// Jika belum ada database, buat dulu
if (!mysqli_select_db($conn, $cfg['db'])) {
    echo "Database '{$cfg['db']}' tidak ada, membuat...\n";
    $createDbSql = "CREATE DATABASE IF NOT EXISTS `{$cfg['db']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";

    if (mysqli_query($conn, $createDbSql)) {
        echo "✅ Database '{$cfg['db']}' berhasil dibuat\n";

        // Pilih database
        if (mysqli_select_db($conn, $cfg['db'])) {
            echo "✅ Database '{$cfg['db']}' berhasil dipilih\n";
        } else {
            echo "❌ Gagal memilih database: " . mysqli_error($conn) . "\n";
            mysqli_close($conn);
            exit(1);
        }
    } else {
        echo "❌ Gagal membuat database: " . mysqli_error($conn) . "\n";
        mysqli_close($conn);
        exit(1);
    }
} else {
    echo "✅ Database '{$cfg['db']}' sudah ada dan berhasil dipilih\n";
}

// Import struktur tabel
$sqlFile = __DIR__ . '/include/db_appsiswa.sql';
if (file_exists($sqlFile)) {
    echo "\nMengimport struktur tabel...\n";
    $sqlContent = file_get_contents($sqlFile);

    // Bersihkan komentar dan baris kosong
    $sqlContent = preg_replace('/--.*$/m', '', $sqlContent);
    $sqlContent = preg_replace('/^\s*$/m', '', $sqlContent);

    // Split berdasarkan titik koma
    $commands = array_filter(array_map('trim', explode(';', $sqlContent)));

    $successCount = 0;
    $errorCount = 0;

    foreach ($commands as $command) {
        if (!empty($command) && !preg_match('/^(SET|START|COMMIT)/i', $command)) {
            if (mysqli_query($conn, $command)) {
                $successCount++;
            } else {
                $errorCount++;
                if ($errorCount <= 3) { // Tampilkan hanya 3 error pertama
                    echo "❌ Error: " . mysqli_error($conn) . "\n";
                }
            }
        }
    }

    echo "✅ Import selesai: $successCount berhasil, $errorCount gagal\n";
} else {
    echo "❌ File SQL tidak ditemukan: $sqlFile\n";
}

// Insert data sample
echo "\nMemasukkan data sample...\n";

// Sample guru
$guruQueries = [
    "INSERT IGNORE INTO tbl_guru (no_induk, nama_guru, status_kepegawaian, foto, status) VALUES
    ('123456789', 'Ahmad Surya', 'PNS', '', 'Aktif')",
    "INSERT IGNORE INTO tbl_guru (no_induk, nama_guru, status_kepegawaian, foto, status) VALUES
    ('987654321', 'Siti Aminah', 'PNS', '', 'Aktif')"
];

foreach ($guruQueries as $query) {
    if (mysqli_query($conn, $query)) {
        echo "✅ Sample guru berhasil ditambahkan\n";
        break; // Cukup satu berhasil
    }
}

// Sample jadwal
$jadwalQueries = [
    "INSERT IGNORE INTO tbl_mapel_ampu (no_induk, nama_mapel, kelas, hari, jam_mulai, jam_selesai) VALUES
    ('123456789', 'Matematika', 'X-A', 'Senin', '07:00', '08:30')",
    "INSERT IGNORE INTO tbl_mapel_ampu (no_induk, nama_mapel, kelas, hari, jam_mulai, jam_selesai) VALUES
    ('123456789', 'Bahasa Indonesia', 'X-B', 'Selasa', '08:30', '10:00')",
    "INSERT IGNORE INTO tbl_mapel_ampu (no_induk, nama_mapel, kelas, hari, jam_mulai, jam_selesai) VALUES
    ('987654321', 'Fisika', 'XI-A', 'Rabu', '10:00', '11:30')"
];

foreach ($jadwalQueries as $query) {
    if (mysqli_query($conn, $query)) {
        echo "✅ Sample jadwal berhasil ditambahkan\n";
        break; // Cukup satu berhasil
    }
}

// Cek hasil akhir
echo "\n=== CEK HASIL AKHIR ===\n";
$result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
    echo "- " . $row[0] . "\n";
}

echo "\nTotal tabel: " . count($tables) . "\n";

// Cek data guru
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_guru");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "Total guru: " . $row['total'] . "\n";
}

// Cek data jadwal
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mapel_ampu");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "Total jadwal: " . $row['total'] . "\n";
}

echo "\n🎉 SETUP DATABASE SELESAI!\n";
echo "Sekarang Anda bisa mengakses sistem cetak jurnal dengan data sample.\n";

mysqli_close($conn);
?>