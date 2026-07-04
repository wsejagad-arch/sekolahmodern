<?php 
include "../koneksi.php";
$departid = 0;
$kelas = "";

if(isset($_POST['depart'])){
   $departid = mysqli_real_escape_string($conn,$_POST['depart']); // department id
}

$users_arr = array();

$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
if($departid == 1){
    $sql = "SELECT * FROM tbl_guru WHERE id_sekolah = $idSekolah";

    $result = mysqli_query($conn,$sql);
    
    while( $row = mysqli_fetch_array($result) ){
        $userid = $row['no_induk'];
        $name = $row['nama_guru'];
    
        $users_arr[] = array("id" => $userid, "name" => $name, "kelas" => $kelas);
    }
} else if($departid == 2) {
	$sql = "SELECT * FROM tbl_siswa WHERE id_sekolah = $idSekolah";

    $result = mysqli_query($conn,$sql);
    
    while( $row = mysqli_fetch_array($result) ){
        $userid = $row['no_induk'];
        $name = $row['nama_siswa'];
		$kelass = $row['kelas'];
    
        $users_arr[] = array("id" => $userid, "name" => $name, "kelas" => $kelass);
    }
}

// encoding array to json format
echo json_encode($users_arr);
?>
