<?php
include 'koneksi.php';

echo "=== CEK DATA KELAS ===\n";
$result = mysqli_query($conn, 'SELECT * FROM tbl_kelas LIMIT 5');
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        echo $row['id_kelas'] . ' - ' . $row['kelas'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

echo "\n=== CEK DATA GURU ===\n";
$result = mysqli_query($conn, 'SELECT nip, nama_guru FROM tbl_guru LIMIT 5');
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        echo $row['nip'] . ' - ' . $row['nama_guru'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

echo "\n=== CEK DATA WALI KELAS ===\n";
$result = mysqli_query($conn, 'SELECT * FROM tbl_wali_kelas');
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row['id_wali'] . " - Kelas: " . $row['id_kelas'] . " - NIP: " . $row['nip_wali'] . " - Nama: " . $row['nama_wali'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
?>