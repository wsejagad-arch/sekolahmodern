<?php
// Koneksi database
$conn = new mysqli("localhost", "root", "", "sijurnal");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Check if table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'tbl_wali_kelas'");
if ($checkTable && $checkTable->num_rows > 0) {
    echo "<h3>Tabel tbl_wali_kelas DITEMUKAN</h3>";

    // Get table structure
    $result = $conn->query("DESCRIBE tbl_wali_kelas");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row['Field'] . "</td><td>" . $row['Type'] . "</td><td>" . $row['Null'] . "</td><td>" . $row['Key'] . "</td><td>" . $row['Default'] . "</td></tr>";
    }
    echo "</table>";

    // Sample data
    echo "<h3>Sample Data</h3>";
    $sample = $conn->query("SELECT * FROM tbl_wali_kelas LIMIT 5");
    if ($sample->num_rows > 0) {
        echo "<pre>";
        while ($row = $sample->fetch_assoc()) {
            print_r($row);
        }
        echo "</pre>";
    } else {
        echo "Tidak ada data";
    }
} else {
    echo "Tabel tbl_wali_kelas TIDAK DITEMUKAN";
}

$conn->close();
