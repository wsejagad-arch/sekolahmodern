<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }


include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');
$id = 1;
$tglskr = date('Y-m-d H:i:s');

// Migration Check: Ensure gemini_api_key exists
$checkCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_setting LIKE 'gemini_api_key'");
if ($checkCol && mysqli_num_rows($checkCol) == 0) {
    mysqli_query($conn, "ALTER TABLE tbl_setting ADD COLUMN gemini_api_key VARCHAR(255) DEFAULT ''");
    mysqli_query($conn, "UPDATE tbl_setting SET gemini_api_key='AIzaSyC9zh6FHEnbqrW1MSlO4fVnSdu2L8SjSE8' WHERE id=1");
}

// Pemrosesan form
if (isset($_POST['submit'])) {
    //definisikan variabel dulu
      $nmlembaga = mysqli_real_escape_string($conn, $_POST['namaLembaga']);
      $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
	  $pimpinan = mysqli_real_escape_string($conn, $_POST['namapimpinan']);
	  $nip = mysqli_real_escape_string($conn, $_POST['nippimpinan']);
	  $gemini_api_key = mysqli_real_escape_string($conn, $_POST['gemini_api_key']);
	  $maintenance = isset($_POST['maintenance_mode']) ? 1 : 0;
	  $fotolama = $_POST['foto'];
	  $namafile = $_FILES['file']['name'];
	  $ukuranFile = $_FILES['file']['size'];
	  $error = $_FILES['file']['error'];
	  $tmpName = $_FILES['file']['tmp_name'];
	  $isilog = "$nama"." mengubah data lembaga";
      
	  if($error != UPLOAD_ERR_NO_FILE) {
		$cekfoto = cek_foto($namafile);
		mysqli_query($conn, "UPDATE tbl_setting SET nama_sekolah='$nmlembaga', alamat='$alamat', nama_pimpinan='$pimpinan', nip_pimpinan='$nip', logo='$cekfoto', maintenance_mode='$maintenance', gemini_api_key='$gemini_api_key' WHERE id='$id'");
		move_uploaded_file($tmpName, 'img/' . $cekfoto);
		unlink('img/' . $fotolama);
		mysqli_query($conn, "INSERT INTO tbl_log(waktu, isi_log) VALUES('$tglskr', '$isilog')");
		?>
		<script>
			  Swal.fire({
			  position: 'top-end',
			  icon: 'success',
			  title: 'Berhasil merubah data lembaga!',
			  showConfirmButton: false,
			  timer: 1500
			  }).then(function(){
				  window.location.href = "?page=setting";
			  })
		</script>
	<?php } else if($error === UPLOAD_ERR_NO_FILE) {
		mysqli_query($conn, "UPDATE tbl_setting SET nama_sekolah='$nmlembaga', alamat='$alamat', nama_pimpinan='$pimpinan', nip_pimpinan='$nip', maintenance_mode='$maintenance', gemini_api_key='$gemini_api_key' WHERE id='$id'");
		mysqli_query($conn, "INSERT INTO tbl_log(waktu, isi_log) VALUES('$tglskr', '$isilog')"); ?>
		<script>
			  Swal.fire({
			  position: 'top-end',
			  icon: 'success',
			  title: 'Berhasil merubah data lembaga!',
			  showConfirmButton: false,
			  timer: 1500
			  }).then(function(){
				  window.location.href = "?page=setting";
			  })
		</script>
	<?php } else { ?>
		<script>Swal.fire('Gagal', 'merubah data lembaga!', 'error')</script>
	<?php }
}
?>

    <div class="container-fluid">
    <div class="container">
    <div class="alert" style="background-color: #ffffff; outline: 1px solid lightgrey">
        <h4>DATA LEMBAGA</h4>
    </div>

<?php
// Ambil dulu data lembaga
$res_lembaga = mysqli_query($conn, "SELECT * FROM tbl_setting WHERE id='1'");
$data = mysqli_fetch_array($res_lembaga);
?>

