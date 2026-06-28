<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect('127.0.0.1', 'root', '', 'sijurnal', 3306);
if ($conn) {
    $res = mysqli_query($conn, "SHOW TABLES");
    if ($res) {
        while ($row = mysqli_fetch_array($res)) {
            echo "Table: " . $row[0] . "\n";
        }
    }
}
