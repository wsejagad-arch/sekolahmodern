<?php
/**
 * api/absen_siswa.php
 * Endpoint: POST - Simpan presensi mandiri siswa (face-verified + geolocation)
 *
 * POST params:
 *   id_mapel  - int, jadwal yang diabsen
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
include __DIR__ . '/../functions.php';
require_once __DIR__ . '/../notification_helper.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi database tidak tersedia']);
    exit;
}

date_default_timezone_set('Asia/Jakarta');

$nis     = $_SESSION['no_induk'];
$kelas   = $_SESSION['kelas'] ?? '';
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantPresensi = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_presensi_setting', 'id_sekolah') ? "WHERE id_sekolah={$tenantId}" : "";
$tenantMapel = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_mapel_ampu', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$tenantAbsen = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_absen', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$idMapel = isset($_POST['id_mapel']) ? (int)$_POST['id_mapel'] : 0;
$sisLat  = isset($_POST['lat'])  ? (float)$_POST['lat']  : null;
$sisLng  = isset($_POST['lng'])  ? (float)$_POST['lng']  : null;

function jsonOut($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    $out = json_encode($data);
    file_put_contents(__DIR__ . '/absen_log.txt', date('Y-m-d H:i:s') . " - " . print_r($_POST, true) . " - " . $out . "\n", FILE_APPEND);
    echo $out;
    exit;
}

// ── Validasi input ────────────────────────────────────────────────────────────
if ($idMapel <= 0) jsonOut(['success' => false, 'message' => 'id_mapel tidak valid'], 400);
if ($sisLat === null || $sisLng === null) jsonOut(['success' => false, 'message' => 'Koordinat GPS diperlukan'], 400);

// ── Pastikan kolom sumber ada di tbl_absen ────────────────────────────────────
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM tbl_absen LIKE 'sumber'");
if ($colCheck && mysqli_num_rows($colCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE tbl_absen ADD COLUMN sumber ENUM('guru','siswa') DEFAULT 'guru' AFTER status");
}

// ── Baca setting presensi (lokasi sekolah) ────────────────────────────────────
// Default: SMA Negeri 1 Sumber, Cirebon (-6.7656, 108.3891), radius 30 km
$defLat    = -6.7656;
$defLng    = 108.3891;
$defRadius = 30000; // meter

$settingLat    = $defLat;
$settingLng    = $defLng;
$settingRadius = $defRadius;

// Ambil setting lokasi (abaikan error jika tabel belum ada untuk menghindari Metadata Lock)
$qSet = @mysqli_query($conn, "SELECT lat, lng, radius_m FROM tbl_presensi_setting {$tenantPresensi} ORDER BY id DESC LIMIT 1");
if ($qSet && ($rowSet = mysqli_fetch_assoc($qSet))) {
    if (!empty($rowSet['lat']))      $settingLat    = (float)$rowSet['lat'];
    if (!empty($rowSet['lng']))      $settingLng    = (float)$rowSet['lng'];
    if (!empty($rowSet['radius_m'])) $settingRadius = (int)$rowSet['radius_m'];
}

// ── Hitung jarak (Haversine formula) ─────────────────────────────────────────
function haversineDistance($lat1, $lng1, $lat2, $lng2) {
    $R = 6371000; // meter
    $phi1 = deg2rad($lat1);
    $phi2 = deg2rad($lat2);
    $dPhi = deg2rad($lat2 - $lat1);
    $dLam = deg2rad($lng2 - $lng1);
    $a = sin($dPhi / 2) ** 2 + cos($phi1) * cos($phi2) * sin($dLam / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

$jarak = haversineDistance($sisLat, $sisLng, $settingLat, $settingLng);

if ($jarak > $settingRadius) {
    $jarakKm = round($jarak / 1000, 2);
    $radiusKm = round($settingRadius / 1000, 2);
    jsonOut([
        'success'  => false,
        'message'  => "Lokasi Anda terlalu jauh dari sekolah. Jarak: {$jarakKm} km (maks: {$radiusKm} km).",
        'jarak_m'  => round($jarak),
        'radius_m' => $settingRadius,
    ]);
}

// ── Validasi jadwal: id_mapel harus milik kelas siswa ─────────────────────────
$idMapelEsc = mysqli_real_escape_string($conn, $idMapel);
$kelasEsc   = mysqli_real_escape_string($conn, $kelas);

$qMapel = mysqli_query($conn,
    "SELECT id_mapel, nama_mapel, no_induk AS no_induk_guru, jam_mulai, jam_selesai, hari
     FROM tbl_mapel_ampu
     WHERE {$tenantMapel} AND id_mapel = '$idMapelEsc' AND kelas = '$kelasEsc' LIMIT 1"
);
if (!$qMapel || mysqli_num_rows($qMapel) === 0) {
    jsonOut(['success' => false, 'message' => 'Jadwal tidak valid untuk kelas Anda'], 400);
}
$mapelRow = mysqli_fetch_assoc($qMapel);
$namaMapel  = $mapelRow['nama_mapel'];
$nipGuru    = $mapelRow['no_induk_guru'];
// ── Tentukan status: Hadir atau Telat ─────────────────────────────────────────
$jamMulai    = $mapelRow['jam_mulai'] ?? '00:00:00';   // e.g. "07:00:00"
$waktuIni    = date('H:i:s');                          // jam server saat ini
$statusAbsen = (strtotime($waktuIni) > strtotime($jamMulai)) ? 'H/T' : 'Hadir';

// ── Validasi jam terakhir (absen pulang) ──────────────────────────────────────
// Cek apakah mapel ini adalah jam paling akhir untuk kelas tsb hari ini
$jamSelesaiMapel = $mapelRow['jam_selesai'] ?? '00:00:00';
$hariMapelEsc    = mysqli_real_escape_string($conn, $mapelRow['hari'] ?? '');

$qLastChk = mysqli_query($conn,
    "SELECT COUNT(*) AS cnt FROM tbl_mapel_ampu
     WHERE {$tenantMapel} AND kelas = '$kelasEsc' AND hari = '$hariMapelEsc'
       AND jam_selesai > '" . mysqli_real_escape_string($conn, $jamSelesaiMapel) . "'"
);
$rLastChk     = $qLastChk ? mysqli_fetch_assoc($qLastChk) : ['cnt' => 0];
$isLastPeriod = ((int)($rLastChk['cnt'] ?? 0) === 0);

if ($isLastPeriod) {
    // Absen pulang: hanya boleh antara jam_selesai s/d 23:59
    $batasMax = '23:59:59';
    if (strtotime($waktuIni) > strtotime($batasMax)) {
        jsonOut([
            'success' => false,
            'message' => 'Waktu absen pulang sudah berakhir (batas maksimal 23:59 WIB)',
            'kode'    => 'LEWAT_BATAS',
        ]);
    }
    // Jam terakhir: status selalu Hadir (tidak ada logika Telat untuk absen pulang)
    $statusAbsen = 'Hadir';
}
$tglHariIni = date('Y-m-d');
$nisEsc     = mysqli_real_escape_string($conn, $nis);
$tglEsc     = mysqli_real_escape_string($conn, $tglHariIni);

$qCek = mysqli_query($conn,
    "SELECT id FROM tbl_absen
     WHERE {$tenantAbsen} AND no_induk = '$nisEsc' AND tanggal = '$tglEsc' AND kelas = '$kelasEsc' AND id_mapel = '$idMapelEsc'
     LIMIT 1"
);

if ($qCek && mysqli_num_rows($qCek) > 0) {
    // Sudah absen → update jika sebelumnya oleh sistem/siswa (bukan diubah guru)
    $existing = mysqli_fetch_assoc($qCek);
    $idAbsen = $existing['id'];

    // Cek apakah sudah di-edit guru
    $colChkSumber = mysqli_query($conn, "SHOW COLUMNS FROM tbl_absen LIKE 'sumber'");
    if ($colChkSumber && mysqli_num_rows($colChkSumber) > 0) {
        $qSumber = mysqli_query($conn, "SELECT sumber FROM tbl_absen WHERE {$tenantAbsen} AND id = '$idAbsen'");
        $rSumber = mysqli_fetch_assoc($qSumber);
        if (($rSumber['sumber'] ?? 'siswa') === 'guru') {
            jsonOut([
                'success' => false,
                'message' => "Presensi ini sudah diverifikasi guru dan tidak dapat diubah lagi.",
            ]);
        }
    }

    $updRes = mysqli_query($conn,
        "UPDATE tbl_absen SET status = '$statusAbsen', status_akhir = '$statusAbsen', no_induk_guru = '".mysqli_real_escape_string($conn, $nipGuru)."',
         sumber = 'siswa'
         WHERE {$tenantAbsen} AND id = '$idAbsen'"
    );
    if ($updRes) {
        if (function_exists('notif_trigger_presensi')) {
            notif_trigger_presensi($conn, $nisEsc, $statusAbsen);
        }
        jsonOut(['success' => true, 'message' => ($statusAbsen === 'H/T' ? '⚠️ Presensi diperbarui: TERLAMBAT — ' : 'Presensi berhasil diperbarui: ') . $namaMapel, 'status' => 'updated', 'status_absen' => $statusAbsen]);
    } else {
        jsonOut(['success' => false, 'message' => 'Gagal memperbarui presensi: ' . mysqli_error($conn)], 500);
    }
} else {
    // Belum absen → insert baru
    $nipGuruEsc   = mysqli_real_escape_string($conn, $nipGuru);
    $namaMapelEsc = mysqli_real_escape_string($conn, $namaMapel);

    $insRes = mysqli_query($conn,
        "INSERT INTO tbl_absen (tanggal, kelas, id_mapel, no_induk_guru, no_induk, status, status_akhir, sumber)
         VALUES ('$tglEsc', '$kelasEsc', '$idMapelEsc', '$nipGuruEsc', '$nisEsc', '$statusAbsen', '$statusAbsen', 'siswa')"
    );
    if ($insRes) {
        if (function_exists('notif_trigger_presensi')) {
            notif_trigger_presensi($conn, $nisEsc, $statusAbsen);
        }
        jsonOut(['success' => true, 'message' => ($statusAbsen === 'H/T' ? '⚠️ Presensi tercatat TERLAMBAT: ' : 'Presensi berhasil: ') . $namaMapel, 'status' => 'inserted', 'status_absen' => $statusAbsen]);
    } else {
        // Mungkin kolom sumber belum ada, coba ulang tanpa sumber
        $insRes2 = mysqli_query($conn,
            "INSERT INTO tbl_absen (tanggal, kelas, id_mapel, no_induk_guru, no_induk, status, status_akhir)
             VALUES ('$tglEsc', '$kelasEsc', '$idMapelEsc', '$nipGuruEsc', '$nisEsc', '$statusAbsen', '$statusAbsen')"
        );
        if ($insRes2) {
            if (function_exists('notif_trigger_presensi')) {
                notif_trigger_presensi($conn, $nisEsc, $statusAbsen);
            }
            jsonOut(['success' => true, 'message' => ($statusAbsen === 'H/T' ? '⚠️ Presensi tercatat TERLAMBAT: ' : 'Presensi berhasil: ') . $namaMapel, 'status' => 'inserted', 'status_absen' => $statusAbsen]);
        } else {
            jsonOut(['success' => false, 'message' => 'Gagal menyimpan presensi: ' . mysqli_error($conn)], 500);
        }
    }
}
