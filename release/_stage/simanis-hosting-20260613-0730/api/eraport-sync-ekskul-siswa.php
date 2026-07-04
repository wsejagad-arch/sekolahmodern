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

$discover = eraport_discover_and_fetch_student_ekskul([
    'deep_probe' => true,
    'student_limit' => 25,
]);
if (empty($discover['success'])) {
    echo json_encode([
        'success' => false,
        'message' => (string)($discover['message'] ?? 'Discovery ekskul siswa gagal.'),
        'debug' => $discover['debug'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$relations = is_array($discover['relations'] ?? null) ? $discover['relations'] : [];
$candidates = is_array($discover['candidates'] ?? null) ? $discover['candidates'] : [];

$fallbackGenerated = 0;
if (count($relations) === 0) {
    $kelasEkskulRows = [];
    $qEkskulKelas = @mysqli_query($conn, "SELECT DISTINCT nama_kelas_ekskul, nama_ekskul FROM tbl_ekskul_eraport WHERE nama_kelas_ekskul <> '' AND nama_ekskul <> ''");
    if ($qEkskulKelas) {
        while ($er = mysqli_fetch_assoc($qEkskulKelas)) {
            $kelasEkskulRows[] = [
                'nama_kelas_ekskul' => trim((string)($er['nama_kelas_ekskul'] ?? '')),
                'nama_ekskul' => trim((string)($er['nama_ekskul'] ?? '')),
            ];
        }
    }

    $siswaByKelas = [];
    $qSiswa = @mysqli_query($conn, "SELECT nis, nama_siswa, kelas FROM tbl_siswa_eraport WHERE kelas IS NOT NULL AND kelas <> ''");
    if ($qSiswa) {
        while ($sr = mysqli_fetch_assoc($qSiswa)) {
            $kelasKey = mb_strtolower(trim((string)($sr['kelas'] ?? '')));
            if ($kelasKey === '') {
                continue;
            }
            if (!isset($siswaByKelas[$kelasKey])) {
                $siswaByKelas[$kelasKey] = [];
            }
            $siswaByKelas[$kelasKey][] = [
                'nis' => trim((string)($sr['nis'] ?? '')),
                'nama_siswa' => trim((string)($sr['nama_siswa'] ?? '')),
                'kelas_siswa' => trim((string)($sr['kelas'] ?? '')),
            ];
        }
    }

    foreach ($kelasEkskulRows as $ek) {
        $kelasEkskulKey = mb_strtolower((string)($ek['nama_kelas_ekskul'] ?? ''));
        $namaEkskul = (string)($ek['nama_ekskul'] ?? '');
        if ($kelasEkskulKey === '' || $namaEkskul === '') {
            continue;
        }

        if (empty($siswaByKelas[$kelasEkskulKey])) {
            continue;
        }

        foreach ($siswaByKelas[$kelasEkskulKey] as $ss) {
            $relations[] = [
                'nis' => (string)($ss['nis'] ?? ''),
                'nama_siswa' => (string)($ss['nama_siswa'] ?? ''),
                'kelas_siswa' => (string)($ss['kelas_siswa'] ?? ''),
                'nama_ekskul' => $namaEkskul,
                'sumber_endpoint' => 'fallback:kelas_ekskul',
            ];
            $fallbackGenerated++;
        }
    }
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
    UNIQUE KEY uniq_relasi (nis, nama_siswa, nama_ekskul)
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

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_ekskul_siswa_discovery_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    run_id VARCHAR(40) NOT NULL,
    endpoint VARCHAR(200) NOT NULL,
    method VARCHAR(10) DEFAULT NULL,
    status_code INT DEFAULT NULL,
    has_keyword TINYINT(1) NOT NULL DEFAULT 0,
    relations_found INT NOT NULL DEFAULT 0,
    preview_text TEXT,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_run_id (run_id),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$inserted = 0;
$updated = 0;
$errors = [];
$logInserted = 0;

$kelasByNis = [];
$kelasByNama = [];
$qSiswaMap = mysqli_query($conn, "SELECT nis, nama_siswa, kelas FROM tbl_siswa_eraport");
if ($qSiswaMap) {
    while ($sr = mysqli_fetch_assoc($qSiswaMap)) {
        $nisMap = trim((string)($sr['nis'] ?? ''));
        $namaMap = mb_strtolower(trim((string)($sr['nama_siswa'] ?? '')));
        $kelasMap = trim((string)($sr['kelas'] ?? ''));

        if ($kelasMap === '') {
            continue;
        }
        if ($nisMap !== '' && !isset($kelasByNis[$nisMap])) {
            $kelasByNis[$nisMap] = $kelasMap;
        }
        if ($namaMap !== '' && !isset($kelasByNama[$namaMap])) {
            $kelasByNama[$namaMap] = $kelasMap;
        }
    }
}

$runId = date('YmdHis') . '-' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
foreach ($candidates as $cand) {
    $endpoint = mysqli_real_escape_string($conn, (string)($cand['endpoint'] ?? ''));
    $method = mysqli_real_escape_string($conn, (string)($cand['method'] ?? 'GET'));
    $statusCode = (int)($cand['status_code'] ?? 0);
    $hasKeyword = !empty($cand['has_keyword']) ? 1 : 0;
    $relationsFound = (int)($cand['relations_found'] ?? 0);
    $previewText = mysqli_real_escape_string($conn, (string)($cand['preview'] ?? ''));

    if ($endpoint === '') {
        continue;
    }

    $sqlLog = "INSERT INTO tbl_ekskul_siswa_discovery_log (
        run_id, endpoint, method, status_code, has_keyword, relations_found, preview_text, created_at
    ) VALUES (
        '{$runId}', '{$endpoint}', '{$method}', {$statusCode}, {$hasKeyword}, {$relationsFound}, '{$previewText}', NOW()
    )";

    if (@mysqli_query($conn, $sqlLog)) {
        $logInserted++;
    }
}

foreach ($relations as $it) {
    $nis = mysqli_real_escape_string($conn, trim((string)($it['nis'] ?? '')));
    $namaSiswa = mysqli_real_escape_string($conn, trim((string)($it['nama_siswa'] ?? '')));
    $nisRaw = trim((string)($it['nis'] ?? ''));
    $namaRaw = trim((string)($it['nama_siswa'] ?? ''));
    $namaRawKey = mb_strtolower($namaRaw);
    $kelasRaw = trim((string)($it['kelas_siswa'] ?? ''));
    if ($nisRaw !== '' && isset($kelasByNis[$nisRaw])) {
        $kelasRaw = (string)$kelasByNis[$nisRaw];
    } elseif ($namaRawKey !== '' && isset($kelasByNama[$namaRawKey])) {
        $kelasRaw = (string)$kelasByNama[$namaRawKey];
    }
    $kelasSiswa = mysqli_real_escape_string($conn, $kelasRaw);
    $namaEkskul = mysqli_real_escape_string($conn, trim((string)($it['nama_ekskul'] ?? '')));
    $sumberEndpoint = mysqli_real_escape_string($conn, trim((string)($it['sumber_endpoint'] ?? '')));

    if ($namaEkskul === '') {
        continue;
    }

    $sql = "INSERT INTO tbl_ekskul_siswa_eraport (
        nis, nama_siswa, kelas_siswa, nama_ekskul, sumber_endpoint, synced_at
    ) VALUES (
        " . ($nis !== '' ? "'{$nis}'" : "NULL") . ",
        " . ($namaSiswa !== '' ? "'{$namaSiswa}'" : "NULL") . ",
        " . ($kelasSiswa !== '' ? "'{$kelasSiswa}'" : "NULL") . ",
        '{$namaEkskul}',
        " . ($sumberEndpoint !== '' ? "'{$sumberEndpoint}'" : "NULL") . ",
        NOW()
    ) ON DUPLICATE KEY UPDATE
        kelas_siswa=VALUES(kelas_siswa),
        sumber_endpoint=VALUES(sumber_endpoint),
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
$q = mysqli_query($conn, "SELECT nis, nama_siswa, kelas_siswa, nama_ekskul, sumber_endpoint, synced_at FROM tbl_ekskul_siswa_eraport ORDER BY synced_at DESC LIMIT 30");
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $preview[] = $r;
    }
}

$success = count($relations) > 0;
$message = $success
    ? 'Discovery + sinkron relasi ekskul siswa selesai.'
    : 'Discovery selesai, tetapi relasi ekskul per siswa belum ditemukan dari endpoint yang terdeteksi.';

echo json_encode([
    'success' => $success,
    'message' => $message,
    'summary' => [
        'endpoint_checked' => (int)($discover['endpoint_checked'] ?? 0),
        'student_ids_sample' => $discover['student_ids_sample'] ?? [],
        'deep_probe' => !empty($discover['deep_probe']),
        'relations_found' => count($relations),
        'fallback_generated' => $fallbackGenerated,
        'inserted' => $inserted,
        'updated' => $updated,
        'candidate_logs_saved' => $logInserted,
        'candidate_run_id' => $runId,
        'errors' => count($errors),
    ],
    'candidates' => array_slice($candidates, 0, 20),
    'errors' => array_slice($errors, 0, 5),
    'preview' => $preview,
], JSON_UNESCAPED_UNICODE);
