<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login sebagai guru.']);
    exit;
}

require_once __DIR__ . '/../../koneksi.php';
require_once __DIR__ . '/../../eraport_helper.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi database tidak tersedia.']);
    exit;
}

function upload_leger_table_exists(mysqli $conn, string $table): bool
{
    $safe = mysqli_real_escape_string($conn, $table);
    $q = @mysqli_query($conn, "SHOW TABLES LIKE '{$safe}'");
    return (bool)($q && mysqli_num_rows($q) > 0);
}

function upload_leger_is_wali_or_teacher(mysqli $conn, string $nip, string $kelas): bool
{
    $nipEsc = mysqli_real_escape_string($conn, $nip);
    $kelasEsc = mysqli_real_escape_string($conn, $kelas);

    if (upload_leger_table_exists($conn, 'tbl_wali_kelas') && upload_leger_table_exists($conn, 'tbl_kelas')) {
        $q = @mysqli_query(
            $conn,
            "SELECT wk.id_wali
             FROM tbl_wali_kelas wk
             JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas
             WHERE wk.nip_wali='{$nipEsc}' AND k.kelas='{$kelasEsc}'
             LIMIT 1"
        );
        if ($q && mysqli_num_rows($q) > 0) {
            return true;
        }
    }

    if (upload_leger_table_exists($conn, 'tbl_kelas')) {
        $qCol = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_kelas LIKE 'nip_wali'");
        if ($qCol && mysqli_num_rows($qCol) > 0) {
            $q = @mysqli_query($conn, "SELECT id_kelas FROM tbl_kelas WHERE nip_wali='{$nipEsc}' AND kelas='{$kelasEsc}' LIMIT 1");
            if ($q && mysqli_num_rows($q) > 0) {
                return true;
            }
        }
    }

    $qAmpu = @mysqli_query($conn, "SELECT id_mapel FROM tbl_mapel_ampu WHERE no_induk='{$nipEsc}' AND kelas='{$kelasEsc}' LIMIT 1");
    return (bool)($qAmpu && mysqli_num_rows($qAmpu) > 0);
}

$kelas = trim((string)($_POST['kelas'] ?? ''));
$semester = trim((string)($_POST['semester'] ?? ''));
$nip = (string)$_SESSION['no_induk'];

if (!isset($_FILES['leger_file']) || !is_uploaded_file($_FILES['leger_file']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File leger wajib dipilih.']);
    exit;
}

$file = $_FILES['leger_file'];
$ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
if ($ext !== 'xlsx') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format file harus .xlsx sesuai template leger.']);
    exit;
}

$binary = file_get_contents((string)$file['tmp_name']);
if ($binary === false || $binary === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File leger tidak dapat dibaca.']);
    exit;
}

$rows = eraport_parse_xlsx_rows_from_binary($binary);
$parsed = eraport_extract_leger_detail_rows($rows);
$kelasFromFile = trim((string)($parsed['meta']['kelas'] ?? ''));
if ($kelas === '') {
    $kelas = $kelasFromFile;
}
if ($kelas === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kelas tidak ditemukan. Isi kelas atau pastikan template memuat baris kelas.']);
    exit;
}

if (!upload_leger_is_wali_or_teacher($conn, $nip, $kelas)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengunggah leger kelas ini.']);
    exit;
}

$result = eraport_store_leger_snapshot($conn, $kelas, $parsed, $semester, $nip, (string)$file['name']);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
