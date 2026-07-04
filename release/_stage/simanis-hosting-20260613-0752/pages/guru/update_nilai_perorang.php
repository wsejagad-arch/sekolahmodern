<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['no_induk'])) { exit('Harus login'); }
if($_SESSION['hak_akses'] != 2) { exit('Akses ditolak'); }
include '../../koneksi.php';

$tanggal = mysqli_real_escape_string($conn, $_POST['tanggal'] ?? '');
$idmapel = (int)($_POST['idmapel'] ?? 0);
$nis = mysqli_real_escape_string($conn, $_POST['no_induk_siswa'] ?? '');
$nilai = isset($_POST['nilai']) && is_array($_POST['nilai']) ? $_POST['nilai'] : [];
if ($tanggal === '' || $idmapel <= 0 || $nis === '') { http_response_code(400); echo 'invalid'; exit; }

// Pastikan target item memang milik pertemuan dimaksud
if (count($nilai) > 0) {
  $ids = array_map('intval', array_keys($nilai));
  $idStr = implode(',', $ids);
  $valid = mysqli_query($conn, "SELECT id FROM tbl_penilaian_item WHERE id IN (".$idStr.") AND tanggal='".$tanggal."' AND id_mapel=".$idmapel);
  $allowed = [];
  while ($r = mysqli_fetch_assoc($valid)) { $allowed[] = (int)$r['id']; }
  foreach ($nilai as $idItem => $val) {
    $idItem = (int)$idItem;
    if (!in_array($idItem, $allowed, true)) continue;
    if ($val === '' || $val === null) {
      // kosongkan nilai -> hapus baris
      mysqli_query($conn, "DELETE FROM tbl_nilai_item WHERE id_item=".$idItem." AND no_induk_siswa='".$nis."'");
    } else if (is_numeric($val)) {
      $num = floatval($val); if ($num < 0) $num = 0; if ($num > 100) $num = 100;
      $stmt = mysqli_prepare($conn, "INSERT INTO tbl_nilai_item (id_item, no_induk_siswa, nilai) VALUES (?,?,?) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai)");
      mysqli_stmt_bind_param($stmt, 'isd', $idItem, $nis, $num);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
    }
  }
}
echo 'ok';

