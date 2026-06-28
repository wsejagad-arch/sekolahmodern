<?php
include 'koneksi.php';

echo "All tables in database:\n";
$result = mysqli_query($conn, 'SHOW TABLES');
while($row = mysqli_fetch_array($result)) {
    echo $row[0] . "\n";
}
?>