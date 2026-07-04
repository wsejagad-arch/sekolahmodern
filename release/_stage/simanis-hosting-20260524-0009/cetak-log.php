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

?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Cetak Log System</title>
    <link rel="stylesheet" type="text/css" href="css/mycss.css">
  </head>
  <body>
    <div class="laporan">
	<h3>Data Log System</h3>
	<p class="judul">Tanggal <?= tgl_indo($tglawal); ?> s.d <?= tgl_indo($tglakhir); ?></p>
      <table class="laporan-table">
		<tr>
			<th>NO.</th>
			<th>TANGGAL</th>
			<th>LOG SYSTEM</th>
		</tr>
			<?php
				// ambil data dari tabel log 
				$no = 1;
				$log = mysqli_query($conn, "SELECT * FROM tbl_log WHERE DATE(waktu) BETWEEN '$tglawal' AND '$tglakhir' ORDER BY waktu ASC");
				while($data = mysqli_fetch_array($log)) { ?>
					<tr>
						<td><?= $no++; ?></td>
						<td><?= $data['waktu']; ?></td>
						<td><?= $data['isi_log']; ?></td>
					</tr>
			<?php	
				}
			?>
	  </table>
    </div>
  </body>
</html>

<script>window.print();</script>
