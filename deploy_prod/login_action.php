<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

include "koneksi.php";

$username = mysqli_real_escape_string($conn, $_POST["username"] ?? '');
$passwordRaw = $_POST["password"] ?? '';
$p = md5(mysqli_real_escape_string($conn, $passwordRaw));
$akses = $_POST['hak_akses'] ?? '';
$stt = "Aktif";

// cek dulu siapa yang login
if($akses == 1) {
		$sql = "select * from tbl_user where username='".$username."' and password='".$p."' limit 1";
		$hasil = mysqli_query ($conn,$sql);
		$jumlah = mysqli_num_rows($hasil);


	if ($jumlah>0) {
		$row = mysqli_fetch_assoc($hasil);
		$_SESSION["id_user"]=$row["id_user"];
		$_SESSION["username"]=$row["username"];
		$_SESSION["nama"]=$row["nama"];
		$_SESSION["hak_akses"]=$row["hak_akses"];
	
		header("Location:home.php");	
	} else {
		header("location:index.php?gagallogin");
	}
} else if($akses == 2) {
	// LOGIN GURU DENGAN NIP SAJA: jika password kosong, bypass cek password pengguna
	if ($passwordRaw === '' || $passwordRaw === null) {
		// Terima NIP pada kolom nip_guru atau no_induk untuk kompatibilitas data
		$sql = "SELECT g.*, 2 AS hak_akses FROM tbl_guru g WHERE (g.no_induk='".$username."' OR g.nip_guru='".$username."') AND g.status='".$stt."' LIMIT 1";
	} else {
		$sql = "SELECT * FROM tbl_guru g JOIN tbl_pengguna p ON g.no_induk = p.no_induk WHERE p.no_induk='".$username."' AND p.password='".$p."' AND g.status='".$stt."' LIMIT 1";
	}
	$hasil = mysqli_query($conn, $sql);
	$jumlah = mysqli_num_rows($hasil);

	if ($jumlah>0) {
		$row = mysqli_fetch_assoc($hasil);
		$_SESSION["no_induk"]=$row["no_induk"];
		$_SESSION["nama_guru"]=$row["nama_guru"] ?? '';
		$_SESSION["status_kepegawaian"]=$row["status_kepegawaian"] ?? '';
		$_SESSION["hak_akses"]= 2; // paksa hak akses guru
    
		header("Location:pages/guru/guru.php");
		exit;
	} else {
		header("location:index.php?gagallogin");
		exit;
	}
} else if($akses == 3) {
	$sql = "select * from tbl_siswa s JOIN tbl_pengguna p ON s.no_induk = p.no_induk WHERE s.no_induk='".$username."' and p.password='".$p."' and s.status='".$stt."' limit 1";
	$hasil = mysqli_query ($conn,$sql);
	$jumlah = mysqli_num_rows($hasil);
	
	if ($jumlah>0) {
		$row = mysqli_fetch_assoc($hasil);
		$_SESSION["no_induk"]=$row["no_induk"];
		$_SESSION["nama_siswa"]=$row["nama_siswa"];
		$_SESSION["kelas"]=$row["kelas"];
		$_SESSION["hak_akses"]=$row["hak_akses"];
	
		header("Location:pages/siswa/siswa.php");
	} else {
		header("location:index.php?gagallogin");
	}
} else {
	header("location:index.php?gagallogin");
}

?>
