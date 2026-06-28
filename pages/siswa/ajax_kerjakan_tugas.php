<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["no_induk"]) || (int)($_SESSION['hak_akses'] ?? 0) !== 3) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../koneksi.php';

$nis = $_SESSION['no_induk'];
$id_tugas = isset($_POST['id_tugas']) ? (int)$_POST['id_tugas'] : 0;

if ($id_tugas <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID Tugas tidak valid']);
    exit;
}

$nisEsc = mysqli_real_escape_string($conn, $nis);

// Cek apakah sudah ada (mungkin status lain)
$cek = mysqli_query($conn, "SELECT id FROM tbl_tugas_siswa WHERE id_tugas = $id_tugas AND no_induk_siswa = '$nisEsc'");
if ($cek && mysqli_num_rows($cek) > 0) {
    // Jika sudah ada, mungkin update
    $q = "UPDATE tbl_tugas_siswa SET status = 'Menunggu Konfirmasi', waktu_submit = NOW() WHERE id_tugas = $id_tugas AND no_induk_siswa = '$nisEsc'";
} else {
    // Insert baru
    $q = "INSERT INTO tbl_tugas_siswa (id_tugas, no_induk_siswa, status, waktu_submit) VALUES ($id_tugas, '$nisEsc', 'Menunggu Konfirmasi', NOW())";
}

if (mysqli_query($conn, $q)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}
