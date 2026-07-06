<?php
$lines = file("c:\xampp\htdocs\jurnal\pages\admin\monitoring-izin.php");
// Print out lines matching "validasi_wali_kelas"
foreach ($lines as $i => $line) {
    if (stripos($line, "validasi_wali_kelas") !== false) {
        echo ($i+1) . ": " . trim($line) . "\n";
    }
}
?>
