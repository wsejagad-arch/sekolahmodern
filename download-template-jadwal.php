<?php
/**
 * Download Template Jadwal - Dinamis dari Database
 * 
 * Template Excel yang dihasilkan berisi:
 * - Sheet 1: "Jadwal Guru" - Template utama untuk diisi
 * - Sheet 2: "Daftar Guru" - Referensi no_induk + nama_guru dari database
 * - Sheet 3: "Daftar Kelas" - Referensi nama kelas dari database
 */
session_start();
include "koneksi.php";
include "SimpleXLSXGen.php";

use Shuchkin\SimpleXLSXGen;

if (!isset($_SESSION['no_induk'])) {
    header("Location: login.php");
    exit();
}

// ── 1. Ambil data guru dari database ──
$qGuru = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru ORDER BY nama_guru ASC");
$guru_list = [];
if ($qGuru) {
    while ($r = mysqli_fetch_assoc($qGuru)) {
        $guru_list[] = $r;
    }
}

// ── 2. Ambil data kelas dari database ──
$qKelas = mysqli_query($conn, "SELECT nama_kelas FROM tbl_kelas ORDER BY nama_kelas ASC");
// Fallback: coba kolom 'kelas' jika 'nama_kelas' tidak ada
if (!$qKelas) {
    $qKelas = mysqli_query($conn, "SELECT kelas AS nama_kelas FROM tbl_kelas ORDER BY kelas ASC");
}
$kelas_list = [];
if ($qKelas) {
    while ($r = mysqli_fetch_assoc($qKelas)) {
        $kelas_list[] = $r['nama_kelas'];
    }
}

// ── Sheet 1: Jadwal Guru (template utama) ──
$jadwal_rows = [];
// Header dengan style bold
$jadwal_rows[] = [
    '<style bgcolor="#059669" color="#FFFFFF"><b>no_induk</b></style>',
    '<style bgcolor="#059669" color="#FFFFFF"><b>nama_guru</b></style>',
    '<style bgcolor="#059669" color="#FFFFFF"><b>nama_mapel</b></style>',
    '<style bgcolor="#059669" color="#FFFFFF"><b>kelas</b></style>',
    '<style bgcolor="#059669" color="#FFFFFF"><b>hari</b></style>',
    '<style bgcolor="#059669" color="#FFFFFF"><b>jam_mulai</b></style>',
    '<style bgcolor="#059669" color="#FFFFFF"><b>jam_selesai</b></style>',
    '<style bgcolor="#059669" color="#FFFFFF"><b>ruang</b></style>',
];
// Baris contoh
if (!empty($guru_list)) {
    $sample = $guru_list[0];
    $jadwal_rows[] = [
        $sample['no_induk'],
        $sample['nama_guru'],
        'Contoh Mapel',
        !empty($kelas_list) ? $kelas_list[0] : 'X-1',
        'Senin',
        '07:00',
        '07:45',
        'Ruang 1',
    ];
}
// 20 baris kosong untuk diisi pengguna
for ($i = 0; $i < 20; $i++) {
    $jadwal_rows[] = ['', '', '', '', '', '', '', ''];
}

// ── Sheet 2: Daftar Guru (referensi) ──
$guru_rows = [];
$guru_rows[] = [
    '<style bgcolor="#2563EB" color="#FFFFFF"><b>No</b></style>',
    '<style bgcolor="#2563EB" color="#FFFFFF"><b>Nomor Induk</b></style>',
    '<style bgcolor="#2563EB" color="#FFFFFF"><b>Nama Guru</b></style>',
];
foreach ($guru_list as $idx => $g) {
    $guru_rows[] = [$idx + 1, $g['no_induk'], $g['nama_guru']];
}

// ── Sheet 3: Daftar Kelas (referensi) ──
$kelas_rows = [];
$kelas_rows[] = [
    '<style bgcolor="#D97706" color="#FFFFFF"><b>No</b></style>',
    '<style bgcolor="#D97706" color="#FFFFFF"><b>Nama Kelas</b></style>',
];
foreach ($kelas_list as $idx => $k) {
    $kelas_rows[] = [$idx + 1, $k];
}

// ── Generate Excel ──
$xlsx = SimpleXLSXGen::fromArray($jadwal_rows, 'Jadwal Guru');

// Set lebar kolom Sheet 1
$xlsx->setColWidth(1, 22);  // no_induk
$xlsx->setColWidth(2, 35);  // nama_guru
$xlsx->setColWidth(3, 20);  // nama_mapel
$xlsx->setColWidth(4, 15);  // kelas
$xlsx->setColWidth(5, 12);  // hari
$xlsx->setColWidth(6, 12);  // jam_mulai
$xlsx->setColWidth(7, 12);  // jam_selesai
$xlsx->setColWidth(8, 18);  // ruang

// Tambah Sheet 2: Daftar Guru
$xlsx->addSheet($guru_rows, 'Daftar Guru');
$xlsx->setColWidth(1, 6);   // No
$xlsx->setColWidth(2, 22);  // Nomor Induk
$xlsx->setColWidth(3, 35);  // Nama Guru

// Tambah Sheet 3: Daftar Kelas
$xlsx->addSheet($kelas_rows, 'Daftar Kelas');
$xlsx->setColWidth(1, 6);   // No
$xlsx->setColWidth(2, 25);  // Nama Kelas

// Download file
$xlsx->downloadAs('template_jadwal.xlsx');
exit();
?>
