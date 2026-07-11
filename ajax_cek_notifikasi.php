<?php
session_start();
if (!isset($_SESSION["no_induk"])) {
    http_response_code(403);
    exit;
}
include "koneksi.php";
header('Content-Type: application/json');

$no_induk = mysqli_real_escape_string($conn, $_SESSION['no_induk']);
$q = mysqli_query($conn, "SELECT id_notifikasi, pesan, created_at FROM tbl_notifikasi WHERE penerima_id='$no_induk' AND is_read=0 ORDER BY created_at ASC LIMIT 1");

if ($q && mysqli_num_rows($q) > 0) {
    $r = mysqli_fetch_assoc($q);
    echo json_encode(['status' => 'ada', 'data' => $r]);
} else {
    echo json_encode(['status' => 'kosong']);
}
?>
