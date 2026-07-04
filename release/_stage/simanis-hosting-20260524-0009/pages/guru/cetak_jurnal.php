<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["no_induk"])) {
    header("location: ../../index.php?haruslogin");
    exit;
}

include "../../koneksi.php";
include "../../functions.php";
date_default_timezone_set('Asia/Jakarta');

$nipguru = $_SESSION['no_induk'];
$namaguru = $_SESSION['nama_guru'];
$lembaga = data_lembaga();
$tahunAjaran = "2025/2026"; // ubah jika perlu

// Ambil data kelas yang diajar guru
$kelasQuery = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk = '$nipguru'");

// Filter
$tgl1 = $_GET['tgl1'] ?? '';
$tgl2 = $_GET['tgl2'] ?? '';
$kelasFilter = $_GET['kelas'] ?? '';
$where = "a.no_induk = '$nipguru'";
if (!empty($tgl1) && !empty($tgl2)) {
  $where .= " AND m.tanggal BETWEEN '$tgl1' AND '$tgl2'";
}
if (!empty($kelasFilter)) {
    $where .= " AND a.kelas = '$kelasFilter'";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Cetak Jurnal</title>
  <link rel="stylesheet" href="../../css/bootstrap.min.css">
  <style>
    @media print {
      .btn-cetak, .form-filter { display: none; }
    }
    body {
      font-family: Arial, sans-serif;
      font-size: 13px;
    }
    h4, h5, h2 {
      text-align: center;
      margin: 5px 0;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      border: 1px solid black;
      padding: 6px;
    }
    .kop td {
      border: none;
    }
    .ttd td {
      border: none;
      text-align: center;
      padding-top: 40px;
    }
  </style>
</head>
<body>
<div class="container mt-4">

  <!-- Filter Form -->
  <form method="get" class="form-filter row g-3 mb-4">
    <div class="col-md-3">
      <label for="tgl1" class="form-label">Dari Tanggal</label>
      <input type="date" id="tgl1" name="tgl1" value="<?= $tgl1; ?>" class="form-control">
    </div>
    <div class="col-md-3">
      <label for="tgl2" class="form-label">Sampai Tanggal</label>
      <input type="date" id="tgl2" name="tgl2" value="<?= $tgl2; ?>" class="form-control">
    </div>
    <div class="col-md-3">
      <label for="kelas" class="form-label">Pilih Kelas</label>
      <select name="kelas" id="kelas" class="form-select">
        <option value="">Semua Kelas</option>
        <?php while ($k = mysqli_fetch_array($kelasQuery)) { ?>
          <option value="<?= $k['kelas']; ?>" <?= $kelasFilter == $k['kelas'] ? 'selected' : ''; ?>>
            <?= $k['kelas']; ?>
          </option>
        <?php } ?>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
    </div>
  </form>

  <!-- Tombol Cetak -->
  <button class="btn btn-success btn-cetak mb-3" onclick="window.print()">🖨️ Save / Cetak PDF</button>

  <!-- Kop Surat -->
  <table class="kop" style="width:100%; border-bottom: 3px solid black; margin-bottom: 20px;">
    <tr>
      <td style="width: 80px;">
        <img src="../../img/<?= $lembaga['logo']; ?>" style="width: 70px; height: 70px;">
      </td>
      <td style="text-align: center;">
        <div style="font-size: 18px; font-weight: bold;">PEMERINTAH PROVINSI JAWA TENGAH</div>
        <div style="font-size: 20px; font-weight: bold;">DINAS PENDIDIKAN DAN KEBUDAYAAN</div>
        <div style="font-size: 22px; font-weight: bold;">SMA NEGERI 1 SUMBER</div>
        <div style="font-size: 14px;">L. Raya Sumber - Rembang Km. 2, Sekarsari, Kec. Sumber, Kab. Rembang, Jawa Tengah, 59253</div>
        <div style="font-size: 14px;">Telp. (0231) 123456 • Email: sma1sumber@sch.id • Website: www.sma1sumber.sch.id</div>
      </td>
    </tr>
  </table>

  <!-- Judul -->
  <h4 style="text-decoration: underline; font-weight: bold;">JURNAL MENGAJAR GURU SMA NEGERI 1 SUMBER</h4>

  <!-- Identitas -->
  <table style="margin-top: 20px; margin-bottom: 20px; font-size: 14px; border-collapse: collapse;">
    <tr>
      <td style="width: 170px; border: none;">Nama Guru</td>
      <td style="width: 5px; border: none;">:</td>
      <td style="border: none; padding-left: 4px;"><?= $namaguru; ?></td>
    </tr>
    <tr>
      <td style="border: none;">NIP</td>
      <td style="border: none;">:</td>
      <td style="border: none; padding-left: 4px;"><?= $nipguru; ?></td>
    </tr>
    <tr>
      <td style="border: none;">Tahun Pelajaran</td>
      <td style="border: none;">:</td>
      <td style="border: none; padding-left: 4px;"><?= $tahunAjaran; ?></td>
    </tr>
  </table>

  <!-- Tabel Jurnal -->
  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>Tanggal</th>
        <th>Jam</th>
        <th>Kelas</th>
        <th>Mata Pelajaran</th>
        <th>Materi</th>
        <th>Siswa Absen</th>
        <th>Catatan</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $sql = mysqli_query($conn, "SELECT m.*, a.kelas, a.nama_mapel, a.jam_mulai, a.jam_selesai 
      FROM tbl_materi m 
      JOIN tbl_mapel_ampu a ON m.id_mapel = a.id_mapel 
      WHERE $where
      ORDER BY m.tanggal ASC"); // diubah dari DESC ke ASC agar urut dari tanggal muda ke tua

    if (mysqli_num_rows($sql) > 0) {
      $no = 1;
      while ($data = mysqli_fetch_array($sql)) {
        echo "<tr>
          <td>$no</td>
          <td>" . tgl_indo($data['tanggal']) . "</td>
          <td>{$data['jam_mulai']} - {$data['jam_selesai']}</td>
          <td>{$data['kelas']}</td>
          <td>{$data['nama_mapel']}</td>
          <td>{$data['materi']}</td>
          <td>{$data['absen']}</td>
          <td>{$data['keterangan']}</td>
        </tr>";
        $no++;
      }
    } else {
      echo '<tr><td colspan="8" class="text-center text-danger">Data tidak ditemukan.</td></tr>';
    }
    ?>
    </tbody>
  </table>

  <!-- Tanda Tangan -->
  <table class="ttd mt-5">
    <tr>
      <td width="50%">
        Mengetahui,<br>
        Kepala Sekolah<br><br><br><br>
        <strong><u>(........................................)</u></strong><br>
        NIP. ....................................
      </td>
      <td width="50%">
        Sumber, <?= tgl_indo(date("Y-m-d")); ?><br>
        Guru Mata Pelajaran<br><br><br><br>
        <strong><u>(<?= $namaguru; ?>)</u></strong><br>
        NIP. <?= $nipguru; ?>
      </td>
    </tr>
  </table>

</div>
</body>
</html>
