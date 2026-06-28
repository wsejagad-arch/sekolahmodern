<?php
include "../koneksi.php";
$nip = "";
$kelas = "";

if(isset($_POST['nip'])){
   $nip = mysqli_real_escape_string($conn,$_POST['nip']); // ambil nip
}

$users_arr = array();

$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$sql = mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$nip' AND id_sekolah = $idSekolah");
$jum = mysqli_num_rows($sql);
if($jum < 1) {
	$sql = mysqli_query($conn, "SELECT * FROM tbl_siswa WHERE no_induk='$nip' AND id_sekolah = $idSekolah");
	$row = mysqli_fetch_array($sql);
	$nama = $row['nama_siswa'] ?? '';
	$kelas = $row['kelas'] ?? '';
	
	$users_arr[] = array("nama" => $nama, "kelas" => $kelas);
} else {
	$sql = mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$nip' AND id_sekolah = $idSekolah");
	$row = mysqli_fetch_array($sql);
	$nama = $row['nama_guru'] ?? '';
	
	$users_arr[] = array("nama" => $nama, "kelas" => $kelas);
}

// encoding array to json format
echo json_encode($users_arr);
?>
