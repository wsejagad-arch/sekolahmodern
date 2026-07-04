<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['no_induk'])) {
	header("location:index.php?haruslogin");
	exit();
} else if($_SESSION['hak_akses'] != 3) { ?>
	<script>window.location='404.html';</script>
<?php
}

include "../../koneksi.php";
include "../../functions.php";
date_default_timezone_set('Asia/Jakarta');
$kls = $_SESSION['kelas'];
$namakm = $_SESSION['nama_siswa'];
$tglskr = date("Y-m-d");
$hariini = ubah_nama_hari($tglskr);

if(isset($_POST['getDetail'])) {
	$id = $_POST['getDetail'];
	$sql = mysqli_query($conn, "SELECT * FROM tbl_siswa s JOIN tbl_mapel_ampu m ON s.kelas = m.kelas JOIN tbl_guru g ON m.no_induk = g.no_induk WHERE m.id_mapel='$id'");
	$dat = mysqli_fetch_array($sql); ?>
	
	<div>
		<p class="text-absen mt-3">Kehadiran Guru</p>
		<form method="POST" action="simpankehadiran.php">
		<input type="text" name="tanggal" value="<?= $tglskr; ?>" hidden>
		<input type="text" name="nip" value="<?= $dat['no_induk']; ?>" hidden>
		<input type="text" name="namaguru" value="<?= $dat['nama_guru']; ?>" hidden>
		<input type="text" name="namamapel" value="<?= $dat['nama_mapel']; ?>" hidden>
		<input type="text" name="kelas" value="<?= $kls; ?>" hidden>
		<input type="text" name="namakm" value="<?= $namakm; ?>" hidden>
		<section class="Absen-check d-flex mt-1">
		  <div class="form-check">
			<input class="form-check-input" type="radio" name="sttkehadiran" value="1" id="flexRadioDefault1"/>
			<label class="form-check-label" for="flexRadioDefault1">Hadir</label>
		  </div>
		  <div class="form-check mx-2">
			<input class="form-check-input" type="radio" name="sttkehadiran" value="0" id="flexRadioDefault2"/>
			<label class="form-check-label" for="flexRadioDefault2">Tidak Hadir</label>
		  </div>
		</section>
		<div class="mb-3 my-3 text-keterangan">
		  <label for="exampleFormControlTextarea1" class="form-label mb-3">Keterangan</label>
		  <textarea class="form-control inputan" id="exampleFormControlTextarea1" name="keterangan" rows="3" placeholder="Masukan Keterangan"></textarea>
		</div>
		
		<button type="submit" name="submit" class="btn btn-submit-laporan w-100"><a class="text-decoration-none">Kirim laporan</a></button>
		</form>
	</div>
	
	
<?php
}
?>
