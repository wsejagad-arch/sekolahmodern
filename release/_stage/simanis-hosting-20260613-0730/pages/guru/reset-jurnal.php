<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function reset_jurnal_response(bool $success, string $message): void
{
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

if (!isset($_SESSION['no_induk'])) {
    http_response_code(401);
    reset_jurnal_response(false, 'Sesi login sudah habis. Silakan login ulang.');
}

if ((int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    http_response_code(403);
    reset_jurnal_response(false, 'Akses ditolak.');
}

include '../../koneksi.php';

if (!$conn) {
    http_response_code(500);
    reset_jurnal_response(false, 'Koneksi database gagal.');
}

date_default_timezone_set('Asia/Jakarta');

$idMapel = (int)($_POST['idmapel'] ?? 0);
if ($idMapel <= 0) {
    http_response_code(400);
    reset_jurnal_response(false, 'Jadwal tidak valid.');
}

$nipGuru = (string)$_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nipGuru);
$tanggal = date('Y-m-d');
$tanggalEsc = mysqli_real_escape_string($conn, $tanggal);

$qMapel = mysqli_query($conn, "SELECT id_mapel FROM tbl_mapel_ampu WHERE id_mapel=$idMapel AND no_induk='$nipEsc' LIMIT 1");
if (!$qMapel || mysqli_num_rows($qMapel) === 0) {
    http_response_code(403);
    reset_jurnal_response(false, 'Jadwal tidak ditemukan atau bukan jadwal Anda.');
}

$dateCol = null;
$colTanggal = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_materi LIKE 'tanggal'");
if ($colTanggal && mysqli_num_rows($colTanggal) > 0) {
    $dateCol = 'tanggal';
} else {
    $colDate = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_materi LIKE 'date'");
    if ($colDate && mysqli_num_rows($colDate) > 0) {
        $dateCol = 'date';
    }
}

if (!$dateCol) {
    http_response_code(500);
    reset_jurnal_response(false, 'Kolom tanggal jurnal tidak ditemukan.');
}

$files = [];
$qMateri = mysqli_query($conn, "SELECT file_materi FROM tbl_materi WHERE id_mapel=$idMapel AND no_induk='$nipEsc' AND `$dateCol`='$tanggalEsc'");
if ($qMateri) {
    while ($row = mysqli_fetch_assoc($qMateri)) {
        if (!empty($row['file_materi'])) {
            $files[] = basename((string)$row['file_materi']);
        }
    }
}

$deleteAbsen = @mysqli_query($conn, "DELETE FROM tbl_absen WHERE id_mapel=$idMapel AND tanggal='$tanggalEsc' AND no_induk_guru='$nipEsc'");
if ($deleteAbsen === false) {
    $deleteAbsen = @mysqli_query($conn, "DELETE FROM tbl_absen WHERE id_mapel=$idMapel AND tanggal='$tanggalEsc'");
}

$deleteMateri = mysqli_query($conn, "DELETE FROM tbl_materi WHERE id_mapel=$idMapel AND no_induk='$nipEsc' AND `$dateCol`='$tanggalEsc'");

if (!$deleteMateri) {
    error_log('reset-jurnal.php - delete failed: ' . mysqli_error($conn));
    http_response_code(500);
    reset_jurnal_response(false, 'Gagal mereset jurnal.');
}

foreach ($files as $file) {
    $paths = [
        __DIR__ . '/../../file_materi/' . $file,
        __DIR__ . '/../../materi/' . $file
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

reset_jurnal_response(true, 'Jurnal berhasil direset.');
