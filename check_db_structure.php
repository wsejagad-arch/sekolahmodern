<?php
include "koneksi.php";

echo "=== DATABASE STRUCTURE CHECK ===" . PHP_EOL;

if (!$conn) {
    die("Database connection failed!" . PHP_EOL);
}

echo "Database connected successfully!" . PHP_EOL;
echo "Database: " . $cfg['db'] . PHP_EOL;
echo PHP_EOL;

// Check table structures
$tables = ['tbl_jurnal', 'tbl_kehadiran', 'tbl_kelas', 'tbl_guru', 'tbl_siswa'];

foreach ($tables as $table) {
    echo "=== $table ===" . PHP_EOL;
    
    // Check if table exists
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($check) == 0) {
        echo "Table $table does NOT exist" . PHP_EOL;
        continue;
    }
    
    // Show table structure
    $result = mysqli_query($conn, "DESCRIBE $table");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "  " . $row['Field'] . " (" . $row['Type'] . ")" . ($row['Key'] ? " [" . $row['Key'] . "]" : "") . PHP_EOL;
        }
        
        // Show sample data count
        $count = mysqli_query($conn, "SELECT COUNT(*) as total FROM $table");
        if ($count) {
            $countRow = mysqli_fetch_assoc($count);
            echo "  Total records: " . $countRow['total'] . PHP_EOL;
        }
    }
    echo PHP_EOL;
}

// Check some sample data from existing tables
echo "=== SAMPLE DATA ===" . PHP_EOL;

// Sample from tbl_guru
$guru = mysqli_query($conn, "SELECT id_guru, nama_guru, status_kepegawaian FROM tbl_guru LIMIT 3");
if ($guru && mysqli_num_rows($guru) > 0) {
    echo "Sample tbl_guru data:" . PHP_EOL;
    while ($row = mysqli_fetch_assoc($guru)) {
        echo "  ID: " . $row['id_guru'] . " | Nama: " . $row['nama_guru'] . " | Status: " . $row['status_kepegawaian'] . PHP_EOL;
    }
}

echo PHP_EOL;
?>