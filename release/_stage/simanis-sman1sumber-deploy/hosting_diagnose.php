<?php
// hosting_diagnose.php
// Upload file ini ke root hosting (public_html/hosting_diagnose.php)
// Buka di browser: https://sijurnal.sma1sumber.sch.id/hosting_diagnose.php
// HAPUS file ini setelah selesai debugging (ada info sensitif)

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnosa Hosting - SI Jurnal</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; overflow-x: auto; }
        h2 { margin-top: 0; color: #333; }
    </style>
</head>
<body>
<h1>🔍 Diagnosa Hosting SI Jurnal</h1>
<p><strong>Waktu:</strong> <?= date('Y-m-d H:i:s') ?></p>

<?php
function section($title, $content, $status = 'info') {
    $icon = $status === 'ok' ? '✅' : ($status === 'error' ? '❌' : 'ℹ️');
    echo "<div class='section'><h2>$icon $title</h2><pre>$content</pre></div>\n";
}

// 1. Info PHP
$phpInfo = "PHP Version: " . PHP_VERSION . "\n";
$phpInfo .= "SAPI: " . PHP_SAPI . "\n";
$phpInfo .= "OS: " . PHP_OS . "\n";
$phpInfo .= "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
$phpInfo .= "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
section('1. Informasi PHP', $phpInfo);

// 2. Ekstensi PHP
$mysqli = extension_loaded('mysqli') ? '✅ ADA' : '❌ TIDAK ADA';
$pdo = extension_loaded('pdo_mysql') ? '✅ ADA' : '❌ TIDAK ADA';
$extInfo = "mysqli: $mysqli\n";
$extInfo .= "pdo_mysql: $pdo\n";
$extInfo .= "\nSemua ekstensi:\n" . implode(', ', get_loaded_extensions());
section('2. Ekstensi Database', $extInfo, extension_loaded('mysqli') ? 'ok' : 'error');

// 3. Cari file koneksi.php
$koneksiPath = null;
$searchPaths = [
    __DIR__ . '/koneksi.php',
    __DIR__ . '/../koneksi.php',
    $_SERVER['DOCUMENT_ROOT'] . '/koneksi.php',
];
$pathInfo = "Mencari koneksi.php...\n\n";
foreach ($searchPaths as $path) {
    $exists = file_exists($path) ? '✅' : '❌';
    $pathInfo .= "$exists $path\n";
    if (file_exists($path) && !$koneksiPath) {
        $koneksiPath = $path;
    }
}
section('3. Lokasi File Koneksi', $pathInfo, $koneksiPath ? 'ok' : 'error');

// 4. Test koneksi database
$connStatus = '';
$conn = null;
if ($koneksiPath) {
    try {
        ob_start();
        include $koneksiPath;
        $includeOutput = ob_get_clean();
        
        if (isset($conn) && $conn instanceof mysqli) {
            if ($conn->connect_errno) {
                $connStatus = "❌ GAGAL KONEKSI\n";
                $connStatus .= "Error ({$conn->connect_errno}): {$conn->connect_error}\n";
            } else {
                $connStatus = "✅ KONEKSI BERHASIL\n";
                $connStatus .= "Host Info: " . $conn->host_info . "\n";
                $connStatus .= "Charset: " . $conn->character_set_name() . "\n";
                
                $dbResult = $conn->query('SELECT DATABASE() AS db');
                if ($dbResult) {
                    $dbRow = $dbResult->fetch_assoc();
                    $connStatus .= "Database Aktif: " . ($dbRow['db'] ?? 'NULL') . "\n";
                }
            }
        } else {
            $connStatus = "❌ Variabel \$conn tidak ditemukan atau bukan instance mysqli\n";
            if ($includeOutput) {
                $connStatus .= "\nOutput include:\n$includeOutput\n";
            }
        }
    } catch (Throwable $e) {
        $connStatus = "❌ EXCEPTION:\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString();
    }
} else {
    $connStatus = "❌ File koneksi.php tidak ditemukan!";
}
section('4. Status Koneksi Database', $connStatus, (isset($conn) && $conn instanceof mysqli && !$conn->connect_errno) ? 'ok' : 'error');

// 5. Daftar tabel
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_errno) {
    $tableInfo = '';
    $result = $conn->query('SHOW TABLES');
    if ($result) {
        $tables = [];
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        $tableInfo .= "Jumlah tabel: " . count($tables) . "\n\n";
        $tableInfo .= implode("\n", $tables);
        section('5. Daftar Tabel Database', $tableInfo, 'ok');
        
        // 6. Cek tabel penting
        $requiredTables = ['tbl_mapel_ampu', 'tbl_materi', 'tbl_siswa', 'tbl_guru', 'tbl_absen'];
        $checkInfo = '';
        foreach ($requiredTables as $table) {
            $check = $conn->query("SHOW TABLES LIKE '$table'");
            $exists = ($check && $check->num_rows > 0) ? '✅' : '❌';
            $checkInfo .= "$exists $table";
            
            if ($check && $check->num_rows > 0) {
                // Cek struktur kolom
                $cols = $conn->query("SHOW COLUMNS FROM `$table`");
                if ($cols) {
                    $columnNames = [];
                    while ($col = $cols->fetch_assoc()) {
                        $columnNames[] = $col['Field'];
                    }
                    $checkInfo .= " (" . implode(', ', $columnNames) . ")";
                }
            }
            $checkInfo .= "\n";
        }
        section('6. Tabel Aplikasi', $checkInfo);
    } else {
        section('5. Daftar Tabel', '❌ Gagal query SHOW TABLES: ' . $conn->error, 'error');
    }
    
    // 7. Test query jurnal
    $testInfo = "Testing query tbl_materi...\n\n";
    $testQuery = "SELECT id_materi, date, id_mapel FROM tbl_materi LIMIT 5";
    $testResult = $conn->query($testQuery);
    if ($testResult) {
        $testInfo .= "✅ Query berhasil\n";
        $testInfo .= "Jumlah rows: " . $testResult->num_rows . "\n\n";
        while ($row = $testResult->fetch_assoc()) {
            $testInfo .= json_encode($row, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        $testInfo .= "❌ Query gagal\n";
        $testInfo .= "Error: " . $conn->error . "\n";
        $testInfo .= "Errno: " . $conn->errno . "\n";
    }
    section('7. Test Query tbl_materi', $testInfo, $testResult ? 'ok' : 'error');
}

// 8. Cek permission direktori log
$logPath = __DIR__ . '/pages/guru/jurnal_errors.log';
$logDir = dirname($logPath);
$permInfo = "Path log: $logPath\n\n";
$permInfo .= "Direktori pages/guru: " . (is_dir($logDir) ? '✅ Ada' : '❌ Tidak ada') . "\n";
if (is_dir($logDir)) {
    $permInfo .= "Writable: " . (is_writable($logDir) ? '✅ Ya' : '❌ Tidak') . "\n";
}
if (file_exists($logPath)) {
    $permInfo .= "File log exists: ✅ Ya\n";
    $permInfo .= "File writable: " . (is_writable($logPath) ? '✅ Ya' : '❌ Tidak') . "\n";
    $permInfo .= "File size: " . filesize($logPath) . " bytes\n";
} else {
    $permInfo .= "File log exists: ❌ Belum ada (akan dibuat otomatis)\n";
}
section('8. Permission File Log', $permInfo);

// 9. Cek file koneksi.php content (hanya kredensial, JANGAN TAMPILKAN PASSWORD!)
if ($koneksiPath && file_exists($koneksiPath)) {
    $koneksiContent = file_get_contents($koneksiPath);
    // Mask password
    $koneksiContentMasked = preg_replace('/(password|pass)\s*=\s*["\']([^"\']+)["\']/i', '$1="***HIDDEN***"', $koneksiContent);
    section('9. Koneksi.php Content (Password Hidden)', htmlspecialchars(substr($koneksiContentMasked, 0, 1000)));
}

?>

<div class="section">
    <h2>📋 Ringkasan & Rekomendasi</h2>
    <pre><?php
    echo "Checklist:\n\n";
    echo (extension_loaded('mysqli') ? '✅' : '❌') . " Ekstensi mysqli tersedia\n";
    echo ($koneksiPath ? '✅' : '❌') . " File koneksi.php ditemukan\n";
    echo (isset($conn) && $conn instanceof mysqli && !$conn->connect_errno ? '✅' : '❌') . " Koneksi database berhasil\n";
    echo (is_dir($logDir) && is_writable($logDir) ? '✅' : '❌') . " Direktori log writable\n";
    
    echo "\n\nLangkah selanjutnya:\n";
    if (!extension_loaded('mysqli')) {
        echo "- Aktifkan ekstensi mysqli di php.ini hosting\n";
    }
    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_errno) {
        echo "- Periksa kredensial database di koneksi.php\n";
        echo "- Pastikan database 'smasumb1_simanis' ada di hosting\n";
        echo "- Periksa user 'smasumb1_simanis1' punya akses ke database tersebut\n";
    }
    if (is_dir($logDir) && !is_writable($logDir)) {
        echo "- Set permission 755 atau 775 untuk folder pages/guru/\n";
    }
    echo "\n⚠️ PENTING: HAPUS FILE INI SETELAH SELESAI (ada info database)!\n";
    ?></pre>
</div>

</body>
</html>
