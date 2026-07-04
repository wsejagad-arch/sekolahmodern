<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['username'])) {
	header("location:index.php?haruslogin");
	exit();
}

if($_SESSION['hak_akses'] != 1) {
	header("location:404.html");
	exit();
}

include "koneksi.php";
include "functions.php";
$tglawal = $_POST['tglAwal'];
$tglakhir = $_POST['tglAkhir'];
$nipguru = $_POST['namaguru'];

$sql = mysqli_query($conn, "SELECT nama_guru FROM tbl_guru WHERE nama-guru='$guru'");
$dguru = mysqli_fetch_array($sql);
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Cetak Jurnal Guru <?= $guru['nama_guru']; ?></title>
    <link rel="stylesheet" type="text/css" href="css/mycss.css">
  </head>
  <body>
    <div class="laporan">
	<h3>Data Jurnal Guru an. <?= $dguru['nama_guru']; ?></h3>
	<p class="judul">Tanggal <?= tgl_indo($tglawal); ?> s.d <?= tgl_indo($tglakhir); ?></p>
      <table class="laporan-table">
		<tr>
		 <tr>
                        <th>NO.</th>
                        <th>JAM KE</th>
						<th>KELAS</th>
                        <th>NAMA GURU</th>
						<th>MATA PELAJARAN</th>
						<th>MATERI</th>
						<th>SISWA ABSEN</th>
						<th>CATATAN</th>
		</tr>
			<?php
				// ambil data dari tabel kehadiran 
				$no = 1;
				$hadir = mysqli_query($conn, "SELECT * FROM tbl_kehadiran WHERE no_induk='$nipguru' AND tanggal BETWEEN '$tglawal' AND '$tglakhir' ORDER BY tanggal ASC");
				$jum = mysqli_num_rows($hadir);
				if($jum < 1) { ?>
					<tr>
						<td colspan="7" style="text-align:center;">Belum ada laporan kehadiran!</td>
					</tr>
				<?php 
				} else {
					while($data = mysqli_fetch_array($hadir)) { ?>
					<tr>
						<td><?= $no++; ?></td>
						<td><?= tgl_indo($data['tanggal']); ?></td>
						<td style="text-align:center;"><?= $data['kelas']; ?></td>
						<td><?= $data['nama_mapel']; ?></td>
						<?php
							if($data['status_kehadiran'] == "1") { ?>
							<td>Hadir</td>
						<?php
							} else { ?>
							<td style="color:red;">Tidak Hadir</td>
						<?php 
							}
						?>
						<td><?= $data['catatan']; ?></td>
						<td><?= $data['nama_ketua_kelas']; ?></td>
					</tr>
			<?php	
				}
			}
			?>
	  </table>
    </div>
  </body>
</html>

<script>window.print();</script>
