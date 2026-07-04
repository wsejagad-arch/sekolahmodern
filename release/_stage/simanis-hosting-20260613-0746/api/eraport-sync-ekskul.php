<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_admin_ajax();
require_once __DIR__ . '/../eraport_helper.php';

if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database tidak tersedia.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$fetch = eraport_login_and_fetch_ekskul();
if (empty($fetch['success'])) {
    echo json_encode([
        'success' => false,
        'message' => (string)($fetch['message'] ?? 'Gagal ambil data ekskul dari e-Raport.'),
        'debug' => $fetch['debug'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$items = is_array($fetch['items'] ?? null) ? $fetch['items'] : [];

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_ekskul_eraport (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_no INT DEFAULT NULL,
    nama_kelas_ekskul VARCHAR(160) NOT NULL,
    jenis_ekskul VARCHAR(200) NOT NULL,
    nama_ekskul VARCHAR(160) NOT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    sekolah_id VARCHAR(80) DEFAULT NULL,
    synced_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_ekskul (nama_kelas_ekskul, nama_ekskul)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$cfg = eraport_get_config();
$semester = mysqli_real_escape_string($conn, (string)($cfg['semester'] ?? ''));
$sekolahId = mysqli_real_escape_string($conn, (string)($cfg['sekolah_id'] ?? ''));

$inserted = 0;
$updated = 0;
$errors = [];

foreach ($items as $it) {
    $sourceNo = (int)($it['no'] ?? 0);
    $namaKelas = mysqli_real_escape_string($conn, (string)($it['nama_kelas_ekskul'] ?? ''));
    $jenis = mysqli_real_escape_string($conn, (string)($it['jenis_ekskul'] ?? ''));
    $nama = mysqli_real_escape_string($conn, (string)($it['nama_ekskul'] ?? ''));

    if ($namaKelas === '' || $nama === '') {
        continue;
    }

    $sql = "INSERT INTO tbl_ekskul_eraport (
        source_no, nama_kelas_ekskul, jenis_ekskul, nama_ekskul, semester, sekolah_id, synced_at
    ) VALUES (
        {$sourceNo}, '{$namaKelas}', '{$jenis}', '{$nama}', '{$semester}', '{$sekolahId}', NOW()
    ) ON DUPLICATE KEY UPDATE
        source_no=VALUES(source_no),
        jenis_ekskul=VALUES(jenis_ekskul),
        semester=VALUES(semester),
        sekolah_id=VALUES(sekolah_id),
        synced_at=NOW()";

    $ok = mysqli_query($conn, $sql);
    if (!$ok) {
        $errors[] = mysqli_error($conn);
        continue;
    }

    $aff = mysqli_affected_rows($conn);
    if ($aff === 1) {
        $inserted++;
    } elseif ($aff >= 2) {
        $updated++;
    }
}

$preview = [];
$q = mysqli_query($conn, "SELECT source_no, nama_kelas_ekskul, jenis_ekskul, nama_ekskul, synced_at FROM tbl_ekskul_eraport ORDER BY source_no ASC, nama_kelas_ekskul ASC LIMIT 30");
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $preview[] = $r;
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Sinkron data ekskul selesai.',
    'summary' => [
        'fetched' => count($items),
        'inserted' => $inserted,
        'updated' => $updated,
        'errors' => count($errors),
    ],
    'errors' => array_slice($errors, 0, 5),
    'preview' => $preview,
], JSON_UNESCAPED_UNICODE);
