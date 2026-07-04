<?php
// Session diinisialisasi dengan cara yang sama seperti login_action.php
ini_set('session.gc_maxlifetime', 315360000);
session_set_cookie_params([
    'lifetime' => 315360000,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

$wantJson = isset($_GET['mode']) && $_GET['mode'] === 'json';

// Cek autentikasi
if (!isset($_SESSION['username'])) {
    if ($wantJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Sesi tidak valid, silakan login ulang']);
        exit;
    }
    header('Location: index.php?haruslogin');
    exit;
}
if (($_SESSION['hak_akses'] ?? 0) != 1) {
    if ($wantJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Akses ditolak']);
        exit;
    }
    header('Location: 403.php');
    exit;
}

include __DIR__ . '/koneksi.php';

$id = trim($_REQUEST['no_induk'] ?? '');

// Validasi parameter
if ($id === '') {
    if ($wantJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Parameter no_induk tidak valid']);
        exit;
    }
    echo '<script>alert("Parameter tidak valid");window.location="home.php?page=data-siswa";</script>';
    exit;
}

$idEsc = mysqli_real_escape_string($conn, $id);

// Cek apakah siswa ada
$cek = mysqli_query($conn, "SELECT nama_siswa FROM tbl_siswa WHERE no_induk='$idEsc' LIMIT 1");
if (!$cek || mysqli_num_rows($cek) === 0) {
    if ($wantJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Data siswa tidak ditemukan']);
        exit;
    }
    echo '<script>alert("Data siswa tidak ditemukan");window.location="home.php?page=data-siswa";</script>';
    exit;
}
$rowSiswa   = mysqli_fetch_assoc($cek);
$namaSiswa  = $rowSiswa['nama_siswa'];
$namaAdmin  = $_SESSION['nama'] ?? 'Admin';

date_default_timezone_set('Asia/Jakarta');
$tglskr = date('Y-m-d H:i:s');

// Hapus akun login siswa terlebih dahulu (jika ada) – pakai LEFT JOIN bisa NULL
mysqli_query($conn, "DELETE FROM tbl_pengguna WHERE no_induk='$idEsc'");

// Hapus data siswa
$sqlHapus = mysqli_query($conn, "DELETE FROM tbl_siswa WHERE no_induk='$idEsc'");

if ($sqlHapus && mysqli_affected_rows($conn) > 0) {
    $isilog = mysqli_real_escape_string($conn, "$namaAdmin menghapus data siswa $namaSiswa (NIS: $id)");
    mysqli_query($conn, "INSERT INTO tbl_log (waktu, isi_log) VALUES ('$tglskr', '$isilog')");

    if ($wantJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Data siswa berhasil dihapus']);
        exit;
    }
    echo '<script>window.location="home.php?page=data-siswa&deleted=1";</script>';
} else {
    $errMsg = mysqli_error($conn);
    if ($wantJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus data: ' . $errMsg]);
        exit;
    }
    echo '<script>alert("Gagal menghapus data!");window.location="home.php?page=data-siswa";</script>';
}
