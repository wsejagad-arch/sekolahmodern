<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
if (!isset($_SESSION['no_induk'])) {
    echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    exit;
}
include "../koneksi.php";
require_once "../notification_helper.php";
// create settings table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_presensi_setting (id INT PRIMARY KEY AUTO_INCREMENT, lat DOUBLE, lng DOUBLE, radius_m INT, updated_at DATETIME)");

$no_induk = $_SESSION['no_induk'];
$kelas = $_POST['kelas'] ?? '';
$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
$tgl = date('Y-m-d');

if($lat === null || $lng === null){
    echo json_encode(['success'=>false,'message'=>'Lokasi tidak dikirim']); exit;
}

// get allowed location
$q = mysqli_query($conn, "SELECT * FROM tbl_presensi_setting ORDER BY id DESC LIMIT 1");
$allow = mysqli_fetch_assoc($q);
if($allow){
    // haversine
    function dist($lat1,$lon1,$lat2,$lon2){
        $R = 6371000; // m
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }
    $d = dist($lat, $lng, floatval($allow['lat']), floatval($allow['lng']));
    if($d > intval($allow['radius_m'])){
        echo json_encode(['success'=>false,'message'=>'Anda tidak berada di lokasi sekolah (jarak '.round($d).' m)']); exit;
    }
}

// check if already present today
$cek = mysqli_query($conn, "SELECT * FROM tbl_absen WHERE no_induk='$no_induk' AND DATE(tanggal)='$tgl' LIMIT 1");
if(mysqli_num_rows($cek) > 0){
    echo json_encode(['success'=>false,'message'=>'Anda sudah absen hari ini']); exit;
}

// insert
$waktu = date('Y-m-d H:i:s');
$ins = mysqli_query($conn, "INSERT INTO tbl_absen (no_induk, kelas, tanggal, status, lat, lng) VALUES ('$no_induk', '$kelas', '$waktu', 'Hadir', '$lat', '$lng')");
if($ins){
    if (function_exists('notif_trigger_presensi')) {
        notif_trigger_presensi($conn, $no_induk, 'Hadir', $waktu);
    }
    echo json_encode(['success'=>true,'message'=>'Terdata hadir pada '.$waktu]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB error']);
}

?>
