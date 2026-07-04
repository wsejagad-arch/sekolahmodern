<?php
// Setup Pengumuman Database

require_once 'config.php';

try {
    // Create pengumuman table
    $sql = "CREATE TABLE IF NOT EXISTS pengumuman (
        id INT PRIMARY KEY AUTO_INCREMENT,
        judul VARCHAR(255) NOT NULL,
        isi TEXT NOT NULL,
        tanggal_dibuat DATETIME DEFAULT CURRENT_TIMESTAMP,
        tanggal_diupdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM('aktif', 'tidak aktif') DEFAULT 'aktif',
        created_by INT,
        updated_by INT,
        FOREIGN KEY (created_by) REFERENCES users(id),
        FOREIGN KEY (updated_by) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $conn->exec($sql);
    echo "✓ Tabel pengumuman berhasil dibuat<br>";

    // Create pengumuman_kategori table
    $sql = "CREATE TABLE IF NOT EXISTS pengumuman_kategori (
        id INT PRIMARY KEY AUTO_INCREMENT,
        pengumuman_id INT NOT NULL,
        kategori VARCHAR(100),
        FOREIGN KEY (pengumuman_id) REFERENCES pengumuman(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $conn->exec($sql);
    echo "✓ Tabel pengumuman_kategori berhasil dibuat<br>";

    echo "<br><strong>✓ Setup pengumuman selesai!</strong>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>