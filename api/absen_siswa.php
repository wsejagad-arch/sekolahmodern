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
require_once __DIR__ . '/../plugins/FileCache.php';

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
$cacheKeySetting = 'presensi_setting_' . $tenantId;
$rowSet = FileCache::get($cacheKeySetting);
if ($rowSet === false) {
    $qSet = @mysqli_query($conn, "SELECT lat, lng, radius_m FROM tbl_presensi_setting {$tenantPresensi} ORDER BY id DESC LIMIT 1");
    if ($qSet && ($row = mysqli_fetch_assoc($qSet))) {
        $rowSet = $row;
        FileCache::set($cacheKeySetting, $rowSet, 3600); // 1 jam cache
    } else {
        $rowSet = [];
    }
}

if (!empty($rowSet)) {
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

$cacheKeyMapel = 'mapel_' . $tenantId . '_' . $idMapelEsc . '_' . md5($kelasEsc);
$mapelRow = FileCache::get($cacheKeyMapel);

if ($mapelRow === false) {
    $qMapel = mysqli_query($conn,
        "SELECT id_mapel, nama_mapel, no_induk AS no_induk_guru, jam_mulai, jam_selesai, hari
         FROM tbl_mapel_ampu
         WHERE {$tenantMapel} AND id_mapel = '$idMapelEsc' AND kelas = '$kelasEsc' LIMIT 1"
    );
    if ($qMapel && mysqli_num_rows($qMapel) > 0) {
        $mapelRow = mysqli_fetch_assoc($qMapel);
        FileCache::set($cacheKeyMapel, $mapelRow, 600); // 10 menit
    }
}

if (empty($mapelRow)) {
    jsonOut(['success' => false, 'message' => 'Jadwal tidak valid untuk kelas Anda'], 400);
}
$namaMapel  = $mapelRow['nama_mapel'];
$nipGuru    = $mapelRow['no_induk_guru'];
// ── Tentukan setting jam dari database / app_config ───────────────────────────
$jamPulangMulai   = '15:30:00';
$jamPulangSelesai = '17:00:00';
$jamMasukBatas   = '07:00:00';

$qCfg = @mysqli_query($conn, "SELECT kunci, nilai FROM tbl_app_config WHERE kunci IN ('jam_pulang_mulai', 'jam_pulang_selesai', 'jam_masuk_batas')");
if ($qCfg) {
    while ($rC = mysqli_fetch_assoc($qCfg)) {
        if ($rC['kunci'] === 'jam_pulang_mulai')   $jamPulangMulai   = $rC['nilai'];
        if ($rC['kunci'] === 'jam_pulang_selesai') $jamPulangSelesai = $rC['nilai'];
        if ($rC['kunci'] === 'jam_masuk_batas')   $jamMasukBatas   = $rC['nilai'];
    }
}

$waktuIni    = date('H:i:s');
$tglHariIni  = date('Y-m-d');
$nisEsc      = mysqli_real_escape_string($conn, $nis);
$tglEsc      = mysqli_real_escape_string($conn, $tglHariIni);
$kelasEsc    = mysqli_real_escape_string($conn, $kelas);
$namaMapel   = $mapelRow['nama_mapel'];
$nipGuru     = $mapelRow['no_induk_guru'];

// ── Cek apakah ini Absen Pulang ───────────────────────────────────────────────
$jamSelesaiMapel = $mapelRow['jam_selesai'] ?? '00:00:00';
$hariMapelEsc    = mysqli_real_escape_string($conn, $mapelRow['hari'] ?? '');
$isLastPeriod    = false;

$qLastChk = mysqli_query($conn,
    "SELECT COUNT(*) AS cnt FROM tbl_mapel_ampu
     WHERE {$tenantMapel} AND kelas = '$kelasEsc' AND hari = '$hariMapelEsc'
       AND jam_selesai > '" . mysqli_real_escape_string($conn, $jamSelesaiMapel) . "'"
);
$rLastChk     = $qLastChk ? mysqli_fetch_assoc($qLastChk) : ['cnt' => 0];
$isLastPeriod = ((int)($rLastChk['cnt'] ?? 0) === 0);

$isAbsenPulang = (isset($_POST['tipe']) && $_POST['tipe'] === 'pulang') || $isLastPeriod;

if ($isAbsenPulang) {
    // 1. Syarat Wajib: Harus sudah presensi masuk pagi ini!
    $qCekMasuk = mysqli_query($conn, "SELECT id, status FROM tbl_absen WHERE {$tenantAbsen} AND no_induk='$nisEsc' AND tanggal='$tglEsc' AND status IN ('Hadir','H/T','Telat','T') LIMIT 1");
    if (!$qCekMasuk || mysqli_num_rows($qCekMasuk) === 0) {
        jsonOut([
            'success' => false,
            'message' => 'Anda belum melakukan presensi masuk pagi ini (status A/TAM). Presensi pulang tidak dapat dilakukan.',
            'kode'    => 'BELUM_ABSEN_MASUK'
        ], 400);
    }
    $rMasuk = mysqli_fetch_assoc($qCekMasuk);
    $statusMasukPagi = $rMasuk['status'] ?? 'Hadir';

    // 2. Cek Waktu Buka Absen Pulang (Default 15:30 WIB)
    if (strtotime($waktuIni) < strtotime($jamPulangMulai)) {
        $jamBukaFmt = date('H:i', strtotime($jamPulangMulai));
        $jamKunciFmt = date('H:i', strtotime($jamPulangSelesai));
        jsonOut([
            'success' => false,
            'message' => "Absen pulang belum dibuka. Absen pulang hanya dapat dilakukan pukul {$jamBukaFmt} - {$jamKunciFmt} WIB.",
            'kode'    => 'BELUM_WAKTU_PULANG'
        ], 400);
    }

    // 3. Cek Kunci Absen Pulang (Default 17:00 WIB)
    if (strtotime($waktuIni) > strtotime($jamPulangSelesai)) {
        $jamKunciFmt = date('H:i', strtotime($jamPulangSelesai));
        jsonOut([
            'success' => false,
            'message' => "Waktu absen pulang telah dikunci (maksimal pukul {$jamKunciFmt} WIB).",
            'kode'    => 'KUNCI_PULANG_SELESAI'
        ], 400);
    }

    // Jika masuk pagi Telat (T) -> H/T, Jika Hadir -> Hadir
    $statusAbsen = in_array($statusMasukPagi, ['Telat', 'T', 'H/T']) ? 'H/T' : 'Hadir';

} else {
    // Presensi Masuk (Pagi): Tepat waktu (Hadir) atau Terlambat (T)
    $jamLimit = !empty($mapelRow['jam_mulai']) ? $mapelRow['jam_mulai'] : $jamMasukBatas;
    $statusAbsen = (strtotime($waktuIni) > strtotime($jamLimit)) ? 'T' : 'Hadir';
}

$qCek = mysqli_query($conn,
    "SELECT id FROM tbl_absen
     WHERE {$tenantAbsen} AND no_induk = '$nisEsc' AND tanggal = '$tglEsc' AND kelas = '$kelasEsc' AND id_mapel = '$idMapelEsc'
     LIMIT 1"
);

if ($qCek && mysqli_num_rows($qCek) > 0) {
    $existing = mysqli_fetch_assoc($qCek);
    $idAbsen = $existing['id'];

    $updRes = mysqli_query($conn,
        "UPDATE tbl_absen SET status = '$statusAbsen', status_akhir = '$statusAbsen', no_induk_guru = '".mysqli_real_escape_string($conn, $nipGuru)."',
         sumber = 'siswa'
         WHERE {$tenantAbsen} AND id = '$idAbsen'"
    );
    if ($updRes) {
        if (function_exists('notif_trigger_presensi')) {
            notif_trigger_presensi($conn, $nisEsc, $statusAbsen);
        }
        jsonOut(['success' => true, 'message' => ($statusAbsen === 'T' ? '⚠️ Presensi dicatat: TERLAMBAT — ' : 'Presensi berhasil: ') . $namaMapel, 'status' => 'updated', 'status_absen' => $statusAbsen]);
    } else {
        jsonOut(['success' => false, 'message' => 'Gagal memperbarui presensi: ' . mysqli_error($conn)], 500);
    }
} else {
    // Insert baru
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
        jsonOut(['success' => true, 'message' => ($statusAbsen === 'T' ? '⚠️ Presensi dicatat TERLAMBAT: ' : 'Presensi berhasil: ') . $namaMapel, 'status' => 'inserted', 'status_absen' => $statusAbsen]);
    }
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
