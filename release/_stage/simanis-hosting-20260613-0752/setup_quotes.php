<?php
// Setup Quotes Database

require_once 'config.php';

try {
    // Create quotes table
    $sql = "CREATE TABLE IF NOT EXISTS quotes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        quote TEXT NOT NULL,
        author VARCHAR(100) NOT NULL,
        category VARCHAR(50) DEFAULT 'motivasi',
        status ENUM('aktif', 'tidak aktif') DEFAULT 'aktif',
        tanggal_dibuat DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (created_by) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $conn->exec($sql);
    echo "✓ Tabel quotes berhasil dibuat<br>";

    // Insert sample quotes
    $sampleQuotes = [
        ["Pendidikan adalah senjata paling ampuh untuk mengubah dunia.", "Nelson Mandela", "pendidikan"],
        ["Kesuksesan adalah kemampuan untuk beralih dari satu kegagalan ke kegagalan berikutnya tanpa kehilangan semangat.", "Winston Churchill", "motivasi"],
        ["Belajar tanpa berpikir tidak ada gunanya, berpikir tanpa belajar sangat berbahaya.", "Confucius", "pendidikan"],
        ["Masa depan milik mereka yang percaya pada keindahan mimpi mereka.", "Eleanor Roosevelt", "motivasi"],
        ["Pendidikan bukan persiapan untuk hidup, pendidikan adalah hidup itu sendiri.", "John Dewey", "pendidikan"]
    ];

    foreach ($sampleQuotes as $q) {
        $stmt = $conn->prepare("INSERT INTO quotes (quote, author, category, status, created_by) VALUES (?, ?, ?, 'aktif', 1)");
        $stmt->execute([$q[0], $q[1], $q[2]]);
    }
    echo "✓ Sample quotes berhasil ditambahkan<br>";

    echo "<br><strong>✓ Setup quotes selesai!</strong>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
