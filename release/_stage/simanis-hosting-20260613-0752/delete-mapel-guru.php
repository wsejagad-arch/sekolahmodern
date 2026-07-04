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
$idguru = $_GET['id'];
$id = $_GET['id_mapel'];
$noinduk = $_GET['no_induk'];
$sql = mysqli_query($conn, "DELETE FROM tbl_mapel_ampu WHERE id_mapel='$id'");
if($sql) { ?>
	<script>window.location="home.php?page=detail-guru&id=<?= $idguru; ?>&no_induk=<?= $noinduk; ?>";</script>
<?php	
} else { ?>
	<script>alert('Gagal menghapus jadwal!');</script>
<?php	
}
