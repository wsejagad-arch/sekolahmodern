<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['no_induk'])) { header('Location: ../../index.php?haruslogin'); exit; }
if($_SESSION['hak_akses'] != 2) { echo 'Akses ditolak'; exit; }

include '../../koneksi.php';

date_default_timezone_set('Asia/Jakarta');
$nipguru   = $_SESSION['no_induk'];
$idmapel   = mysqli_real_escape_string($conn, $_POST['idmapel'] ?? '');
$kelas     = mysqli_real_escape_string($conn, $_POST['kelas'] ?? '');
$mapel     = mysqli_real_escape_string($conn, $_POST['mapel'] ?? '');
$tanggal   = date('Y-m-d');

$no_induk_list = $_POST['no_induk'] ?? [];
$tugas = $_POST['tugas'] ?? [];
$uh    = $_POST['uh'] ?? [];
$us    = $_POST['us'] ?? [];

// Buat tabel jika belum ada
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_nilai (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  id_mapel INT NOT NULL,
  kelas VARCHAR(50) NOT NULL,
  mapel VARCHAR(100) NOT NULL,
  no_induk_guru VARCHAR(50) NOT NULL,
  no_induk_siswa VARCHAR(50) NOT NULL,
  nilai_tugas FLOAT DEFAULT 0,
  nilai_uh FLOAT DEFAULT 0,
  nilai_us FLOAT DEFAULT 0,
  CONSTRAINT uniq_nilai UNIQUE (tanggal, id_mapel, no_induk_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$ok = true;
foreach ($no_induk_list as $nis) {
  $nisSafe = mysqli_real_escape_string($conn, $nis);
  $vtugas = floatval($tugas[$nis] ?? 0);
  $vuh    = floatval($uh[$nis] ?? 0);
  $vus    = floatval($us[$nis] ?? 0);

  // Upsert by tanggal+id_mapel+siswa
  $sql = "INSERT INTO tbl_nilai (tanggal, id_mapel, kelas, mapel, no_induk_guru, no_induk_siswa, nilai_tugas, nilai_uh, nilai_us)
          VALUES ('$tanggal', '$idmapel', '$kelas', '$mapel', '$nipguru', '$nisSafe', '$vtugas', '$vuh', '$vus')
          ON DUPLICATE KEY UPDATE nilai_tugas=VALUES(nilai_tugas), nilai_uh=VALUES(nilai_uh), nilai_us=VALUES(nilai_us), kelas=VALUES(kelas), mapel=VALUES(mapel)";
  if (!mysqli_query($conn, $sql)) {
    $ok = false;
  }
}

if ($ok) {
  header('Location: guru.php?nilai_sukses=1');
} else {
  header('Location: guru.php?nilai_gagal=1');
}

