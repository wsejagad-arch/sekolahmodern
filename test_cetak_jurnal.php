<?php
/**
 * Test Script untuk Cetak Jurnal
 * Menguji apakah sistem cetak jurnal sudah berfungsi dengan benar
 */

// Simulasi parameter GET untuk test
$_GET['guru'] = '0029'; // NIP guru yang ada di database
$_GET['tglAwal'] = '2025-07-01';
$_GET['tglAkhir'] = '2025-09-16';

// Simulasi session
$_SESSION['username'] = '0029';
$_SESSION['hak_akses'] = 1;

// Include file yang akan ditest
include 'cetak-jurnal.php';
?>