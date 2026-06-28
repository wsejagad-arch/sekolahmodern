<?php
include 'c:\xampp\htdocs\jurnal\koneksi.php';
$res = mysqli_query($conn, "SELECT status, COUNT(*) as cnt FROM tbl_siswa GROUP BY status");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['status'] . ": " . $row['cnt'] . "\n";
}
?>
