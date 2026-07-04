<?php
include 'koneksi.php';

$kelas = $_GET['kelas'];

$sql = "SELECT * FROM tbl_data WHERE kelas = '$kelas' AND no_induk = '5555'";
$result = mysqli_query($conn, $sql);

$data = array();
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>

