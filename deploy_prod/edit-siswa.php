<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');
$no_induk = $_GET['no_induk'];
// Pemrosesan form
if (isset($_POST['submit'])) {
    //definisikan variabel dulu
      $noinduk = trim(mysqli_real_escape_string($conn, $_POST['noinduk']));
      $nami = mysqli_real_escape_string($conn, $_POST['nama']);
	  $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
	  $status = mysqli_real_escape_string($conn, $_POST['status']);
	  $tglskr = date('Y-m-d H:i:s');
	  $isilog = "$nama"." mengubah data siswa dengan NIS "."$noinduk";
	  
	  $update = mysqli_query($conn, "UPDATE tbl_siswa SET kelas='$kelas', status='$status' WHERE no_induk='$no_induk'");
	  if($update) {
		mysqli_query($conn, "INSERT INTO tbl_log(waktu, isi_log) VALUES('$tglskr', '$isilog')");
		?>
		<script>
			  Swal.fire({
			  position: 'top-end',
			  icon: 'success',
			  title: 'Berhasil merubah data siswa!',
			  showConfirmButton: false,
			  timer: 1500
			  }).then(function(){
				  window.location.href = "?page=data-siswa";
			  })
		</script>
	<?php } else { ?>
		<script>Swal.fire('Gagal', 'merubah data siswa', 'error')</script>
	<?php }
}
?>

    <div class="container-fluid">
    <div class="container">
    <div class="alert" style="background-color: #ffffff; outline: 1px solid lightgrey">
        <h4>Tambah Data Siswa</h4>
    </div>

<div class="container rounded" style="background-color: #ffffff; outline: 1px solid lightgrey">
<form method="POST" action="" class="needs-validation" novalidate>

<?php 
// Tampilkan data siswa yang akan diedit
$siswa = mysqli_query($conn, "SELECT * FROM tbl_siswa WHERE no_induk='$no_induk'");
$dsiswa = mysqli_fetch_array($siswa); 
?>

<!-- No induk siswa -->
<div class="form-group col-sm-4 pt-4">
    <label for="noinduk">NO INDUK SISWA:</label>
    <input type="number" class="form-control" id="noinduk" name="noinduk" value="<?= $dsiswa['no_induk']; ?>" readonly>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- No induk siswa -->

<!-- Nama Siswa -->
<div class="form-group col-sm-4">
    <label for="namasiswa">NAMA SISWA:</label>
    <input type="text" class="form-control" id="nama" name="nama" value="<?= $dsiswa['nama_siswa']; ?>" readonly>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Nama Siswa -->

<!-- Kelas -->
<div class="form-group col-sm-4">
    <label for="kelas">KELAS:</label>
    <select class="form-control" name="kelas">
        <?php
		//Tampilkan data kelas
		$kelas = mysqli_query($conn, "SELECT * FROM tbl_kelas ORDER BY id_kelas ASC");
		while ($dkelas = mysqli_fetch_array($kelas)) { 
			if($dkelas['kelas'] == $dsiswa['kelas']) { ?>
				<option value="<?= $dkelas['kelas']; ?>" selected><?= $dkelas['kelas']; ?></option>
			<?php } else { ?>
				<option value="<?= $dkelas['kelas']; ?>"><?= $dkelas['kelas']; ?></option>
			<?php } } ?>
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Kelas -->

<!-- Status -->
<div class="form-group col-sm-4">
    <label for="status">STATUS SISWA:</label>
    <select class="form-control" name="status">
        <?php if($dsiswa['status'] == "Aktif") : ?>
		<option value="Aktif" selected>Aktif</option>
		<option value="Non-Aktif">Non-Aktif</option>
		<option value="Lulus">Lulus</option>
		<?php endif; ?>
		
		<?php if($dsiswa['status'] == "Non-Aktif") : ?>
		<option value="Aktif">Aktif</option>
		<option value="Non-Aktif" selected>Non-Aktif</option>
		<option value="Lulus">Lulus</option>
		<?php endif; ?>
		
		<?php if($dsiswa['status'] == "Lulus") : ?>
		<option value="Aktif">Aktif</option>
		<option value="Non-Aktif">Non-Aktif</option>
		<option value="Lulus" selected>Lulus</option>
		<?php endif; ?>
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Status -->

<!-- Tombol Submit dan cancel -->
<div class="form-group col-sm-2 pb-4">
<table style="border: none;">
<tr>
    <td><input type="submit" onclick="return confirm('Apakah data sudah benar?');" class="btn btn-success" id="submit" name="submit" value="Simpan"></td>
    <td><a class="btn btn-warning" href="?page=data-siswa">Cancel</a></td>
</tr>
</table>
  </div>
<!-- end of submit dan cancel -->

</form>
</div>
</div>
</div>

