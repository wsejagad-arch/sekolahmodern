<?php
require 'koneksi.php';
$res = mysqli_query($conn, "SHOW COLUMNS FROM tbl_siswa");
while($row = mysqli_fetch_assoc($res)){
    echo $row['Field'] . '|' . $row['Type'] . "\n";
}
echo "----\n";
$res = mysqli_query($conn, "SHOW COLUMNS FROM tbl_7kih_jurnal");
if($res){
    while($row = mysqli_fetch_assoc($res)){
        echo 'jurnal_' . $row['Field'] . '|' . $row['Type'] . "\n";
    }
}
?>
