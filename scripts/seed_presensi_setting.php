<?php
// One-time script to insert a default presensi setting.
// Usage: run with PHP CLI: c:\xampp\php\php.exe scripts\seed_presensi_setting.php
include __DIR__ . '/../koneksi.php';

$lat = -7.456789; // example latitude - replace with school's actual coords
$lng = 110.123456; // example longitude
$radius = 150;
$schedule = json_encode(["monday"=>["in"=>"07:00","out"=>"15:00"], "tuesday"=>["in"=>"07:00","out"=>"15:00"]]);
$holidays = "2026-01-01\n2026-02-18";

// create table if not exists
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
mysqli_query($conn, $create);

$latEsc = mysqli_real_escape_string($conn, $lat);
$lngEsc = mysqli_real_escape_string($conn, $lng);
$radEsc = intval($radius);
$schedEsc = mysqli_real_escape_string($conn, $schedule);
$holEsc = mysqli_real_escape_string($conn, $holidays);

$q = "INSERT INTO tbl_presensi_setting (lat,lng,radius_m,schedule,holidays) VALUES ('$latEsc','$lngEsc','$radEsc','$schedEsc','$holEsc')";
$res = mysqli_query($conn, $q);
if($res){
  echo "Inserted default presensi setting.\n";
} else {
  echo "Insert failed: " . mysqli_error($conn) . "\n";
}

?>
