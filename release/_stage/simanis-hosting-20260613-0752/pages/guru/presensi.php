<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["no_induk"])) { header("location: ../../index.php?haruslogin"); exit; }
if($_SESSION['hak_akses'] != 2) { echo '<script>window.location="../../404.html";</script>'; exit; }

include '../../koneksi.php';
include '../../functions.php';
include_once '../../nocache.php';
date_default_timezone_set('Asia/Jakarta');

$nipguru = $_SESSION['no_induk'];

// Ambil opsi kelas yang diajar guru
$kelasOpts = [];
$qK = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='".$nipguru."' ORDER BY kelas ASC");
while ($r = mysqli_fetch_assoc($qK)) { $kelasOpts[] = $r['kelas']; }

$kelas = isset($_GET['kelas']) ? mysqli_real_escape_string($conn, $_GET['kelas']) : ($kelasOpts[0] ?? '');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('n');
if ($bulan < 1 || $bulan > 12) { $bulan = (int)date('n'); }

// Jika kelas kosong (guru belum punya jadwal)
if ($kelas === '') {
    $kelas = '';
}

$namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$weekendDays = 0; // jumlah hari sabtu + minggu
for($d=1;$d<=$daysInMonth;$d++){ if(isWeekend($tahun,$bulan,$d)) $weekendDays++; }
// Kumpulkan hanya hari kerja (Senin-Jumat) untuk ditampilkan di tabel
$workdays = [];
for($d=1;$d<=$daysInMonth;$d++){
    if(!isWeekend($tahun,$bulan,$d)) $workdays[] = $d;
}
$workdayCount = count($workdays);
$firstDay = sprintf('%04d-%02d-01', $tahun, $bulan);
$lastDay = sprintf('%04d-%02d-%02d', $tahun, $bulan, $daysInMonth);

// Ambil siswa aktif pada kelas
$siswa = [];
if ($kelas !== '') {
    $qs = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas='".$kelas."' AND status='Aktif' ORDER BY nama_siswa ASC");
    while ($s = mysqli_fetch_assoc($qs)) { $siswa[] = $s; }
}

// Ambil data absen untuk kelas & bulan
$absenMap = [];
if ($kelas !== '') {
    $qa = mysqli_query($conn, "SELECT no_induk, tanggal, status FROM tbl_absen WHERE kelas='".$kelas."' AND tanggal BETWEEN '".$firstDay."' AND '".$lastDay."'");
    while ($a = mysqli_fetch_assoc($qa)) {
        $nis = $a['no_induk'];
        $tgl = $a['tanggal'];
        // Map status ke huruf singkat SIAD
        $st = strtolower(trim($a['status']));
        $code = 'H';
        if ($st === 'sakit') $code = 'S';
        elseif ($st === 'ijin' || $st === 'izin') $code = 'I';
        elseif ($st === 'alpha') $code = 'A';
        elseif ($st === 'dispen' || $st === 'dispensasi') $code = 'D';
        $absenMap[$nis][$tgl] = $code;
    }
}

