<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_admin_ajax();

if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database tidak tersedia.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$createSql = "CREATE TABLE IF NOT EXISTS tbl_siswa_eraport (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_no INT DEFAULT NULL,
    peserta_didik_id VARCHAR(80) NOT NULL,
    nama_siswa VARCHAR(200) NOT NULL,
    nis VARCHAR(40) DEFAULT NULL,
    nisn VARCHAR(40) DEFAULT NULL,
    jenis_kelamin VARCHAR(10) DEFAULT NULL,
    ttl VARCHAR(200) DEFAULT NULL,
    agama VARCHAR(60) DEFAULT NULL,
    tingkat VARCHAR(30) DEFAULT NULL,
    kelas VARCHAR(60) DEFAULT NULL,
    synced_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_peserta_didik (peserta_didik_id),
    KEY idx_nama_siswa (nama_siswa),
    KEY idx_kelas (kelas),
    KEY idx_jk (jenis_kelamin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@mysqli_query($conn, $createSql);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
$q = trim((string)($_GET['q'] ?? ''));
$kelas = trim((string)($_GET['kelas'] ?? ''));
$jk = trim((string)($_GET['jk'] ?? ''));

if ($page < 1) {
    $page = 1;
}
if ($perPage < 1) {
    $perPage = 25;
}
if ($perPage > 100) {
    $perPage = 100;
}

$whereParts = [];
if ($q !== '') {
    $qEsc = mysqli_real_escape_string($conn, $q);
    $whereParts[] = "(nama_siswa LIKE '%{$qEsc}%' OR nis LIKE '%{$qEsc}%' OR nisn LIKE '%{$qEsc}%' OR peserta_didik_id LIKE '%{$qEsc}%')";
}
if ($kelas !== '') {
    $kelasEsc = mysqli_real_escape_string($conn, $kelas);
    $whereParts[] = "kelas = '{$kelasEsc}'";
}
if ($jk !== '') {
    $jkEsc = mysqli_real_escape_string($conn, $jk);
    $whereParts[] = "jenis_kelamin = '{$jkEsc}'";
}

$whereSql = '';
if (!empty($whereParts)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereParts);
}

$countSql = "SELECT COUNT(*) AS total FROM tbl_siswa_eraport {$whereSql}";
$countRes = mysqli_query($conn, $countSql);
if (!$countRes) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menghitung data siswa.',
        'debug' => mysqli_error($conn),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$total = (int)(mysqli_fetch_assoc($countRes)['total'] ?? 0);
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$dataSql = "SELECT source_no, nama_siswa, nis, nisn, jenis_kelamin, kelas, peserta_didik_id, synced_at
            FROM tbl_siswa_eraport
            {$whereSql}
            ORDER BY nama_siswa ASC
            LIMIT {$perPage} OFFSET {$offset}";
$dataRes = mysqli_query($conn, $dataSql);
if (!$dataRes) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil data siswa.',
        'debug' => mysqli_error($conn),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rows = [];
while ($row = mysqli_fetch_assoc($dataRes)) {
    $rows[] = $row;
}

$kelasOptions = [];
$kelasRes = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_siswa_eraport WHERE kelas IS NOT NULL AND kelas <> '' ORDER BY kelas ASC");
if ($kelasRes) {
    while ($kr = mysqli_fetch_assoc($kelasRes)) {
        $kelasOptions[] = (string)$kr['kelas'];
    }
}

$jkOptions = [];
$jkRes = mysqli_query($conn, "SELECT DISTINCT jenis_kelamin FROM tbl_siswa_eraport WHERE jenis_kelamin IS NOT NULL AND jenis_kelamin <> '' ORDER BY jenis_kelamin ASC");
if ($jkRes) {
    while ($jr = mysqli_fetch_assoc($jkRes)) {
        $jkOptions[] = (string)$jr['jenis_kelamin'];
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Data siswa lokal berhasil dimuat.',
    'filters' => [
        'q' => $q,
        'kelas' => $kelas,
        'jk' => $jk,
    ],
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages,
    ],
    'options' => [
        'kelas' => $kelasOptions,
        'jk' => $jkOptions,
    ],
    'rows' => $rows,
], JSON_UNESCAPED_UNICODE);
