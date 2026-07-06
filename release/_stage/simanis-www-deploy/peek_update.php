<?php
$lines = file("c:\xampp\htdocs\jurnal\update_izin.php");
// Print out the top 50 lines to understand how it processes updates
foreach (array_slice($lines, 0, 50) as $i => $line) {
    echo ($i+1) . ": " . rtrim($line) . "\n";
}
?>
