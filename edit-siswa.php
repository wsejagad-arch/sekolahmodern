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

// Auto-migrate: tambah kolom jabatan ke tbl_siswa jika belum ada
$_jabatanChk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_siswa LIKE 'jabatan'");
if ($_jabatanChk && mysqli_num_rows($_jabatanChk) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_siswa ADD COLUMN jabatan ENUM('Siswa','Ketua Kelas') DEFAULT 'Siswa' AFTER kelas");
}

// Pemrosesan form
if (isset($_POST['submit'])) {
    //definisikan variabel dulu
      $noinduk = trim(mysqli_real_escape_string($conn, $_POST['noinduk']));
      $nami    = mysqli_real_escape_string($conn, $_POST['nama']);
	  $kelas   = mysqli_real_escape_string($conn, $_POST['kelas']);
	  $status  = mysqli_real_escape_string($conn, $_POST['status']);
	  $jabatan = in_array(isset($_POST['jabatan']) ? $_POST['jabatan'] : '', ['Siswa','Ketua Kelas']) ? $_POST['jabatan'] : 'Siswa';
	  $tglskr  = date('Y-m-d H:i:s');
	  $isilog  = "$nama mengubah data siswa dengan NIS $noinduk";

	  // Cek kolom yang benar-benar ADA di tbl_siswa (aman jika jabatan belum ada di DB hosting)
	  $_siswaCols = [];
	  $_colQs = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_siswa");
	  if ($_colQs) {
	      while ($_colRs = mysqli_fetch_assoc($_colQs)) {
	          $_siswaCols[] = $_colRs['Field'];
	      }
	  }

	  // Build SET clause dinamis
	  $_setClauses = ["kelas='$kelas'", "status='$status'"];
	  if (in_array('jabatan', $_siswaCols)) $_setClauses[] = "jabatan='$jabatan'";
	  $_setStrSiswa = implode(', ', $_setClauses);

	  $update = mysqli_query($conn, "UPDATE tbl_siswa SET {$_setStrSiswa} WHERE no_induk='$no_induk'");
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
	<?php } else {
		$_dbErrSiswa = mysqli_error($conn);
		?>
		<script>Swal.fire('Gagal Menyimpan!', 'Error database: <?= htmlspecialchars($_dbErrSiswa) ?>', 'error')</script>
	<?php }
}
?>

    <div class="container-fluid">
    <div class="container">
    <div class="alert" style="background-color: #ffffff; outline: 1px solid lightgrey">
        <h4>Tambah Data Siswa</h4>
    </div>

<div class="container rounded" style="background-color: #ffffff; outline: 1px solid lightgrey">
<form method="POST" action="?page=edit-siswa&no_induk=<?= htmlspecialchars($no_induk) ?>" class="needs-validation" novalidate>

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

<!-- Jabatan -->
<div class="form-group col-sm-4">
    <label for="jabatan">JABATAN SISWA:</label>
    <select class="form-control" name="jabatan">
        <option value="Siswa" <?= (!isset($dsiswa['jabatan']) || $dsiswa['jabatan'] == 'Siswa') ? 'selected' : '' ?>>Siswa Biasa</option>
        <option value="Ketua Kelas" <?= (isset($dsiswa['jabatan']) && $dsiswa['jabatan'] == 'Ketua Kelas') ? 'selected' : '' ?>>Ketua Kelas</option>
    </select>
    <small class="text-muted">Ketua Kelas dapat konfirmasi kehadiran guru</small>
  </div>
<!-- Jabatan -->

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

