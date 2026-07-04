<?php
// api/set_ketua_kelas.php — Tetapkan / hapus Ketua Kelas untuk satu kelas
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

require_once __DIR__ . '/../koneksi.php';

$kelas   = trim($_POST['kelas']   ?? '');
$noInduk = trim($_POST['no_induk'] ?? '');

if ($kelas === '') {
    echo json_encode(['success' => false, 'message' => 'Parameter kelas tidak boleh kosong.']);
    exit;
}

$kelasEsc   = mysqli_real_escape_string($conn, $kelas);
$noIndukEsc = mysqli_real_escape_string($conn, $noInduk);

// Auto-migrate kolom jabatan jika belum ada
$_chk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_siswa LIKE 'jabatan'");
if ($_chk && mysqli_num_rows($_chk) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_siswa ADD COLUMN jabatan ENUM('Siswa','Ketua Kelas') DEFAULT 'Siswa' AFTER kelas");
}

// 1. Reset semua siswa di kelas ini menjadi 'Siswa'
$reset = mysqli_query($conn,
    "UPDATE tbl_siswa SET jabatan = 'Siswa'
     WHERE kelas = '$kelasEsc'"
);
if (!$reset) {
    echo json_encode(['success' => false, 'message' => 'Gagal mereset jabatan: ' . mysqli_error($conn)]);
    exit;
}

// 2. Jika no_induk dipilih, naikkan jadi Ketua Kelas
if ($noInduk !== '') {
    // Validasi bahwa siswa tersebut benar-benar ada di kelas ini
    $cek = mysqli_query($conn,
        "SELECT no_induk FROM tbl_siswa
         WHERE no_induk = '$noIndukEsc' AND kelas = '$kelasEsc' AND status = 'Aktif'
         LIMIT 1"
    );
    if (!$cek || mysqli_num_rows($cek) === 0) {
        echo json_encode(['success' => false, 'message' => 'Siswa tidak ditemukan di kelas ' . htmlspecialchars($kelas) . '.']);
        exit;
    }

    $set = mysqli_query($conn,
        "UPDATE tbl_siswa SET jabatan = 'Ketua Kelas'
         WHERE no_induk = '$noIndukEsc'"
    );
    if (!$set) {
        echo json_encode(['success' => false, 'message' => 'Gagal menetapkan Ketua Kelas: ' . mysqli_error($conn)]);
        exit;
    }

    // Ambil nama
    $rNama = mysqli_query($conn, "SELECT nama_siswa FROM tbl_siswa WHERE no_induk = '$noIndukEsc' LIMIT 1");
    $dNama = mysqli_fetch_assoc($rNama);
    $nama  = $dNama['nama_siswa'] ?? $noInduk;

    echo json_encode([
        'success'  => true,
        'message'  => htmlspecialchars($nama) . ' berhasil ditetapkan sebagai Ketua Kelas ' . htmlspecialchars($kelas) . '.',
        'no_induk' => $noInduk,
        'nama'     => $nama,
        'kelas'    => $kelas,
    ]);
} else {
    // Hapus / kosongkan ketua kelas
    echo json_encode([
        'success' => true,
        'message' => 'Ketua Kelas untuk kelas ' . htmlspecialchars($kelas) . ' berhasil dihapus.',
        'no_induk' => '',
        'nama'     => '',
        'kelas'    => $kelas,
    ]);
}
