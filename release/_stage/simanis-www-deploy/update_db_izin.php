<?php
require_once 'koneksi.php';

$queries = [
    "ALTER TABLE tbl_izin ADD COLUMN kategori_pengajuan VARCHAR(50) DEFAULT 'Tidak Masuk'",
    "ALTER TABLE tbl_izin ADD COLUMN opsi_kembali VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE tbl_izin ADD COLUMN acc_wali VARCHAR(20) DEFAULT 'Pending'",
    "ALTER TABLE tbl_izin ADD COLUMN acc_satpam VARCHAR(20) DEFAULT 'Pending'",
    "ALTER TABLE tbl_izin ADD COLUMN waktu_keluar DATETIME DEFAULT NULL",
    "ALTER TABLE tbl_izin ADD COLUMN waktu_kembali DATETIME DEFAULT NULL"
];

foreach ($queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Success: $sql\n";
    } else {
        echo "Error or already exists: " . mysqli_error($conn) . " - $sql\n";
    }
}
?>
