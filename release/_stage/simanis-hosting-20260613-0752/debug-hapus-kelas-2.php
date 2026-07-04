<?php
// Test koneksi database dan query yang digunakan di hapus-kelas-2.php
include "koneksi.php";

echo "<h1>🔍 Debug Hapus Kelas 2</h1>";

// Test koneksi database
if ($conn) {
    echo "<p>✅ <strong>Koneksi database:</strong> OK</p>";
} else {
    echo "<p>❌ <strong>Koneksi database:</strong> GAGAL - " . mysqli_connect_error() . "</p>";
    exit;
}

// Test struktur tabel kelas
echo "<h3>📋 Struktur Tabel Kelas:</h3>";
$desc_kelas = mysqli_query($conn, "DESCRIBE tbl_kelas");
if ($desc_kelas) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($desc_kelas)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Error: " . mysqli_error($conn) . "</p>";
}

// Test struktur tabel siswa
echo "<h3>👥 Struktur Tabel Siswa:</h3>";
$desc_siswa = mysqli_query($conn, "DESCRIBE tbl_siswa");
if ($desc_siswa) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($desc_siswa)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Error: " . mysqli_error($conn) . "</p>";
}

// Test query yang digunakan di hapus-kelas-2
echo "<h3>🔍 Test Query Hapus Kelas 2:</h3>";

$query = "SELECT k.id_kelas, k.kelas as nama_kelas, 
                 COUNT(s.kelas) as jumlah_siswa,
                 g.nama_guru as wali_kelas
          FROM tbl_kelas k
          LEFT JOIN tbl_siswa s ON k.kelas = s.kelas
          LEFT JOIN tbl_wali_kelas wk ON k.id_kelas = wk.id_kelas
          LEFT JOIN tbl_guru g ON wk.id_guru = g.id_guru
          GROUP BY k.id_kelas, k.kelas, g.nama_guru
          ORDER BY k.kelas";

echo "<p><strong>Query:</strong></p>";
echo "<pre>" . htmlspecialchars($query) . "</pre>";

$result = mysqli_query($conn, $query);

if ($result) {
    echo "<p>✅ <strong>Query berhasil dijalankan!</strong></p>";
    
    echo "<h4>📊 Hasil Query:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nama Kelas</th><th>Jumlah Siswa</th><th>Wali Kelas</th></tr>";
    
    $data_count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $data_count++;
        echo "<tr>";
        echo "<td>" . $row['id_kelas'] . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_kelas']) . "</td>";
        echo "<td>" . $row['jumlah_siswa'] . "</td>";
        echo "<td>" . htmlspecialchars($row['wali_kelas'] ?? '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Total data:</strong> $data_count kelas</p>";
    
} else {
    echo "<p>❌ <strong>Query error:</strong> " . mysqli_error($conn) . "</p>";
}

// Test query sederhana untuk kelas
echo "<h3>🏫 Test Query Kelas Sederhana:</h3>";
$simple_query = "SELECT * FROM tbl_kelas LIMIT 5";
$simple_result = mysqli_query($conn, $simple_query);

if ($simple_result) {
    echo "<p>✅ Query sederhana berhasil</p>";
    echo "<table border='1' cellpadding='5'>";
    $first_row = true;
    while ($row = mysqli_fetch_assoc($simple_result)) {
        if ($first_row) {
            echo "<tr>";
            foreach (array_keys($row) as $column) {
                echo "<th>$column</th>";
            }
            echo "</tr>";
            $first_row = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Error: " . mysqli_error($conn) . "</p>";
}

// Test query sederhana untuk siswa
echo "<h3>👨‍🎓 Test Query Siswa Sederhana:</h3>";
$siswa_query = "SELECT * FROM tbl_siswa LIMIT 5";
$siswa_result = mysqli_query($conn, $siswa_query);

if ($siswa_result) {
    echo "<p>✅ Query siswa berhasil</p>";
    echo "<table border='1' cellpadding='5'>";
    $first_row = true;
    while ($row = mysqli_fetch_assoc($siswa_result)) {
        if ($first_row) {
            echo "<tr>";
            foreach (array_keys($row) as $column) {
                echo "<th>$column</th>";
            }
            echo "</tr>";
            $first_row = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Error: " . mysqli_error($conn) . "</p>";
}

echo "<hr>";
echo "<p><a href='home.php?page=hapus-kelas-2'>🔙 Kembali ke Hapus Kelas 2</a></p>";
?>