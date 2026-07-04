<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || (int)($_SESSION['hak_akses'] ?? 0) !== 1) {
    echo json_encode(['success'=>false,'msg'=>'Unauthorized']); exit;
}
require_once __DIR__ . '/koneksi.php';

// auto-create tbl_pengaturan
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pengaturan (
  kunci VARCHAR(60) PRIMARY KEY,
  nilai VARCHAR(255) DEFAULT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
mysqli_query($conn, "INSERT IGNORE INTO tbl_pengaturan (kunci,nilai) VALUES ('izin_edit_profil','0')");

// Baca nilai saat ini lalu toggle
$qNow = mysqli_query($conn, "SELECT nilai FROM tbl_pengaturan WHERE kunci='izin_edit_profil' LIMIT 1");
$now  = ($qNow && ($r = mysqli_fetch_assoc($qNow))) ? (int)$r['nilai'] : 0;
$new  = $now ? 0 : 1;

mysqli_query($conn, "UPDATE tbl_pengaturan SET nilai='$new' WHERE kunci='izin_edit_profil'");

echo json_encode(['success'=>true, 'izin'=>$new]);
