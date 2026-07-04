<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['no_induk'])) { exit('Harus login'); }
if($_SESSION['hak_akses'] != 2) { exit('Akses ditolak'); }
include '../../koneksi.php';

$tanggal = mysqli_real_escape_string($conn, $_POST['tanggal'] ?? '');
$idmapel = (int)($_POST['idmapel'] ?? 0);
$kelas = mysqli_real_escape_string($conn, $_POST['kelas'] ?? '');
if ($tanggal === '' || $idmapel <= 0) { http_response_code(400); echo 'invalid'; exit; }

// Cari semua item penilaian untuk pertemuan ini (opsional filter kelas jika disediakan)
$q = "SELECT id FROM tbl_penilaian_item WHERE tanggal='".$tanggal."' AND id_mapel=".$idmapel;
if ($kelas !== '') { $q .= " AND kelas='".$kelas."'"; }
$items = mysqli_query($conn, $q);
$ids = [];
while ($r = mysqli_fetch_assoc($items)) { $ids[] = (int)$r['id']; }
if (count($ids) > 0) {
  $idStr = implode(',', $ids);
  mysqli_query($conn, "DELETE FROM tbl_nilai_item WHERE id_item IN (".$idStr.")");
}
echo 'ok';

