<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli('127.0.0.1', 'root', '', 'sijurnal', 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "UPDATE tbl_siswa SET kelas = NULL WHERE (status = 'Alumni' OR status = 'Lulus') AND kelas IS NOT NULL";
if (mysqli_query($conn, $query)) {
    echo "Success: " . mysqli_affected_rows($conn) . " rows updated.\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
?>
