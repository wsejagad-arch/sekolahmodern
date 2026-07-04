<?php
include '../../koneksi.php';

// Add updated_at column if it doesn't exist
$check_column_query = "SHOW COLUMNS FROM tbl_tugas LIKE 'updated_at'";
$check_result = mysqli_query($conn, $check_column_query);

if (mysqli_num_rows($check_result) == 0) {
    $add_column_query = "ALTER TABLE tbl_tugas ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    
    if (mysqli_query($conn, $add_column_query)) {
        echo "Column 'updated_at' berhasil ditambahkan ke tabel tbl_tugas.\n";
    } else {
        echo "Error menambahkan kolom 'updated_at': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Column 'updated_at' sudah ada di tabel tbl_tugas.\n";
}

// Show current table structure
$describe_query = "DESCRIBE tbl_tugas";
$describe_result = mysqli_query($conn, $describe_query);

echo "\nStruktur tabel tbl_tugas:\n";
echo "=========================\n";
while ($row = mysqli_fetch_assoc($describe_result)) {
    echo sprintf("%-20s %-15s %-10s %-5s %-15s %s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'], 
        $row['Key'], 
        $row['Default'], 
        $row['Extra']
    );
}

mysqli_close($conn);
?>