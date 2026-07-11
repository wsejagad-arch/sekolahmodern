<?php
/**
 * Cetak Kehadiran Siswa - Admin Panel
 * Menampilkan rekap kehadiran siswa per kelas, per mapel, dan rekap rentang bulan
 * Data diambil dari tbl_absen, tbl_siswa, tbl_kelas, tbl_mapel_ampu
 */
if (!isset($_SESSION["username"])) {
    header("location: index.php?haruslogin");
    exit;
}
if ($hakakses != 1 && $hakakses != 5) { ?>
    <script>window.location='404.html';</script>
<?php exit; }

include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');

// Helper: cari wali kelas berdasarkan nama kelas
function getWaliKelas($conn, $namaKelas) {
    $escKelas = mysqli_real_escape_string($conn, $namaKelas);
    $q = mysqli_query($conn, "SELECT g.nama_guru, g.nip_guru FROM tbl_wali_kelas wk 
        JOIN tbl_kelas k ON wk.id_kelas = k.id_kelas 
        JOIN tbl_guru g ON wk.nip_wali = g.no_induk 
        WHERE k.kelas = '$escKelas' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) return mysqli_fetch_assoc($q);
    return null;
}

// Ambil semua kelas
$allKelas = [];
$qK = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas ORDER BY kelas ASC");
while ($r = mysqli_fetch_assoc($qK)) { $allKelas[] = $r['kelas']; }

