<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["no_induk"])) {
    header("location: ../../index.php?haruslogin");
    exit;
} else if ($_SESSION['hak_akses'] != 3) {
    echo "<script>window.location='../../404.html';</script>";
    exit;
}

include "../../koneksi.php";
include "../../functions.php";
date_default_timezone_set('Asia/Jakarta');
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantAbsen = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_absen', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$tenantAbsenAlias = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_absen', 'id_sekolah') ? "a.id_sekolah={$tenantId}" : "1=1";
$tenantMapelAlias = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_mapel_ampu', 'id_sekolah') ? "ma.id_sekolah={$tenantId}" : "1=1";
$tenantGuruAlias = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_guru', 'id_sekolah') ? "g.id_sekolah={$tenantId}" : "1=1";
$tenantSiswa = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_siswa', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$tenantPresensi = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_presensi_setting', 'id_sekolah') ? "WHERE id_sekolah={$tenantId}" : "";
$tenantKonfirmasi = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_konfirmasi_kehadiran_guru', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";

// ── Pastikan kolom `sumber` ada di tbl_absen ─────────────────────────────────
$_colChk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_absen LIKE 'sumber'");
if ($_colChk && mysqli_num_rows($_colChk) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_absen ADD COLUMN sumber ENUM('guru','siswa') DEFAULT 'guru' AFTER status");
}
// Re-check after potential ALTER
$_colChk2   = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_absen LIKE 'sumber'");
$_hasSumber = ($_colChk2 && mysqli_num_rows($_colChk2) > 0);

// ── Auto-migrate: tambah kolom jabatan ke tbl_siswa ───────────────────────────
$_jabColChk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_siswa LIKE 'jabatan'");
if ($_jabColChk && mysqli_num_rows($_jabColChk) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_siswa ADD COLUMN jabatan ENUM('Siswa','Ketua Kelas') DEFAULT 'Siswa' AFTER kelas");
}

