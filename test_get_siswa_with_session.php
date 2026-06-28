<?php
// Test script: set session and GET param, then include endpoint
session_start();
// pick an existing kelas from DB (from import we saw 'XI IPA 1')
$_SESSION['no_induk'] = '199801012000111002';
$_SESSION['hak_akses'] = 2;
// pick a kelas that exists in local DB
$_GET['kelas'] = 'X E 5';

// Capture output
ob_start();
include __DIR__ . '/get_siswa_by_kelas.php';
$out = ob_get_clean();
file_put_contents('php://stdout', $out);
?>