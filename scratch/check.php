<?php
include __DIR__ . '/koneksi.php';
$q = mysqli_query($conn, "SELECT id_guru, nama_guru, no_wa FROM tbl_guru LIMIT 20");
if (!$q) { die("Error: " . mysqli_error($conn)); }
print_r(mysqli_fetch_all($q, MYSQLI_ASSOC));
