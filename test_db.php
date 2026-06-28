<?php 
require 'bootstrap.php'; 
$res = mysqli_query($conn, 'SELECT * FROM tbl_ekinerja_dokumen'); 
while ($r = mysqli_fetch_assoc($res)) { 
    print_r($r); 
} 
?>
