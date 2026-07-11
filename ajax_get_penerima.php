<?php
session_start();
if (!isset($_SESSION["username"]) || ($_SESSION['hak_akses'] != 1 && $_SESSION['hak_akses'] != 5)) {
    http_response_code(403);
    exit;
}
include "koneksi.php";
header('Content-Type: application/json');

$tipe = $_GET['tipe'] ?? '';
$data = [];

if ($tipe === 'Guru') {
    $q = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru ORDER BY nama_guru ASC");
    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = ['id' => $r['no_induk'], 'nama' => $r['nama_guru']];
    }
} else if ($tipe === 'Siswa') {
    $q = mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas FROM tbl_siswa WHERE status='Aktif' ORDER BY kelas ASC, nama_siswa ASC");
    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = ['id' => $r['no_induk'], 'nama' => $r['nama_siswa'] . ' (' . $r['kelas'] . ')'];
    }
}

echo json_encode($data);
?>
