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
date_default_timezone_set('Asia/Jakarta');
$tglskr = date('Y-m-d H:i:s');
$nama = $_SESSION['nama'];
$kelas = $_REQUEST['kelas'];
$id = $_REQUEST['id_kelas'];
$isilog = "$nama "."menghapus kelas"." $kelas"." dari daftar";
$sql = mysqli_query($conn, "DELETE FROM tbl_kelas WHERE id_kelas='$id'");
if($sql) { 
	mysqli_query($conn, "INSERT INTO tbl_log (waktu, isi_log) VALUES ('$tglskr', '$isilog')");
?>
	<script>window.location="home.php?page=tambah-data-kelas";</script>
<?php	
} else { ?>
	<script>alert('Gagal menghapus data!');</script>
<?php	
}
