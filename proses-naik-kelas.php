<?php
ini_set('session.gc_maxlifetime', 315360000);
session_set_cookie_params([
    'lifetime' => 315360000,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

$hakakses = $_SESSION['hakakses'] ?? ($_SESSION['hak_akses'] ?? 0);

if (!isset($_SESSION['username']) || $hakakses != 1) {
    echo '<script>alert("Akses ditolak");window.location="index.php?haruslogin";</script>';
    exit;
}

include __DIR__ . '/koneksi.php';

$aksi = $_POST['aksi'] ?? '';
$kelas_tujuan = trim($_POST['kelas_tujuan'] ?? '');
$returnUrl = $_POST['return_url'] ?? 'home.php?page=data-siswa';

// Ensure returnUrl is local
if (strpos($returnUrl, 'home.php') !== 0) {
    $returnUrl = 'home.php?page=data-siswa';
}

if (empty($kelas_tujuan) || empty($aksi)) {
    echo '<script>alert("Data tidak lengkap!");window.location="' . addslashes($returnUrl) . '";</script>';
    exit;
}

$kelasTujuanEsc = mysqli_real_escape_string($conn, $kelas_tujuan);
$namaAdmin  = $_SESSION['nama'] ?? 'Admin';
date_default_timezone_set('Asia/Jakarta');
$tglskr = date('Y-m-d H:i:s');

if ($aksi === 'massal') {
    $kelas_asal = trim($_POST['kelas_asal'] ?? '');
    if (empty($kelas_asal)) {
        echo '<script>alert("Kelas asal tidak boleh kosong!");window.location="' . addslashes($returnUrl) . '";</script>';
        exit;
    }
    
    $kelasAsalEsc = mysqli_real_escape_string($conn, $kelas_asal);
    $tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
    $tenantSiswa = function_exists('mt_column_exists') && mt_column_exists($conn, 'tbl_siswa', 'id_sekolah') ? " AND id_sekolah={$tenantId}" : "";

    $query = "UPDATE tbl_siswa SET kelas = '$kelasTujuanEsc' WHERE kelas = '$kelasAsalEsc' AND status = 'Aktif' {$tenantSiswa}";
    $update = mysqli_query($conn, $query);

    if ($update) {
        // Update historical tables and related tables to keep data intact in the new class
        @mysqli_query($conn, "UPDATE tbl_absen SET kelas = '$kelasTujuanEsc' WHERE kelas = '$kelasAsalEsc' {$tenantSiswa}");
        @mysqli_query($conn, "UPDATE tbl_izin_siswa SET kelas_siswa = '$kelasTujuanEsc' WHERE kelas_siswa = '$kelasAsalEsc' " . str_replace("id_sekolah", "id_sekolah", $tenantSiswa));
        @mysqli_query($conn, "UPDATE tbl_pelanggaran_siswa SET kelas = '$kelasTujuanEsc' WHERE kelas = '$kelasAsalEsc' {$tenantSiswa}");
        @mysqli_query($conn, "UPDATE tbl_siswa_eraport SET kelas = '$kelasTujuanEsc' WHERE kelas = '$kelasAsalEsc' {$tenantSiswa}");
        
        $jml = mysqli_affected_rows($conn);
        $isilog = mysqli_real_escape_string($conn, "$namaAdmin memindahkan $jml siswa dari kelas $kelas_asal ke $kelas_tujuan");
        mysqli_query($conn, "INSERT INTO tbl_log (waktu, isi_log) VALUES ('$tglskr', '$isilog')");
        
        echo '<script>alert("Berhasil memindahkan ' . $jml . ' siswa dari kelas ' . addslashes($kelas_asal) . ' ke kelas ' . addslashes($kelas_tujuan) . '");window.location="' . addslashes($returnUrl) . '";</script>';
    } else {
        echo '<script>alert("Gagal memindahkan kelas massal!");window.location="' . addslashes($returnUrl) . '";</script>';
    }
} elseif ($aksi === 'individu') {
    $no_induk = trim($_POST['no_induk'] ?? '');
    if (empty($no_induk)) {
        echo '<script>alert("NIS siswa tidak boleh kosong!");window.location="' . addslashes($returnUrl) . '";</script>';
        exit;
    }

    $noIndukEsc = mysqli_real_escape_string($conn, $no_induk);
    $tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
    $tenantSiswa = function_exists('mt_column_exists') && mt_column_exists($conn, 'tbl_siswa', 'id_sekolah') ? " AND id_sekolah={$tenantId}" : "";

    $query = "UPDATE tbl_siswa SET kelas = '$kelasTujuanEsc' WHERE no_induk = '$noIndukEsc' {$tenantSiswa}";
    $update = mysqli_query($conn, $query);

    if ($update) {
        // Update historical tables and related tables to keep data intact in the new class
        @mysqli_query($conn, "UPDATE tbl_absen SET kelas = '$kelasTujuanEsc' WHERE no_induk = '$noIndukEsc' {$tenantSiswa}");
        @mysqli_query($conn, "UPDATE tbl_izin_siswa SET kelas_siswa = '$kelasTujuanEsc' WHERE no_induk_siswa = '$noIndukEsc' " . str_replace("id_sekolah", "id_sekolah", $tenantSiswa));
        @mysqli_query($conn, "UPDATE tbl_pelanggaran_siswa SET kelas = '$kelasTujuanEsc' WHERE no_induk = '$noIndukEsc' {$tenantSiswa}");
        @mysqli_query($conn, "UPDATE tbl_siswa_eraport SET kelas = '$kelasTujuanEsc' WHERE nis = '$noIndukEsc' {$tenantSiswa}");
        
        $isilog = mysqli_real_escape_string($conn, "$namaAdmin memindahkan siswa NIS $no_induk ke kelas $kelas_tujuan");
        mysqli_query($conn, "INSERT INTO tbl_log (waktu, isi_log) VALUES ('$tglskr', '$isilog')");
        
        echo '<script>alert("Berhasil memindahkan siswa ke kelas ' . addslashes($kelas_tujuan) . '");window.location="' . addslashes($returnUrl) . '";</script>';
    } else {
        echo '<script>alert("Gagal memindahkan siswa!");window.location="' . addslashes($returnUrl) . '";</script>';
    }
} else {
    echo '<script>alert("Aksi tidak dikenal!");window.location="' . addslashes($returnUrl) . '";</script>';
}
