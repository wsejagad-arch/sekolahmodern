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

$mappingsRaw = (string)($_POST['mappings'] ?? '');
if ($mappingsRaw === '') {
    $jsonInput = file_get_contents('php://input');
    if (is_string($jsonInput) && trim($jsonInput) !== '') {
        $decoded = json_decode($jsonInput, true);
        if (is_array($decoded) && isset($decoded['mappings'])) {
            $mappingsRaw = json_encode($decoded['mappings'], JSON_UNESCAPED_UNICODE);
        }
    }
}

$mappings = json_decode($mappingsRaw, true);
if (!is_array($mappings) || count($mappings) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Mapping manual kosong. Tambahkan minimal 1 mapping ekskul-kelas.',
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
    }
}

$validMappings = [];
foreach ($mappings as $m) {
    $namaEkskul = trim((string)($m['nama_ekskul'] ?? ''));
    $kelas = trim((string)($m['kelas'] ?? ''));
    if ($namaEkskul === '' || $kelas === '') {
        continue;
    }
    $key = mb_strtolower($namaEkskul) . '|' . mb_strtolower($kelas);
    $validMappings[$key] = [
        'nama_ekskul' => $namaEkskul,
        'kelas' => $kelas,
    ];
}
$validMappings = array_values($validMappings);

if (count($validMappings) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada mapping valid. Pastikan nama ekskul dan kelas terisi.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$inserted = 0;
$updated = 0;
$errors = [];
$affectedSiswa = 0;

foreach ($validMappings as $map) {
    $namaEkskulRaw = (string)$map['nama_ekskul'];
    $kelasRaw = (string)$map['kelas'];

    $kelasEsc = mysqli_real_escape_string($conn, $kelasRaw);
    $qSiswa = @mysqli_query($conn, "SELECT nis, nama_siswa, kelas FROM tbl_siswa_eraport WHERE kelas = '{$kelasEsc}'");
    if (!$qSiswa) {
        $errors[] = 'Query siswa gagal untuk kelas ' . $kelasRaw;
        continue;
    }

    while ($sr = mysqli_fetch_assoc($qSiswa)) {
        $nis = trim((string)($sr['nis'] ?? ''));
        $namaSiswa = trim((string)($sr['nama_siswa'] ?? ''));
        $kelasSiswa = trim((string)($sr['kelas'] ?? ''));

        if ($namaSiswa === '') {
            continue;
        }

        $nisEsc = mysqli_real_escape_string($conn, $nis);
        $namaEsc = mysqli_real_escape_string($conn, $namaSiswa);
        $kelasSiswaEsc = mysqli_real_escape_string($conn, $kelasSiswa);
        $ekskulEsc = mysqli_real_escape_string($conn, $namaEkskulRaw);

        $sql = "INSERT INTO tbl_ekskul_siswa_eraport (
            nis, nama_siswa, kelas_siswa, nama_ekskul, sumber_endpoint, synced_at
        ) VALUES (
            " . ($nisEsc !== '' ? "'{$nisEsc}'" : "NULL") . ",
            '{$namaEsc}',
            " . ($kelasSiswaEsc !== '' ? "'{$kelasSiswaEsc}'" : "NULL") . ",
            '{$ekskulEsc}',
            'manual:kelas_mapping',
            NOW()
        ) ON DUPLICATE KEY UPDATE
            kelas_siswa=VALUES(kelas_siswa),
            sumber_endpoint=VALUES(sumber_endpoint),
            synced_at=NOW()";

        $ok = @mysqli_query($conn, $sql);
        if (!$ok) {
            $errors[] = mysqli_error($conn);
            continue;
        }

        $affectedSiswa++;
        $aff = mysqli_affected_rows($conn);
        if ($aff === 1) {
            $inserted++;
        } elseif ($aff >= 2) {
            $updated++;
        }
    }
}

$preview = [];
$qPrev = @mysqli_query($conn, "SELECT nis, nama_siswa, kelas_siswa, nama_ekskul, sumber_endpoint, synced_at
    FROM tbl_ekskul_siswa_eraport
    ORDER BY synced_at DESC
    LIMIT 30");
if ($qPrev) {
    while ($r = mysqli_fetch_assoc($qPrev)) {
        $preview[] = $r;
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Generate relasi manual ekskul-siswa selesai.',
    'summary' => [
        'mapping_count' => count($validMappings),
        'affected_siswa_rows' => $affectedSiswa,
        'inserted' => $inserted,
        'updated' => $updated,
        'errors' => count($errors),
    ],
    'errors' => array_slice($errors, 0, 10),
    'preview' => $preview,
], JSON_UNESCAPED_UNICODE);
