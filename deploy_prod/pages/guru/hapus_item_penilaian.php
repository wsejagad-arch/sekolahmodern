<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['no_induk'])) { exit('Harus login'); }
if($_SESSION['hak_akses'] != 2) { exit('Akses ditolak'); }
include '../../koneksi.php';

$id = (int)($_POST['id_item'] ?? 0);
if ($id <= 0) { http_response_code(400); echo 'invalid'; exit; }

// Hapus nilai terkait lalu item
mysqli_query($conn, "DELETE FROM tbl_nilai_item WHERE id_item=".$id);
mysqli_query($conn, "DELETE FROM tbl_penilaian_item WHERE id=".$id." LIMIT 1");
echo 'ok';

