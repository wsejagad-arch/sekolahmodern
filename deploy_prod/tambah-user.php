<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');
$namauser = $_SESSION['nama'];
$tglskr = date('Y-m-d H:i:s');
// Pemrosesan form
if (isset($_POST['submit'])) {
    //definisikan variabel dulu
      $username = mysqli_real_escape_string($conn, $_POST['username']);
      $nama = mysqli_real_escape_string($conn, $_POST['nama']);
      $password = mysqli_real_escape_string($conn, $_POST['password']);
      $hak_akses = $_POST['hak_akses'];
      $pwd = md5($password);
      
	  $isilog = "$namauser" . " menambah admin baru dengan nama " . "$nama";
	  $cek = cek_user($username);
	  if($cek == True) {
		mysqli_query($conn, "INSERT INTO tbl_user(username, nama, password, hak_akses) VALUES('$username','$nama','$pwd','$hak_akses')");
		mysqli_query($conn, "INSERT INTO tbl_log (waktu, isi_log) VALUES ('$tglskr', '$isilog')");
		?>
		<script>
			  Swal.fire({
			  position: 'top-end',
			  icon: 'success',
			  title: 'Berhasil menambah admin!',
			  showConfirmButton: false,
			  timer: 1500
			  }).then(function(){
				  window.location.href = "home.php?page=lihatuser";
			  })
		</script>
	<?php } else { ?>
		<script>Swal.fire('Gagal', 'User ini telah terdaftar!', 'error')</script>
	<?php }
}
?>

    <div class="container-fluid">
    <div class="container">
    <div class="alert" style="background-color: #ffffff; outline: 1px solid lightgrey">
        <h4>Tambah User</h4>
    </div>

<div class="container rounded" style="background-color: #ffffff; outline: 1px solid lightgrey">
<form method="POST" action="" class="needs-validation" novalidate>

<!-- Kategori Pegawai -->
<div class="form-group col-sm-2 pt-4">
    <label for="kategori_user">Kategori User:</label>
    <select class="form-control" id="kategoriUser" name="kategori_user">
        <option selected disabled>-- pilih --</option>
        <option value="1">Guru</option>
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Kategori Pegawai -->

<!-- Username -->
<div class="form-group col-sm-6">
    <label for="userName">Username:</label>
    <select class="form-control" id="userName" name="username" required>
        <option selected disabled>-- pilih --</option>
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Username -->

<!-- Nama -->
<div class="form-group col-sm-4">
    <label for="nama">Nama:</label>
    <input type="text" class="form-control" id="nama" name="nama" readonly>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Nama -->

<!-- Password -->
<div class="form-group col-sm-4">
    <label for="password">Password:</label>
    <input type="password" class="form-control" id="password" name="password" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Password -->

<!-- Hak Akses -->
<div class="form-group col-sm-2">
    <label for="hak_akses">Hak Akses:</label>
    <select class="form-control" name="hak_akses">
        <option selected disabled>-- pilih --</option>
        <option value="1">Super Admin</option>
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Hak Akses -->

<!-- Tombol Submit dan cancel -->
<div class="form-group col-sm-2 pb-4">
<table style="border: none;">
<tr>
    <td><input type="submit" class="btn btn-success" id="submit" name="submit" value="Simpan"></td>
    <td><a class="btn btn-warning" href="home.php">Cancel</a></td>
</tr>
</table>
  </div>
<!-- end of submit dan cancel -->

</form>
</div>
</div>
</div>

