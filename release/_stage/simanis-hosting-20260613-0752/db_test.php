<?php
// Simple DB connection test
include __DIR__ . '/koneksi.php';

header('Content-Type: text/plain');
if ($conn) {
    echo "OK: Connected to MySQL server '" . $host . "' and database '" . $db . "' as user '" . $user . "'\n";
    // Try a tiny query
    $res = mysqli_query($conn, 'SELECT 1 AS ok');
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        echo "Query test: ".$row['ok']."\n";
    } else {
        echo "Query test failed: ".mysqli_error($conn)."\n";
    }
} else {
    echo "ERROR: Connection resource is null.\n";
}

