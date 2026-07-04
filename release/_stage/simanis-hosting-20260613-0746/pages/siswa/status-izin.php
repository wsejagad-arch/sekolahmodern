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

$nis        = $_SESSION['no_induk'];
$kelas      = $_SESSION['kelas'];
$nama_siswa = $_SESSION['nama_siswa'] ?? 'Siswa';
$nisEsc     = mysqli_real_escape_string($conn, $nis);

// ── Rentang histori: 1 tahun ke belakang ──────────────────────────────
$tglAwal  = date('Y-m-d', strtotime('-1 year'));
$tglAkhir = date('Y-m-d');

// ── Tab aktif ─────────────────────────────────────────────────────────
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'aktif';

// ── Ambil data ────────────────────────────────────────────────────────
$izinAktif   = [];
$izinHistori = [];
$tblAda      = false;

$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_izin_siswa'");
if ($tblCheck && mysqli_num_rows($tblCheck) > 0) {
    $tblAda = true;

    $idSekolah = mt_current_school_id();
    // Izin aktif (proses / belum selesai)
    $qAktif = mysqli_query($conn, "SELECT * FROM tbl_izin_siswa
        WHERE id_sekolah=$idSekolah AND no_induk_siswa='$nisEsc'
          AND status_izin NOT IN ('Disetujui Penuh','Ditolak')
        ORDER BY waktu_pengajuan DESC");
    if ($qAktif) while ($r = mysqli_fetch_assoc($qAktif)) $izinAktif[] = $r;

    // Histori 1 tahun (semua status)
    $qHistori = mysqli_query($conn, "SELECT * FROM tbl_izin_siswa
        WHERE id_sekolah=$idSekolah AND no_induk_siswa='$nisEsc'
          AND tanggal_izin BETWEEN '$tglAwal' AND '$tglAkhir'
        ORDER BY tanggal_izin DESC");
    if ($qHistori) while ($r = mysqli_fetch_assoc($qHistori)) $izinHistori[] = $r;
}

// ── Statistik 1 tahun ─────────────────────────────────────────────────
$statTotal     = count($izinHistori);
$statDisetujui = 0; $statDitolak = 0; $statProses = 0;
$statSakit     = 0; $statIzin    = 0; $statDispen = 0;
foreach ($izinHistori as $iz) {
    if ($iz['status_izin'] === 'Disetujui Penuh') $statDisetujui++;
    elseif ($iz['status_izin'] === 'Ditolak')     $statDitolak++;
    else                                           $statProses++;

    if ($iz['jenis_izin'] === 'Sakit')          $statSakit++;
    elseif ($iz['jenis_izin'] === 'Izin')       $statIzin++;
    elseif ($iz['jenis_izin'] === 'Dispensasi') $statDispen++;
}

// ── Helper: nama guru ─────────────────────────────────────────────────
function getNamaGuruByNip($conn, $nip) {
    if (empty($nip)) return '-';
    $ne = mysqli_real_escape_string($conn, $nip);
    $idSekolah = mt_current_school_id();
    $r  = mysqli_query($conn, "SELECT nama_guru FROM tbl_guru WHERE id_sekolah=$idSekolah AND no_induk='$ne' LIMIT 1");
    if ($r && mysqli_num_rows($r) > 0) return mysqli_fetch_assoc($r)['nama_guru'];
    return $nip;
}

// ── Helper: warna + ikon status ───────────────────────────────────────
function statusMeta($s) {
    switch ($s) {
        case 'Disetujui Penuh':    return ['bg-green-100 text-green-800',   'fa-check-circle',   'Disetujui Penuh'];
        case 'Ditolak':            return ['bg-red-100 text-red-800',       'fa-times-circle',   'Ditolak'];
        case 'Menunggu Guru BK':   return ['bg-purple-100 text-purple-800', 'fa-hourglass-half', 'Menunggu Guru BK'];
        case 'Menunggu Wali Kelas':
        case 'Menunggu Validasi':
        default:                   return ['bg-blue-100 text-blue-800',     'fa-hourglass-half', 'Menunggu Wali Kelas'];
    }
}

// ── Helper: step aktif (2 = WK, 3 = BK, 4 = selesai, -1 = ditolak) ──
function getStep($izin) {
    if ($izin['status_izin'] === 'Ditolak') return -1;
    if ($izin['status_izin'] === 'Disetujui Penuh') return 4;
    if ($izin['validasi_wali_kelas'] === 'Disetujui') return 3;
    return 2;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Perizinan – <?= htmlspecialchars($nama_siswa) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ── Step progress bar ─────────────────────────────── */
        .steps { display:flex; align-items:flex-start; position:relative; }
        .step  { flex:1; text-align:center; position:relative; }
        .step:not(:last-child)::after {
            content:''; position:absolute;
            top:18px; left:50%; width:100%; height:3px;
            background:#e5e7eb; z-index:0;
        }
        .step.done:not(:last-child)::after   { background:#16a34a; }
        .step.active:not(:last-child)::after { background:#e5e7eb; }
        .step.reject:not(:last-child)::after { background:#fca5a5; }
        .step-circle {
            width:38px; height:38px; border-radius:50%;
            display:inline-flex; align-items:center; justify-content:center;
            font-size:.85rem; font-weight:700; position:relative; z-index:1; transition:.2s;
        }
        .step.done   .step-circle { background:#16a34a; color:#fff; border:3px solid #16a34a; }
        .step.active .step-circle { background:#eff6ff; color:#1d4ed8; border:3px solid #2563eb; }
        .step.wait   .step-circle { background:#f3f4f6; color:#9ca3af; border:3px solid #d1d5db; }
        .step.reject .step-circle { background:#fee2e2; color:#dc2626; border:3px solid #dc2626; }
        .step-label  { font-size:.65rem; margin-top:5px; color:#6b7280; line-height:1.3; }
        .step.done   .step-label  { color:#16a34a; font-weight:600; }
        .step.active .step-label  { color:#2563eb; font-weight:600; }
        .step.reject .step-label  { color:#dc2626; font-weight:600; }
        /* ── Timeline histori ───────────────────────────────── */
        .hist-line { position:relative; }
        .hist-line::before {
            content:''; position:absolute;
            left:19px; top:0; bottom:0; width:2px;
            background:#e5e7eb;
        }
        /* ── Tabs ───────────────────────────────────────────── */
        .tab-btn { padding:8px 18px; border-radius:8px 8px 0 0; font-size:.85rem; font-weight:600; transition:.15s; cursor:pointer; text-decoration:none; display:inline-block; }
        .tab-btn.active { background:#2563eb; color:#fff; }
        .tab-btn:not(.active) { background:#f1f5f9; color:#64748b; }
        .tab-btn:not(.active):hover { background:#e2e8f0; }
        /* ── Card ───────────────────────────────────────────── */
        .izin-card { transition:box-shadow .2s; }
        .izin-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); }
        /* ── Line-clamp fallback ────────────────────────────── */
        .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
<div class="w-full max-w-3xl mx-auto px-3 py-5 sm:px-5">

    <!-- ─── Header ──────────────────────────────────────────────────── -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-5 mb-5 text-white shadow">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold"><i class="fas fa-clipboard-list mr-2"></i>Status Perizinan</h1>
                <p class="text-blue-100 text-sm mt-0.5"><?= htmlspecialchars($nama_siswa) ?> &middot; Kelas <?= htmlspecialchars($kelas) ?></p>
            </div>
            <a href="siswa.php" class="bg-white/20 hover:bg-white/30 text-white text-sm font-medium px-4 py-2 rounded-xl transition">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- ─── Statistik 1 Tahun ────────────────────────────────────────── -->
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-2">
        <div class="bg-white rounded-xl p-3 shadow-sm text-center">
            <p class="text-2xl font-bold text-gray-800"><?= $statTotal ?></p>
            <p class="text-xs text-gray-500 mt-0.5">Total</p>
        </div>
        <div class="bg-white rounded-xl p-3 shadow-sm text-center">
            <p class="text-2xl font-bold text-green-600"><?= $statDisetujui ?></p>
            <p class="text-xs text-gray-500 mt-0.5">Disetujui</p>
        </div>
        <div class="bg-white rounded-xl p-3 shadow-sm text-center">
            <p class="text-2xl font-bold text-red-500"><?= $statDitolak ?></p>
            <p class="text-xs text-gray-500 mt-0.5">Ditolak</p>
        </div>
        <div class="bg-white rounded-xl p-3 shadow-sm text-center">
            <p class="text-2xl font-bold text-orange-500"><?= $statSakit ?></p>
            <p class="text-xs text-gray-500 mt-0.5">Sakit</p>
        </div>
        <div class="bg-white rounded-xl p-3 shadow-sm text-center">
            <p class="text-2xl font-bold text-blue-600"><?= $statIzin ?></p>
            <p class="text-xs text-gray-500 mt-0.5">Izin</p>
        </div>
        <div class="bg-white rounded-xl p-3 shadow-sm text-center">
            <p class="text-2xl font-bold text-purple-600"><?= $statDispen ?></p>
            <p class="text-xs text-gray-500 mt-0.5">Dispensasi</p>
        </div>
    </div>
    <p class="text-xs text-gray-400 mb-4 pl-1">
        <i class="fas fa-calendar-alt mr-1"></i>Periode: <?= date('d/m/Y', strtotime($tglAwal)) ?> – <?= date('d/m/Y') ?>
    </p>

    <!-- ─── Tabs ─────────────────────────────────────────────────────── -->
    <div class="flex gap-1 mb-0">
        <a href="?tab=aktif" class="tab-btn <?= $tab === 'aktif' ? 'active' : '' ?>">
            <i class="fas fa-spinner mr-1"></i>Sedang Diproses
            <?php if (count($izinAktif) > 0): ?>
                <span class="ml-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full"><?= count($izinAktif) ?></span>
            <?php endif; ?>
        </a>
        <a href="?tab=histori" class="tab-btn <?= $tab === 'histori' ? 'active' : '' ?>">
            <i class="fas fa-history mr-1"></i>Histori 1 Tahun
        </a>
    </div>

    <!-- ─── Konten Tab ───────────────────────────────────────────────── -->
    <div class="bg-white rounded-b-2xl rounded-tr-2xl shadow p-4 sm:p-5">

    <?php if (!$tblAda): ?>
        <div class="text-center py-10 text-gray-400">
            <i class="fas fa-database text-4xl mb-3"></i>
            <p>Tabel izin belum tersedia. Hubungi administrator.</p>
        </div>

    <!-- ══════════════════════ TAB AKTIF ════════════════════════════════ -->
    <?php elseif ($tab === 'aktif'): ?>

        <?php if (empty($izinAktif)): ?>
        <div class="text-center py-12">
            <i class="fas fa-check-circle text-green-400 text-5xl mb-3"></i>
            <p class="text-gray-600 font-medium">Tidak ada pengajuan izin yang sedang diproses.</p>
            <a href="ajukan-izin.php" class="inline-flex items-center gap-1 mt-4 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-5 rounded-full text-sm transition">
                <i class="fas fa-plus"></i> Ajukan Izin Baru
            </a>
        </div>
        <?php else: ?>
        <p class="text-sm text-gray-500 mb-4">
            <i class="fas fa-info-circle mr-1 text-blue-400"></i>
            <?= count($izinAktif) ?> pengajuan sedang dalam proses validasi.
        </p>

        <?php foreach ($izinAktif as $izin):
            $step = getStep($izin);
            $rejected = ($step === -1);
            list($stColor, $stIcon, $stLabel) = statusMeta($izin['status_izin']);
            $jenisColor = 'bg-blue-100 text-blue-700';
            if ($izin['jenis_izin'] === 'Sakit')          $jenisColor = 'bg-orange-100 text-orange-700';
            elseif ($izin['jenis_izin'] === 'Dispensasi') $jenisColor = 'bg-purple-100 text-purple-700';

            $steps = [
                1 => ['label'=>'Diajukan',   'icon'=>'fa-paper-plane',
                      'done'=>true, 'active'=>false, 'reject'=>false],
                2 => ['label'=>'Wali Kelas', 'icon'=>'fa-chalkboard-teacher',
                      'done'  => ($izin['validasi_wali_kelas']==='Disetujui'),
                      'active'=> ($izin['validasi_wali_kelas']==='Menunggu' && !$rejected),
                      'reject'=> ($izin['validasi_wali_kelas']==='Ditolak')],
                3 => ['label'=>'Guru BK',    'icon'=>'fa-user-tie',
                      'done'  => ($izin['validasi_guru_bk']==='Disetujui'),
                      'active'=> ($izin['validasi_wali_kelas']==='Disetujui' && $izin['validasi_guru_bk']==='Menunggu' && !$rejected),
                      'reject'=> ($izin['validasi_guru_bk']==='Ditolak')],
                4 => ['label'=>'Selesai',    'icon'=>'fa-check-double',
                      'done'  => ($izin['status_izin']==='Disetujui Penuh'),
                      'active'=> false, 'reject'=>false],
            ];
        ?>
        <div class="izin-card border border-gray-200 rounded-xl mb-5 overflow-hidden">

            <!-- Card header -->
            <div class="flex flex-wrap items-center justify-between px-4 py-2.5 bg-slate-50 border-b gap-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $jenisColor ?>">
                        <?= htmlspecialchars($izin['jenis_izin']) ?>
                    </span>
                    <span class="text-gray-500 text-xs font-medium">
                        <?= date('d M Y', strtotime($izin['tanggal_izin'])) ?>
                    </span>
                </div>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $stColor ?>">
                    <i class="fas <?= $stIcon ?> text-xs"></i> <?= $stLabel ?>
                </span>
            </div>

            <!-- ── Progress Stepper ──────────────────────────────────── -->
            <div class="px-5 pt-4 pb-2">
                <div class="steps">
                    <?php foreach ($steps as $sNum => $sData):
                        if ($sData['reject'])        $cls = 'reject';
                        elseif ($sData['done'])      $cls = 'done';
                        elseif ($sData['active'])    $cls = 'active';
                        else                         $cls = 'wait';
                    ?>
                    <div class="step <?= $cls ?>">
                        <div class="step-circle mx-auto">
                            <?php if ($cls === 'done'):   ?><i class="fas fa-check text-sm"></i>
                            <?php elseif ($cls === 'reject'): ?><i class="fas fa-times text-sm"></i>
                            <?php elseif ($cls === 'active'): ?><i class="fas <?= $sData['icon'] ?> text-sm"></i>
                            <?php else: ?><span class="text-xs"><?= $sNum ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="step-label"><?= $sData['label'] ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Card body -->
            <div class="px-4 pb-3">

                <!-- Selfie -->
                <?php if (!empty($izin['foto_selfie'])): ?>
                <div class="flex items-center gap-3 mt-3 mb-2 bg-indigo-50 rounded-lg p-2">
                    <img src="../../uploads/izin/<?= htmlspecialchars($izin['foto_selfie']) ?>"
                         alt="Swafoto" class="w-12 h-12 object-cover rounded-lg border-2 border-indigo-300 cursor-pointer"
                         onclick="this.requestFullscreen?.()">
                    <div>
                        <p class="text-xs font-semibold text-indigo-700"><i class="fas fa-camera mr-1"></i>Swafoto Pengajuan</p>
                        <p class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($izin['waktu_pengajuan'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Alasan -->
                <p class="text-gray-700 text-sm mt-2 mb-3 leading-relaxed"><?= nl2br(htmlspecialchars($izin['detail_izin'])) ?></p>

                <!-- Detail validasi per tahap -->
                <div class="bg-gray-50 rounded-xl p-3 space-y-2.5">
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Riwayat Validasi</p>

                    <!-- Diajukan -->
                    <div class="flex items-center gap-2.5 text-xs">
                        <span class="w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user-graduate text-[10px]"></i>
                        </span>
                        <div class="flex-1 min-w-0">
                            <span class="font-medium text-gray-700">Pengajuan Siswa</span>
                            <span class="text-gray-400 ml-1">· <?= date('d/m/Y H:i', strtotime($izin['waktu_pengajuan'])) ?></span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-semibold flex-shrink-0">Terkirim</span>
                    </div>

                    <!-- Wali Kelas -->
                    <?php
                    $wkStatus = $izin['validasi_wali_kelas'];
                    $wkBadge  = $wkStatus==='Disetujui' ? 'bg-green-100 text-green-700' : ($wkStatus==='Ditolak' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');
                    $wkCircle = $wkStatus==='Disetujui' ? 'bg-green-500' : ($wkStatus==='Ditolak' ? 'bg-red-500' : 'bg-yellow-400');
                    $wkNama   = !empty($izin['validator_wali_kelas']) ? getNamaGuruByNip($conn, $izin['validator_wali_kelas']) : null;
                    $wkWaktu  = !empty($izin['waktu_validasi_wali_kelas']) ? date('d/m/Y H:i', strtotime($izin['waktu_validasi_wali_kelas'])) : null;
                    ?>
                    <div class="flex items-center gap-2.5 text-xs">
                        <span class="w-6 h-6 rounded-full <?= $wkCircle ?> text-white flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-chalkboard-teacher text-[10px]"></i>
                        </span>
                        <div class="flex-1 min-w-0 overflow-hidden">
                            <span class="font-medium text-gray-700">Wali Kelas</span>
                            <?php if ($wkNama): ?><span class="text-gray-400 ml-1">· <?= htmlspecialchars($wkNama) ?></span><?php endif; ?>
                            <?php if ($wkWaktu): ?><span class="text-gray-400 ml-1">· <?= $wkWaktu ?></span><?php endif; ?>
                        </div>
                        <span class="px-2 py-0.5 rounded-full <?= $wkBadge ?> font-semibold flex-shrink-0"><?= $wkStatus ?></span>
                    </div>

                    <!-- Guru BK -->
                    <?php
                    $bkStatus  = $izin['validasi_guru_bk'];
                    $bkBadge   = $bkStatus==='Disetujui' ? 'bg-green-100 text-green-700' : ($bkStatus==='Ditolak' ? 'bg-red-100 text-red-700' : 'bg-purple-100 text-purple-700');
                    $bkCircle  = $bkStatus==='Disetujui' ? 'bg-green-500' : ($bkStatus==='Ditolak' ? 'bg-red-500' : 'bg-purple-400');
                    $bkNama    = !empty($izin['validator_guru_bk']) ? getNamaGuruByNip($conn, $izin['validator_guru_bk']) : null;
                    $bkWaktu   = !empty($izin['waktu_validasi_guru_bk']) ? date('d/m/Y H:i', strtotime($izin['waktu_validasi_guru_bk'])) : null;
                    $bkOpacity = ($wkStatus === 'Disetujui' || $wkStatus === 'Ditolak') ? '' : 'opacity-40';
                    ?>
                    <div class="flex items-center gap-2.5 text-xs <?= $bkOpacity ?>">
                        <span class="w-6 h-6 rounded-full <?= $bkCircle ?> text-white flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user-tie text-[10px]"></i>
                        </span>
                        <div class="flex-1 min-w-0 overflow-hidden">
                            <span class="font-medium text-gray-700">Guru BK</span>
                            <?php if ($bkNama): ?><span class="text-gray-400 ml-1">· <?= htmlspecialchars($bkNama) ?></span><?php endif; ?>
                            <?php if ($bkWaktu): ?><span class="text-gray-400 ml-1">· <?= $bkWaktu ?></span><?php endif; ?>
                        </div>
                        <span class="px-2 py-0.5 rounded-full <?= $bkBadge ?> font-semibold flex-shrink-0">
                            <?= $wkStatus === 'Disetujui' ? $bkStatus : 'Menunggu WK' ?>
                        </span>
                    </div>
                </div>

                <!-- Catatan penolakan -->
                <?php if (!empty($izin['catatan_penolakan'])): ?>
                <div class="mt-3 bg-red-50 border border-red-200 rounded-lg p-3">
                    <p class="text-xs font-semibold text-red-600 mb-1">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Catatan Penolakan:
                    </p>
                    <p class="text-sm text-red-700"><?= nl2br(htmlspecialchars($izin['catatan_penolakan'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- Lokasi GPS -->
                <?php if (!empty($izin['lokasi_izin'])): ?>
                <p class="text-xs text-gray-400 mt-2">
                    <i class="fas fa-map-marker-alt mr-1 text-red-400"></i>
                    <a href="https://maps.google.com/?q=<?= urlencode($izin['lokasi_izin']) ?>" target="_blank"
                       class="text-blue-500 hover:underline">Lihat Lokasi GPS</a>
                </p>
                <?php endif; ?>
            </div>

            <!-- Card footer -->
            <div class="px-4 py-2 bg-slate-50 border-t">
                <p class="text-xs text-gray-400">
                    <i class="fas fa-clock mr-1"></i>Diajukan: <?= date('d/m/Y H:i', strtotime($izin['waktu_pengajuan'])) ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>


    <!-- ══════════════════════ TAB HISTORI ══════════════════════════════ -->
    <?php else: ?>

        <?php if (empty($izinHistori)): ?>
        <div class="text-center py-12">
            <i class="fas fa-inbox text-gray-300 text-5xl mb-3"></i>
            <p class="text-gray-500">Belum ada riwayat izin dalam 1 tahun terakhir.</p>
        </div>
        <?php else: ?>

        <!-- Bar chart frekuensi per bulan -->
        <?php
        $bulanData = [];
        for ($i = 11; $i >= 0; $i--) {
            $bKey = date('Y-m', strtotime("-$i month"));
            $bulanData[$bKey] = ['label'=>date('M y', strtotime("-$i month")), 'count'=>0, 'disetujui'=>0];
        }
        foreach ($izinHistori as $iz) {
            $bKey = date('Y-m', strtotime($iz['tanggal_izin']));
            if (isset($bulanData[$bKey])) {
                $bulanData[$bKey]['count']++;
                if ($iz['status_izin'] === 'Disetujui Penuh') $bulanData[$bKey]['disetujui']++;
            }
        }
        $maxCount = max(array_column($bulanData, 'count') ?: [1]);
        if ($maxCount < 1) $maxCount = 1;
        ?>
        <div class="mb-5 bg-slate-50 rounded-xl p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">
                <i class="fas fa-chart-bar mr-1 text-blue-400"></i>Frekuensi per Bulan
            </p>
            <div class="flex items-end gap-1.5 h-24 overflow-x-auto pb-1">
                <?php foreach ($bulanData as $bKey => $bd):
                    $barH     = round(($bd['count'] / $maxCount) * 100);
                    $setujuPct= $bd['count'] > 0 ? round(($bd['disetujui'] / $bd['count']) * 100) : 0;
                ?>
                <div class="flex flex-col items-center gap-0.5 flex-1 min-w-[28px]">
                    <span class="text-[9px] text-gray-500 font-medium leading-none"><?= $bd['count'] ?: '' ?></span>
                    <div class="w-full relative rounded overflow-hidden bg-gray-200" style="height:<?= max($barH, $bd['count']>0?8:2) ?>px; min-height:<?= $bd['count']>0?'8px':'2px' ?>">
                        <div class="absolute inset-0 bg-blue-300"></div>
                        <div class="absolute bottom-0 w-full bg-green-500" style="height:<?= $setujuPct ?>%"></div>
                    </div>
                    <span class="text-[8px] text-gray-400 text-center leading-tight"><?= $bd['label'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="flex gap-4 mt-2 text-[10px] text-gray-400">
                <span><span class="inline-block w-2.5 h-2.5 bg-blue-300 rounded-sm mr-1"></span>Diajukan</span>
                <span><span class="inline-block w-2.5 h-2.5 bg-green-500 rounded-sm mr-1"></span>Disetujui</span>
            </div>
        </div>

        <!-- Daftar histori – timeline -->
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-4">
            <i class="fas fa-history mr-1 text-blue-400"></i>Riwayat Lengkap (<?= count($izinHistori) ?> pengajuan)
        </p>

        <div class="hist-line space-y-0">
        <?php
        $prevBulan = '';
        foreach ($izinHistori as $iz):
            list($stColor, $stIcon, $stLabel) = statusMeta($iz['status_izin']);
            $jenisColor = 'text-blue-600';
            if ($iz['jenis_izin'] === 'Sakit')          $jenisColor = 'text-orange-500';
            elseif ($iz['jenis_izin'] === 'Dispensasi') $jenisColor = 'text-purple-600';

            $dotBg = 'bg-blue-500';
            $dotIco= 'fa-hourglass-half';
            if ($iz['status_izin'] === 'Disetujui Penuh') { $dotBg = 'bg-green-500'; $dotIco = 'fa-check'; }
            elseif ($iz['status_izin'] === 'Ditolak')     { $dotBg = 'bg-red-500';   $dotIco = 'fa-times'; }
            elseif ($iz['status_izin'] === 'Menunggu Guru BK') { $dotBg = 'bg-purple-500'; }

            $bulanIni = date('F Y', strtotime($iz['tanggal_izin']));
            if ($bulanIni !== $prevBulan):
                $prevBulan = $bulanIni;
        ?>
        <!-- Separator bulan -->
        <div class="flex items-center gap-2 pl-10 py-2 relative z-10">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider bg-slate-100 px-2 py-0.5 rounded"><?= $bulanIni ?></span>
            <div class="flex-1 h-px bg-gray-200"></div>
        </div>
        <?php endif; ?>

        <!-- Item timeline -->
        <div class="flex gap-3 pb-3 relative">
            <!-- Dot -->
            <div class="flex-shrink-0 w-10 flex justify-center pt-1">
                <div class="w-8 h-8 rounded-full flex items-center justify-center z-10 relative <?= $dotBg ?> text-white shadow-sm">
                    <i class="fas <?= $dotIco ?> text-xs"></i>
                </div>
            </div>

            <!-- Card -->
            <div class="flex-1 bg-white border border-gray-200 rounded-xl px-4 py-3 izin-card">
                <!-- Baris atas -->
                <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-sm text-gray-800"><?= date('d M Y', strtotime($iz['tanggal_izin'])) ?></span>
                        <span class="text-xs font-semibold <?= $jenisColor ?>">
                            <i class="fas fa-tag text-[10px] mr-0.5"></i><?= htmlspecialchars($iz['jenis_izin']) ?>
                        </span>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold <?= $stColor ?>">
                        <i class="fas <?= $stIcon ?> text-[10px]"></i> <?= $stLabel ?>
                    </span>
                </div>

                <!-- Alasan singkat -->
                <p class="text-xs text-gray-600 mb-2 line-clamp-2 leading-relaxed">
                    <?= htmlspecialchars(mb_substr($iz['detail_izin'], 0, 130)) ?><?= mb_strlen($iz['detail_izin']) > 130 ? '…' : '' ?>
                </p>

                <!-- Mini progress stepper -->
                <div class="flex items-center gap-1 mb-2">
                    <?php
                    $miniSteps = [
                        ['label'=>'Ajukan', 'done'=>true,  'reject'=>false],
                        ['label'=>'WK',     'done'=>($iz['validasi_wali_kelas']==='Disetujui'), 'reject'=>($iz['validasi_wali_kelas']==='Ditolak')],
                        ['label'=>'BK',     'done'=>($iz['validasi_guru_bk']==='Disetujui'),    'reject'=>($iz['validasi_guru_bk']==='Ditolak')],
                        ['label'=>'Selesai','done'=>($iz['status_izin']==='Disetujui Penuh'),   'reject'=>false],
                    ];
                    foreach ($miniSteps as $msIdx => $ms):
                        $mc = $ms['reject'] ? 'bg-red-400' : ($ms['done'] ? 'bg-green-500' : 'bg-gray-200');
                    ?>
                    <div class="flex flex-col items-center">
                        <div class="w-5 h-5 rounded-full <?= $mc ?> flex items-center justify-center text-[9px]">
                            <?php if ($ms['reject']): ?><i class="fas fa-times text-white"></i>
                            <?php elseif ($ms['done']): ?><i class="fas fa-check text-white"></i>
                            <?php else: ?><span class="text-gray-400 text-[8px]">·</span>
                            <?php endif; ?>
                        </div>
                        <span class="text-[8px] text-gray-400 mt-0.5 whitespace-nowrap"><?= $ms['label'] ?></span>
                    </div>
                    <?php if ($msIdx < count($miniSteps)-1):
                        $lineColor = ($ms['done'] && !$ms['reject']) ? 'bg-green-400' : 'bg-gray-200';
                    ?>
                    <div class="flex-1 h-0.5 mb-3 <?= $lineColor ?>"></div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Footer row: waktu + aksi -->
                <div class="flex flex-wrap items-center justify-between gap-2 mt-1">
                    <span class="text-[10px] text-gray-400">
                        <i class="fas fa-clock mr-0.5"></i><?= date('d/m/Y H:i', strtotime($iz['waktu_pengajuan'])) ?>
                    </span>
                    <div class="flex gap-2">
                        <?php if (!empty($iz['foto_selfie'])): ?>
                        <a href="../../uploads/izin/<?= htmlspecialchars($iz['foto_selfie']) ?>" target="_blank"
                           class="text-[11px] text-indigo-500 hover:underline flex items-center gap-0.5">
                            <i class="fas fa-camera"></i> Selfie
                        </a>
                        <?php endif; ?>
                        <?php if ($iz['status_izin'] === 'Disetujui Penuh'): ?>
                        <a href="../guru/cetak-tiket-izin.php?id=<?= $iz['id_izin'] ?>" target="_blank"
                           class="text-[11px] bg-blue-500 hover:bg-blue-600 text-white px-2.5 py-0.5 rounded-full flex items-center gap-0.5 transition">
                            <i class="fas fa-print text-[10px]"></i> Cetak
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Catatan penolakan -->
                <?php if (!empty($iz['catatan_penolakan'])): ?>
                <div class="mt-2 bg-red-50 border border-red-200 rounded-lg px-3 py-1.5 text-xs text-red-600">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <?= htmlspecialchars(mb_substr($iz['catatan_penolakan'], 0, 120)) ?><?= mb_strlen($iz['catatan_penolakan']) > 120 ? '…' : '' ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div><!-- /hist-line -->

        <?php endif; ?>
    <?php endif; ?>

    <!-- Tombol ajukan baru -->
    <?php if ($tblAda): ?>
    <div class="text-center border-t pt-4 mt-4">
        <a href="ajukan-izin.php"
           class="inline-flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-5 rounded-full text-sm transition shadow">
            <i class="fas fa-plus-circle"></i> Ajukan Izin Baru
        </a>
    </div>
    <?php endif; ?>

    </div><!-- /konten tab -->
</div><!-- /container -->
</body>
</html>
