<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['username'])) {
	header("location:index.php?haruslogin");
	exit();
}

if($_SESSION['hak_akses'] != 1) {
	header("location:404.html");
	exit();
}

include "koneksi.php";
$id = $_REQUEST['id_thn'];
$sql = mysqli_query($conn, "DELETE FROM tbl_thn_ajaran WHERE id_thn='$id'");
if($sql) { ?>
	<script>window.location="home.php?page=tambah-tahun-ajaran";</script>
<?php	
} else { ?>
	<script>alert('Gagal menghapus data!');</script>
<?php	
}
