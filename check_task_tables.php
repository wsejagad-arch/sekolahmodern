<?php
include 'koneksi.php';

echo "Checking for task-related tables:\n";

// Check for specific task tables
$tables = ['tbl_tugas', 'tbl_task', 'tbl_assignment'];
foreach($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if(mysqli_num_rows($result) > 0) {
        echo "Found: $table\n";
    }
}

echo "\nAll tables containing 'tugas' or 'task':\n";
$result = mysqli_query($conn, 'SHOW TABLES');
while($row = mysqli_fetch_array($result)) {
    if(strpos(strtolower($row[0]), 'tugas') !== false || strpos(strtolower($row[0]), 'task') !== false) {
        echo $row[0] . "\n";
    }
}

echo "\nChecking for existing tugas-related columns in tbl_nilai:\n";
$result = mysqli_query($conn, "DESCRIBE tbl_nilai");
while($row = mysqli_fetch_array($result)) {
    if(strpos(strtolower($row[0]), 'tugas') !== false) {
        echo "Column: " . $row[0] . " Type: " . $row[1] . "\n";
    }
}
?>