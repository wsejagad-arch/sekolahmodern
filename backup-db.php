<?php
session_start();
// Proteksi: Hanya Admin Pusat
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 1) {
    die("Akses ditolak. Hanya Admin Pusat yang dapat melakukan backup.");
}

require_once 'koneksi.php';

// Pastikan koneksi dan db aktif
if (!$conn) {
    die("Koneksi database gagal.");
}

// Ambil nama database aktif
$dbNameQuery = mysqli_query($conn, "SELECT DATABASE()");
$dbNameRow = mysqli_fetch_row($dbNameQuery);
$dbName = $dbNameRow[0] ?? 'jurnal_backup';

$filename = "backup_data_" . $dbName . "_" . date("Y-m-d_H-i-s") . ".sql";

// Set header untuk download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename=' . $filename);
header('Cache-Control: no-cache, no-store, must-revalidate'); 
header('Pragma: no-cache'); 
header('Expires: 0');

$tables = [];
$result = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

$sqlScript = "-- ==============================================\n";
$sqlScript .= "-- Backup Database Sistem Jurnal\n";
$sqlScript .= "-- Tanggal: " . date("Y-m-d H:i:s") . "\n";
$sqlScript .= "-- ==============================================\n\n";
$sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    // Drop table and Create table statement
    $result = mysqli_query($conn, "SHOW CREATE TABLE `" . $table . "`");
    $row = mysqli_fetch_row($result);
    
    $sqlScript .= "DROP TABLE IF EXISTS `" . $table . "`;\n";
    $sqlScript .= $row[1] . ";\n\n";
    
    // Dump data
    $result = mysqli_query($conn, "SELECT * FROM `" . $table . "`");
    $columnCount = mysqli_num_fields($result);
    
    $rowCount = mysqli_num_rows($result);
    if ($rowCount > 0) {
        $sqlScript .= "INSERT INTO `" . $table . "` VALUES \n";
        $i = 0;
        while ($row = mysqli_fetch_row($result)) {
            $sqlScript .= "(";
            for ($j = 0; $j < $columnCount; $j++) {
                if (isset($row[$j])) {
                    // Escape data and wrap in single quotes
                    $escaped = mysqli_real_escape_string($conn, $row[$j]);
                    $sqlScript .= "'" . $escaped . "'";
                } else {
                    $sqlScript .= "NULL";
                }
                
                if ($j < ($columnCount - 1)) {
                    $sqlScript .= ",";
                }
            }
            $i++;
            if ($i < $rowCount) {
                $sqlScript .= "),\n";
            } else {
                $sqlScript .= ");\n";
            }
        }
    }
    $sqlScript .= "\n\n"; 
}

$sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";

echo $sqlScript;
exit;
?>
