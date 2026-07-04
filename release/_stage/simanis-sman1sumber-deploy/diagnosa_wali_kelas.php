<?php
include 'koneksi.php';

echo "<h2>=== DIAGNOSA DATA WALI KELAS ===</h2>";

// Cek struktur tabel
echo "<h3>1. Struktur Tabel tbl_wali_kelas:</h3>";
$result = mysqli_query($conn, "DESCRIBE tbl_wali_kelas");
if ($result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
}

// Cek data wali kelas
echo "<h3>2. Data Wali Kelas Saat Ini:</h3>";
$result = mysqli_query($conn, "SELECT * FROM tbl_wali_kelas");
if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID Wali</th><th>ID Kelas</th><th>NIP Wali</th><th>Nama Wali</th><th>Created At</th><th>Updated At</th></tr>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['id_wali']}</td>";
        echo "<td>{$row['id_kelas']}</td>";
        echo "<td>{$row['nip_wali']}</td>";
        echo "<td>{$row['nama_wali']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "<td>{$row['updated_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange'>Tidak ada data wali kelas</p>";
}

// Cek data kelas
echo "<h3>3. Data Kelas:</h3>";
$result = mysqli_query($conn, "SELECT * FROM tbl_kelas ORDER BY kelas");
if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID Kelas</th><th>Kelas</th></tr>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['id_kelas']}</td>";
        echo "<td>{$row['kelas']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Tidak ada data kelas!</p>";
}

// Cek data guru
echo "<h3>4. Data Guru:</h3>";
$result = mysqli_query($conn, "SELECT nip, nama_guru FROM tbl_guru ORDER BY nama_guru LIMIT 10");
if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>NIP</th><th>Nama Guru</th></tr>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['nip']}</td>";
        echo "<td>{$row['nama_guru']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Tidak ada data guru!</p>";
}

// Cek query utama
echo "<h3>5. Test Query Utama:</h3>";
$query = "SELECT k.id_kelas, k.kelas,
         wk.id_wali, wk.nip_wali, wk.nama_wali, wk.created_at
         FROM tbl_kelas k
         LEFT JOIN tbl_wali_kelas wk ON k.id_kelas = wk.id_kelas
         ORDER BY k.kelas ASC";

$result = mysqli_query($conn, $query);
if ($result) {
    echo "<p style='color:green'>Query berhasil! Total rows: " . mysqli_num_rows($result) . "</p>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Kelas</th><th>Wali Kelas</th><th>NIP</th><th>Status</th></tr>";
    while($row = mysqli_fetch_assoc($result)) {
        $status = !empty($row['id_wali']) ? 'Ada Wali' : 'Belum Ada Wali';
        echo "<tr>";
        echo "<td>{$row['kelas']}</td>";
        echo "<td>" . (!empty($row['nama_wali']) ? $row['nama_wali'] : '<em>Belum ditentukan</em>') . "</td>";
        echo "<td>" . (!empty($row['nip_wali']) ? $row['nip_wali'] : '-') . "</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Query gagal: " . mysqli_error($conn) . "</p>";
}

echo "<h3>6. Cek Constraint dan Foreign Key:</h3>";
$result = mysqli_query($conn, "SHOW CREATE TABLE tbl_wali_kelas");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "<pre>" . $row['Create Table'] . "</pre>";
} else {
    echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
}
?>