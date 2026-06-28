<?php
require 'koneksi.php';
$res = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_array($res)) {
    $tables[] = $row[0];
}

foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $q = mysqli_query($conn, "DESCRIBE `$t`");
    while ($r = mysqli_fetch_assoc($q)) {
        echo "  - " . $r['Field'] . " (" . $r['Type'] . ")\n";
    }
}
?>
