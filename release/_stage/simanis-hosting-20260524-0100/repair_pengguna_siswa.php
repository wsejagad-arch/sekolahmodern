<?php
// Admin-only quick repair: create missing tbl_pengguna records for students
session_start();
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 1) {
  http_response_code(403);
  echo "Forbidden";
  exit;
}
require_once __DIR__.'/koneksi.php';
header('Content-Type: text/plain');

// Ensure tbl_pengguna exists
$tbl = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pengguna'");
if (!$tbl || mysqli_num_rows($tbl) === 0) {
  echo "Tabel tbl_pengguna belum ada. Pastikan migrasi database sudah berjalan.\n";
  exit(1);
}

// Create accounts for students who don't have tbl_pengguna
$sql = "INSERT INTO tbl_pengguna (no_induk, password, hak_akses)
        SELECT s.no_induk, MD5(s.no_induk) AS password, 3 AS hak
        FROM tbl_siswa s
        LEFT JOIN tbl_pengguna p ON p.no_induk = s.no_induk AND p.hak_akses = 3
        WHERE p.no_induk IS NULL";

if (mysqli_query($conn, $sql)) {
  $affected = mysqli_affected_rows($conn);
  echo "OK: $affected akun siswa dibuat/ditambahkan.\n";
} else {
  echo "ERROR: ".mysqli_error($conn)."\n";
}
