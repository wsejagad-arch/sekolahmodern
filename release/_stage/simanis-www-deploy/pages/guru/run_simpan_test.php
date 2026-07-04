<?php
// Test wrapper to simulate form POST to simpanmateri.php
$_POST = [
    'tanggal' => date('Y-m-d'),
    'nip' => '198312262022212010',
    'idmapel' => '1800',
    'namamapel' => 'PRAKARYA DAN KEWIRAUSAHAAN',
    'kelas' => 'XI F 8',
    'materi' => 'Tes materi otomatis',
    'kegiatan' => 'Tes kegiatan otomatis',
    'keterangan' => 'Test insert via CLI',
];
// No file upload, no absen
$_FILES = [];
// Make script treat as AJAX
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

// Include the target script
include __DIR__ . '/simpanmateri.php';
?>