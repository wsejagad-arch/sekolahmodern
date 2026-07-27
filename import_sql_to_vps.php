<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');

require_once __DIR__ . '/koneksi_local.php';

if (!$conn) {
    die("Koneksi gagal!\n");
}

echo "Berhasil terhubung ke: " . mysqli_get_host_info($conn) . "\n";

$file_path = __DIR__ . '/smasumb1_simanis.sql';
if (!file_exists($file_path)) {
    die("File SQL tidak ditemukan!\n");
}

echo "Mulai import $file_path...\n";

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0;");

$file = fopen($file_path, "r");
$delimiter = ';';
$query = '';
$count = 0;

while (!feof($file)) {
    $line = fgets($file);
    if ($line === false) break;

    $trimLine = trim($line);
    
    // Skip empty lines and comments (if not part of a trigger string, but we just check start)
    if ($trimLine == '' || strpos($trimLine, '--') === 0 || strpos($trimLine, '/*') === 0) {
        if (empty($query)) continue;
    }
    
    // Check if line changes delimiter
    if (preg_match('/^DELIMITER\s+(.+)$/i', $trimLine, $matches)) {
        $delimiter = $matches[1];
        continue;
    }

    $query .= $line;
    
    // Check if query ends with the current delimiter
    if (preg_match('/' . preg_quote($delimiter, '/') . '\s*$/i', trim($query))) {
        // Remove delimiter from end of query
        $query = preg_replace('/' . preg_quote($delimiter, '/') . '\s*$/i', '', trim($query));
        
        if (!empty($query)) {
            $res = mysqli_query($conn, $query);
            if (!$res) {
                echo "Error pada query: " . substr($query, 0, 100) . "...\n";
                echo "Pesan Error: " . mysqli_error($conn) . "\n";
            }
            $count++;
        }
        $query = '';
    }
}
fclose($file);

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1;");
echo "Import selesai. Total query executed: $count\n";
?>
