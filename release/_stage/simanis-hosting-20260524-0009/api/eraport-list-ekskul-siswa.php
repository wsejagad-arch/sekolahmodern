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

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_ekskul_siswa_eraport (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nis VARCHAR(30) DEFAULT NULL,
    nama_siswa VARCHAR(160) DEFAULT NULL,
    kelas_siswa VARCHAR(80) DEFAULT NULL,
    nama_ekskul VARCHAR(160) NOT NULL,
    sumber_endpoint VARCHAR(200) DEFAULT NULL,
    synced_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_relasi (nis, nama_siswa, nama_ekskul),
    KEY idx_nama_ekskul (nama_ekskul),
    KEY idx_kelas_siswa (kelas_siswa),
    KEY idx_synced_at (synced_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$hasKelasSiswaCol = false;
$qCol = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_ekskul_siswa_eraport LIKE 'kelas_siswa'");
if ($qCol && mysqli_num_rows($qCol) > 0) {
    $hasKelasSiswaCol = true;
}
if (!$hasKelasSiswaCol) {
    try {
        @mysqli_query($conn, "ALTER TABLE tbl_ekskul_siswa_eraport ADD COLUMN kelas_siswa VARCHAR(80) DEFAULT NULL AFTER nama_siswa");
    } catch (Throwable $e) {
        // Ignore duplicate-column race conditions; requests should continue returning JSON.
    }
}

$page = (int)($_GET['page'] ?? $_POST['page'] ?? 1);
$perPage = (int)($_GET['per_page'] ?? $_POST['per_page'] ?? 25);
$q = trim((string)($_GET['q'] ?? $_POST['q'] ?? ''));
$ekskul = trim((string)($_GET['ekskul'] ?? $_POST['ekskul'] ?? ''));
$kelas = trim((string)($_GET['kelas'] ?? $_POST['kelas'] ?? ''));

if ($page < 1) {
    $page = 1;
}
if ($perPage < 1) {
    $perPage = 25;
}
if ($perPage > 200) {
    $perPage = 200;
}

$where = ["nama_ekskul <> ''"];
if ($q !== '') {
    $qEsc = mysqli_real_escape_string($conn, $q);
    $where[] = "(nama_siswa LIKE '%{$qEsc}%' OR nis LIKE '%{$qEsc}%' OR nama_ekskul LIKE '%{$qEsc}%')";
}
if ($ekskul !== '') {
    $ekskulEsc = mysqli_real_escape_string($conn, $ekskul);
    $where[] = "nama_ekskul='{$ekskulEsc}'";
}
if ($kelas !== '') {
    $kelasEsc = mysqli_real_escape_string($conn, $kelas);
    $where[] = "kelas_siswa='{$kelasEsc}'";
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$total = 0;
$qCount = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tbl_ekskul_siswa_eraport {$whereSql}");
if ($qCount && ($rCount = mysqli_fetch_assoc($qCount))) {
    $total = (int)($rCount['total'] ?? 0);
}

$totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;
if ($offset < 0) {
    $offset = 0;
}

$rows = [];
$qRows = mysqli_query($conn, "SELECT nis, nama_siswa, kelas_siswa, nama_ekskul, sumber_endpoint, synced_at
    FROM tbl_ekskul_siswa_eraport
    {$whereSql}
    ORDER BY nama_ekskul ASC, nama_siswa ASC, nis ASC
    LIMIT {$perPage} OFFSET {$offset}");
if ($qRows) {
    while ($r = mysqli_fetch_assoc($qRows)) {
        $rows[] = $r;
    }
}

$groups = [];
$qGroup = mysqli_query($conn, "SELECT nama_ekskul, COUNT(*) AS total_siswa
    FROM tbl_ekskul_siswa_eraport
    {$whereSql}
    GROUP BY nama_ekskul
    ORDER BY total_siswa DESC, nama_ekskul ASC
    LIMIT 300");
if ($qGroup) {
    while ($r = mysqli_fetch_assoc($qGroup)) {
        $groups[] = $r;
    }
}

$diag = [
    'relasi_total' => $total,
    'siswa_total' => 0,
    'ekskul_master_total' => 0,
    'log_discovery_total' => 0,
];
$qDiagSiswa = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM tbl_siswa_eraport");
if ($qDiagSiswa && ($dr = mysqli_fetch_assoc($qDiagSiswa))) {
    $diag['siswa_total'] = (int)($dr['total'] ?? 0);
}
$qDiagEkskul = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM tbl_ekskul_eraport");
if ($qDiagEkskul && ($dr = mysqli_fetch_assoc($qDiagEkskul))) {
    $diag['ekskul_master_total'] = (int)($dr['total'] ?? 0);
}
$qDiagLog = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM tbl_ekskul_siswa_discovery_log");
if ($qDiagLog && ($dr = mysqli_fetch_assoc($qDiagLog))) {
    $diag['log_discovery_total'] = (int)($dr['total'] ?? 0);
}

$optionsEkskul = [];
$qEkskul = mysqli_query($conn, "SELECT DISTINCT nama_ekskul FROM tbl_ekskul_siswa_eraport WHERE nama_ekskul <> '' ORDER BY nama_ekskul ASC");
if ($qEkskul) {
    while ($r = mysqli_fetch_assoc($qEkskul)) {
        $v = trim((string)($r['nama_ekskul'] ?? ''));
        if ($v !== '') {
            $optionsEkskul[] = $v;
        }
    }
}

$optionsKelas = [];
$qKelas = mysqli_query($conn, "SELECT DISTINCT kelas_siswa FROM tbl_ekskul_siswa_eraport WHERE kelas_siswa IS NOT NULL AND kelas_siswa <> '' ORDER BY kelas_siswa ASC");
if ($qKelas) {
    while ($r = mysqli_fetch_assoc($qKelas)) {
        $v = trim((string)($r['kelas_siswa'] ?? ''));
        if ($v !== '') {
            $optionsKelas[] = $v;
        }
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Data siswa per ekstrakurikuler berhasil dimuat.',
    'filters' => [
        'q' => $q,
        'ekskul' => $ekskul,
        'kelas' => $kelas,
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
        'ekskul' => $optionsEkskul,
        'kelas' => $optionsKelas,
    ],
    'groups' => $groups,
    'diagnostic' => $diag,
    'rows' => $rows,
], JSON_UNESCAPED_UNICODE);
