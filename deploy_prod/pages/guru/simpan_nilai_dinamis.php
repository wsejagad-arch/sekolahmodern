<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['no_induk'])) { exit('Harus login'); }
if($_SESSION['hak_akses'] != 2) { exit('Akses ditolak'); }

include '../../koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$idmapel = isset($_POST['idmapel']) ? (int)$_POST['idmapel'] : 0;
$tanggal = isset($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d');
$nilai = isset($_POST['nilai']) && is_array($_POST['nilai']) ? $_POST['nilai'] : [];

// Pastikan tabel ada
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_nilai_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_item INT NOT NULL,
  no_induk_siswa VARCHAR(50) NOT NULL,
  nilai FLOAT DEFAULT 0,
  UNIQUE KEY uniq_nilai_item (id_item, no_induk_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Simpan nilai per item per siswa
foreach ($nilai as $idItem => $bySiswa) {
  $idItem = (int)$idItem;
  foreach ($bySiswa as $nis => $val) {
    $nis = mysqli_real_escape_string($conn, $nis);
    $num = is_numeric($val) ? floatval($val) : null;
    if ($num === null || $num < 0) continue;
    if ($num > 100) $num = 100;
    $stmt = mysqli_prepare($conn, "INSERT INTO tbl_nilai_item (id_item, no_induk_siswa, nilai) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai)");
    mysqli_stmt_bind_param($stmt, 'isd', $idItem, $nis, $num);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
  }
}

echo '<div class="alert alert-success py-2">Nilai berhasil disimpan.</div>';
?>
<script>
  (function(){
    try {
      const modalEl = document.getElementById('modalNilai');
      if (modalEl) {
        if (window.bootstrap && typeof bootstrap.Modal === 'function') {
          const instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
          instance.hide();
        } else if (window.$ && typeof $(modalEl).modal === 'function') {
          $(modalEl).modal('hide');
        }
      }
    } catch (e) { console.warn('Tidak bisa menutup modal dari simpan_nilai_dinamis:', e); }
  })();
</script>

