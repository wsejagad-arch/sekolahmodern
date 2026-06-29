<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['no_induk'])) {
	header("location:index.php?haruslogin");
	exit();
}

if($_SESSION['hak_akses'] != 2) {
	header("location:404.html");
	exit();
}

include "../../koneksi.php";
$id = $_REQUEST['id'];
$fmateri = $_REQUEST['file'];
$sql = mysqli_query($conn, "DELETE FROM tbl_materi WHERE id_materi='$id'");
if($sql) { 
	if ($fmateri) {
		@unlink('../../materi/' . $fmateri);
	}
	header('location:../../home.php?p=dashboard_guru&sukses=hapusjurnal');	
} else { 
	header('location:../../home.php?p=dashboard_guru&gagal=hapusjurnal');
}
