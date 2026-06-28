<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include "koneksi.php";
?>

    <div class="container-fluid">
    <div class="container">
    <div class="alert alert-primary">
        <h4>Cetak Laporan Junal kelas</h4>
    </div>

<div class="container rounded" style="background-color: #d6eaf8; padding-top:30px;">
<form method="POST" action="cetak-jurnal.php" onsubmit="return cetak-jurnal.php()" target="cetak-jurnal.php">

<!-- Tgl Awal -->
<div class="form-group col-sm-4">
    <label for="tglawal">Dari Tanggal:</label>
    <input type="date" class="form-control" id="tglAwal" name="tglAwal">
  </div>
<!-- End of Tgl Awal -->

<!-- Tgl Akhir -->
<div class="form-group col-sm-4">
    <label for="tglakhir">Sampai Tanggal:</label>
    <input type="date" class="form-control" id="tglAkhir" name="tglAkhir">
  </div>
<!-- End of Tgl Akhir -->

<!-- Nama Guru -->
<div class="form-group col-sm-4">
    <label for="kelas">Kelas:</label>
    <select class="form-control" id="kelas" name="kelas" required>
        <option value="" selected disabled>-- pilih --</option>
	
	//	<?php
		
			$sql = mysqli_query($conn, "SELECT no_induk, kelas FROM tbl_materi");
			while($data = mysqli_fetch_array($sql)) { ?>
			<option value="<?= $data['no_induk']; ?>"><?= $data['kelas']; ?></option>
		<?php
			}
		?> 
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Nama Guru -->

<!-- Tombol Submit dan cancel -->
<div class="form-group col-sm-4 pb-4">
<table style="border: none;">
<tr>
    <td><button type="submit" class="btn btn-success" id="submit" name="submit"><i class="fas fa-print"></i> Cetak jurnal</button></td>
    <td><a class="btn btn-warning" href="?page=jurnal">Cancel</a></td>
</tr>
</table>
  </div>
<!-- end of submit dan cancel -->


</form>
</div>
</div>
</div>

