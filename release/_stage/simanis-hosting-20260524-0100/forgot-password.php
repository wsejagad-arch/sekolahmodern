<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
}

include "koneksi.php";

//proses jika tombol rubah di klik
if(isset($_POST['submit'])) {
//membuat variabel untuk menyimpan data inputan yang di isikan di form
$iduser = $_SESSION['id_user'];
$password_lama			= mysqli_real_escape_string($conn, $_POST['passwordlama']);
$password_baru			= mysqli_real_escape_string($conn, $_POST['passwordbaru']);
$konfirmasi_password	= mysqli_real_escape_string($conn, $_POST['konfirmasipassword']);

$password_lama	= md5($password_lama);
$cek 			= mysqli_num_rows(mysqli_query($conn, "SELECT password FROM tbl_user WHERE password='$password_lama' AND id_user='$iduser'"));

if($cek < 1) {
	header("location: forgot-password.php?gagal1");
} else if (strlen($password_baru) < 8) {
	header("location: forgot-password.php?gagal2");
} else if($password_baru != $konfirmasi_password) {
	header("location: forgot-password.php?gagal3");
} else {
	$password_baru = md5($password_baru);
	$update = mysqli_query($conn, "UPDATE tbl_user SET password='$password_baru' WHERE id_user='$iduser'");
	session_destroy();
	header("location:index.php?rubahpassword");
}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">

  <title>Ganti Password</title>

  <!-- Custom fonts for this template-->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

  <!-- Custom styles for this template-->
  <link href="css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body class="bg-gradient-primary">

  <div class="container">

    <!-- Outer Row -->
    <div class="row justify-content-center">

      <div class="col-xl-10 col-lg-12 col-md-9">

        <div class="card o-hidden border-0 shadow-lg my-5">
          <div class="card-body p-0">
            <!-- Nested Row within Card Body -->
            <div class="row">
              <div class="col-lg-6 d-none d-lg-block"><img src="img/img_kredensial.jpeg" alt="ganti password" height="670px"></div>
              <div class="col-lg-6">
                <div class="p-5">
                  <form class="user mt-4" style="background-color: rgba(255, 255, 255, 0.8); box-shadow: 0 0 10px rgba(0, 0, 0, 0.3); opacity: 1.0; padding: 30px; border-radius: 10px;" method="post" action="">
				  <div class="text-center">
                    <h1 class="h4 text-gray-900 mb-2">Mau ganti password?</h1>
                    <hr>
					
					<?php
					if (isset($_GET["gagal1"])) {
					echo '<div class="alert alert-danger" id="error-alert">
						<strong>Gagal!</strong> password lama salah.
						</div>';
					} else if(isset($_GET['gagal2'])) {
						echo '<div class="alert alert-danger" id="error-alert">
						<strong>Gagal!</strong> password harus lebih dari 5 karakter.
						</div>';
					} else if(isset($_GET['gagal3'])) {
						echo '<div class="alert alert-danger" id="error-alert">
						<strong>Gagal!</strong> password tidak sesuai.
						</div>';
					}
				  ?>
					
                  </div>
                    <div class="form-group">
                      <input type="password" name="passwordlama" class="form-control form-control-user" id="exampleInputPwdLama" aria-describedby="emailHelp" placeholder="Password lama ...">
                    </div>
                    <div class="form-group">
                      <input type="password" name="passwordbaru" class="form-control form-control-user" id="exampleInputPwdBaru" aria-describedby="pwdHelp" placeholder="Password Baru ...">
                    </div>
                    <div class="form-group">
                      <input type="password" name="konfirmasipassword" class="form-control form-control-user" id="exampleInputKonfirm" aria-describedby="pwdKonf" placeholder="Konfirmasi password ...">
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary btn-user btn-block">
                      Ganti Password
                    </button>
					
					<hr>
                  <div class="text-center">
                    <a class="small" href="home.php">Kembali</a>
                  </div>
                  </form>
                  
                  
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>

  </div>

  <!-- Bootstrap core JavaScript-->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Core plugin JavaScript-->
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- Custom scripts for all pages-->
  <script src="js/sb-admin-2.min.js"></script>

  <!-- Alert jquery -->
  <script>
  $("#error-alert").fadeTo(4000, 500).slideUp(500, function(){
    $("#error-alert").slideUp(500);
});
</script>
  <!-- End Alert Jquery -->

</body>

</html>

