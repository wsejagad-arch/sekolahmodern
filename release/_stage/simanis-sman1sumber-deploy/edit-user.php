<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include "koneksi.php";
//ambil id user
$iduser = mysqli_real_escape_string($conn, $_GET['id_user']);
// Pemrosesan form
if (isset($_POST['submit'])) {
    //definisikan variabel dulu
      $password = mysqli_real_escape_string($conn, $_POST['password']);
      $pwd = md5($password);
      $sql = mysqli_query($conn, "UPDATE tbl_user SET password='$pwd' WHERE id_user='$iduser'");
	  if($sql) { ?>
		<script>
			  Swal.fire({
			  position: 'top-end',
			  icon: 'success',
			  title: 'Berhasil merubah password!',
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
        <h4>Reset Password User</h4>
    </div>

<div class="container rounded" style="background-color: #ffffff; outline: 1px solid lightgrey">
<form method="POST" action="" class="needs-validation" novalidate>

<!-- Password Baru -->
<div class="form-group col-sm-4 pt-4">
    <label for="password">Password Baru:</label>
    <input type="password" class="form-control" id="passwordBaru" name="password" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Password baru -->

<!-- Tombol Submit dan cancel -->
<div class="form-group col-sm-2 pb-4">
<table style="border: none;">
<tr>
    <td><input type="submit" class="btn btn-success" id="submit" name="submit" value="Ubah Password"></td>
    <td><a class="btn btn-warning" href="?page=lihatuser">Cancel</a></td>
</tr>
</table>
  </div>
<!-- end of submit dan cancel -->

</form>
</div>
</div>
</div>

