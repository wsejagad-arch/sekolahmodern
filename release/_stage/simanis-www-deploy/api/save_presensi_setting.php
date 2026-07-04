<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
if(!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses']!=1){
  echo json_encode(['success'=>false,'message'=>'Forbidden']); exit;
}
include __DIR__ . '/../koneksi.php';

$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
$radius = isset($_POST['radius_m']) ? intval($_POST['radius_m']) : 100;
$schedule = isset($_POST['schedule']) ? $_POST['schedule'] : null; // expected JSON string
$holidays = isset($_POST['holidays']) ? $_POST['holidays'] : null; // newline separated

if($lat===null || $lng===null){ echo json_encode(['success'=>false,'message'=>'lat/lng required']); exit; }

// create table if not exists with additional fields
$create = "CREATE TABLE IF NOT EXISTS tbl_presensi_setting (
  id INT PRIMARY KEY AUTO_INCREMENT,
  lat DOUBLE,
  lng DOUBLE,
  radius_m INT,
  schedule TEXT,
  holidays TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn,$create);

// insert new setting (keep history)
// insert new setting – delete old first to keep only one row
$latEsc = mysqli_real_escape_string($conn, $lat);
$lngEsc = mysqli_real_escape_string($conn, $lng);
$radEsc = intval($radius);
$schedEsc = mysqli_real_escape_string($conn, $schedule);
$holEsc = mysqli_real_escape_string($conn, $holidays);

$idSekolah = mt_current_school_id();
mysqli_query($conn, "DELETE FROM tbl_presensi_setting WHERE id_sekolah = $idSekolah"); // keep only latest for this school
$ins = mysqli_query($conn, "INSERT INTO tbl_presensi_setting (lat,lng,radius_m,schedule,holidays,id_sekolah) VALUES ('$latEsc','$lngEsc','$radEsc','$schedEsc','$holEsc',$idSekolah)");
if($ins){
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_presensi_setting WHERE id_sekolah = $idSekolah ORDER BY id DESC LIMIT 1"));
    echo json_encode(['success'=>true,'message'=>'Saved','data'=>$row]);
} else { echo json_encode(['success'=>false,'message'=>'DB error: '.mysqli_error($conn)]); }

?>
