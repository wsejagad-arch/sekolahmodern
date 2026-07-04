<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['no_induk'])) {
	header("location:index.php?haruslogin");
	exit();
} else if($_SESSION['hak_akses'] != 3) { ?>
	<script>window.location='404.html';</script>
<?php
}

include "../../koneksi.php";

$tanggal = $_POST['tanggal'];
$nip = $_POST['nip'];
$namaguru = $_POST['namaguru'];
$namamapel = $_POST['namamapel'];
$kelas = $_POST['kelas'];
$namakm = $_POST['namakm'];
$stt = $_POST['sttkehadiran'];
$ket = $_POST['keterangan'];

$sql = mysqli_query($conn, "INSERT INTO tbl_kehadiran (tanggal, no_induk, nama_guru, nama_mapel, kelas, nama_ketua_kelas, status_kehadiran, catatan) VALUES ('$tanggal','$nip','$namaguru','$namamapel','$kelas','$namakm','$stt','$ket')");

if($sql) { 
	header("location:siswa.php?sukses");	
} else { 
	header("location:siswa.php?gagal");
}
?>
