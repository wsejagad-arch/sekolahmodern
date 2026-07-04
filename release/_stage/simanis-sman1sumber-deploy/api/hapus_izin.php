<?php
// api/hapus_izin.php — Hapus satu pengajuan izin siswa (admin only)
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['hak_akses']) || (int)$_SESSION['hak_akses'] !== 1) {
    http_response_code(403);
    echo json_encode(array('success' => false, 'msg' => 'Akses ditolak.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'msg' => 'Method tidak diizinkan.'));
    exit;
}

require_once __DIR__ . '/../koneksi.php';

$id = isset($_POST['id_izin']) ? (int)$_POST['id_izin'] : 0;
if ($id <= 0) {
    echo json_encode(array('success' => false, 'msg' => 'ID izin tidak valid.'));
    exit;
}

// Cek apakah kolom foto_selfie ada (hosting mungkin belum ada kolom ini)
$colCheck = @$conn->query("SHOW COLUMNS FROM tbl_izin_siswa LIKE 'foto_selfie'");
$hasFotoCol = ($colCheck && $colCheck->num_rows > 0);
if ($colCheck) $colCheck->free();

$selectSql = $hasFotoCol
    ? "SELECT id_izin, foto_selfie FROM tbl_izin_siswa WHERE id_izin = ? LIMIT 1"
    : "SELECT id_izin FROM tbl_izin_siswa WHERE id_izin = ? LIMIT 1";

// Cek data ada & ambil foto
$stmt = $conn->prepare($selectSql);
if (!$stmt) {
    echo json_encode(array('success' => false, 'msg' => 'Kesalahan database: ' . $conn->error));
    exit;
}
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(array('success' => false, 'msg' => 'Data izin tidak ditemukan.'));
    exit;
}

// Hapus file foto selfie jika ada
if ($hasFotoCol && !empty($row['foto_selfie'])) {
    $fotoPath = __DIR__ . '/../uploads/izin/' . basename($row['foto_selfie']);
    if (file_exists($fotoPath)) {
        @unlink($fotoPath);
    }
}

// Hapus record
$del = $conn->prepare("DELETE FROM tbl_izin_siswa WHERE id_izin = ?");
if (!$del) {
    echo json_encode(array('success' => false, 'msg' => 'Gagal menyiapkan query hapus.'));
    exit;
}
$del->bind_param('i', $id);
$del->execute();
$affected = $del->affected_rows;
$del->close();

if ($affected > 0) {
    echo json_encode(array('success' => true, 'msg' => 'Pengajuan izin berhasil dihapus.'));
} else {
    echo json_encode(array('success' => false, 'msg' => 'Data tidak berhasil dihapus.'));
}
