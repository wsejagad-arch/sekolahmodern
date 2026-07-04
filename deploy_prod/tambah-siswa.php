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
      $noinduk = trim(mysqli_real_escape_string($conn, $_POST['noinduk']));
	  $hashnoinduk = md5($noinduk);
      $nami = mysqli_real_escape_string($conn, $_POST['nama']);
	  $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
	  $status = mysqli_real_escape_string($conn, $_POST['status']);
	  $akses = $_POST['hak_akses'];
	  $tglskr = date('Y-m-d H:i:s');
	  $isilog = "$nama"." menambahkan data siswa dengan NIS "."$noinduk"." kedalam sistem";
	  
	  $cek = cek_siswa($noinduk);
	  if($cek == True) {
		mysqli_query($conn, "INSERT INTO tbl_siswa(no_induk, nama_siswa, kelas, status) VALUES('$noinduk','$nami', '$kelas', '$status')");
		mysqli_query($conn, "INSERT INTO tbl_pengguna(no_induk, password, hak_akses) VALUES('$noinduk', '$hashnoinduk', '$akses')");
		mysqli_query($conn, "INSERT INTO tbl_log(waktu, isi_log) VALUES('$tglskr', '$isilog')");
		?>
		<script>
			  Swal.fire({
			  position: 'top-end',
			  icon: 'success',
			  title: 'Berhasil menambah data siswa!',
			  showConfirmButton: false,
			  timer: 1500
			  }).then(function(){
				  window.location.href = "?page=data-siswa";
			  })
		</script>
	<?php } else { ?>
		<script>Swal.fire('Gagal', 'Siswa dengan NIS ini sudah ada di dalam daftar!', 'error')</script>
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

<!-- No induk siswa -->
<div class="form-group col-sm-4 pt-4">
    <label for="noinduk">NO INDUK SISWA:</label>
    <input type="number" class="form-control" id="noinduk" name="noinduk" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- No induk siswa -->

<!-- Nama Siswa -->
<div class="form-group col-sm-4">
    <label for="namasiswa">NAMA SISWA:</label>
    <input type="text" class="form-control" id="nama" name="nama" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Nama Siswa -->

<!-- Kelas -->
<div class="form-group col-sm-4">
    <label for="kelas">KELAS:</label>
    <select class="form-control" name="kelas">
        <option selected disabled>-- pilih --</option>
        <?php
		//Tampilkan data kelas
		$kelas = mysqli_query($conn, "SELECT * FROM tbl_kelas ORDER BY id_kelas ASC");
		while ($dkelas = mysqli_fetch_array($kelas)) { ?>
			<option value="<?= $dkelas['kelas']; ?>"><?= $dkelas['kelas']; ?></option>
		<?php } ?>
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Kelas -->

<!-- Hak Akses -->
<input type="text" class="form-control" id="hak_akses" name="hak_akses" value="3" hidden>
<!-- Hak Akses -->

<!-- Keaktifan -->
<input type="text" class="form-control" id="status" name="status" value="Aktif" hidden>
<!-- Keaktifan -->

<!-- Tombol Submit dan cancel -->
<div class="form-group col-sm-2 pb-4">
<table style="border: none;">
<tr>
    <td><input type="submit" onclick="return confirm('Pastikan NOMOR INDUK dan NAMA siswa sudah benar, setelah disimpan, data tersebut tidak bisa dirubah lagi!');" class="btn btn-success" id="submit" name="submit" value="Simpan"></td>
    <td><a class="btn btn-warning" href="?page=data-siswa">Cancel</a></td>
</tr>
</table>
  </div>
<!-- end of submit dan cancel -->

</form>
</div>
</div>
</div>

