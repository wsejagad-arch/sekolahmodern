<?php
include "koneksi.php";
header('Content-Type: application/json; charset=utf-8');

$search = $_GET['q'] ?? '';

$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$sql = mysqli_query($conn, "
    SELECT no_induk, nama_siswa, kelas
    FROM tbl_siswa
    WHERE status='Aktif'
      AND id_sekolah = $idSekolah
      AND (nama_siswa LIKE '%$search%' OR no_induk LIKE '%$search%')
    ORDER BY nama_siswa ASC
    LIMIT 20
");

$results = [];
if ($sql) {
    while($row = mysqli_fetch_assoc($sql)) {
        $results[] = [
            "id"   => $row['no_induk'],
            "text" => $row['nama_siswa'] . " (" . $row['kelas'] . ")"
        ];
    }
} else {
    // Jika query gagal, return error
    echo json_encode(["error" => "Database query failed: " . mysqli_error($conn)]);
    exit;
}

echo json_encode(["results" => $results]);

