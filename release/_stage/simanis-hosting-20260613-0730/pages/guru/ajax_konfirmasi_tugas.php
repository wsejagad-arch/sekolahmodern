<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../koneksi.php';

$nipGuru = $_SESSION['no_induk'];
$nis = isset($_POST['nis']) ? $_POST['nis'] : '';
$id_tugas = isset($_POST['id_tugas']) ? (int)$_POST['id_tugas'] : 0;

if ($id_tugas <= 0 || empty($nis)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

$nisEsc = mysqli_real_escape_string($conn, $nis);
$nipEsc = mysqli_real_escape_string($conn, $nipGuru);

// Pastikan guru ini adalah pemilik tugas tersebut
$cekTugas = mysqli_query($conn, "SELECT id FROM tbl_tugas WHERE id = $id_tugas AND no_induk_guru = '$nipEsc'");
if (!$cekTugas || mysqli_num_rows($cekTugas) == 0) {
    echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan atau bukan milik Anda.']);
    exit;
}

// Update status siswa
$q = "UPDATE tbl_tugas_siswa SET status = 'Selesai', waktu_konfirmasi = NOW() WHERE id_tugas = $id_tugas AND no_induk_siswa = '$nisEsc'";

if (mysqli_query($conn, $q)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}
