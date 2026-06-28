<?php
require_once 'bootstrap.php';
$res = mysqli_query($conn, "DESCRIBE tbl_mapel_ampu");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . "\n";
}
?>
