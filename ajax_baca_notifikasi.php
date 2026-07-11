<?php
session_start();
if (!isset($_SESSION["no_induk"])) {
    http_response_code(403);
    exit;
}
include "koneksi.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (isset($data['id'])) {
    $id = (int)$data['id'];
    $no_induk = mysqli_real_escape_string($conn, $_SESSION['no_induk']);
    mysqli_query($conn, "UPDATE tbl_notifikasi SET is_read=1 WHERE id_notifikasi=$id AND penerima_id='$no_induk'");
    echo json_encode(['status' => 'ok']);
}
?>