// ── Buat tabel konfirmasi kehadiran guru ─────────────────────────────────────
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_konfirmasi_kehadiran_guru (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  id_mapel INT NOT NULL,
  kelas VARCHAR(100) NOT NULL,
  no_induk_guru VARCHAR(25) NOT NULL,
  nama_guru VARCHAR(150) DEFAULT '',
  nama_mapel VARCHAR(100) DEFAULT '',
  no_induk_ketua VARCHAR(25) NOT NULL,
  nama_ketua VARCHAR(150) NOT NULL,
  status ENUM('Hadir','Telat','Izin','Tidak Hadir Tanpa Tugas','Tidak Hadir Ada Tugas') NOT NULL,
  catatan TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_konfirm (tanggal, id_mapel, kelas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Jadwal hari ini untuk kelas siswa (untuk fitur absen mandiri) ─────────────
$jadwalHariIni = [];
$hariEng = strtolower(date('l')); // e.g. 'monday'
$dayMap = [
    'monday' => 'Senin',
    'tuesday' => 'Selasa',
    'wednesday' => 'Rabu',
    'thursday' => 'Kamis',
    'friday' => 'Jumat',
    'saturday' => 'Sabtu',
    'sunday' => 'Minggu'
];
$hariIndo = $dayMap[$hariEng] ?? ucfirst($hariEng);
$kelasEscGlobal = mysqli_real_escape_string($conn, $_SESSION['kelas'] ?? '');
$hariEscGlobal  = mysqli_real_escape_string($conn, $hariIndo);
$tglHariIni     = date('Y-m-d');

$nisEscJadwal  = mysqli_real_escape_string($conn, $_SESSION['no_induk']);
$sumberSubq    = $_hasSumber
    ? "(SELECT sumber FROM tbl_absen a
        WHERE {$tenantAbsenAlias}
          AND a.no_induk = '$nisEscJadwal'
          AND a.tanggal = '$tglHariIni'
          AND a.id_mapel = ma.id_mapel
        LIMIT 1) AS sumber_absen,"
    : "'guru' AS sumber_absen,";

$qJadwal = mysqli_query(
    $conn,
    "SELECT ma.id_mapel, ma.nama_mapel, ma.jam_mulai, ma.jam_selesai, g.nama_guru,
            (SELECT status FROM tbl_absen a
             WHERE {$tenantAbsenAlias}
               AND a.no_induk = '$nisEscJadwal'
               AND a.tanggal = '$tglHariIni'
               AND a.id_mapel = ma.id_mapel
             LIMIT 1) AS status_absen,
            $sumberSubq
            NULL AS _pad
     FROM tbl_mapel_ampu ma
     LEFT JOIN tbl_guru g ON ma.no_induk = g.no_induk
     WHERE {$tenantMapelAlias} AND ({$tenantGuruAlias} OR g.no_induk IS NULL) AND ma.kelas = '$kelasEscGlobal' AND ma.hari = '$hariEscGlobal'
     ORDER BY ma.jam_mulai ASC"
);
if ($qJadwal) {
    while ($r = mysqli_fetch_assoc($qJadwal)) {
        $jadwalHariIni[] = $r;
    }
}

// ── Presensi setting (lokasi sekolah) ─────────────────────────────────────────
// Buat tabel jika belum ada
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_presensi_setting (
  id INT PRIMARY KEY AUTO_INCREMENT, lat DOUBLE, lng DOUBLE, radius_m INT,
  schedule TEXT, holidays TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$presensiSetting = ['lat' => -6.7656, 'lng' => 108.3891, 'radius_m' => 30000];
$tblSettingChk = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_presensi_setting'");
if ($tblSettingChk && mysqli_num_rows($tblSettingChk) > 0) {
    $qS = mysqli_query($conn, "SELECT lat, lng, radius_m FROM tbl_presensi_setting {$tenantPresensi} ORDER BY id DESC LIMIT 1");
    if ($qS && ($rS = mysqli_fetch_assoc($qS))) {
        if (!empty($rS['lat']))      $presensiSetting['lat']      = (float)$rS['lat'];
        if (!empty($rS['lng']))      $presensiSetting['lng']      = (float)$rS['lng'];
        if (!empty($rS['radius_m'])) $presensiSetting['radius_m'] = (int)$rS['radius_m'];
    } else {
        // Insert default SMA Negeri 1 Sumber
        $defSched = mysqli_real_escape_string($conn, json_encode(['monday' => ['in' => '07:00', 'out' => '15:00'], 'tuesday' => ['in' => '07:00', 'out' => '15:00'], 'wednesday' => ['in' => '07:00', 'out' => '15:00'], 'thursday' => ['in' => '07:00', 'out' => '15:00'], 'friday' => ['in' => '07:00', 'out' => '12:00']]));
        mysqli_query($conn, "INSERT INTO tbl_presensi_setting (lat,lng,radius_m,schedule,holidays) VALUES (-6.7656,108.3891,30000,'$defSched','')");
    }
}

$nis        = $_SESSION['no_induk'];
$kelas      = $_SESSION['kelas'];
$namaSiswa  = $_SESSION['nama_siswa'] ?? 'Siswa';

// ── Cek apakah siswa punya izin Disetujui Penuh untuk hari ini ──────────────
$izinHariIni = null;
$nisEscIzin = mysqli_real_escape_string($conn, $nis);
$qIzin = mysqli_query($conn, "SELECT id_izin, kategori_pengajuan, jenis_izin, detail_izin, validator_wali_kelas, validator_guru_bk 
                               FROM tbl_izin_siswa 
                               WHERE no_induk_siswa = '$nisEscIzin' 
                                 AND tanggal_izin = '$tglHariIni' 
                                 AND status_izin IN ('Disetujui Penuh','Disetujui') 
                               LIMIT 1");
if ($qIzin && mysqli_num_rows($qIzin) > 0) {
    $izinHariIni = mysqli_fetch_assoc($qIzin);
}

// ── Cek apakah siswa adalah ketua kelas ─────────────────────────────────────
$isKetuaKelas = false;
$_jabQ = @mysqli_query($conn, "SELECT jabatan FROM tbl_siswa WHERE {$tenantSiswa} AND no_induk='$nisEscJadwal' LIMIT 1");
if ($_jabQ && ($jr = mysqli_fetch_assoc($_jabQ))) {
    $isKetuaKelas = ($jr['jabatan'] === 'Ketua Kelas');
    $_SESSION['jabatan'] = $jr['jabatan'] ?? 'Siswa';
}

// ── Fetch konfirmasi hari ini (hanya ketua kelas) ─────────────────────────────
$konfirmasiHariIni = [];
if ($isKetuaKelas) {
    $qKonf = mysqli_query(
        $conn,
        "SELECT id_mapel, status, catatan, nama_guru, nama_mapel, updated_at
         FROM tbl_konfirmasi_kehadiran_guru
         WHERE {$tenantKonfirmasi} AND tanggal='$tglHariIni' AND kelas='$kelasEscGlobal'"
    );
    if ($qKonf) {
        while ($rk = mysqli_fetch_assoc($qKonf)) {
            $konfirmasiHariIni[(int)$rk['id_mapel']] = $rk;
        }
    }
}

// Pilihan bulan (dari GET, default bulan ini)
$bulan = $_GET['bulan'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
    $bulan = date('Y-m');
}

$nisEsc   = mysqli_real_escape_string($conn, $nis);
$klsEsc   = mysqli_real_escape_string($conn, $kelas);
$bulanEsc = mysqli_real_escape_string($conn, $bulan);

// ── Cek tabel ───────────────────────────────────────────────────────────────
$tblChk    = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_absen'");
$tblExists = ($tblChk && mysqli_num_rows($tblChk) > 0);

// ── Ringkasan ────────────────────────────────────────────────────────────────
$summary = ['Hadir' => 0, 'Ijin' => 0, 'Sakit' => 0, 'Dispen' => 0, 'Alpha' => 0];
if ($tblExists) {
    $qSum = mysqli_query(
        $conn,
        "SELECT
            SUM(CASE WHEN LOWER(TRIM(status)) IN ('hadir','telat') THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN LOWER(TRIM(status)) IN ('ijin','izin') THEN 1 ELSE 0 END) AS ijin,
            SUM(CASE WHEN LOWER(TRIM(status)) = 'sakit' THEN 1 ELSE 0 END) AS sakit,
            SUM(CASE WHEN LOWER(TRIM(status)) IN ('dispen','dispensasi') THEN 1 ELSE 0 END) AS dispen,
            SUM(CASE WHEN LOWER(TRIM(status)) = 'alpha' THEN 1 ELSE 0 END) AS alpha
         FROM tbl_absen
         WHERE {$tenantAbsen}
           AND no_induk='$nisEsc'
           AND kelas='$klsEsc'
           AND DATE_FORMAT(tanggal,'%Y-%m')='$bulanEsc'"
    );
    if ($qSum && ($row = mysqli_fetch_assoc($qSum))) {
        $summary['Hadir'] = (int)$row['hadir'];
        $summary['Ijin'] = (int)$row['ijin'];
        $summary['Sakit'] = (int)$row['sakit'];
        $summary['Dispen'] = (int)$row['dispen'];
        $summary['Alpha'] = (int)$row['alpha'];
    }
}
$totalPertemuan = array_sum($summary);

// ── Detail ───────────────────────────────────────────────────────────────────
$detailList = [];
if ($tblExists) {
    $qDet = mysqli_query(
        $conn,
        "SELECT a.tanggal, ma.nama_mapel, ma.jam_mulai, ma.jam_selesai, g.nama_guru, a.status
         FROM tbl_absen a
         LEFT JOIN tbl_mapel_ampu ma ON a.id_mapel = ma.id_mapel
         LEFT JOIN tbl_guru g ON ma.no_induk = g.no_induk
         WHERE {$tenantAbsenAlias}
           AND ({$tenantMapelAlias} OR ma.id_mapel IS NULL)
           AND ({$tenantGuruAlias} OR g.no_induk IS NULL)
           AND a.no_induk='$nisEsc'
           AND a.kelas='$klsEsc'
           AND DATE_FORMAT(a.tanggal,'%Y-%m')='$bulanEsc'
         ORDER BY a.tanggal ASC, ma.jam_mulai ASC"
    );
    if ($qDet) {
        while ($row = mysqli_fetch_assoc($qDet)) {
            $detailList[] = $row;
        }
    }
}

// ── Daftar bulan untuk dropdown ───────────────────────────────────────────────
$bulanList = [];
if ($tblExists) {
    $qBln = mysqli_query(
        $conn,
        "SELECT DISTINCT DATE_FORMAT(tanggal,'%Y-%m') AS bln
         FROM tbl_absen
         WHERE {$tenantAbsen} AND no_induk='$nisEsc' AND kelas='$klsEsc'
         ORDER BY bln DESC LIMIT 12"
    );
    if ($qBln) {
        while ($r = mysqli_fetch_assoc($qBln)) {
            $bulanList[] = $r['bln'];
        }
    }
}
if (!in_array(date('Y-m'), $bulanList)) {
    array_unshift($bulanList, date('Y-m'));
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function statusBadge($status)
{
    switch (strtolower($status)) {
        case 'hadir':
            return 'bg-green-100 text-green-700';
        case 'sakit':
            return 'bg-blue-100 text-blue-700';
        case 'ijin':
            return 'bg-yellow-100 text-yellow-700';
        case 'dispen':
            return 'bg-purple-100 text-purple-700';
        default:
            return 'bg-red-100 text-red-700';
    }
}

function summaryCard($label, $value, $colorBg, $colorText, $icon)
{
    echo "<div class='flex flex-col items-center justify-center $colorBg rounded-xl py-3 px-1'>
            <i class='fas $icon $colorText text-xl mb-1'></i>
            <span class='text-2xl font-bold $colorText'>$value</span>
            <span class='text-xs text-gray-500 mt-0.5'>$label</span>
          </div>";
}

function bulanIndo($ym)
{
    $bln = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    ];
    list($y, $m) = explode('-', $ym);
    return ($bln[$m] ?? $m) . ' ' . $y;
}

$pctHadir = ($totalPertemuan > 0) ? round(($summary['Hadir'] / $totalPertemuan) * 100) : 0;

function konfirmasiStatusBadge($status)
{
    switch ($status) {
        case 'Hadir':
            return 'bg-green-100 text-green-700';
        case 'Telat':
            return 'bg-yellow-100 text-yellow-700';
        case 'Izin':
            return 'bg-blue-100 text-blue-700';
        case 'Tidak Hadir Tanpa Tugas':
            return 'bg-red-200 text-red-800';
        case 'Tidak Hadir Ada Tugas':
            return 'bg-orange-100 text-orange-700';
        default:
            return 'bg-gray-100 text-gray-600';
    }
}
function konfirmasiButtonColor($opt)
{
    switch ($opt) {
        case 'Hadir':
            return 'border-green-300 text-green-700 hover:bg-green-50';
        case 'Telat':
            return 'border-yellow-300 text-yellow-700 hover:bg-yellow-50';
        case 'Izin':
            return 'border-blue-300 text-blue-700 hover:bg-blue-50';
        case 'Tidak Hadir Tanpa Tugas':
            return 'border-red-300 text-red-700 hover:bg-red-50';
        case 'Tidak Hadir Ada Tugas':
            return 'border-orange-300 text-orange-700 hover:bg-orange-50';
        default:
            return 'border-gray-300 text-gray-600 hover:bg-gray-50';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presensi Saya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- face-api.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <style>
        body {
            background-color: #f4f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 90px;
        }

        .header-bg {
            background: linear-gradient(135deg, #0052D4, #4364F7, #6FB1FC);
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
            padding-bottom: 40px;
            margin-bottom: -30px;
        }

        .card-shadow {
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .bottom-nav {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border-radius: 50px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            display: flex;
            padding: 10px 30px;
            gap: 40px;
            z-index: 50;
            width: max-content;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 11px;
            color: #9ca3af;
            text-decoration: none;
            gap: 4px;
            position: relative;
        }

        .nav-item.active {
            color: #3b82f6;
            font-weight: 600;
        }

        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -6px;
            width: 5px;
            height: 5px;
            background-color: #3b82f6;
            border-radius: 50%;
        }

        /* kamera modal */
        #cameraModal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, .7);
            align-items: center;
            justify-content: center;
        }

        #cameraModal.open {
            display: flex;
        }

        #videoWrap {
            position: relative;
            width: min(360px, 90vw);
            border-radius: 1rem;
            overflow: hidden;
        }

        #previewVideo {
            width: 100%;
            display: block;
            transform: scaleX(-1);
        }

        #faceCanvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            transform: scaleX(-1);
        }

        #ovalGuide {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 62%;
            padding-bottom: 80%;
            border-radius: 50%;
            border: 3px dashed rgba(255, 255, 255, .6);
            pointer-events: none;
        }

        .face-ok {
            border-color: #22c55e !important;
        }

        .face-fail {
            border-color: #ef4444 !important;
        }

        .badge-siswa {
            background: #ede9fe;
            color: #7c3aed;
        }

        .badge-guru {
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>

<body>
    <div class="header-bg pt-6 px-4 pb-12 sm:px-6">
        <div class="max-w-4xl mx-auto flex justify-between items-center relative z-10">
            <div class="flex items-center gap-3">
                <div class="bg-white p-2 rounded-full shadow-sm flex items-center justify-center" style="width:42px;height:42px;">
                    <i class="fas fa-fingerprint text-blue-500 text-xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white leading-tight">Presensi Saya</h1>
                    <p class="text-[11px] text-blue-100 mt-0.5">
                        <?= htmlspecialchars(strtoupper($namaSiswa)) ?> &middot; KELAS <?= htmlspecialchars(strtoupper($kelas)) ?>
                    </p>
                </div>
            </div>
            <a href="siswa.php" class="bg-white text-blue-600 hover:bg-gray-50 text-xs font-semibold px-4 py-2 rounded-full shadow-sm flex items-center transition-colors">
                <i class="fas fa-arrow-left mr-1.5"></i>Kembali
            </a>
        </div>
    </div>

    <div class="w-full max-w-4xl mx-auto px-4 sm:px-6 relative z-20">
        
        <!-- PRESENSI MANDIRI CARD -->
        <div class="mb-5 bg-white border <?= $izinHariIni ? 'border-green-200' : 'border-gray-100' ?> rounded-xl p-4 shadow-sm card-shadow">
            <h2 class="text-xs font-bold <?= $izinHariIni ? 'text-green-600' : 'text-blue-600' ?> uppercase tracking-wider mb-4 flex items-center">
                <i class="fas <?= $izinHariIni ? 'fa-check-circle' : 'fa-calendar-check' ?> mr-2 <?= $izinHariIni ? 'text-green-500' : 'text-blue-500' ?> text-sm"></i> 
                <?= $izinHariIni ? 'PRESENSI TIDAK DIPERLUKAN' : 'PRESENSI MANDIRI' ?> — <?= strtoupper(htmlspecialchars($hariIndo)) ?>, <?= strtoupper(tgl_indo($tglHariIni)) ?>
            </h2>

            <?php if ($izinHariIni): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 text-green-600 text-xl mt-0.5">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-green-900 mb-1">Anda memiliki izin yang sudah disetujui penuh</p>
                            <div class="text-xs text-green-800 space-y-0.5">
                                <p><strong>Kategori:</strong> <?= htmlspecialchars($izinHariIni['kategori_pengajuan']) ?></p>
                                <p><strong>Jenis:</strong> <?= htmlspecialchars($izinHariIni['jenis_izin']) ?></p>
                                <?php if (!empty($izinHariIni['detail_izin'])): ?>
                                <p><strong>Keterangan:</strong> <?= htmlspecialchars($izinHariIni['detail_izin']) ?></p>
                                <?php endif; ?>
                                <p class="mt-2"><strong>Persetujuan:</strong> Wali Kelas ✓ | Guru BK ✓</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif (empty($jadwalHariIni)): ?>
                <div class="text-center py-6">
                    <p class="text-sm text-gray-500"><i class="fas fa-bed mr-2 text-gray-300"></i>Tidak ada jadwal pelajaran hari ini.</p>
                </div>
            <?php endif; ?>
            
            <?php if (!$izinHariIni && !empty($jadwalHariIni)): ?>
                <?php
                $lastMapelId = null;
                $_maxSel = '';
                foreach ($jadwalHariIni as $_j) {
                    if (isset($_j['jam_selesai']) && strcmp($_j['jam_selesai'], $_maxSel) > 0) {
                        $_maxSel = $_j['jam_selesai'];
                        $lastMapelId = (int)$_j['id_mapel'];
                    }
                }
                ?>
                <div class="space-y-4" id="jadwalList">
                    <?php foreach ($jadwalHariIni as $idx => $jdw):
                        $jamMul = !empty($jdw['jam_mulai']) ? date('H:i', strtotime($jdw['jam_mulai'])) : '-';
                        $jamSel = !empty($jdw['jam_selesai']) ? date('H:i', strtotime($jdw['jam_selesai'])) : '';
                        $jam = ($jamSel && $jamSel !== '-') ? "$jamMul - $jamSel" : $jamMul;
                        $sudahAbsen = !empty($jdw['status_absen']);
                        $byGuru = ($jdw['sumber_absen'] ?? '') === 'guru';
                        $bySiswa = ($jdw['sumber_absen'] ?? '') === 'siswa';
                        $isLast = ((int)$jdw['id_mapel'] === (int)$lastMapelId);
                        
                        $nm = strtoupper($jdw['nama_mapel']);
                        if (strpos($nm, 'BAHASA') !== false) {
                            $iconBg = 'bg-cyan-400'; $iconClass = 'fa-comment-dots';
                        } elseif (strpos($nm, 'PKN') !== false || strpos($nm, 'PENDIDIKAN PANCASILA') !== false) {
                            $iconBg = 'bg-blue-400'; $iconClass = 'fa-book-open';
                        } else {
                            $iconBg = 'bg-blue-600'; $iconClass = 'fa-users';
                        }
                    ?>
                        <div class="flex items-center justify-between <?= $idx > 0 ? 'border-t border-gray-100 pt-4' : '' ?>">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="shrink-0 w-10 h-10 <?= $iconBg ?> text-white rounded-full flex items-center justify-center shadow-sm">
                                    <i class="fas <?= $iconClass ?> text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($nm) ?></p>
                                    <div class="flex items-center text-[11px] text-gray-500 mt-0.5 gap-3">
                                        <span><i class="fas fa-clock mr-1 text-gray-400"></i><?= $jam ?></span>
                                        <?php if (!empty($jdw['nama_guru'])): ?>
                                            <span class="truncate"><i class="fas fa-user mr-1 text-gray-400"></i><?= htmlspecialchars(strtoupper($jdw['nama_guru'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="shrink-0 flex flex-col items-end gap-1.5 pl-2">
                                <?php if ($sudahAbsen): ?>
                                    <span class="text-[11px] font-semibold px-3 py-1 rounded-full <?= $byGuru ? 'badge-guru' : ($bySiswa ? 'badge-siswa' : 'bg-green-100 text-green-700') ?>">
                                        <i class="fas fa-check-circle mr-1"></i> <?= htmlspecialchars(strtoupper($jdw['status_absen'])) ?>
                                    </span>
                                <?php else: ?>
                                    <button type="button"
                                        class="btn-absen-mandiri text-[12px] font-semibold px-4 py-1.5 rounded-lg text-white shadow-sm transition-all active:scale-95 flex items-center
                                        <?= $isLast ? 'bg-blue-800 hover:bg-blue-900' : 'bg-blue-500 hover:bg-blue-600' ?>"
                                        data-idmapel="<?= (int)$jdw['id_mapel'] ?>"
                                        data-mapel="<?= htmlspecialchars($jdw['nama_mapel'], ENT_QUOTES) ?>"
                                        data-jam-mulai="<?= htmlspecialchars($jdw['jam_mulai'] ?? '') ?>"
                                        data-jam-selesai="<?= htmlspecialchars($jdw['jam_selesai'] ?? '') ?>"
                                        data-is-last="<?= $isLast ? '1' : '0' ?>"
                                        onclick="openCameraForMapel(<?= (int)$jdw['id_mapel'] ?>, '<?= htmlspecialchars($jdw['nama_mapel'], ENT_QUOTES | ENT_HTML5) ?>', '<?= htmlspecialchars($jdw['jam_mulai'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($jdw['jam_selesai'] ?? '', ENT_QUOTES) ?>', <?= $isLast ? 'true' : 'false' ?>)">
                                        <i class="fas <?= $isLast ? 'fa-sign-out-alt' : 'fa-fingerprint' ?> mr-1.5"></i><?= $isLast ? 'Absen Pulang' : 'Absen' ?>
                                    </button>
                                <?php endif; ?>
                                <div id="time-ind-<?= (int)$jdw['id_mapel'] ?>" class="text-[10px] text-right font-medium"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── KONFIRMASI KEHADIRAN GURU (Ketua Kelas) ─────────────────────── -->
        <?php if ($isKetuaKelas): ?>
            <div class="mb-5 bg-white border border-amber-100 rounded-xl p-4 shadow-sm card-shadow">
                <h2 class="text-xs font-bold text-amber-600 uppercase tracking-wide mb-1 flex items-center gap-2">
                    <i class="fas fa-user-shield"></i> Konfirmasi Kehadiran Guru
                </h2>
                <p class="text-[11px] text-amber-600/70 mb-4">Konfirmasikan kehadiran guru mata pelajaran pada hari ini.</p>

                <?php if (empty($jadwalHariIni)): ?>
                    <p class="text-sm text-gray-500 italic"><i class="fas fa-calendar-times mr-1"></i>Tidak ada jadwal hari ini.</p>
                <?php else: ?>
                    <div class="space-y-3" id="konfirmasiList">
                        <?php foreach ($jadwalHariIni as $idx => $jdw):
                            $idMapelK = (int)$jdw['id_mapel'];
                            $konf     = $konfirmasiHariIni[$idMapelK] ?? null;
                            $jamMulK  = !empty($jdw['jam_mulai'])   ? date('H:i', strtotime($jdw['jam_mulai']))   : '-';
                            $jamSelK  = !empty($jdw['jam_selesai']) ? date('H:i', strtotime($jdw['jam_selesai'])) : '';
                            $jamK     = ($jamSelK && $jamSelK !== '-') ? "$jamMulK \u2013 $jamSelK" : $jamMulK;
                        ?>
                            <div class="<?= $idx > 0 ? 'border-t border-gray-100 pt-3' : '' ?>" id="konfirm-card-<?= $idMapelK ?>">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div>
                                        <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($jdw['nama_mapel']) ?></p>
                                        <p class="text-[11px] text-gray-500 mt-0.5">
                                            <i class="fas fa-chalkboard-teacher mr-1"></i><?= htmlspecialchars($jdw['nama_guru'] ?? '-') ?>
                                            &nbsp;&middot;&nbsp;<i class="fas fa-clock mr-1"></i><?= $jamK ?>
                                        </p>
                                    </div>
                                    <?php if ($konf): ?>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded <?= konfirmasiStatusBadge($konf['status']) ?> shrink-0" id="badge-<?= $idMapelK ?>">
                                            <?= htmlspecialchars($konf['status']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($konf): ?>
                                    <div class="flex justify-between items-center">
                                        <div class="text-[11px] text-gray-400"><i class="fas fa-check-circle text-green-400 mr-1"></i>Sudah dikonfirmasi
                                            <?php if (!empty($konf['catatan'])): ?> &middot; <em><?= htmlspecialchars($konf['catatan']) ?></em><?php endif; ?>
                                        </div>
                                        <button type="button" onclick="hapusKonfirmasi(<?= $idMapelK ?>)" class="text-[11px] text-red-400 hover:text-red-600 font-semibold">
                                            <i class="fas fa-undo mr-0.5"></i>Batal
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-wrap gap-1.5" id="konfirm-buttons-<?= $idMapelK ?>">
                                        <?php foreach (['Hadir', 'Telat', 'Izin', 'Tidak Hadir Tanpa Tugas', 'Tidak Hadir Ada Tugas'] as $optK): ?>
                                            <button type="button" onclick="kirimKonfirmasi(<?= $idMapelK ?>, this)"
                                                data-status="<?= htmlspecialchars($optK, ENT_QUOTES) ?>"
                                                class="text-[11px] font-semibold px-3 py-1 rounded-full border <?= konfirmasiButtonColor($optK) ?> hover:opacity-80 transition-all active:scale-95">
                                                <?= htmlspecialchars($optK) ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- FILTER BULAN & REKAP -->
        <div class="mb-24 flex flex-col gap-4">
            
            <form method="GET" action="presensi.php" class="bg-white rounded-xl p-3 shadow-sm border border-gray-100 flex items-center justify-between card-shadow">
                <label class="text-xs font-bold text-gray-600 flex items-center">
                    <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>Pilih Bulan
                </label>
                <div class="flex gap-2 items-center">
                    <select name="bulan"
                        class="border-none bg-gray-50 rounded-lg px-3 py-1.5 text-xs font-semibold text-gray-700
                               focus:outline-none focus:ring-2 focus:ring-blue-100 cursor-pointer">
                        <?php foreach ($bulanList as $bl): ?>
                            <option value="<?= $bl ?>" <?= ($bl === $bulan) ? 'selected' : '' ?>>
                                <?= strtoupper(bulanIndo($bl)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow-sm transition-all active:scale-95">
                        Tampilkan
                    </button>
                </div>
            </form>

            <?php if (!$tblExists): ?>
                <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 rounded-xl p-4 text-sm card-shadow">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Tabel absensi belum tersedia. Silakan hubungi administrator.
                </div>
            <?php else: ?>

                <!-- Rekap -->
                <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm card-shadow">
                    <h2 class="text-xs font-bold text-gray-600 uppercase tracking-wide mb-4 flex items-center">
                        <i class="fas fa-chart-pie mr-2 text-blue-500 text-sm"></i> REKAP — <?= strtoupper(bulanIndo($bulan)) ?>
                    </h2>
                    
                    <div class="mb-4 overflow-hidden">
                        <div class="grid grid-cols-4 gap-3 min-w-full">
                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 text-center min-w-0">
                                <div class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-green-100 text-green-700 mb-3 mx-auto">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="text-[10px] uppercase tracking-[0.2em] font-semibold text-slate-500 break-words">Hadir</div>
                                <div class="mt-2 text-xl font-extrabold text-slate-900"><?= $summary['Hadir'] ?></div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 text-center min-w-0">
                                <div class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-yellow-100 text-yellow-700 mb-3 mx-auto">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="text-[10px] uppercase tracking-[0.2em] font-semibold text-slate-500 break-words">Ijin</div>
                                <div class="mt-2 text-xl font-extrabold text-slate-900"><?= $summary['Ijin'] ?></div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 text-center min-w-0">
                                <div class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-red-100 text-red-700 mb-3 mx-auto">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                <div class="text-[10px] uppercase tracking-[0.2em] font-semibold text-slate-500 break-words">Alpha</div>
                                <div class="mt-2 text-xl font-extrabold text-slate-900"><?= $summary['Alpha'] ?></div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 text-center min-w-0">
                                <div class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-blue-100 text-blue-700 mb-3 mx-auto">
                                    <i class="fas fa-heartbeat"></i>
                                </div>
                                <div class="text-[10px] uppercase tracking-[0.2em] font-semibold text-slate-500 break-words">Sakit/Dispen</div>
                                <div class="mt-2 text-xl font-extrabold text-slate-900"><?= $summary['Sakit'] + $summary['Dispen'] ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($totalPertemuan > 0): ?>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="flex justify-between text-xs font-bold text-gray-600 mb-2">
                                <span>Tingkat Kehadiran</span>
                                <span class="<?= ($pctHadir >= 75) ? 'text-green-500' : 'text-red-500' ?>"><?= $pctHadir ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="progress-bar <?= ($pctHadir >= 75) ? 'bg-green-500' : 'bg-red-400' ?> h-2 rounded-full"
                                    style="width: <?= $pctHadir ?>%"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Detail Empty State or List -->
                <?php if (empty($detailList)): ?>
                    <div class="bg-white border border-gray-100 rounded-xl p-8 shadow-sm flex flex-col items-center justify-center card-shadow">
                        <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-clipboard-list text-blue-300 text-3xl"></i>
                        </div>
                        <p class="text-gray-500 font-medium text-sm text-center">Tidak ada data presensi<br>pada <?= bulanIndo($bulan) ?>.</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm card-shadow mb-8">
                        <div class="mb-3 text-xs font-bold text-gray-600 uppercase tracking-wide flex items-center">
                            <i class="fas fa-list-ul mr-2 text-blue-500"></i>Detail Pertemuan
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-gray-600 border-collapse">
                                <thead class="bg-gray-50 border-b text-[10px] uppercase text-gray-500 font-bold tracking-wider">
                                    <tr>
                                        <th class="py-2 px-3">No</th>
                                        <th class="py-2 px-3 whitespace-nowrap">Hari/Tgl</th>
                                        <th class="py-2 px-3">Mata Pelajaran</th>
                                        <th class="py-2 px-3 text-center">Status</th>
                                        <th class="py-2 px-3 text-center whitespace-nowrap">Ket Akhir</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php
                                    $rekapHarian = [];
                                    foreach ($detailList as $row) {
                                        $tgl = $row['tanggal'];
                                        if (!isset($rekapHarian[$tgl])) {
                                            $rekapHarian[$tgl] = [
                                                'mapel' => [],
                                                'status' => [],
                                                'last_status' => ''
                                            ];
                                        }
                                        $st = strtolower(trim($row['status']));
                                        $huruf = 'A';
                                        if ($st == 'hadir') $huruf = 'H';
                                        elseif ($st == 'ijin' || $st == 'izin') $huruf = 'I';
                                        elseif ($st == 'sakit') $huruf = 'S';
                                        elseif ($st == 'dispen' || $st == 'dispensasi') $huruf = 'D';
                                        else $huruf = strtoupper(substr($st, 0, 1));
                                        
                                        $rekapHarian[$tgl]['mapel'][] = $row['nama_mapel'];
                                        $rekapHarian[$tgl]['status'][] = $huruf;
                                        $rekapHarian[$tgl]['last_status'] = $huruf;
                                    }

                                    $no = 1;
                                    foreach ($rekapHarian as $tgl => $d):
                                        $counts = array_count_values($d['status']);
                                        $hadir = $counts['H'] ?? 0;
                                        $total = count($d['status']);
                                        $nonHadir = $total - $hadir;
                                        
                                        $ket = 'A';
                                        if ($hadir > $nonHadir) {
                                            $ket = 'H';
                                        } elseif ($hadir == $nonHadir) {
                                            $ket = $d['last_status'];
                                        } else {
                                            $maxC = 0; $maxS = 'A';
                                            foreach ($counts as $s => $c) {
                                                if ($s != 'H' && $c > $maxC) {
                                                    $maxC = $c; $maxS = $s;
                                                }
                                            }
                                            $ket = $maxS;
                                        }

                                        $hariTgl = function_exists('tgl_indo') ? tgl_indo($tgl) : date('d M Y', strtotime($tgl));
                                        $mapelStr = implode('<br>', $d['mapel']);
                                        $statusStr = implode('<br>', $d['status']);
                                        
                                        $badgeKet = 'bg-red-100 text-red-700';
                                        if ($ket == 'H') $badgeKet = 'bg-green-100 text-green-700';
                                        elseif (in_array($ket, ['I','S','D'])) $badgeKet = 'bg-yellow-100 text-yellow-700';
                                    ?>
                                    <tr>
                                        <td class="py-2 px-3 align-top font-bold text-gray-500"><?= $no++ ?></td>
                                        <td class="py-2 px-3 align-top whitespace-nowrap font-bold text-[11px] text-gray-800"><?= htmlspecialchars($hariTgl) ?></td>
                                        <td class="py-2 px-3 align-top text-[11px] leading-relaxed text-gray-600"><?= $mapelStr ?></td>
                                        <td class="py-2 px-3 align-top text-[11px] text-center font-black leading-relaxed"><?= $statusStr ?></td>
                                        <td class="py-2 px-3 align-middle text-center">
                                            <span class="inline-block px-3 py-1 rounded-full text-[11px] font-black <?= $badgeKet ?>">
                                                <?= $ket ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- BOTTOM NAV -->
    <?php include 'siswa_footer.php'; ?>


    <!-- ── MODAL KAMERA & WAJAH ───────────────────────────────────────── -->
    <div id="cameraModal" role="dialog" aria-modal="true" aria-labelledby="cameraTitle">
        <div class="bg-white rounded-2xl shadow-2xl w-min(380px,95vw) p-5 mx-4 max-w-sm w-full relative">
            <h3 id="cameraTitle" class="text-base font-bold text-gray-800 mb-1 text-center">
                <i class="fas fa-camera text-blue-500 mr-1"></i>Verifikasi Wajah
            </h3>
            <p class="text-xs text-gray-500 text-center mb-4" id="cameraSubtitle">Posisikan wajah Anda di dalam oval</p>

            <div id="videoWrap" class="mx-auto border-4 border-gray-100">
                <video id="previewVideo" autoplay playsinline muted></video>
                <canvas id="faceCanvas"></canvas>
                <div id="ovalGuide"></div>
            </div>

            <!-- Status deteksi -->
            <div id="faceStatus" class="mt-4 text-center text-sm font-medium text-gray-500 bg-gray-50 rounded-lg py-2">
                <i class="fas fa-spinner fa-spin mr-1 text-blue-500"></i>Memuat model deteksi…
            </div>

            <!-- Petunjuk -->
            <ul class="mt-3 text-[11px] text-gray-500 list-none space-y-1 bg-blue-50/50 p-3 rounded-lg border border-blue-100">
                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i>Hadapkan wajah ke kamera (depan)</li>
                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i>Pastikan cahaya cukup</li>
                <li class="flex items-center gap-2"><i class="fas fa-times-circle text-red-500"></i>Wajah miring/samping tidak terdeteksi</li>
            </ul>

            <!-- Tombol -->
            <div class="mt-4 flex gap-2">
                <button id="btnAbsenKonfirm"
                    disabled
                    class="flex-1 bg-blue-500 text-white text-sm font-bold py-2.5 rounded-xl opacity-50 cursor-not-allowed transition-all shadow-sm">
                    <i class="fas fa-check mr-1"></i>Konfirmasi
                </button>
                <button onclick="closeCamera()"
                    class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors shadow-sm">
                    Batal
                </button>
            </div>
            <div id="modalMsg" class="mt-3 text-xs text-center font-medium"></div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════
     FACE DETECTION + GEOLOCATION + PRESENSI MANDIRI
════════════════════════════════════════════════════════════════════════════ -->
    <script>
        (function() {

            // ── Konfigurasi ────────────────────────────────────────────────────────────────
            const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights';
            const API_ABSEN = '../../api/absen_siswa.php';
            const SCHOOL_LAT = <?= $presensiSetting['lat'] ?>;
            const SCHOOL_LNG = <?= $presensiSetting['lng'] ?>;
            const RADIUS_M = <?= $presensiSetting['radius_m'] ?>;

            // ── State ──────────────────────────────────────────────────────────────────────
            let currentMapelId = null;
            let currentMapelNm = '';
            let currentJamMulai = '';
            let currentJamSelesai = '';
            let currentIsLast = false;
            let stream = null;
            let detectionTimer = null;
            let modelsLoaded = false;
            let faceOk = false;
            let userCoords = null;
            let gpsOk = false;
            let videoEl, canvasEl, ovalEl, statusEl, btnKonfirm, modalMsg, cameraModal;

            // ── Parse jam string "HH:MM:SS" ke Date hari ini ─────────────────────────────
            function parseJam(jamStr) {
                if (!jamStr) return null;
                const hm = jamStr.split(':');
                const d = new Date();
                d.setHours(parseInt(hm[0], 10), parseInt(hm[1], 10), parseInt(hm[2] || '0', 10), 0);
                return d;
            }

            // ── Cek terlambat berdasarkan jam_mulai ───────────────────────────────────────
            function isTelat(jamMulaiStr) {
                const base = parseJam(jamMulaiStr);
                return base ? (new Date() > base) : false;
            }

            // ── Update indikator waktu di setiap kartu jadwal ─────────────────────────────
            function updateTimeIndicators() {
                const now = new Date();
                const batas = new Date();
                batas.setHours(19, 0, 0, 0);

                document.querySelectorAll('.btn-absen-mandiri').forEach(function(btn) {
                    const id = btn.getAttribute('data-idmapel');
                    const jm = btn.getAttribute('data-jam-mulai');
                    const js = btn.getAttribute('data-jam-selesai');
                    const isLast = btn.getAttribute('data-is-last') === '1';
                    const ind = document.getElementById('time-ind-' + id);
                    if (!ind) return;

                    if (isLast) {
                        // ── Jam terakhir: window mulai jam_selesai s/d 19:00 ─────────────
                        const selesai = parseJam(js);
                        if (!selesai) return;
                        if (now < selesai) {
                            btn.disabled = true;
                            btn.classList.add('opacity-50', 'cursor-not-allowed');
                            btn.classList.remove('hover:bg-blue-600');
                            const sisaMnt = Math.max(0, Math.round((selesai - now) / 60000));
                            ind.innerHTML = '<span style="color:#92400e;font-size:10px"><i class="fas fa-door-closed mr-1"></i>Pulang mulai ' +
                                (js.substring(0, 5)) + ' (' + sisaMnt + ' mnt lagi)</span>';
                        } else if (now > batas) {
                            btn.disabled = true;
                            btn.classList.add('opacity-50', 'cursor-not-allowed');
                            btn.classList.remove('hover:bg-blue-600');
                            ind.innerHTML = '<span style="color:#9ca3af;font-size:10px"><i class="fas fa-times-circle mr-1"></i>Waktu absen berakhir</span>';
                        } else {
                            btn.disabled = false;
                            btn.classList.remove('opacity-50', 'cursor-not-allowed');
                            btn.classList.add('hover:bg-blue-600');
                            ind.innerHTML = '<span style="color:#166534;font-size:10px"><i class="fas fa-sign-out-alt mr-1"></i>Sedia hingga 19:00</span>';
                        }
                    } else {
                        // ── Bukan jam terakhir: logika Hadir/Telat biasa ─────────────────
                        if (!jm) return;
                        if (isTelat(jm)) {
                            ind.innerHTML = '<span style="color:#b45309;font-size:10px"><i class="fas fa-exclamation-triangle mr-1"></i>Akan TERLAMBAT</span>';
                        } else {
                            const base = parseJam(jm);
                            const sisaMnt = Math.max(0, Math.round((base - now) / 60000));
                            if (sisaMnt <= 30) {
                                ind.innerHTML = '<span style="color:#166534;font-size:10px"><i class="fas fa-clock mr-1"></i>' + sisaMnt + ' mnt lagi tepat waktu</span>';
                            } else {
                                ind.innerHTML = '<span style="color:#16a34a;font-size:10px"><i class="fas fa-check-circle mr-1"></i>Tepat waktu</span>';
                            }
                        }
                    }
                });
            }

            // ── Haversine ──────────────────────────────────────────────────────────────────
            function haversine(lat1, lng1, lat2, lng2) {
                const R = 6371000;
                const toR = d => d * Math.PI / 180;
                const dLat = toR(lat2 - lat1),
                    dLng = toR(lng2 - lng1);
                const a = Math.sin(dLat / 2) ** 2 + Math.cos(toR(lat1)) * Math.cos(toR(lat2)) * Math.sin(dLng / 2) ** 2;
                return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            }

            // ── Load face-api models ───────────────────────────────────────────────────────
            async function ensureModels() {
                if (modelsLoaded) return;
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL);
                modelsLoaded = true;
            }

            // ── Front-face validator ───────────────────────────────────────────────────────
            function isFrontFace(detection) {
                if (!detection) return false;
                if (detection.detection.score < 0.55) return false;
                const lm = detection.landmarks;
                if (!lm) return false;
                const pts = lm.positions;

                // Avg center of left eye (pts 36-41) and right eye (pts 42-47)
                const avgPts = (arr) => arr.reduce((a, p) => ({
                    x: a.x + p.x,
                    y: a.y + p.y
                }), {
                    x: 0,
                    y: 0
                });
                const leftEyePts = pts.slice(36, 42);
                const rightEyePts = pts.slice(42, 48);
                const le = avgPts(leftEyePts);
                const re = avgPts(rightEyePts);
                le.x /= 6;
                le.y /= 6;
                re.x /= 6;
                re.y /= 6;

                const noseTip = pts[30]; // point 30 = nose tip
                const mouthTop = pts[51]; // top of mouth
                const box = detection.detection.box;

                // Eye separation must be at least 18% of face width
                const eyeDist = Math.abs(le.x - re.x);
                if (eyeDist < box.width * 0.18) return false;

                // Eyes y-diff must be < 25% of face height (both at similar height)
                const eyeYDiff = Math.abs(le.y - re.y);
                if (eyeYDiff > box.height * 0.25) return false;

                // Nose tip must be between the two eyes horizontally (within 10% tolerance)
                const eyeMinX = Math.min(le.x, re.x) - box.width * 0.10;
                const eyeMaxX = Math.max(le.x, re.x) + box.width * 0.10;
                if (noseTip.x < eyeMinX || noseTip.x > eyeMaxX) return false;

                // Mouth must be below eyes
                if (mouthTop.y < Math.max(le.y, re.y)) return false;

                return true;
            }

            // ── Detection loop ─────────────────────────────────────────────────────────────
            async function detectionLoop() {
                if (!videoEl || videoEl.paused || videoEl.ended) return;

                const options = new faceapi.TinyFaceDetectorOptions({
                    inputSize: 224,
                    scoreThreshold: 0.5
                });
                const detection = await faceapi.detectSingleFace(videoEl, options).withFaceLandmarks(true);

                const ctx = canvasEl.getContext('2d');
                canvasEl.width = videoEl.videoWidth;
                canvasEl.height = videoEl.videoHeight;
                ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);

                if (detection) {
                    // Draw landmarks
                    faceapi.draw.drawDetections(canvasEl, [detection.detection]);
                    faceapi.draw.drawFaceLandmarks(canvasEl, [detection]);

                    faceOk = isFrontFace(detection);
                    if (faceOk) {
                        ovalEl.className = 'face-ok';
                        statusEl.innerHTML = '<i class="fas fa-check-circle text-green-500 mr-1"></i><span class="text-green-700 font-semibold">Wajah terdeteksi! ' + (gpsOk ? 'Siap absen.' : 'Menunggu GPS…') + '</span>';
                    } else {
                        ovalEl.className = 'face-fail';
                        statusEl.innerHTML = '<i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i><span class="text-yellow-700">Hadapkan wajah ke depan (kedua mata harus terlihat)</span>';
                    }
                } else {
                    faceOk = false;
                    ovalEl.className = '';
                    statusEl.innerHTML = '<i class="fas fa-search text-blue-400 mr-1"></i>Mendeteksi wajah…';
                }

                updateKonfirmBtn();
                detectionTimer = setTimeout(detectionLoop, 300);
            }

            function updateKonfirmBtn() {
                if (faceOk && gpsOk) {
                    btnKonfirm.disabled = false;
                    btnKonfirm.classList.remove('opacity-50', 'cursor-not-allowed');
                    btnKonfirm.classList.add('hover:bg-blue-600');
                } else {
                    btnKonfirm.disabled = true;
                    btnKonfirm.classList.add('opacity-50', 'cursor-not-allowed');
                    btnKonfirm.classList.remove('hover:bg-blue-600');
                }
            }

            // ── GPS check ─────────────────────────────────────────────────────────────────
            function checkGPS() {
                gpsOk = false;
                updateKonfirmBtn();
                if (!navigator.geolocation) {
                    statusEl.innerHTML = '<span class="text-red-500"><i class="fas fa-times-circle mr-1"></i>Browser tidak mendukung GPS</span>';
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        userCoords = {
                            lat: pos.coords.latitude,
                            lng: pos.coords.longitude
                        };
                        const dist = haversine(userCoords.lat, userCoords.lng, SCHOOL_LAT, SCHOOL_LNG);
                        if (dist <= RADIUS_M) {
                            gpsOk = true;
                        } else {
                            const distKm = (dist / 1000).toFixed(1);
                            const radKm = (RADIUS_M / 1000).toFixed(1);
                            modalMsg.innerHTML = `<span class="text-red-500"><i class="fas fa-map-marker-alt mr-1"></i>Lokasi terlalu jauh: ${distKm} km (maks ${radKm} km)</span>`;
                        }
                        updateKonfirmBtn();
                    },
                    function(err) {
                        modalMsg.innerHTML = '<span class="text-red-400"><i class="fas fa-exclamation-triangle mr-1"></i>Gagal mendapatkan GPS: ' + err.message + '</span>';
                    }, {
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            }

            // ── Open camera ────────────────────────────────────────────────────────────────
            window.openCameraForMapel = async function(idMapel, namaMapel, jamMulai, jamSelesai, isLast) {
                currentMapelId = idMapel;
                currentMapelNm = namaMapel;
                currentJamMulai = jamMulai || '';
                currentJamSelesai = jamSelesai || '';
                currentIsLast = isLast || false;

                videoEl = document.getElementById('previewVideo');
                canvasEl = document.getElementById('faceCanvas');
                ovalEl = document.getElementById('ovalGuide');
                statusEl = document.getElementById('faceStatus');
                btnKonfirm = document.getElementById('btnAbsenKonfirm');
                modalMsg = document.getElementById('modalMsg');
                cameraModal = document.getElementById('cameraModal');

                document.getElementById('cameraSubtitle').textContent = 'Absen: ' + namaMapel;

                // ── Pesan status waktu di modal ───────────────────────────────────────────
                (function renderModalTimeMsg() {
                    const now = new Date();
                    const batas = new Date();
                    batas.setHours(19, 0, 0, 0);
                    if (isLast && jamSelesai) {
                        const selesai = parseJam(jamSelesai);
                        if (now < selesai) {
                            modalMsg.innerHTML = '<span style="color:#92400e;background:#fef3c7;border:1px solid #f59e0b;border-radius:.5rem;padding:4px 10px;display:inline-block;font-size:11px;">' +
                                '<i class="fas fa-door-closed mr-1"></i>Belum waktunya pulang — absen tersedia mulai <strong>' +
                                jamSelesai.substring(0, 5) + ' WIB</strong></span>';
                        } else if (now > batas) {
                            modalMsg.innerHTML = '<span style="color:#991b1b;background:#fee2e2;border:1px solid #fca5a5;border-radius:.5rem;padding:4px 10px;display:inline-block;font-size:11px;">' +
                                '<i class="fas fa-times-circle mr-1"></i>Waktu absen pulang sudah berakhir (batas <strong>19:00 WIB</strong>)</span>';
                        } else {
                            modalMsg.innerHTML = '<span style="color:#166534;background:#dcfce7;border:1px solid #86efac;border-radius:.5rem;padding:4px 10px;display:inline-block;font-size:11px;">' +
                                '<i class="fas fa-sign-out-alt mr-1"></i>Absen pulang — berlaku hingga <strong>19:00 WIB</strong></span>';
                        }
                    } else if (jamMulai) {
                        const late = isTelat(jamMulai);
                        if (late) {
                            modalMsg.innerHTML = '<span style="color:#b45309;background:#fef3c7;border:1px solid #f59e0b;border-radius:.5rem;padding:4px 10px;display:inline-block;font-size:11px;"><i class="fas fa-exclamation-triangle mr-1"></i>Waktu masuk sudah lewat — presensi akan dicatat <strong>TERLAMBAT</strong></span>';
                        } else {
                            modalMsg.innerHTML = '<span style="color:#166534;background:#dcfce7;border:1px solid #86efac;border-radius:.5rem;padding:4px 10px;display:inline-block;font-size:11px;"><i class="fas fa-check-circle mr-1"></i>Tepat waktu — presensi akan dicatat <strong>HADIR</strong></span>';
                        }
                    } else {
                        modalMsg.innerHTML = '';
                    }
                })();
                faceOk = false;
                gpsOk = false;
                userCoords = null;
                updateKonfirmBtn();
                cameraModal.classList.add('open');

                // Load models 
                statusEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Memuat model face detection…';
                try {
                    await ensureModels();
                } catch (e) {
                    statusEl.innerHTML = '<span class="text-red-500">Gagal memuat model: ' + e.message + '</span>';
                    return;
                }

                // Start camera
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: 'user',
                            width: 480,
                            height: 360
                        }
                    });
                    videoEl.srcObject = stream;
                    await videoEl.play();
                } catch (e) {
                    statusEl.innerHTML = '<span class="text-red-500"><i class="fas fa-times-circle mr-1"></i>Kamera tidak tersedia: ' + e.message + '</span>';
                    return;
                }

                // Start detection loop
                detectionTimer = setTimeout(detectionLoop, 500);

                // GPS simultaneously
                statusEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Mendeteksi wajah & GPS…';
                checkGPS();
            };

            // ── Close camera ───────────────────────────────────────────────────────────────
            window.closeCamera = function() {
                cameraModal.classList.remove('open');
                if (detectionTimer) clearTimeout(detectionTimer);
                if (stream) {
                    stream.getTracks().forEach(t => t.stop());
                    stream = null;
                }
                if (videoEl) {
                    videoEl.srcObject = null;
                }
                faceOk = false;
                gpsOk = false;
            };

            // ── Submit attendance ──────────────────────────────────────────────────────────
            document.addEventListener('DOMContentLoaded', function() {
                const btn = document.getElementById('btnAbsenKonfirm');
                if (!btn) return;
                // Update indikator saat halaman dimuat & setiap 30 detik
                updateTimeIndicators();
                setInterval(updateTimeIndicators, 30000);

                btn.addEventListener('click', async function() {
                    if (!faceOk || !gpsOk || !userCoords) return;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan…';
                    const modalMsg = document.getElementById('modalMsg');
                    modalMsg.innerHTML = '';

                    const fd = new FormData();
                    fd.append('id_mapel', currentMapelId);
                    fd.append('lat', userCoords.lat);
                    fd.append('lng', userCoords.lng);

                    try {
                        const res = await fetch(API_ABSEN, {
                            method: 'POST',
                            body: fd
                        });
                        const json = await res.json();
                        if (json.success) {
                            const statusLabel = json.status_absen || 'Hadir';
                            const isLate = (statusLabel === 'Telat');
                            const statusClass = isLate ?
                                'style="background:#fef3c7;color:#92400e"' :
                                'style="background:#dcfce7;color:#166534"';
                            modalMsg.innerHTML = '<span class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-1"></i>' + json.message + '</span>';
                            btn.innerHTML = '<i class="fas fa-check mr-1"></i>' + (isLate ? 'Terlambat!' : 'Berhasil!');
                            setTimeout(() => {
                                closeCamera();
                                location.reload();
                            }, 1500);
                        } else {
                            modalMsg.innerHTML = '<span class="text-red-500"><i class="fas fa-times-circle mr-1"></i>' + (json.message || 'Gagal') + '</span>';
                            btn.innerHTML = '<i class="fas fa-check mr-1"></i>Konfirmasi Absen';
                            btn.disabled = false;
                        }
                    } catch (e) {
                        modalMsg.innerHTML = '<span class="text-red-500">Error: ' + e.message + '</span>';
                        btn.innerHTML = '<i class="fas fa-check mr-1"></i>Konfirmasi Absen';
                        btn.disabled = false;
                    }
                });
            });

        })(); // IIFE end

        // ── Konfirmasi Kehadiran Guru (Ketua Kelas) ────────────────────────────────────
        (function() {
            const KONFIRM_API = '../../api/konfirmasi_kehadiran_guru.php';
            const TGL_HARI_INI = '<?= $tglHariIni ?>';

            window.kirimKonfirmasi = async function(idMapel, btnEl) {
                const status = btnEl.dataset.status;
                const buttons = document.getElementById('konfirm-buttons-' + idMapel);
                if (buttons) buttons.querySelectorAll('button').forEach(b => {
                    b.disabled = true;
                    b.classList.add('opacity-50');
                });

                const fd = new FormData();
                fd.append('id_mapel', idMapel);
                fd.append('status', status);
                fd.append('tanggal', TGL_HARI_INI);

                try {
                    const res = await fetch(KONFIRM_API, {
                        method: 'POST',
                        body: fd
                    });
                    const json = await res.json();
                    if (json.ok) {
                        location.reload();
                    } else {
                        alert('Gagal menyimpan konfirmasi: ' + (json.msg || 'Error tidak diketahui'));
                        if (buttons) buttons.querySelectorAll('button').forEach(b => {
                            b.disabled = false;
                            b.classList.remove('opacity-50');
                        });
                    }
                } catch (e) {
                    alert('Error jaringan: ' + e.message);
                    if (buttons) buttons.querySelectorAll('button').forEach(b => {
                        b.disabled = false;
                        b.classList.remove('opacity-50');
                    });
                }
            };

            window.hapusKonfirmasi = async function(idMapel) {
                if (!confirm('Hapus/batalkan konfirmasi ini?')) return;
                const fd = new FormData();
                fd.append('id_mapel', idMapel);
                fd.append('tanggal', TGL_HARI_INI);
                fd.append('hapus', '1');

                try {
                    const res = await fetch(KONFIRM_API, {
                        method: 'POST',
                        body: fd
                    });
                    const json = await res.json();
                    if (json.ok) {
                        location.reload();
                    } else {
                        alert('Gagal: ' + (json.msg || 'Error'));
                    }
                } catch (e) {
                    alert('Error jaringan: ' + e.message);
                }
            };
        })();
    </script>

</body>

</html>
