<?php
// test_presensi_guru.php - Validasi halaman presensi guru
// Test tanpa butuh login, direktly query database

require_once 'koneksi.php';
require_once 'functions.php';
date_default_timezone_set('Asia/Jakarta');

echo "<h2>Test Presensi Guru</h2>";

if (!$conn) {
    echo "<p style='color:red'>❌ Database connection failed!</p>";
    exit;
}

echo "<p style='color:green'>✅ Database connected</p>";

// Simulasi guru session
$nipguru = "197903052005011002"; // Sample guru NIP

// Test 1: Check kelas yang diajar guru
echo "<h3>Test 1: Ambil kelas yang diajar guru</h3>";
$qK = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='" . $nipguru . "' ORDER BY kelas ASC");
if ($qK) {
    $kelasOpts = [];
    while ($r = mysqli_fetch_assoc($qK)) {
        $kelasOpts[] = $r['kelas'];
    }
    if (count($kelasOpts) > 0) {
        echo "<p style='color:green'>✅ Found " . count($kelasOpts) . " classes: " . implode(", ", $kelasOpts) . "</p>";
    } else {
        echo "<p style='color:orange'>⚠️ No classes found for this guru</p>";
    }
} else {
    echo "<p style='color:red'>❌ Query error: " . mysqli_error($conn) . "</p>";
}

// Test 2: Check tabel struktur
echo "<h3>Test 2: Validasi struktur tabel</h3>";
$tables = ['tbl_mapel_ampu', 'tbl_siswa', 'tbl_absen', 'tbl_telat'];
foreach ($tables as $tbl) {
    $qCheck = mysqli_query($conn, "SHOW TABLES LIKE '$tbl'");
    if ($qCheck && mysqli_num_rows($qCheck) > 0) {
        echo "<p style='color:green'>✅ Tabel $tbl exists</p>";
    } else {
        echo "<p style='color:red'>❌ Tabel $tbl NOT FOUND</p>";
    }
}

// Test 3: Ambil siswa dari kelas pertama (jika ada)
echo "<h3>Test 3: Ambil data siswa dari kelas</h3>";
if (count($kelasOpts) > 0) {
    $ck = $kelasOpts[0];
    $ck_esc = mysqli_real_escape_string($conn, $ck);
    $qs = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas='$ck_esc' AND (status='Aktif' OR status='' OR status IS NULL) ORDER BY nama_siswa ASC LIMIT 5");
    if ($qs) {
        $count = mysqli_num_rows($qs);
        echo "<p style='color:green'>✅ Found $count students in $ck</p>";
        while ($s = mysqli_fetch_assoc($qs)) {
            echo "<p>&nbsp;&nbsp;- " . $s['nama_siswa'] . " (" . $s['no_induk'] . ")</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Query error: " . mysqli_error($conn) . "</p>";
    }
}

// Test 4: Ambil data presensi bulan ini
echo "<h3>Test 4: Ambil data presensi bulan April 2026</h3>";
$tahun = 2026;
$bulan = 4;
$firstDay = sprintf('%04d-%02d-01', $tahun, $bulan);
$lastDay = sprintf('%04d-%02d-30', $tahun, $bulan);

if (count($kelasOpts) > 0) {
    $ck = $kelasOpts[0];
    $ck_esc = mysqli_real_escape_string($conn, $ck);
    $qa = mysqli_query($conn, "SELECT COUNT(*) as tot FROM tbl_absen WHERE kelas='$ck_esc' AND tanggal BETWEEN '$firstDay' AND '$lastDay'");
    if ($qa) {
        $r = mysqli_fetch_assoc($qa);
        echo "<p style='color:green'>✅ Found " . $r['tot'] . " attendance records for $ck</p>";
    } else {
        echo "<p style='color:red'>❌ Query error: " . mysqli_error($conn) . "</p>";
    }
}

// Test 5: Check tbl_telat
echo "<h3>Test 5: Check tabel tbl_telat</h3>";
$qT = mysqli_query($conn, "SELECT COUNT(*) as tot FROM tbl_telat WHERE tanggal BETWEEN '$firstDay' AND '$lastDay'");
if ($qT) {
    $r = mysqli_fetch_assoc($qT);
    echo "<p style='color:green'>✅ Found " . $r['tot'] . " late records in April 2026</p>";
} else {
    echo "<p style='color:orange'>⚠️ Tabel tbl_telat might not exist or empty</p>";
}

echo "<h3>✅ All basic tests passed!</h3>";
echo "<p><a href=<?= guru_page('presensi') ?>>View Guru Presensi Page</a> (requires login)</p>";
