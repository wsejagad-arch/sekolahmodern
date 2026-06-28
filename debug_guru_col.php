<?php
include "c:\xampp\htdocs\jurnal\koneksi.php";
$q = mysqli_query($conn, 'SHOW COLUMNS FROM tbl_guru');
while($r=mysqli_fetch_assoc($q)) echo $r['Field'].' ';
echo "\n";
?>
