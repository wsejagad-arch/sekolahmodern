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

$ekskul = [];
$qEkskul = @mysqli_query($conn, "SELECT DISTINCT nama_ekskul FROM tbl_ekskul_eraport WHERE nama_ekskul <> '' ORDER BY nama_ekskul ASC");
if ($qEkskul) {
    while ($r = mysqli_fetch_assoc($qEkskul)) {
        $v = trim((string)($r['nama_ekskul'] ?? ''));
        if ($v !== '') {
            $ekskul[] = $v;
        }
    }
}

$kelas = [];
$qKelas = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_siswa_eraport WHERE kelas IS NOT NULL AND kelas <> '' ORDER BY kelas ASC");
if ($qKelas) {
    while ($r = mysqli_fetch_assoc($qKelas)) {
        $v = trim((string)($r['kelas'] ?? ''));
        if ($v !== '') {
            $kelas[] = $v;
        }
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Opsi ekskul dan kelas berhasil dimuat.',
    'options' => [
        'ekskul' => $ekskul,
        'kelas' => $kelas,
    ],
], JSON_UNESCAPED_UNICODE);