<div class="container rounded" style="background-color: #ffffff; outline: 1px solid lightgrey">
<form method="POST" action="" class="needs-validation" enctype="multipart/form-data" novalidate>

<!-- Nama Lembaga -->
<div class="form-group col-sm-4 pt-4">
    <label for="namaLembaga">NAMA LEMBAGA:</label>
    <input type="text" class="form-control" id="namaLembaga" name="namaLembaga" value="<?= $data['nama_sekolah']; ?>" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Nama Lembaga -->

<!-- Alamat -->
<div class="form-group col-sm-6">
    <label for="alamat">ALAMAT:</label>
    <input type="text" class="form-control" id="alamat" name="alamat" value="<?= $data['alamat']; ?>" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Alamat -->

<!-- Nama Pimpinan -->
<div class="form-group col-sm-4">
    <label for="namaPimpinan">NAMA PIMPINAN:</label>
    <input type="text" class="form-control" id="namaPimpinan" name="namapimpinan" value="<?= $data['nama_pimpinan']; ?>" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Nama Pimpinan -->

<!-- NIP Pimpinan -->
<div class="form-group col-sm-4">
    <label for="nipPimpinan">NIP PIMPINAN:</label>
    <input type="text" class="form-control" id="nipPimpinan" name="nippimpinan" value="<?= $data['nip_pimpinan']; ?>" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- NIP Pimpinan -->

<!-- Upload file -->
<div class="form-group col-sm-6">
    <label for="file">Logo:</label>
    <input type="file" class="form-control" id="fileLogo" name="file">
    <small>File yang diizinkan berekstensi .jpg maksimal size 500 KB.</small>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Silahkan pilih file!</div>
</div>
<!-- end of upload file -->

<!-- Gemini API Key -->
<div class="form-group col-sm-6">
    <label for="geminiApiKey">GEMINI API KEY:</label>
    <input type="text" class="form-control" id="geminiApiKey" name="gemini_api_key" value="<?= isset($data['gemini_api_key']) ? htmlspecialchars($data['gemini_api_key']) : ''; ?>">
    <small class="form-text text-muted">Kunci API Google Gemini untuk fitur Analisis AI Walikelas.</small>
</div>
<!-- Gemini API Key -->

<!-- Foto Lama -->
<input type="text" class="form-control" id="fotoLogo" name="foto" value="<?= $data['logo']; ?>" hidden>
<!-- Foto Lama -->

<!-- Maintenance Mode -->
<div class="form-group col-sm-6">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="maintenanceMode" name="maintenance_mode" value="1" <?= $data['maintenance_mode'] ? 'checked' : ''; ?>>
        <label class="form-check-label" for="maintenanceMode">
            Aktifkan Mode Maintenance
        </label>
    </div>
</div>
<!-- Maintenance Mode -->

<!-- Tombol Submit dan cancel -->
<div class="form-group col-sm-2 pb-4">
<table style="border: none;">
<tr>
    <td><input type="submit" onclick="return confirm('Apakah yakin mau merubah data ini?');" class="btn btn-success" id="submit" name="submit" value="Simpan"></td>
    <td><a class="btn btn-warning" href="?page=setting">Cancel</a></td>
</tr>
</table>
  </div>
<!-- end of submit dan cancel -->

</form>
</div>
</div>
</div>

<!-- Script handling upload file -->
<script type="text/javascript">
// ini untuk batasi ukuran
var uploadField = document.getElementById("fileLogo");
uploadField.onchange = function() {
	if(this.files[0].size > 512000) {
		alert("Ukuran file maksimal 512 KB!");
		this.value = "";
	} else if(this.files[0].type != "image/jpeg" && this.files[0].type != "image/png") {
		alert("File yang diizinkan hanya bertipe JPG!");
		this.value= "";
	};
};
</script>
<!-- End of script handling upload file -->

