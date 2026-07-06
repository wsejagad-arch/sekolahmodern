<?php
$lines = file("c:\xampp\htdocs\jurnal\satpam.php");
foreach ($lines as $i => $line) {
    if (strpos($line, 'id="view-') !== false || strpos($line, 'class="section-title"') !== false) {
        echo ($i+1) . ": " . trim($line) . "\n";
    }
}
?>
