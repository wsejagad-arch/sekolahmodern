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
include "functions.php";
date_default_timezone_set('Asia/Jakarta');
$nama = $_SESSION['nama'];
$idguru = $_GET['id'];
$noinduk = $_GET['noinduk'];
$tglskr = date('Y-m-d H:i:s');
$isilog = "$nama ". "mengosongkan jadwal guru dengan No. Induk " . "$noinduk";
$sql = mysqli_query($conn, "DELETE FROM tbl_mapel_ampu WHERE id_guru='$idguru'");
if($sql) { 
	$mat = mysqli_query($conn, "SELECT * FROM tbl_materi WHERE no_induk='$noinduk'");
	while($dmat = mysqli_fetch_array($mat)) {
		$nmfile = $dmat['file_materi'];
		$path_file = "materi/" . $nmfile;
		if(file_exists($path_file)) {
			unlink($path_file);
		}
	}
	mysqli_query($conn, "DELETE FROM tbl_materi WHERE no_induk='$noinduk'");
	mysqli_query($conn, "INSERT INTO tbl_log (waktu, isi_log) VALUES ('$tglskr','$isilog')");
?>
	<script>window.location="home.php?page=detail-guru&id=<?= $idguru; ?>&no_induk=<?= $noinduk; ?>";</script>
<?php	
} else { ?>
	<script>alert('Gagal menghapus jadwal!');window.location="home.php?page=detail-guru&id=<?= $idguru; ?>&no_induk=<?= $noinduk; ?>";</script>
<?php	
}