// Ambil semua mapel (untuk filter per mapel)
$allMapel = [];
$qM = mysqli_query($conn, "SELECT DISTINCT ma.id_mapel, ma.nama_mapel, ma.kelas, g.nama_guru 
    FROM tbl_mapel_ampu ma 
    JOIN tbl_guru g ON ma.no_induk = g.no_induk 
    ORDER BY ma.kelas, ma.nama_mapel");
while ($r = mysqli_fetch_assoc($qM)) { $allMapel[] = $r; }

$namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

// Helper weekend
function isWeekendAdmin($y, $m, $d){
    $w = date('w', strtotime(sprintf('%04d-%02d-%02d',$y,$m,$d)));
    return ($w == 0 || $w == 6);
}

// --- TAB AKTIF ---
$activeTab = $_GET['tab'] ?? 'kelas';

// ==================== TAB 1: KEHADIRAN PER KELAS ====================
$kelas = isset($_GET['kelas']) ? mysqli_real_escape_string($conn, $_GET['kelas']) : '';
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('n');
if ($bulan < 1 || $bulan > 12) $bulan = (int)date('n');

$siswaKelas = [];
$absenMapKelas = [];
$workdaysKelas = [];
$daysInMonthK = 0;

if (!empty($kelas)) {
    $daysInMonthK = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
    for ($d = 1; $d <= $daysInMonthK; $d++) {
        if (!isWeekendAdmin($tahun, $bulan, $d)) $workdaysKelas[] = $d;
    }
    $firstDay = sprintf('%04d-%02d-01', $tahun, $bulan);
    $lastDay = sprintf('%04d-%02d-%02d', $tahun, $bulan, $daysInMonthK);
    $ck_esc = mysqli_real_escape_string($conn, $kelas);

    $qs = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas='$ck_esc' AND (status='Aktif' OR status='' OR status IS NULL) ORDER BY nama_siswa ASC");
    while ($s = mysqli_fetch_assoc($qs)) $siswaKelas[] = $s;

    $qa = mysqli_query($conn, "SELECT no_induk, tanggal, 
        CASE 
            WHEN SUM(status='Alpha') > 0 THEN 'Alpha'
            WHEN SUM(status='Sakit') > 0 THEN 'Sakit'
            WHEN SUM(status='Ijin') > 0 OR SUM(status='Izin') > 0 THEN 'Ijin'
            WHEN SUM(status='Dispen') > 0 OR SUM(status='Dispensasi') > 0 THEN 'Dispen'
            WHEN SUM(status='Telat') > 0 THEN 'Telat'
            ELSE 'Hadir'
        END as status 
        FROM tbl_absen 
        WHERE kelas='$ck_esc' AND tanggal BETWEEN '$firstDay' AND '$lastDay' 
        GROUP BY no_induk, tanggal");
    if ($qa) {
        while ($a = mysqli_fetch_assoc($qa)) {
            $st = strtolower(trim($a['status']));
            $code = 'H';
            if ($st === 'sakit') $code = 'S';
            elseif ($st === 'ijin' || $st === 'izin') $code = 'I';
            elseif ($st === 'alpha') $code = 'A';
            elseif ($st === 'dispen' || $st === 'dispensasi') $code = 'D';
            elseif ($st === 'telat') $code = 'T';
            $absenMapKelas[$a['no_induk']][$a['tanggal']] = $code;
        }
    }
}

// ==================== TAB 2: KEHADIRAN PER MAPEL ====================
$mapelId = isset($_GET['id_mapel']) ? mysqli_real_escape_string($conn, $_GET['id_mapel']) : '';
$bulanMapel = isset($_GET['bulan_mapel']) ? (int)$_GET['bulan_mapel'] : (int)date('n');
$tahunMapel = isset($_GET['tahun_mapel']) ? (int)$_GET['tahun_mapel'] : (int)date('Y');

$siswaMapel = [];
$absenMapMapel = [];
$workdaysMapel = [];
$infoMapel = null;

if (!empty($mapelId)) {
    // Ambil info mapel
    $qInfo = mysqli_query($conn, "SELECT ma.*, g.nama_guru FROM tbl_mapel_ampu ma JOIN tbl_guru g ON ma.no_induk=g.no_induk WHERE ma.id_mapel='$mapelId' LIMIT 1");
    $infoMapel = mysqli_fetch_assoc($qInfo);

    if ($infoMapel) {
        $kelasMapel = $infoMapel['kelas'];
        $daysInMonthM = cal_days_in_month(CAL_GREGORIAN, $bulanMapel, $tahunMapel);
        for ($d = 1; $d <= $daysInMonthM; $d++) {
            if (!isWeekendAdmin($tahunMapel, $bulanMapel, $d)) $workdaysMapel[] = $d;
        }
        $firstDayM = sprintf('%04d-%02d-01', $tahunMapel, $bulanMapel);
        $lastDayM = sprintf('%04d-%02d-%02d', $tahunMapel, $bulanMapel, $daysInMonthM);
        $kelasEscM = mysqli_real_escape_string($conn, $kelasMapel);

        $qs = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas='$kelasEscM' AND (status='Aktif' OR status='' OR status IS NULL) ORDER BY nama_siswa ASC");
        while ($s = mysqli_fetch_assoc($qs)) $siswaMapel[] = $s;

        $qa = mysqli_query($conn, "SELECT no_induk, tanggal, status FROM tbl_absen WHERE kelas='$kelasEscM' AND id_mapel='$mapelId' AND tanggal BETWEEN '$firstDayM' AND '$lastDayM'");
        if ($qa) {
            while ($a = mysqli_fetch_assoc($qa)) {
                $st = strtolower(trim($a['status']));
                $code = 'H';
                if ($st === 'sakit') $code = 'S';
                elseif ($st === 'ijin' || $st === 'izin') $code = 'I';
                elseif ($st === 'alpha') $code = 'A';
                elseif ($st === 'dispen' || $st === 'dispensasi') $code = 'D';
                elseif ($st === 'telat') $code = 'T';
                $absenMapMapel[$a['no_induk']][$a['tanggal']] = $code;
            }
        }
    }
}

// ==================== TAB 3: REKAP RENTANG BULAN ====================
$kelasRentang = isset($_GET['kelas_rentang']) ? mysqli_real_escape_string($conn, $_GET['kelas_rentang']) : '';
$bulanDari = isset($_GET['bulan_dari']) ? (int)$_GET['bulan_dari'] : 7; // default Juli
$tahunDari = isset($_GET['tahun_dari']) ? (int)$_GET['tahun_dari'] : (int)date('Y');
$bulanSampai = isset($_GET['bulan_sampai']) ? (int)$_GET['bulan_sampai'] : (int)date('n');
$tahunSampai = isset($_GET['tahun_sampai']) ? (int)$_GET['tahun_sampai'] : (int)date('Y');

$siswaRentang = [];
$rekapRentang = [];

if (!empty($kelasRentang)) {
    $krEsc = mysqli_real_escape_string($conn, $kelasRentang);
    $qs = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas='$krEsc' AND (status='Aktif' OR status='' OR status IS NULL) ORDER BY nama_siswa ASC");
    while ($s = mysqli_fetch_assoc($qs)) $siswaRentang[] = $s;

    $tglDari = sprintf('%04d-%02d-01', $tahunDari, $bulanDari);
    $lastDayR = cal_days_in_month(CAL_GREGORIAN, $bulanSampai, $tahunSampai);
    $tglSampai = sprintf('%04d-%02d-%02d', $tahunSampai, $bulanSampai, $lastDayR);

    // Pre-load rekap for all students in the class with daily status priority
    $rekapMap = [];
    $qRekap = mysqli_query($conn, "SELECT no_induk, status, COUNT(*) as jml FROM (
        SELECT no_induk, tanggal, 
        CASE 
            WHEN SUM(status='Alpha') > 0 THEN 'Alpha'
            WHEN SUM(status='Sakit') > 0 THEN 'Sakit'
            WHEN SUM(status='Ijin') > 0 OR SUM(status='Izin') > 0 THEN 'Ijin'
            WHEN SUM(status='Dispen') > 0 OR SUM(status='Dispensasi') > 0 THEN 'Dispen'
            WHEN SUM(status='Telat') > 0 THEN 'Telat'
            ELSE 'Hadir'
        END as status
        FROM tbl_absen 
        WHERE kelas='$krEsc' AND tanggal BETWEEN '$tglDari' AND '$tglSampai'
        GROUP BY no_induk, tanggal
    ) as daily_status 
    GROUP BY no_induk, status");

    if ($qRekap) {
        while ($r = mysqli_fetch_assoc($qRekap)) {
            $st = strtolower(trim($r['status']));
            $code = 'H';
            if ($st === 'sakit') $code = 'S';
            elseif ($st === 'ijin' || $st === 'izin') $code = 'I';
            elseif ($st === 'alpha') $code = 'A';
            elseif ($st === 'dispen' || $st === 'dispensasi') $code = 'D';
            elseif ($st === 'telat') $code = 'T';
            $rekapMap[$r['no_induk']][$code] = (int)$r['jml'];
        }
    }

    // Pre-load telat from tbl_telat for all students in class
    $telatMap = [];
    $qT = mysqli_query($conn, "SELECT no_induk, COUNT(*) as tot FROM tbl_telat WHERE kelas='$krEsc' AND tanggal BETWEEN '$tglDari' AND '$tglSampai' GROUP BY no_induk");
    if ($qT) {
        while ($rT = mysqli_fetch_assoc($qT)) $telatMap[$rT['no_induk']] = (int)$rT['tot'];
    }

    foreach ($siswaRentang as $sw) {
        $nis = $sw['no_induk'];
        $rekap = [
            'H' => $rekapMap[$nis]['H'] ?? 0,
            'S' => $rekapMap[$nis]['S'] ?? 0,
            'I' => $rekapMap[$nis]['I'] ?? 0,
            'A' => $rekapMap[$nis]['A'] ?? 0,
            'D' => $rekapMap[$nis]['D'] ?? 0,
            'T' => ($rekapMap[$nis]['T'] ?? 0) + ($telatMap[$nis] ?? 0)
        ];
        $rekapRentang[] = array_merge($sw, $rekap);
    }
}

// Ambil data lembaga untuk cetak
$lembagaCetak = data_lembaga();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-clipboard-list"></i> Cetak Kehadiran Siswa</h1>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" id="cetakTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab=='kelas'?'active':'' ?>" href="home.php?page=cetak-kehadiran-siswa&tab=kelas<?= !empty($kelas)?"&kelas=".urlencode($kelas)."&bulan=$bulan&tahun=$tahun":"" ?>">
                <i class="fas fa-users"></i> Per Kelas
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab=='mapel'?'active':'' ?>" href="home.php?page=cetak-kehadiran-siswa&tab=mapel<?= !empty($mapelId)?"&id_mapel=$mapelId&bulan_mapel=$bulanMapel&tahun_mapel=$tahunMapel":"" ?>">
                <i class="fas fa-book"></i> Per Mata Pelajaran
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab=='rentang'?'active':'' ?>" href="home.php?page=cetak-kehadiran-siswa&tab=rentang<?= !empty($kelasRentang)?"&kelas_rentang=".urlencode($kelasRentang)."&bulan_dari=$bulanDari&tahun_dari=$tahunDari&bulan_sampai=$bulanSampai&tahun_sampai=$tahunSampai":"" ?>">
                <i class="fas fa-calendar-alt"></i> Rekap Rentang Bulan
            </a>
        </li>
    </ul>

    <!-- ==================== TAB 1: PER KELAS ==================== -->
    <?php if ($activeTab == 'kelas'): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-filter"></i> Filter Kehadiran Per Kelas</h6>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="cetak-kehadiran-siswa">
                <input type="hidden" name="tab" value="kelas">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Kelas</label>
                    <select name="kelas" class="form-control" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach($allKelas as $k): ?>
                            <option value="<?= htmlspecialchars($k) ?>" <?= $kelas==$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Bulan</label>
                    <select name="bulan" class="form-control">
                        <?php for($b=1;$b<=12;$b++): ?>
                            <option value="<?= $b ?>" <?= $bulan==$b?'selected':'' ?>><?= $namaBulan[$b] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Tahun</label>
                    <input type="number" name="tahun" class="form-control" value="<?= $tahun ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($kelas) && !empty($siswaKelas)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Kehadiran Kelas <?= htmlspecialchars($kelas) ?> — <?= $namaBulan[$bulan] ?> <?= $tahun ?></h6>
            <button class="btn btn-success btn-sm" onclick="cetakTabel('printAreaKelas')"><i class="fas fa-print"></i> Cetak</button>
        </div>
        <div class="card-body">
                <div id="printAreaKelas">
                    <!-- Professional Header (Cop Surat) -->
                    <div class="d-none d-print-block" style="border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px;">
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="width: 15%; text-align: center; border: none;">
                                    <?php if (!empty($lembagaCetak['logo'])): ?>
                                        <img src="img/<?= htmlspecialchars($lembagaCetak['logo']) ?>" style="width: 80px; height: auto;">
                                    <?php endif; ?>
                                </td>
                                <td style="width: 85%; text-align: center; border: none;">
                                    <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: #1a3c6e;"><?= htmlspecialchars($lembagaCetak['nmsekolah'] ?? '') ?></h2>
                                    <p style="margin: 0; font-size: 14px; font-weight: 600;"><?= htmlspecialchars($lembagaCetak['alamat'] ?? '') ?></p>

                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="text-center mb-4 d-none d-print-block">
                        <h4 style="font-weight: 900; text-decoration: underline; margin-bottom: 5px;">REKAP KEHADIRAN SISWA</h4>
                        <p style="font-size: 16px; font-weight: 700;">Kelas: <?= htmlspecialchars($activeTab == 'kelas' ? $kelas : ($activeTab == 'mapel' ? $infoMapel['kelas'] : $kelasRentang)) ?> | Periode: <?= $namaBulan[$bulan] ?> <?= $tahun ?></p>
                    </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" style="font-size:0.75rem;">
                        <thead class="table-primary text-center">
                            <tr>
                                <th rowspan="2" style="vertical-align:middle;">No</th>
                                <th rowspan="2" style="vertical-align:middle;min-width:180px;">Nama Siswa</th>
                                <th colspan="<?= count($workdaysKelas) ?>">Tanggal</th>
                                <th colspan="6">Rekap</th>
                            </tr>
                            <tr>
                                <?php foreach($workdaysKelas as $d): ?><th><?= $d ?></th><?php endforeach; ?>
                                <th>H</th><th>S</th><th>I</th><th>A</th><th>D</th><th>T</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no=1;
                            $grandTotal = ['H'=>0,'S'=>0,'I'=>0,'A'=>0,'D'=>0,'T'=>0];
                            foreach($siswaKelas as $sw):
                                $nis = $sw['no_induk'];
                                $cH=$cS=$cI=$cA=$cD=$cT=0;
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($sw['nama_siswa']) ?></td>
                                <?php foreach($workdaysKelas as $d):
                                    $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                                    $code = $absenMapKelas[$nis][$tgl] ?? '-';
                                    if ($code != '-') { ${"c$code"}++; }
                                    $bgClass = '';
                                    if ($code=='H') $bgClass='background:#d4edda;color:#155724;';
                                    elseif ($code=='S') $bgClass='background:#d1ecf1;color:#0c5460;';
                                    elseif ($code=='I') $bgClass='background:#fff3cd;color:#856404;';
                                    elseif ($code=='A') $bgClass='background:#f8d7da;color:#721c24;';
                                    elseif ($code=='D') $bgClass='background:#e2d5f1;color:#5a3d8a;';
                                    elseif ($code=='T') $bgClass='background:#cfe2ff;color:#084298;';
                                ?>
                                <td class="text-center" style="<?= $bgClass ?>"><?= $code ?></td>
                                <?php endforeach; ?>
                                <td class="text-center fw-bold" style="color:#155724;"><?= $cH ?></td>
                                <td class="text-center fw-bold" style="color:#0c5460;"><?= $cS ?></td>
                                <td class="text-center fw-bold" style="color:#856404;"><?= $cI ?></td>
                                <td class="text-center fw-bold" style="color:#721c24;"><?= $cA ?></td>
                                <td class="text-center fw-bold" style="color:#5a3d8a;"><?= $cD ?></td>
                                <td class="text-center fw-bold" style="color:#084298;"><?= $cT ?></td>
                            </tr>
                            <?php
                                $grandTotal['H']+=$cH; $grandTotal['S']+=$cS; $grandTotal['I']+=$cI;
                                $grandTotal['A']+=$cA; $grandTotal['D']+=$cD; $grandTotal['T']+=$cT;
                            endforeach; ?>
                            <tr class="table-warning fw-bold">
                                <td colspan="2" class="text-center">TOTAL</td>
                                <?php foreach($workdaysKelas as $d): ?><td></td><?php endforeach; ?>
                                <td class="text-center"><?= $grandTotal['H'] ?></td>
                                <td class="text-center"><?= $grandTotal['S'] ?></td>
                                <td class="text-center"><?= $grandTotal['I'] ?></td>
                                <td class="text-center"><?= $grandTotal['A'] ?></td>
                                <td class="text-center"><?= $grandTotal['D'] ?></td>
                                <td class="text-center"><?= $grandTotal['T'] ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="row mt-3 d-none d-print-flex">
                    <div class="col text-center"><small>Keterangan: H=Hadir, S=Sakit, I=Izin, A=Alpha, D=Dispensasi, T=Telat</small></div>
                </div>
                <?php $waliKelasData = getWaliKelas($conn, $kelas); ?>
                <table style="width:100%;margin-top:40px;font-size:14px;" class="d-none d-print-table">
                    <tr>
                        <td style="width:50%;text-align:center;vertical-align:top;padding:10px 20px; border: none;">
                            <p style="margin:0 0 8px;">Mengetahui,</p>
                            <p style="margin:0;font-weight:bold;text-transform:uppercase;">Kepala Sekolah</p>
                            <br><br><br><br>
                            <p style="margin:0;"><u><b><?= htmlspecialchars($lembagaCetak['nmpimpinan'] ?? '_______________') ?></b></u></p>
                            <p style="margin:0;">NIP. <?= htmlspecialchars($lembagaCetak['nippimpinan'] ?? '_______________') ?></p>
                        </td>
                        <td style="width:50%;text-align:center;vertical-align:top;padding:10px 20px; border: none;">
                            <p style="margin:0 0 8px;"><?= date('d') ?> <?= $namaBulan[(int)date('n')] ?> <?= date('Y') ?></p>
                            <p style="margin:0;font-weight:bold;text-transform:uppercase;">Wali Kelas <?= htmlspecialchars($kelas) ?></p>
                            <br><br><br><br>
                            <p style="margin:0;"><u><b><?= htmlspecialchars($waliKelasData['nama_guru'] ?? '_______________') ?></b></u></p>
                            <p style="margin:0;">NIP. <?= htmlspecialchars($waliKelasData['nip_guru'] ?? '_______________') ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <?php elseif (!empty($kelas) && empty($siswaKelas)): ?>
        <div class="alert alert-warning">Tidak ada data siswa di kelas <?= htmlspecialchars($kelas) ?>.</div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- ==================== TAB 2: PER MAPEL ==================== -->
    <?php if ($activeTab == 'mapel'): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-success text-white">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-filter"></i> Filter Kehadiran Per Mata Pelajaran</h6>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="cetak-kehadiran-siswa">
                <input type="hidden" name="tab" value="mapel">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Mata Pelajaran</label>
                    <select name="id_mapel" class="form-control" required>
                        <option value="">-- Pilih Mapel --</option>
                        <?php foreach($allMapel as $mp): ?>
                            <option value="<?= $mp['id_mapel'] ?>" <?= $mapelId==$mp['id_mapel']?'selected':'' ?>>
                                <?= htmlspecialchars($mp['nama_mapel']) ?> — <?= htmlspecialchars($mp['kelas']) ?> (<?= htmlspecialchars($mp['nama_guru']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Bulan</label>
                    <select name="bulan_mapel" class="form-control">
                        <?php for($b=1;$b<=12;$b++): ?>
                            <option value="<?= $b ?>" <?= $bulanMapel==$b?'selected':'' ?>><?= $namaBulan[$b] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Tahun</label>
                    <input type="number" name="tahun_mapel" class="form-control" value="<?= $tahunMapel ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100"><i class="fas fa-search"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($infoMapel && !empty($siswaMapel)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-success">
                <?= htmlspecialchars($infoMapel['nama_mapel']) ?> — Kelas <?= htmlspecialchars($infoMapel['kelas']) ?> 
                (<?= htmlspecialchars($infoMapel['nama_guru']) ?>) — <?= $namaBulan[$bulanMapel] ?> <?= $tahunMapel ?>
            </h6>
            <button class="btn btn-success btn-sm" onclick="cetakTabel('printAreaMapel')"><i class="fas fa-print"></i> Cetak</button>
        </div>
        <div class="card-body">
                <div id="printAreaMapel">
                    <!-- Professional Header (Cop Surat) -->
                    <div class="d-none d-print-block" style="border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px;">
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="width: 15%; text-align: center; border: none;">
                                    <?php if (!empty($lembagaCetak['logo'])): ?>
                                        <img src="img/<?= htmlspecialchars($lembagaCetak['logo']) ?>" style="width: 80px; height: auto;">
                                    <?php endif; ?>
                                </td>
                                <td style="width: 85%; text-align: center; border: none;">
                                    <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: #1a3c6e;"><?= htmlspecialchars($lembagaCetak['nmsekolah'] ?? '') ?></h2>
                                    <p style="margin: 0; font-size: 14px; font-weight: 600;"><?= htmlspecialchars($lembagaCetak['alamat'] ?? '') ?></p>

                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="text-center mb-4 d-none d-print-block">
                        <h4 style="font-weight: 900; text-decoration: underline; margin-bottom: 5px;">REKAP KEHADIRAN PER MATA PELAJARAN</h4>
                        <p style="font-size: 15px; font-weight: 700; margin-bottom: 2px;">Mapel: <?= htmlspecialchars($infoMapel['nama_mapel']) ?> | Kelas: <?= htmlspecialchars($infoMapel['kelas']) ?></p>
                        <p style="font-size: 14px; font-weight: 600; color: #334155;">Guru: <?= htmlspecialchars($infoMapel['nama_guru']) ?> | Periode: <?= $namaBulan[$bulanMapel] ?> <?= $tahunMapel ?></p>
                    </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" style="font-size:0.75rem;">
                        <thead class="table-success text-center">
                            <tr>
                                <th rowspan="2" style="vertical-align:middle;">No</th>
                                <th rowspan="2" style="vertical-align:middle;min-width:180px;">Nama Siswa</th>
                                <th colspan="<?= count($workdaysMapel) ?>">Tanggal</th>
                                <th colspan="6">Rekap</th>
                            </tr>
                            <tr>
                                <?php foreach($workdaysMapel as $d): ?><th><?= $d ?></th><?php endforeach; ?>
                                <th>H</th><th>S</th><th>I</th><th>A</th><th>D</th><th>T</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no=1;
                            $grandTotalM = ['H'=>0,'S'=>0,'I'=>0,'A'=>0,'D'=>0,'T'=>0];
                            foreach($siswaMapel as $sw):
                                $nis = $sw['no_induk'];
                                $cH=$cS=$cI=$cA=$cD=$cT=0;
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($sw['nama_siswa']) ?></td>
                                <?php foreach($workdaysMapel as $d):
                                    $tgl = sprintf('%04d-%02d-%02d', $tahunMapel, $bulanMapel, $d);
                                    $code = $absenMapMapel[$nis][$tgl] ?? '-';
                                    if ($code != '-') { ${"c$code"}++; }
                                    $bgClass = '';
                                    if ($code=='H') $bgClass='background:#d4edda;color:#155724;';
                                    elseif ($code=='S') $bgClass='background:#d1ecf1;color:#0c5460;';
                                    elseif ($code=='I') $bgClass='background:#fff3cd;color:#856404;';
                                    elseif ($code=='A') $bgClass='background:#f8d7da;color:#721c24;';
                                    elseif ($code=='D') $bgClass='background:#e2d5f1;color:#5a3d8a;';
                                    elseif ($code=='T') $bgClass='background:#cfe2ff;color:#084298;';
                                ?>
                                <td class="text-center" style="<?= $bgClass ?>"><?= $code ?></td>
                                <?php endforeach; ?>
                                <td class="text-center fw-bold" style="color:#155724;"><?= $cH ?></td>
                                <td class="text-center fw-bold" style="color:#0c5460;"><?= $cS ?></td>
                                <td class="text-center fw-bold" style="color:#856404;"><?= $cI ?></td>
                                <td class="text-center fw-bold" style="color:#721c24;"><?= $cA ?></td>
                                <td class="text-center fw-bold" style="color:#5a3d8a;"><?= $cD ?></td>
                                <td class="text-center fw-bold" style="color:#084298;"><?= $cT ?></td>
                            </tr>
                            <?php
                                $grandTotalM['H']+=$cH; $grandTotalM['S']+=$cS; $grandTotalM['I']+=$cI;
                                $grandTotalM['A']+=$cA; $grandTotalM['D']+=$cD; $grandTotalM['T']+=$cT;
                            endforeach; ?>
                            <tr class="table-warning fw-bold">
                                <td colspan="2" class="text-center">TOTAL</td>
                                <?php foreach($workdaysMapel as $d): ?><td></td><?php endforeach; ?>
                                <td class="text-center"><?= $grandTotalM['H'] ?></td>
                                <td class="text-center"><?= $grandTotalM['S'] ?></td>
                                <td class="text-center"><?= $grandTotalM['I'] ?></td>
                                <td class="text-center"><?= $grandTotalM['A'] ?></td>
                                <td class="text-center"><?= $grandTotalM['D'] ?></td>
                                <td class="text-center"><?= $grandTotalM['T'] ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php $waliMapelData = $infoMapel ? getWaliKelas($conn, $infoMapel['kelas']) : null; ?>
                <table style="width:100%;margin-top:40px;font-size:14px;" class="d-none d-print-table">
                    <tr>
                        <td style="width:50%;text-align:center;vertical-align:top;padding:10px 20px; border: none;">
                            <p style="margin:0 0 8px;">Mengetahui,</p>
                            <p style="margin:0;font-weight:bold;text-transform:uppercase;">Kepala Sekolah</p>
                            <br><br><br><br>
                            <p style="margin:0;"><u><b><?= htmlspecialchars($lembagaCetak['nmpimpinan'] ?? '_______________') ?></b></u></p>
                            <p style="margin:0;">NIP. <?= htmlspecialchars($lembagaCetak['nippimpinan'] ?? '_______________') ?></p>
                        </td>
                        <td style="width:50%;text-align:center;vertical-align:top;padding:10px 20px; border: none;">
                            <p style="margin:0 0 8px;"><?= date('d') ?> <?= $namaBulan[(int)date('n')] ?> <?= date('Y') ?></p>
                            <p style="margin:0;font-weight:bold;text-transform:uppercase;">Guru Mata Pelajaran</p>
                            <br><br><br><br>
                            <p style="margin:0;"><u><b><?= htmlspecialchars($infoMapel['nama_guru'] ?? '_______________') ?></b></u></p>
                            <p style="margin:0;">NIP. _______________</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <?php elseif (!empty($mapelId) && empty($siswaMapel)): ?>
        <div class="alert alert-warning">Tidak ada data siswa untuk mapel yang dipilih.</div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- ==================== TAB 3: REKAP RENTANG BULAN ==================== -->
    <?php if ($activeTab == 'rentang'): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-info text-white">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-filter"></i> Filter Rekap Rentang Bulan</h6>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="cetak-kehadiran-siswa">
                <input type="hidden" name="tab" value="rentang">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Kelas</label>
                    <select name="kelas_rentang" class="form-control" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach($allKelas as $k): ?>
                            <option value="<?= htmlspecialchars($k) ?>" <?= $kelasRentang==$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Dari Bulan</label>
                    <select name="bulan_dari" class="form-control">
                        <?php for($b=1;$b<=12;$b++): ?>
                            <option value="<?= $b ?>" <?= $bulanDari==$b?'selected':'' ?>><?= $namaBulan[$b] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-bold">Tahun</label>
                    <input type="number" name="tahun_dari" class="form-control" value="<?= $tahunDari ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Sampai Bulan</label>
                    <select name="bulan_sampai" class="form-control">
                        <?php for($b=1;$b<=12;$b++): ?>
                            <option value="<?= $b ?>" <?= $bulanSampai==$b?'selected':'' ?>><?= $namaBulan[$b] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-bold">Tahun</label>
                    <input type="number" name="tahun_sampai" class="form-control" value="<?= $tahunSampai ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-info w-100 text-white"><i class="fas fa-search"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($kelasRentang) && !empty($rekapRentang)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-info">
                Rekap Kelas <?= htmlspecialchars($kelasRentang) ?> — <?= $namaBulan[$bulanDari] ?> <?= $tahunDari ?> s/d <?= $namaBulan[$bulanSampai] ?> <?= $tahunSampai ?>
            </h6>
            <button class="btn btn-info btn-sm text-white" onclick="cetakTabel('printAreaRentang')"><i class="fas fa-print"></i> Cetak</button>
        </div>
        <div class="card-body">
                <div id="printAreaRentang">
                    <!-- Professional Header (Cop Surat) -->
                    <div class="d-none d-print-block" style="border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px;">
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="width: 15%; text-align: center; border: none;">
                                    <?php if (!empty($lembagaCetak['logo'])): ?>
                                        <img src="img/<?= htmlspecialchars($lembagaCetak['logo']) ?>" style="width: 80px; height: auto;">
                                    <?php endif; ?>
                                </td>
                                <td style="width: 85%; text-align: center; border: none;">
                                    <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: #1a3c6e;"><?= htmlspecialchars($lembagaCetak['nmsekolah'] ?? '') ?></h2>
                                    <p style="margin: 0; font-size: 14px; font-weight: 600;"><?= htmlspecialchars($lembagaCetak['alamat'] ?? '') ?></p>

                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="text-center mb-4 d-none d-print-block">
                        <h4 style="font-weight: 900; text-decoration: underline; margin-bottom: 5px;">REKAP KEHADIRAN SISWA (SEMESTER)</h4>
                        <p style="font-size: 16px; font-weight: 700;">Kelas: <?= htmlspecialchars($kelasRentang) ?> | Periode: <?= $namaBulan[$bulanDari] ?> <?= $tahunDari ?> s/d <?= $namaBulan[$bulanSampai] ?> <?= $tahunSampai ?></p>
                    </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead class="table-info text-center">
                            <tr>
                                <th>No</th>
                                <th style="min-width:200px;">Nama Siswa</th>
                                <th>Hadir</th>
                                <th>Sakit</th>
                                <th>Izin</th>
                                <th>Alpha</th>
                                <th>Dispensasi</th>
                                <th>Telat</th>
                                <th>Total Masuk</th>
                                <th>% Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no=1;
                            $gH=$gS=$gI=$gA=$gD=$gT=0;
                            foreach($rekapRentang as $rr):
                                $totalMasuk = $rr['H'];
                                $totalSemua = $rr['H']+$rr['S']+$rr['I']+$rr['A']+$rr['D'];
                                $persen = $totalSemua > 0 ? round(($totalMasuk/$totalSemua)*100,1) : 0;
                                $badgeClass = $persen >= 90 ? 'badge-success' : ($persen >= 75 ? 'badge-warning' : 'badge-danger');
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($rr['nama_siswa']) ?></td>
                                <td class="text-center" style="color:#155724;font-weight:bold;"><?= $rr['H'] ?></td>
                                <td class="text-center" style="color:#0c5460;"><?= $rr['S'] ?></td>
                                <td class="text-center" style="color:#856404;"><?= $rr['I'] ?></td>
                                <td class="text-center" style="color:#721c24;font-weight:bold;"><?= $rr['A'] ?></td>
                                <td class="text-center" style="color:#5a3d8a;"><?= $rr['D'] ?></td>
                                <td class="text-center" style="color:#084298;"><?= $rr['T'] ?></td>
                                <td class="text-center fw-bold"><?= $totalMasuk ?></td>
                                <td class="text-center"><span class="badge <?= $badgeClass ?>"><?= $persen ?>%</span></td>
                            </tr>
                            <?php
                                $gH+=$rr['H']; $gS+=$rr['S']; $gI+=$rr['I']; $gA+=$rr['A']; $gD+=$rr['D']; $gT+=$rr['T'];
                            endforeach; ?>
                            <tr class="table-warning fw-bold">
                                <td colspan="2" class="text-center">TOTAL</td>
                                <td class="text-center"><?= $gH ?></td>
                                <td class="text-center"><?= $gS ?></td>
                                <td class="text-center"><?= $gI ?></td>
                                <td class="text-center"><?= $gA ?></td>
                                <td class="text-center"><?= $gD ?></td>
                                <td class="text-center"><?= $gT ?></td>
                                <td class="text-center"><?= $gH ?></td>
                                <?php $gTotal = $gH+$gS+$gI+$gA+$gD; $gPersen = $gTotal>0?round(($gH/$gTotal)*100,1):0; ?>
                                <td class="text-center"><span class="badge badge-primary"><?= $gPersen ?>%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="row mt-4 d-none d-print-flex">
                    <div class="col text-center mb-2"><small>Keterangan: H=Hadir, S=Sakit, I=Izin, A=Alpha, D=Dispensasi, T=Telat</small></div>
                </div>
                <?php $waliRentangData = getWaliKelas($conn, $kelasRentang); ?>
                <table style="width:100%;margin-top:40px;font-size:14px;" class="d-none d-print-table">
                    <tr>
                        <td style="width:50%;text-align:center;vertical-align:top;padding:10px 20px; border: none;">
                            <p style="margin:0 0 8px;">Mengetahui,</p>
                            <p style="margin:0;font-weight:bold;text-transform:uppercase;">Kepala Sekolah</p>
                            <br><br><br><br>
                            <p style="margin:0;"><u><b><?= htmlspecialchars($lembagaCetak['nmpimpinan'] ?? '_______________') ?></b></u></p>
                            <p style="margin:0;">NIP. <?= htmlspecialchars($lembagaCetak['nippimpinan'] ?? '_______________') ?></p>
                        </td>
                        <td style="width:50%;text-align:center;vertical-align:top;padding:10px 20px; border: none;">
                            <p style="margin:0 0 8px;"><?= date('d') ?> <?= $namaBulan[(int)date('n')] ?> <?= date('Y') ?></p>
                            <p style="margin:0;font-weight:bold;text-transform:uppercase;">Wali Kelas <?= htmlspecialchars($kelasRentang) ?></p>
                            <br><br><br><br>
                            <p style="margin:0;"><u><b><?= htmlspecialchars($waliRentangData['nama_guru'] ?? '_______________') ?></b></u></p>
                            <p style="margin:0;">NIP. <?= htmlspecialchars($waliRentangData['nip_guru'] ?? '_______________') ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <?php elseif (!empty($kelasRentang) && empty($rekapRentang)): ?>
        <div class="alert alert-warning">Tidak ada data siswa untuk kelas dan periode yang dipilih.</div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<script>
function cetakTabel(areaId) {
    var printContents = document.getElementById(areaId).innerHTML;
    var win = window.open('', '_blank');
    win.document.write('<html><head><title>Cetak Kehadiran Siswa</title>');
    win.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">');
    win.document.write('<style>');
    win.document.write('body { font-size: 14px; padding: 20px; font-family: "Plus Jakarta Sans", sans-serif; color: #1e293b; }');
    win.document.write('table { font-size: 12px; width: 100%; border-collapse: collapse; margin-bottom: 20px; }');
    win.document.write('th, td { border: 1px solid #000 !important; padding: 6px 4px !important; }');
    win.document.write('thead th { background-color: #f8fafc !important; color: #000 !important; font-weight: 800 !important; text-transform: uppercase; }');
    win.document.write('.d-none.d-print-block, .d-none.d-print-flex { display: block !important; }');
    win.document.write('.d-none.d-print-table { display: table !important; }');
    win.document.write('.d-none.d-print-table td { border: none !important; }');
    win.document.write('.badge-success { border: 1px solid #28a745; color: #28a745; padding: 2px 8px; border-radius: 10px; font-weight: 700; }');
    win.document.write('.badge-warning { border: 1px solid #ffc107; color: #856404; padding: 2px 8px; border-radius: 10px; font-weight: 700; }');
    win.document.write('.badge-danger { border: 1px solid #dc3545; color: #dc3545; padding: 2px 8px; border-radius: 10px; font-weight: 700; }');
    win.document.write('.badge-primary { border: 1px solid #007bff; color: #007bff; padding: 2px 8px; border-radius: 10px; font-weight: 700; }');
    win.document.write('@page { size: landscape; margin: 15mm; }');
    win.document.write('</style></head><body>');
    win.document.write(printContents);
    win.document.write('</body></html>');
    win.document.close();
    setTimeout(function(){ win.print(); }, 500);
}
</script>
