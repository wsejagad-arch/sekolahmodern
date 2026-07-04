<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['no_induk'])) { exit('Harus login'); }
if($_SESSION['hak_akses'] != 2) { exit('Akses ditolak'); }

include '../../koneksi.php';

date_default_timezone_set('Asia/Jakarta');
$nipguru = $_SESSION['no_induk'];

$idmapel = isset($_POST['idmapel']) ? mysqli_real_escape_string($conn, $_POST['idmapel']) : '';
$kelas = isset($_POST['kelas']) ? mysqli_real_escape_string($conn, $_POST['kelas']) : '';
$mapel = isset($_POST['mapel']) ? mysqli_real_escape_string($conn, $_POST['mapel']) : '';
$tanggal = isset($_POST['tanggal']) ? mysqli_real_escape_string($conn, $_POST['tanggal']) : date('Y-m-d');
$kode = isset($_POST['kode_penilaian']) ? strtoupper(trim($_POST['kode_penilaian'])) : '';
$materi = isset($_POST['materi']) ? trim($_POST['materi']) : '';

// Validasi dasar
if ($idmapel === '' || $kode === '' || $materi === '') {
  echo '<div class="alert alert-danger">Data tidak lengkap.</div>';
  exit;
}

// Pastikan tabel ada
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_penilaian_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  id_mapel INT NOT NULL,
  kelas VARCHAR(50) NOT NULL,
  mapel VARCHAR(100) NOT NULL,
  no_induk_guru VARCHAR(50) NOT NULL,
  kode_penilaian VARCHAR(20) NOT NULL,
  materi VARCHAR(255) NOT NULL,
  UNIQUE KEY uniq_item (tanggal, id_mapel, kode_penilaian)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Simpan item (insert or update materi jika sudah ada)
$stmt = mysqli_prepare($conn, "INSERT INTO tbl_penilaian_item (tanggal,id_mapel,kelas,mapel,no_induk_guru,kode_penilaian,materi) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE materi=VALUES(materi), kelas=VALUES(kelas), mapel=VALUES(mapel), no_induk_guru=VALUES(no_induk_guru)");
mysqli_stmt_bind_param($stmt, 'sisssss', $tanggal, $idmapel, $kelas, $mapel, $nipguru, $kode, $materi);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
  echo '<div class="alert alert-success py-2">Kolom penilaian ditambahkan/diperbarui.</div>';
} else {
  echo '<div class="alert alert-danger py-2">Gagal menambahkan kolom: '.htmlspecialchars(mysqli_error($conn)).'</div>';
}

// Jika bukan request AJAX, redirect ke laman input nilai (supaya guru langsung mengisi)
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax && $ok) {
  $to = 'inputnilai?getDetail=' . urlencode($idmapel);
  header('Location: ' . $to);
  exit;
}

// Reload isi modal secara otomatis
?>
<script>
  (function(){
    if (window.$) {
      $.post('inputnilai', { getDetail: '<?= htmlspecialchars($idmapel); ?>' }, function(html){
        $('.modal-nilai-body').html(html);
      });
    }
  })();
</script>

