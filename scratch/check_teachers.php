<?php
require_once "koneksi.php";

$qGuru = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru LIMIT 50");
$gurus = [];
while ($r = mysqli_fetch_assoc($qGuru)) {
    $gurus[] = $r;
}

foreach($gurus as $g) {
    echo $g['no_induk'] . " - " . $g['nama_guru'] . "\n";
}
