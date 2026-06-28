<?php
include 'koneksi.php';

echo "Checking tbl_tugas structure:\n";
$result = mysqli_query($conn, "DESCRIBE tbl_tugas");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
} else {
    echo "Table doesn't exist yet. Creating...\n";
    // Create table if it doesn't exist
    $createQuery = "CREATE TABLE IF NOT EXISTS tbl_tugas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tanggal DATE NOT NULL,
        id_mapel INT NOT NULL,
        kelas VARCHAR(50) NOT NULL,
        mapel VARCHAR(100) NOT NULL,
        no_induk_guru VARCHAR(50) NOT NULL,
        judul_tugas VARCHAR(255) NOT NULL,
        deskripsi TEXT,
        link_tugas VARCHAR(500) DEFAULT NULL,
        file_tugas VARCHAR(255) DEFAULT NULL,
        tanggal_pengumpulan DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM('aktif', 'selesai', 'dihapus') DEFAULT 'aktif',
        INDEX (tanggal, id_mapel),
        INDEX (no_induk_guru)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (mysqli_query($conn, $createQuery)) {
        echo "✓ Table created successfully with all required columns\n";
    } else {
        echo "✗ Error creating table: " . mysqli_error($conn) . "\n";
    }
}
?>