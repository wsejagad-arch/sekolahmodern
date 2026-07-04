<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['username'])) {
	header("location:404.html");
	exit();
}

if($_SESSION['hak_akses'] != 1) {
	header("location:404.html");
	exit();
}

include "koneksi.php";
$id = $_REQUEST['id_user'];
$datauser = mysqli_query($conn, "SELECT * FROM tbl_user WHERE id_user='$id'");
$dat = mysqli_fetch_array($datauser);
$namana = $dat['nama'];
date_default_timezone_set('Asia/Jakarta');
$nama = $_SESSION['nama'];
$tglskr = date('Y-m-d H:i:s');
$isilog = "$nama ". "menghapus admin dengan nama " . "$namana";

$delete = mysqli_query($conn, "DELETE FROM tbl_user WHERE id_user='$id'");

if ($delete) {
	mysqli_query($conn, "INSERT INTO tbl_log (waktu, isi_log) VALUES ('$tglskr', '$isilog')");
    echo '<script language="javascript">window.location="home.php?page=lihatuser";</script>';
  } else {
    echo '<script language="javascript">alert("Gagal menghapus user!");window.location="home.php?page=lihatuser";</script>';
  }
?>
