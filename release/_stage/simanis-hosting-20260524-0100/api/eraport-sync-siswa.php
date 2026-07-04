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

$fetch = eraport_login_and_fetch_data_siswa();
if (empty($fetch['success'])) {
    echo json_encode([
        'success' => false,
        'message' => (string)($fetch['message'] ?? 'Gagal ambil data siswa dari e-Raport.'),
        'debug' => $fetch['debug'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$items = is_array($fetch['items'] ?? null) ? $fetch['items'] : [];

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_siswa_eraport (
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
    KEY idx_kelas (kelas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$inserted = 0;
$updated = 0;
$errors = [];

foreach ($items as $it) {
    $sourceNo = (int)($it['no'] ?? 0);
    $pesertaDidikId = mysqli_real_escape_string($conn, trim((string)($it['peserta_didik_id'] ?? '')));
    $namaSiswa = mysqli_real_escape_string($conn, trim((string)($it['nama_siswa'] ?? '')));
    $nis = mysqli_real_escape_string($conn, trim((string)($it['nis'] ?? '')));
    $nisn = mysqli_real_escape_string($conn, trim((string)($it['nisn'] ?? '')));
    $jk = mysqli_real_escape_string($conn, trim((string)($it['jenis_kelamin'] ?? '')));
    $ttl = mysqli_real_escape_string($conn, trim((string)($it['ttl'] ?? '')));
    $agama = mysqli_real_escape_string($conn, trim((string)($it['agama'] ?? '')));
    $tingkat = mysqli_real_escape_string($conn, trim((string)($it['tingkat'] ?? '')));
    $kelas = mysqli_real_escape_string($conn, trim((string)($it['kelas'] ?? '')));

    if ($pesertaDidikId === '' || $namaSiswa === '') {
        continue;
    }

    $sql = "INSERT INTO tbl_siswa_eraport (
        source_no, peserta_didik_id, nama_siswa, nis, nisn, jenis_kelamin, ttl, agama, tingkat, kelas, synced_at
    ) VALUES (
        {$sourceNo}, '{$pesertaDidikId}', '{$namaSiswa}', '{$nis}', '{$nisn}', '{$jk}', '{$ttl}', '{$agama}', '{$tingkat}', '{$kelas}', NOW()
    ) ON DUPLICATE KEY UPDATE
        source_no=VALUES(source_no),
        nama_siswa=VALUES(nama_siswa),
        nis=VALUES(nis),
        nisn=VALUES(nisn),
        jenis_kelamin=VALUES(jenis_kelamin),
        ttl=VALUES(ttl),
        agama=VALUES(agama),
        tingkat=VALUES(tingkat),
        kelas=VALUES(kelas),
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
$q = mysqli_query($conn, "SELECT source_no, nama_siswa, nis, nisn, kelas, peserta_didik_id, synced_at FROM tbl_siswa_eraport ORDER BY source_no ASC LIMIT 100");
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $preview[] = $r;
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Sinkron data siswa selesai.',
    'summary' => [
        'fetched' => count($items),
        'inserted' => $inserted,
        'updated' => $updated,
        'errors' => count($errors),
    ],
    'errors' => array_slice($errors, 0, 5),
    'preview' => $preview,
], JSON_UNESCAPED_UNICODE);
