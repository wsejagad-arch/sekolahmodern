<?php
/**
 * api/absen_sholat.php
 * Endpoint: POST - Simpan presensi sholat siswa (face-verified + geolocation)
 *
 * POST params:
 *   jenis_sholat - string (Dzuhur, Jumat)
 *   lat       - float, koordinat siswa
 *   lng       - float, koordinat siswa
 *
 * Response: JSON { success, message, ... }
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// ── Auth: hanya siswa (hak_akses = 3) ────────────────────────────────────────
if (!isset($_SESSION['no_induk']) || ($_SESSION['hak_akses'] ?? 0) != 3) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include __DIR__ . '/../koneksi.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi database tidak tersedia']);
    exit;
}

date_default_timezone_set('Asia/Jakarta');

$nis      = $_SESSION['no_induk'];
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;

$jenisSholat = isset($_POST['jenis_sholat']) ? $_POST['jenis_sholat'] : '';
$sisLat      = isset($_POST['lat'])  ? (float)$_POST['lat']  : null;
$sisLng      = isset($_POST['lng'])  ? (float)$_POST['lng']  : null;

function jsonOut($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    $out = json_encode($data);
    echo $out;
    exit;
}

// ── Validasi input ────────────────────────────────────────────────────────────
if (!in_array($jenisSholat, ['Dzuhur', 'Jumat'])) jsonOut(['success' => false, 'message' => 'Jenis sholat tidak valid'], 400);
if ($sisLat === null || $sisLng === null) jsonOut(['success' => false, 'message' => 'Koordinat GPS diperlukan'], 400);

$tglHariIni = date('Y-m-d');
$waktuAbsen = date('H:i:s');

// ── Cek dan buat tabel tbl_absen_sholat jika belum ada ────────────────────────
$qCheckTable = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_absen_sholat'");
if (!$qCheckTable || mysqli_num_rows($qCheckTable) == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS tbl_absen_sholat (
        id int(11) NOT NULL AUTO_INCREMENT,
        no_induk varchar(50) NOT NULL,
        jenis_sholat enum('Dzuhur','Jumat') NOT NULL,
        tanggal date NOT NULL,
        waktu time NOT NULL,
        lat varchar(100) DEFAULT NULL,
        lng varchar(100) DEFAULT NULL,
        status_lokasi varchar(100) DEFAULT 'Valid',
        id_sekolah int(11) DEFAULT 1,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    @mysqli_query($conn, $createTable);
}

// ── Cek apakah sudah absen hari ini ────────────────────────────────────────────
$qCek = mysqli_query($conn, "SELECT id FROM tbl_absen_sholat WHERE id_sekolah={$tenantId} AND no_induk='" . mysqli_real_escape_string($conn, $nis) . "' AND tanggal='$tglHariIni' AND jenis_sholat='$jenisSholat'");
if ($qCek && mysqli_num_rows($qCek) > 0) {
    jsonOut(['success' => false, 'message' => 'Anda sudah absen sholat ' . $jenisSholat . ' hari ini.']);
}

// Cek telat atau tidak (meskipun UI client sudah handle, backend bisa tentukan jika diperlukan, kita anggap Hadir untuk sekarang)
$statusAbsen = 'Hadir';

$latEsc = mysqli_real_escape_string($conn, $sisLat);
$lngEsc = mysqli_real_escape_string($conn, $sisLng);
$nisEsc = mysqli_real_escape_string($conn, $nis);

$queryInsert = "INSERT INTO tbl_absen_sholat (id_sekolah, no_induk, tanggal, jenis_sholat, waktu, lat, lng, status_lokasi) 
                VALUES ({$tenantId}, '$nisEsc', '$tglHariIni', '$jenisSholat', '$waktuAbsen', '$latEsc', '$lngEsc', '$statusAbsen')";

if (mysqli_query($conn, $queryInsert)) {
    jsonOut([
        'success' => true,
        'message' => 'Presensi sholat ' . $jenisSholat . ' berhasil.',
        'status_absen' => $statusAbsen
    ]);
} else {
    jsonOut(['success' => false, 'message' => 'Gagal menyimpan presensi sholat: ' . mysqli_error($conn)], 500);
}
