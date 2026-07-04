<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');
// Pemrosesan form
if (isset($_POST['submit'])) {
    //definisikan variabel dulu
      $nip = trim(mysqli_real_escape_string($conn, $_POST['nip']));
	  $hashnip = md5($nip);
      $nami = mysqli_real_escape_string($conn, $_POST['nama']);
	  $status_kepegawaian = mysqli_real_escape_string($conn, $_POST['status_kepegawaian']);
	  $status = mysqli_real_escape_string($conn, $_POST['status']);
	  $akses = $_POST['hak_akses'];
	  $tglskr = date('Y-m-d H:i:s');
	  $namafile = $_FILES['file']['name'];
	  $ukuranFile = $_FILES['file']['size'];
	  $error = $_FILES['file']['error'];
	  $tmpName = $_FILES['file']['tmp_name'];
	  $cekfoto = cek_foto($namafile);
	  $isilog = "$nama"." menambahkan data guru dengan NIP/NUPTK "."$nip"." kedalam sistem";
	  
	  $cek = cek_guru($nip);
	  if($cek == True && $error != UPLOAD_ERR_NO_FILE) {
		mysqli_query($conn, "INSERT INTO tbl_guru(no_induk, nama_guru, status_kepegawaian, foto, status) VALUES('$nip','$nami', '$status_kepegawaian', '$cekfoto','$status')");
		move_uploaded_file($tmpName, 'foto/' . $cekfoto);
		mysqli_query($conn, "INSERT INTO tbl_pengguna(no_induk, password, hak_akses) VALUES('$nip', '$hashnip', '$akses')");
		mysqli_query($conn, "INSERT INTO tbl_log(waktu, isi_log) VALUES('$tglskr', '$isilog')");
		?>
		<script>
			  Swal.fire({
			  position: 'top-end',
			  icon: 'success',
			  title: 'Berhasil menambah data guru!',
			  showConfirmButton: false,
			  timer: 1500
			  }).then(function(){
				  window.location.href = "?page=data-guru";
			  })
		</script>
	<?php } else if($cek == True && $error === UPLOAD_ERR_NO_FILE) {
		mysqli_query($conn, "INSERT INTO tbl_guru(no_induk, nama_guru, status_kepegawaian, status) VALUES('$nip','$nami', '$status_kepegawaian','$status')");
		mysqli_query($conn, "INSERT INTO tbl_pengguna(no_induk, password, hak_akses) VALUES('$nip', '$hashnip', '$akses')");
		mysqli_query($conn, "INSERT INTO tbl_log(waktu, isi_log) VALUES('$tglskr', '$isilog')");
		?>
		<script>
			  Swal.fire({
			  position: 'top-end',
			  icon: 'success',
			  title: 'Berhasil menambah data guru!',
			  showConfirmButton: false,
			  timer: 1500
			  }).then(function(){
				  window.location.href = "?page=data-guru";
			  })
		</script>
	<?php } else { ?>
		<script>Swal.fire('Gagal', 'Guru dengan NIP ini sudah ada di dalam daftar!', 'error')</script>
	<?php }
}
?>

    <div class="container-fluid">
    <div class="container">
    <div class="alert" style="background-color: #ffffff; outline: 1px solid lightgrey">
        <h4>Tambah Data Guru</h4>
    </div>

<div class="container rounded" style="background-color: #ffffff; outline: 1px solid lightgrey">
<form method="POST" action="" class="needs-validation" enctype="multipart/form-data" novalidate>

<!-- NIP -->
<div class="form-group col-sm-4 pt-4">
    <label for="nip">NIP/NUPTK:</label>
    <input type="number" class="form-control" id="nip" name="nip" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- NIP -->

<!-- Nama Guru -->
<div class="form-group col-sm-4">
    <label for="nama">Nama Guru:</label>
    <input type="text" class="form-control" id="nama" name="nama" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Nama Guru -->

<!-- Status Kepegawaian -->
<div class="form-group col-sm-4">
    <label for="status_kepegawaian">Status Kepegawaian:</label>
    <select class="form-control" name="status_kepegawaian">
        <option selected disabled>-- pilih --</option>
        <option value="ASN">ASN</option>
        <option value="Non-ASN">Non-ASN</option>
	<!--	<option value="PPPK">PPPK</option>-->
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Status Kepegawaian -->
<!-- Wali Kelas -->
<div class="form-group col-sm-4">
    <label for="wali_kelas">Wali Kelas:</label>
    <select class="form-control" name="wali_kelas">
        <option selected disabled>-- pilih --</option>
 <?php
        $kelasArray = array();
        $sqlKelas = "SELECT DISTINCT kelas FROM tbl_mapel_ampu";
        $resultKelas = mysqli_query($conn, $sqlKelas);
        while ($dataKelas = mysqli_fetch_array($resultKelas)) {
            $kelasArray[] = $dataKelas['kelas']; 
            $selected = (isset($_GET['kelas']) && $_GET['kelas'] == $dataKelas['kelas']) ? 'selected' : '';
            ?>
            <option value="<?= $dataKelas['kelas']; ?>" <?= $selected; ?>>
                <?= $dataKelas['kelas']; ?>
            </option>
        <?php } ?>
<!-- Wali Kelas -->





<!-- Hak Akses -->
<input class="form-control" type="text" name="hak_akses" value="2" hidden>
<!-- Hak Akses -->

<!-- Upload file -->
<div class="form-group col-sm-6">
    <label for="file">Foto Guru:</label>
    <input type="file" class="form-control" id="file" name="file">
    <small>File yang diizinkan berekstensi .jpg maksimal size 500 KB.</small>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Silahkan pilih file!</div>
</div>
<!-- end of upload file -->

<!-- Keaktifan -->
<input type="text" class="form-control" id="status" name="status" value="Aktif" hidden>
<!-- Keaktifan -->

<!-- Tombol Submit dan cancel -->
<div class="form-group col-sm-2 pb-4">
<table style="border: none;">
<tr>
    <td><input type="submit" onclick="return confirm('Apakah data sudah benar? setelah disimpan, NIP/NUPTK tidak bisa dirubah!');" class="btn btn-success" id="submit" name="submit" value="Simpan"></td>
    <td><a class="btn btn-warning" href="?page=data-guru">Cancel</a></td>
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
var uploadField = document.getElementById("file");
uploadField.onchange = function() {
	if(this.files[0].size > 512000) {
		alert("Ukuran file maksimal 512 KB!");
		this.value = "";
	} else if(this.files[0].type != "image/jpeg") {
		alert("File yang diizinkan hanya bertipe JPG!");
		this.value= "";
	};
};
</script>
<!-- End of script handling upload file -->