// Helper weekend
function isWeekend($y, $m, $d){ $w = date('w', strtotime(sprintf('%04d-%02d-%02d',$y,$m,$d))); return ($w == 0 || $w == 6); }
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Presensi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #6f42c1;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-bg: #f8f9fc;
        }
        
        body { 
            background: var(--light-bg); 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .page-header { 
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
            color: #fff; 
            padding: 1.1rem 1rem; 
            border-radius: 0 0 18px 18px; 
            box-shadow: 0 4px 12px rgba(13,110,253,0.25); 
            margin-bottom: 1.5rem; 
        }
        
        .filter-card { 
            border: none; 
            border-radius: 12px; 
            box-shadow: 0 6px 18px rgba(0,0,0,0.06); 
            margin-bottom: 1.5rem;
        }
        
        .table-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .table-wrap { 
            overflow: auto; 
            max-height: 70vh;
            position: relative;
        }
        
        .table thead th { 
            position: sticky; 
            top: 0; 
            background: #fff; 
            z-index: 2; 
            vertical-align: middle;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .thead-top { 
            position: sticky; 
            top: 0; 
            z-index: 3; 
            background: var(--primary-color);
            color: white;
        }
        
        .thead-second { 
            position: sticky; 
            top: 42px; 
            z-index: 2; 
            background: #f8f9fa;
        }
        
        .th-rotate { 
            writing-mode: vertical-rl; 
            transform: rotate(180deg); 
            white-space: nowrap; 
        }
        
        .col-sticky { 
            position: sticky; 
            left: 0; 
            background: #fff; 
            z-index: 4; 
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }
        
        .col-sticky-2 { 
            position: sticky; 
            left: 60px; 
            background: #fff; 
            z-index: 4; 
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }
        
        .weekend { 
            background: #fff5f5; 
            color: var(--danger-color); 
            font-weight: bold;
        }
        
        .legend span { 
            display:inline-block; 
            padding: .25rem .5rem; 
            border-radius: .35rem; 
            margin-right:.25rem; 
            font-size:.825rem; 
            font-weight: 600;
        }
        
        .bg-H { background: #e9f7ef; color: var(--success-color); }
        .bg-I { background: #fff7e6; color: var(--warning-color); }
        .bg-S { background: #e7f0ff; color: var(--info-color); }
        .bg-A { background: #fde7e7; color: var(--danger-color); }
        .bg-D { background: #f3e8ff; color: var(--secondary-color); }
        .bg-L { background:#ffe1e1; color: var(--danger-color); }
        .bg-- { background: #f8f9fa; color: #6c757d; border: 1px dashed #dee2e6; }
        .weekend-cell { opacity:.9; }
        
        .cell { 
            text-align:center; 
            min-width:34px; 
            width:34px; 
            height: 34px;
            padding: 2px;
        }
        
        .cell small { 
            font-weight:600; 
            display: inline-block;
            width: 26px;
            height: 26px;
            line-height: 26px;
            border-radius: 50%;
            font-size: .7rem;
        }
        
        .name-cell { 
            min-width: 220px; 
            max-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sticky-name { 
            position: sticky; 
            left: 0; 
            transform: translateX(60px);
            background:#fff; 
            z-index:5; 
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            width: 220px;
            min-width: 220px;
            max-width: 220px;
        }
        
        .sticky-no { 
            position: sticky; 
            left: 0; 
            background:#fff; 
            z-index:5; 
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            width: 60px;
            min-width: 60px;
            max-width: 60px;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .search-box {
            max-width: 300px;
        }
        
        .summary-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }
        
        .spinner {
            width: 3rem;
            height: 3rem;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .name-cell {
                min-width: 150px;
                max-width: 150px;
            }
            
            .cell {
                min-width: 32px;
                width: 32px;
            }
        }
        
        .rekap-cell {
            font-weight: 700;
            text-align: center;
        }
        
        .rekap-S { color: var(--info-color); }
        .rekap-I { color: var(--warning-color); }
        .rekap-A { color: var(--danger-color); }
    .rekap-D { color: var(--secondary-color); }

    /* Mode kompak */
    .compact .cell {min-width:28px;width:28px;height:28px;padding:1px;}
    .compact .cell small {width:22px;height:22px;line-height:22px;font-size:.6rem;}
    .compact .name-cell {min-width:160px;max-width:160px;}
    .compact .table thead th {font-size:.7rem;}
    .compact .summary-row th {font-size:9px;}
    </style>
</head>
<body>
<div class="loading-overlay">
    <div class="spinner-border text-primary spinner" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<div class="page-header">
    <div class="container d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <i class="bi bi-people-check fs-4 me-2"></i>
            <div>
                <h5 class="mb-0 fw-semibold">Daftar Presensi</h5>
                <small>Rekap kehadiran per bulan</small>
            </div>
        </div>
        <a href="guru.php" class="btn btn-sm btn-light"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>
</div>

<div class="container pb-4">
    <div class="summary-card">
        <div class="row">
            <div class="col-md-8">
                <h5 class="mb-1">Rekap Presensi Kelas <?= htmlspecialchars($kelas); ?></h5>
                <p class="mb-0">Bulan <?= $namaBulan[$bulan]; ?> Tahun <?= $tahun; ?></p>
            </div>
            <div class="col-md-4 d-flex align-items-center justify-content-end">
                <span class="badge bg-light text-dark fs-6">Total Siswa: <?= count($siswa); ?></span>
            </div>
        </div>
    </div>

    <div class="card filter-card">
        <div class="card-body">
            <form class="row g-3 align-items-end" method="get" id="filterForm">
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Kelas</label>
                    <select name="kelas" class="form-select" required>
                        <?php foreach($kelasOpts as $k) { ?>
                            <option value="<?= htmlspecialchars($k); ?>" <?= $kelas===$k?'selected':''; ?>><?= htmlspecialchars($k); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold">Bulan</label>
                    <select name="bulan" class="form-select">
                        <?php for ($b=1;$b<=12;$b++){ ?>
                            <option value="<?= $b; ?>" <?= $bulan===$b?'selected':''; ?>><?= $namaBulan[$b]; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold">Tahun</label>
                    <input type="number" name="tahun" class="form-control" value="<?= $tahun; ?>" min="2020" max="2030" />
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Legenda</label>
                    <div>
                        <span class="bg-H">H: Hadir</span>
                        <span class="bg-I">I: Izin</span>
                        <span class="bg-S">S: Sakit</span>
                        <span class="bg-A">A: Alpha</span>
                        <span class="bg-D">D: Dispensasi</span>
                        <!-- Sabtu & Minggu dihilangkan dari tabel -->
                    </div>
                </div>
                <div class="col-12 col-md-2">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" type="submit" id="applyFilter"><i class="bi bi-funnel me-1"></i> Terapkan</button>
                        <a href="presensi.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i> Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($kelas === '') { ?>
        <div class="alert alert-warning d-flex align-items-center">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <div>Belum ada kelas terdaftar untuk akun Anda. Silakan hubungi admin untuk menambahkan jadwal.</div>
        </div>
    <?php } else if (count($siswa) === 0) { ?>
        <div class="alert alert-info d-flex align-items-center">
            <i class="bi bi-info-circle me-2"></i>
            <div>Tidak ada siswa aktif pada kelas <strong><?= htmlspecialchars($kelas); ?></strong>.</div>
        </div>
    <?php } else { ?>
    
        <div class="action-buttons">
                <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-success btn-action" id="btnExportPDF" title="Ekspor PDF"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                        <button class="btn btn-outline-success btn-action" id="btnExportCSV" title="Ekspor CSV"><i class="bi bi-filetype-csv"></i> CSV</button>
                        <button class="btn btn-outline-success btn-action" id="btnExportXLSX" title="Ekspor Excel"><i class="bi bi-table"></i> Excel</button>
                        <button class="btn btn-outline-secondary btn-action" id="btnToggleRekap" title="Ganti ke mode rekap bulanan"><i class="bi bi-grid-3x3-gap"></i> Mode Rekap</button>
                        <button class="btn btn-outline-secondary btn-action" id="btnToggleVirtual" title="Aktifkan virtual scroll"><i class="bi bi-arrows-move"></i> Virtual</button>
                        <button class="btn btn-outline-secondary btn-action" id="btnToggleCompact" title="Mode kompak"><i class="bi bi-aspect-ratio"></i> Kompak</button>
                        <button class="btn btn-primary btn-action" onclick="window.print()" title="Cetak"><i class="bi bi-printer"></i> Cetak</button>
                </div>
                <div class="d-flex flex-wrap gap-3 ms-auto align-items-center">
                        <div class="form-check form-switch m-0" title="Tampilkan hanya siswa yang memiliki S/I/A/D">
                            <input class="form-check-input" type="checkbox" id="toggleAbsentOnly">
                            <label class="form-check-label small" for="toggleAbsentOnly">Hanya ketidakhadiran</label>
                        </div>
                        <div class="d-flex align-items-center gap-1" id="pageSizeWrapper">
                            <label class="small text-muted">Baris:</label>
                            <select id="pageSize" class="form-select form-select-sm" style="width:90px;">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="all" selected>Semua</option>
                            </select>
                        </div>
                        <div class="search-box">
                                <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" id="searchInput" class="form-control" placeholder="Cari nama siswa...">
                                </div>
                        </div>
                </div>
        </div>
    
    <div class="table-container">
        <div class="table-wrap" id="presensiTable">
            <table class="table table-bordered table-sm align-middle" id="tblPresensi">
                <thead>
                    <tr class="thead-top">
                        <th class="sticky-no" style="width:60px" rowspan="2">No</th>
                        <th class="sticky-name name-cell" rowspan="2">Nama Siswa</th>
                        <th class="text-center" colspan="<?= $workdayCount; ?>"><?= $namaBulan[$bulan]; ?> <?= $tahun; ?></th>
                        <th class="text-center" colspan="4">Rekap SIAD</th>
                    </tr>
                    <tr class="thead-second">
                        <?php foreach ($workdays as $d){ 
                            $dayOfWeek = date('D', strtotime("$tahun-$bulan-$d"));
                        ?>
                            <th class="cell" title="<?= $dayOfWeek; ?>"><?= $d; ?></th>
                        <?php } ?>
                        <th class="text-center">S</th>
                        <th class="text-center">I</th>
                        <th class="text-center">A</th>
                        <th class="text-center">D</th>
                    </tr>
                </thead>
                                <tbody>
                                        <?php 
                                            $idx=1; 
                                            $dayTotals = [];
                                            $grandTotals = ['H'=>0,'S'=>0,'I'=>0,'A'=>0,'D'=>0];
                                            // filledCells dihitung berdasarkan sel yang sudah diisi (bukan -)
                                            $filledCells = 0;
                                            $rekapBulan = [];
                                            foreach ($siswa as $sw) { 
                                                $nis=$sw['no_induk']; 
                                                $nama=$sw['nama_siswa']; 
                                                $cS=$cI=$cA=$cD=$cH=0; 
                                        ?>
                                  <?php 
                                      // We'll compute attendance within loop, but need totalAbs after loop; so buffer row HTML.
                                      ob_start();
                                  ?>
                                  <tr class="siswa-row" data-absent="__PLACEHOLDER__">
                                                <td class="sticky-no text-center"><?= $idx++; ?></td>
                                                <td class="sticky-name name-cell"><?= htmlspecialchars($nama); ?></td>
                                                <?php 
                                                    foreach ($workdays as $d){
                                                        $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                                                        // Jika belum ada presensi, tampilkan strip (-)
                                                        $code = $absenMap[$nis][$tgl] ?? '-';
                                                        if (!isset($dayTotals[$d])) $dayTotals[$d] = ['H'=>0,'S'=>0,'I'=>0,'A'=>0,'D'=>0,'-'=>0];
                                                        if(isset($dayTotals[$d][$code])) $dayTotals[$d][$code]++;
                                                        // Hitung sel yang sudah diisi (bukan -)
                                                        if ($code !== '-') {
                                                            if (!isset($grandTotals[$code])) $grandTotals[$code]=0;
                                                            $grandTotals[$code]++;
                                                            $filledCells++; // increment filled cells
                                                        }
                                                        if ($code==='S') $cS++; 
                                                        elseif ($code==='I') $cI++; 
                                                        elseif ($code==='A') $cA++; 
                                                        elseif ($code==='D') $cD++; 
                                                        elseif ($code==='H') $cH++;
                                                ?>
                            <td class="cell">
                                <small class="bg-<?= $code; ?>"><?= $code; ?></small>
                            </td>
                                                <?php } 
                                                    $totalAbs = $cS + $cI + $cA + $cD; 
                                                ?>
                                                <td class="rekap-cell rekap-S"><?= $cS; ?></td>
                                                <td class="rekap-cell rekap-I"><?= $cI; ?></td>
                                                <td class="rekap-cell rekap-A"><?= $cA; ?></td>
                                                <td class="rekap-cell rekap-D"><?= $cD; ?></td>
                                            </tr>
                                            <?php 
                                                $rekapBulan[] = [
                                                    'nama'=>$nama,
                                                    'H'=>$cH,
                                                    'S'=>$cS,
                                                    'I'=>$cI,
                                                    'A'=>$cA,
                                                    'D'=>$cD,
                                                    'total'=>$workdayCount // hanya hari kerja
                                                ];
                                                $rowHtml = ob_get_clean();
                                                $rowHtml = str_replace('__PLACEHOLDER__', $totalAbs, $rowHtml);
                                                echo $rowHtml; 
                                            ?>
                                        <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-secondary small summary-row">
                                        <th class="sticky-no text-center">#</th>
                                        <th class="sticky-name">Rekap / Hari</th>
                                        <?php foreach($workdays as $d){ $dt = $dayTotals[$d] ?? ['H'=>0,'S'=>0,'I'=>0,'A'=>0,'D'=>0,'-'=>0]; ?>
                                            <th class="cell p-0" style="font-size:10px; line-height:1.1;">
                                                H:<?= $dt['H']; ?><br>
                                                S:<?= $dt['S']; ?> I:<?= $dt['I']; ?><br>
                                                A:<?= $dt['A']; ?> D:<?= $dt['D']; ?><br>
                                                -:<?= $dt['-']; ?>
                                            </th>
                                        <?php } ?>
                                        <th class="rekap-cell rekap-S"><?= $grandTotals['S']; ?></th>
                                        <th class="rekap-cell rekap-I"><?= $grandTotals['I']; ?></th>
                                        <th class="rekap-cell rekap-A"><?= $grandTotals['A']; ?></th>
                                        <th class="rekap-cell rekap-D"><?= $grandTotals['D']; ?></th>
                                    </tr>
                                </tfoot>
            </table>
        </div>
    </div>
        <?php 
            $presentPercent = $filledCells>0? round(($grandTotals['H']/$filledCells)*100,2):0; 
            $absentCells = $grandTotals['S']+$grandTotals['I']+$grandTotals['A']+$grandTotals['D'];
            $absentPercent = $filledCells>0? round(($absentCells/$filledCells)*100,2):0;
        ?>
        <div class="card mb-4">
            <div class="card-body py-2">
                <div class="row g-2 text-center">
                    <div class="col-6 col-md-2"><div class="fw-semibold text-success">Hadir %</div><div><?= $presentPercent; ?>%</div></div>
                    <div class="col-6 col-md-2"><div class="fw-semibold text-danger">Ketidakhadiran %</div><div><?= $absentPercent; ?>%</div></div>
                    <div class="col-6 col-md-2"><div class="fw-semibold text-info">Sakit</div><div><?= $grandTotals['S']; ?></div></div>
                    <div class="col-6 col-md-2"><div class="fw-semibold text-warning">Izin</div><div><?= $grandTotals['I']; ?></div></div>
                    <div class="col-6 col-md-2"><div class="fw-semibold text-danger">Alpha</div><div><?= $grandTotals['A']; ?></div></div>
                    <div class="col-6 col-md-2"><div class="fw-semibold" style="color:var(--secondary-color);">Dispensasi</div><div><?= $grandTotals['D']; ?></div></div>
                </div>
            </div>
        </div>
            <!-- Rekap Bulanan (Hidden Default) -->
            <div class="table-responsive d-none mt-4" id="rekapBulananWrapper">
                <table class="table table-bordered table-sm" id="tblRekapBulanan">
                    <thead>
                        <tr class="table-primary">
                            <th class="text-center" style="width:50px;">No</th>
                            <th>Nama Siswa <button class="btn btn-link p-0 ms-1 sort-name" data-order="asc" title="Urutkan"><i class="bi bi-sort-alpha-down"></i></button></th>
                            <th class="text-center">H</th>
                            <th class="text-center">S</th>
                            <th class="text-center">I</th>
                            <th class="text-center">A</th>
                            <th class="text-center">D</th>
                            <th class="text-center">% Hadir</th>
                            <th class="text-center">% Ketidakhadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $n=1; foreach($rekapBulan as $rb){
                            $pctH = $rb['total']? round(($rb['H']/$rb['total'])*100,2):0;
                            $abs = $rb['S']+$rb['I']+$rb['A']+$rb['D'];
                            $pctAbs = $rb['total']? round(($abs/$rb['total'])*100,2):0;
                        ?>
                        <tr data-name="<?= htmlspecialchars(strtolower($rb['nama'])); ?>" data-absent="<?= $abs; ?>">
                            <td class="text-center"><?= $n++; ?></td>
                            <td><?= htmlspecialchars($rb['nama']); ?></td>
                            <td class="text-center text-success fw-semibold"><?= $rb['H']; ?></td>
                            <td class="text-center text-info"><?= $rb['S']; ?></td>
                            <td class="text-center text-warning"><?= $rb['I']; ?></td>
                            <td class="text-center text-danger"><?= $rb['A']; ?></td>
                            <td class="text-center text-secondary"><?= $rb['D']; ?></td>
                            <td class="text-center fw-semibold"><?= $pctH; ?>%</td>
                            <td class="text-center"><?= $pctAbs; ?>%</td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
    <?php } ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
        // SheetJS for Excel export (loaded on demand)
        function ensureSheetJS(cb){
            if (window.XLSX) return cb();
            var s=document.createElement('script');
            s.src='https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
            s.onload=cb; document.head.appendChild(s);
        }

        function buildDataArray(){
            if ($('#rekapBulananWrapper').is(':visible')){
                var data=[]; var headers=[];
                $('#tblRekapBulanan thead th').each(function(){ headers.push($(this).text().trim()); });
                data.push(headers);
                $('#tblRekapBulanan tbody tr:visible').each(function(){
                    var row=[]; $(this).find('td').each(function(){ row.push($(this).text().trim()); });
                    data.push(row);
                });
                return data;
            } else {
                var data=[]; var headers=[];
                $('#tblPresensi thead tr.thead-second th').each(function(){ headers.push($(this).text().trim()||''); });
                headers[0]='No'; headers[1]='Nama Siswa';
                data.push(headers);
                $('#tblPresensi tbody tr:visible').each(function(){
                    var row=[]; $(this).find('td').each(function(){ row.push($(this).text().trim()); });
                    data.push(row);
                });
                var summary=[]; $('#tblPresensi tfoot th').each(function(){ summary.push($(this).text().replace(/\s+/g,' ').trim()); });
                data.push(summary); return data;
            }
        }

        function exportCSV(){
            var rows = buildDataArray();
            var csv = rows.map(r=> r.map(v=> '"'+v.replace(/"/g,'""')+'"').join(',')).join('\r\n');
            var blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'presensi_<?= $kelas ?>_<?= $namaBulan[$bulan] ?>_<?= $tahun ?>.csv';
            a.click();
        }

        function exportXLSX(){
            ensureSheetJS(function(){
                var aoa = buildDataArray();
                var ws = XLSX.utils.aoa_to_sheet(aoa);
                var wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Presensi');
                XLSX.writeFile(wb, 'presensi_<?= $kelas ?>_<?= $namaBulan[$bulan] ?>_<?= $tahun ?>.xlsx');
            });
        }

        function applyAbsentFilter(){
            if ($('#rekapBulananWrapper').is(':visible')){
                if (!$('#toggleAbsentOnly').is(':checked')){ $('#tblRekapBulanan tbody tr').show(); return; }
                $('#tblRekapBulanan tbody tr').each(function(){
                    var absent = parseInt($(this).attr('data-absent'),10)||0;
                    $(this).toggle(absent>0);
                });
            } else {
                if (!$('#toggleAbsentOnly').is(':checked')){ $('.siswa-row').show(); paginate(); return; }
                $('.siswa-row').each(function(){
                    var absent = parseInt($(this).attr('data-absent'),10)||0;
                    $(this).toggle(absent>0);
                });
                paginate();
            }
        }

        // Pagination
        var currentPage=1; function paginate(){
            var pageSize = $('#pageSize').val();
            var rows = $('.siswa-row:visible');
            if (pageSize==='all'){ rows.show(); renderPager(0,0); return; }
            pageSize = parseInt(pageSize,10)||25;
            var totalPages = Math.ceil(rows.length / pageSize);
            if (currentPage>totalPages) currentPage=1;
            rows.hide();
            var start=(currentPage-1)*pageSize;
            rows.slice(start,start+pageSize).show();
            renderPager(totalPages, currentPage);
        }
        function renderPager(totalPages, active){
            var cont = $('#pager');
            if (!cont.length){ cont=$('<div id="pager" class="mt-2"></div>'); $('#presensiTable').after(cont); }
            if (totalPages<=1){ cont.html(''); return; }
            var html='<nav><ul class="pagination pagination-sm flex-wrap">';
            for (var i=1;i<=totalPages;i++){
                html += '<li class="page-item '+(i===active?'active':'')+'"><a class="page-link" href="#" data-pg="'+i+'">'+i+'</a></li>';
            }
            html+='</ul></nav>';
            cont.html(html);
        }

        $(document).on('click','#pager a.page-link',function(e){ e.preventDefault(); currentPage=parseInt($(this).data('pg'),10); paginate(); });

        $(document).ready(function() {
        // Fungsi pencarian
        $('#searchInput').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            if ($('#rekapBulananWrapper').is(':visible')){
                $('#tblRekapBulanan tbody tr').filter(function(){
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            } else {
                $('.siswa-row').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
                paginate();
            }
        });
        
        // Fungsi ekspor PDF
        $('#btnExportPDF').click(function() {
            $('.loading-overlay').show();
            
            // Sembunyikan elemen yang tidak perlu di PDF
            $('.page-header, .filter-card, .action-buttons').hide();
            
            // Buat elemen untuk judul laporan
            var reportTitle = $('<div class="report-title mb-3"><h4>Laporan Presensi Kelas <?= $kelas ?></h4><p>Bulan: <?= $namaBulan[$bulan] ?> <?= $tahun ?></p><hr></div>');
            $('#presensiTable').prepend(reportTitle);
            
            // Konfigurasi html2pdf
            var element = document.getElementById('presensiTable');
            var opt = {
                margin: [10, 5, 10, 5],
                filename: 'presensi_<?= $kelas ?>_<?= $namaBulan[$bulan] ?>_<?= $tahun ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };
            
            // Generate PDF
            html2pdf().set(opt).from(element).save().then(function() {
                // Tampilkan kembali elemen yang disembunyikan
                $('.page-header, .filter-card, .action-buttons').show();
                reportTitle.remove();
                $('.loading-overlay').hide();
            });
        });
        
        // Ekspor CSV & Excel
        $('#btnExportCSV').click(function(){ exportCSV(); });
        $('#btnExportXLSX').click(function(){ exportXLSX(); });

    // Toggle absent only
    $('#toggleAbsentOnly').change(function(){ applyAbsentFilter(); });

        // Page size change
        $('#pageSize').change(function(){ currentPage=1; paginate(); });

        // Ensure default shows all rows (override any UI defaults/caching)
        try { $('#pageSize').val('all'); } catch(e) { /* ignore */ }

        paginate();

        // Mode Rekap Bulanan toggle
        $('#btnToggleRekap').on('click', function(){
            var isDetail = $('#presensiTable').is(':visible');
            if (isDetail){
                $('#presensiTable').addClass('d-none');
                $('#rekapBulananWrapper').removeClass('d-none');
                $('#pageSizeWrapper,#pager').hide();
                $(this).html('<i class="bi bi-calendar3-week"></i> Mode Detail');
            } else {
                $('#rekapBulananWrapper').addClass('d-none');
                $('#presensiTable').removeClass('d-none');
                $('#pageSizeWrapper').show();
                $(this).html('<i class="bi bi-grid-3x3-gap"></i> Mode Rekap');
                paginate();
            }
            applyAbsentFilter();
        });

        // Sorting nama di tabel rekap
        $(document).on('click','.sort-name', function(e){
            e.preventDefault();
            var order = $(this).data('order');
            var rows = $('#tblRekapBulanan tbody tr').get();
            rows.sort(function(a,b){
                var A=$(a).data('name'); var B=$(b).data('name');
                if (A<B) return order==='asc'? -1:1;
                if (A>B) return order==='asc'? 1:-1;
                return 0;
            });
            $.each(rows, function(i,row){ $('#tblRekapBulanan tbody').append(row); });
            $(this).data('order', order==='asc'?'desc':'asc');
            $(this).find('i').toggleClass('bi-sort-alpha-down bi-sort-alpha-up');
        });

        // Virtual scroll toggle
    var virtualActive=false;
    var compact=false;
        $('#btnToggleVirtual').on('click', function(){
            virtualActive=!virtualActive;
            var wrap = $('#presensiTable .table-wrap');
            if (virtualActive){
                $('#pageSizeWrapper,#pager').hide();
                wrap.css({'max-height':'65vh','overflow-y':'auto'}).addClass('virtual-active');
                $(this).addClass('btn-secondary').removeClass('btn-outline-secondary');
            } else {
                wrap.css({'max-height':'','overflow-y':''}).removeClass('virtual-active');
                if (!$('#rekapBulananWrapper').is(':visible')) $('#pageSizeWrapper,#pager').show();
                $(this).removeClass('btn-secondary').addClass('btn-outline-secondary');
            }
        });

        $('#btnToggleCompact').on('click', function(){
            compact=!compact;
            if(compact){
                $('#presensiTable').addClass('compact');
                $(this).addClass('btn-secondary').removeClass('btn-outline-secondary');
            } else {
                $('#presensiTable').removeClass('compact');
                $(this).removeClass('btn-secondary').addClass('btn-outline-secondary');
            }
        });

        // Tampilkan loading saat filter diaplikasikan
        $('#filterForm').on('submit', function() {
            $('.loading-overlay').show();
        });
    });
</script>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
