<?php
$conn = mysqli_connect('localhost', 'root', '', 'sijurnal');
$names = ['ARIF MUSTOFA', 'AMAT SHOLEH', 'ARDIKA WIDIAYANTO', 'MUH NUR HADI', 'KHAKIM SYAHBATA', 'ALFIN NISFULAILY', 'MUHAMMAD NUR HADI', 'ALFIN', 'NURFIA'];
foreach($names as $name) {
    $res = mysqli_query($conn, "SELECT id_guru, no_induk, nama FROM tbl_guru WHERE nama LIKE '%$name%'");
    while($row = mysqli_fetch_assoc($res)) {
        echo $row['nama'] . ' - ' . $row['no_induk'] . "\n";
    }
}
