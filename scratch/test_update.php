<?php
include 'koneksi.php';

$no_wa = '08123456789';
$idguru = '1'; // Assuming ID 1 exists

echo "Updating id_guru 1...\n";
$q = mysqli_query($conn, "UPDATE tbl_guru SET no_wa='$no_wa' WHERE id_guru='$idguru'");
if (!$q) {
    echo "Error updating: " . mysqli_error($conn) . "\n";
} else {
    echo "Updated " . mysqli_affected_rows($conn) . " rows.\n";
}

$q2 = mysqli_query($conn, "SELECT no_wa FROM tbl_guru WHERE id_guru='$idguru'");
$row = mysqli_fetch_assoc($q2);
echo "New no_wa: " . $row['no_wa'] . "\n";
?>
