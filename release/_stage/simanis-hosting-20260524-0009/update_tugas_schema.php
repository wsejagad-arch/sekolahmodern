<?php
include 'koneksi.php';

echo "Updating tbl_tugas database schema...\n";

// Add new columns to tbl_tugas table
$alterQueries = [
    "ALTER TABLE tbl_tugas ADD COLUMN link_tugas VARCHAR(500) DEFAULT NULL AFTER deskripsi",
    "ALTER TABLE tbl_tugas ADD COLUMN file_tugas VARCHAR(255) DEFAULT NULL AFTER link_tugas"
];

foreach ($alterQueries as $query) {
    echo "Executing: $query\n";
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo "✓ Success\n";
    } else {
        $error = mysqli_error($conn);
        if (strpos($error, 'Duplicate column name') !== false) {
            echo "⚠ Column already exists, skipping\n";
        } else {
            echo "✗ Error: $error\n";
        }
    }
    echo "\n";
}

// Verify the updated structure
echo "Current tbl_tugas structure:\n";
$result = mysqli_query($conn, "DESCRIBE tbl_tugas");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
} else {
    echo "Error getting table structure: " . mysqli_error($conn) . "\n";
}

echo "\nDatabase schema update completed!\n";
?>