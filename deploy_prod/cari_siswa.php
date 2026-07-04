<?php
include "koneksi.php";
header('Content-Type: application/json; charset=utf-8');

$search = $_GET['q'] ?? '';

$sql = mysqli_query($conn, "
    SELECT no_induk, nama_siswa, kelas 
    FROM tbl_siswa 
    WHERE status='Aktif' 
      AND (nama_siswa LIKE '%$search%' OR no_induk LIKE '%$search%')
    ORDER BY nama_siswa ASC 
    LIMIT 20
");

$results = [];
while($row = mysqli_fetch_assoc($sql)) {
    $results[] = [
        "id"   => $row['no_induk'],
        "text" => $row['nama_siswa'] . " (" . $row['kelas'] . ")"
    ];
}

echo json_encode(["results" => $results]);

