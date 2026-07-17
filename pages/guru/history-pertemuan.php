<?php
require_once __DIR__ . '/../../auth_helper.php';
require_once __DIR__ . '/../../bootstrap.php';

// Safe check: guru access level only (hak_akses = 2)
if (!isset($_SESSION["no_induk"]) || $_SESSION['hak_akses'] != 2) {
    header("Location: ../../index.php?haruslogin");
    exit;
}

$nip = $_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nip);

// Filter logic
$filterType = $_GET['filter'] ?? 'semua';
$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate = $_GET['end'] ?? date('Y-m-t');

if ($filterType === 'minggu') {
    $startDate = date('Y-m-d', strtotime('monday this week'));
    $endDate = date('Y-m-d', strtotime('sunday this week'));
} elseif ($filterType === 'bulan') {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
} elseif ($filterType === 'semua') {
    $startDate = '';
    $endDate = '';
}

// Build query
$where = "no_induk = '$nipEsc'";
if ($startDate && $endDate) {
    $startEsc = mysqli_real_escape_string($conn, $startDate);
    $endEsc = mysqli_real_escape_string($conn, $endDate);
    $where .= " AND tanggal BETWEEN '$startEsc' AND '$endEsc'";
}

$qHistory = mysqli_query($conn, "SELECT tanggal, nama_mapel, kelas, materi, kegiatan, absen FROM tbl_materi WHERE $where ORDER BY tanggal DESC, id_materi DESC");
$historyData = [];
if ($qHistory) {
    while ($row = mysqli_fetch_assoc($qHistory)) {
        $historyData[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>History Pertemuan - Guru</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link rel='stylesheet' href='pages/guru/css/guru-2026-scoped.css?v=<?= time() ?>'>
    <link rel='stylesheet' href='pages/guru/css/guru-desktop.css?v=<?= time() ?>'>
    <style>
        body, #content-wrapper, .desktop-sidebar, .desktop-nav, .nav-item, h1, h2, h3, h4, h5, h6, p, span, a, div, button, input, textarea, select, table {
            font-family: 'Poppins', sans-serif !important;
        }
        .filter-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .table-custom {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .table-custom table { margin-bottom: 0; }
        .table-custom thead { background: #f8fafc; }
        .table-custom th { font-weight: 600; color: #475569; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; padding: 12px 16px; }
        .table-custom td { padding: 16px; color: #1e293b; font-size: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .btn-filter { font-weight: 600; border-radius: 10px; padding: 8px 16px; font-size: 13px; }
        .badge-absen { font-size: 11px; padding: 4px 8px; border-radius: 6px; background: #fee2e2; color: #dc2626; display: inline-block; margin-bottom: 4px; border: 1px solid #fecaca; }
        .page-header-premium {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #fff;
            padding: 30px 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .page-header-premium h2 { margin: 0; font-weight: 800; font-size: 1.8rem; letter-spacing: -0.5px; }
        .page-header-premium p { margin: 5px 0 0 0; color: rgba(255,255,255,0.8); font-size: 1rem; }
    </style>
</head>
<body class="bg-slate-50">

<?php include 'guru_sidebar_shared.php'; ?>

<div class="app-shell" id="desktopAppShell">
    <div class="desktop-center-column">
        
        <div class="page-header-premium">
            <div style="position: relative; z-index: 10;">
                <h2><i class="bi bi-clock-history me-2"></i>History Pertemuan</h2>
                <p>Riwayat materi dan kegiatan yang telah Anda laksanakan.</p>
            </div>
            <!-- Decorative circle -->
            <div style="position: absolute; right: -50px; top: -50px; width: 150px; height: 150px; border-radius: 50%; background: rgba(255,255,255,0.05);"></div>
        </div>

        <div class="filter-card">
            <form method="GET" class="row align-items-end g-3" action="router.php">
                <input type="hidden" name="type" value="guru">
                <input type="hidden" name="page" value="history-pertemuan">
                <div class="col-md-3">
                    <label class="form-label text-xs fw-bold text-slate-500 uppercase">Pilih Waktu</label>
                    <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()" style="border-radius:10px; padding:8px 12px; font-size:13px;">
                        <option value="minggu" <?= $filterType == 'minggu' ? 'selected' : '' ?>>Minggu Ini</option>
                        <option value="bulan" <?= $filterType == 'bulan' ? 'selected' : '' ?>>Bulan Ini</option>
                        <option value="semua" <?= $filterType == 'semua' ? 'selected' : '' ?>>Semua Waktu</option>
                        <option value="custom" <?= $filterType == 'custom' ? 'selected' : '' ?>>Kustom Rentang</option>
                    </select>
                </div>
                <?php if ($filterType == 'custom'): ?>
                    <div class="col-md-3">
                        <label class="form-label text-xs fw-bold text-slate-500 uppercase">Dari Tanggal</label>
                        <input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>" class="form-control form-control-sm" style="border-radius:10px; padding:8px 12px; font-size:13px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-xs fw-bold text-slate-500 uppercase">Sampai Tanggal</label>
                        <input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>" class="form-control form-control-sm" style="border-radius:10px; padding:8px 12px; font-size:13px;">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-filter w-100" style="background: #3b82f6; border: none;"><i class="bi bi-search me-1"></i> Terapkan</button>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-custom">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">Tanggal</th>
                            <th width="20%">Kelas & Mapel</th>
                            <th width="25%">Materi / Kegiatan</th>
                            <th width="35%">Siswa Absen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($historyData) > 0): ?>
                            <?php foreach ($historyData as $idx => $row): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="fw-bold"><?= date('d M Y', strtotime($row['tanggal'])) ?></div>
                                        <div class="text-muted" style="font-size:11px;"><?= ubah_nama_hari($row['tanggal']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($row['kelas']) ?></div>
                                        <div class="text-muted" style="font-size:12px;"><?= htmlspecialchars($row['nama_mapel']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold mb-1" style="font-size:13px;"><?= htmlspecialchars($row['materi']) ?></div>
                                        <div class="text-muted" style="font-size:12px; line-height:1.4;"><?= nl2br(htmlspecialchars($row['kegiatan'])) ?></div>
                                    </td>
                                    <td>
                                        <?php
                                        $absenStr = trim($row['absen'] ?? '');
                                        if (empty($absenStr) || $absenStr == '-' || $absenStr == 'Nihil') {
                                            echo '<span class="badge bg-success" style="font-weight:600; padding:4px 8px; border-radius:6px;"><i class="bi bi-check-circle me-1"></i>Hadir Semua</span>';
                                        } else {
                                            // Format the string "Nama : Alasan, Nama2 : Alasan2"
                                            $absenList = explode(',', $absenStr);
                                            foreach ($absenList as $abs) {
                                                $abs = trim($abs);
                                                if (!empty($abs)) {
                                                    echo '<div class="badge-absen"><i class="bi bi-person-x-fill me-1"></i>' . htmlspecialchars($abs) . '</div> ';
                                                }
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-folder-x display-4 d-block mb-3" style="color: #cbd5e1;"></i>
                                        Tidak ada data pertemuan pada rentang tanggal tersebut.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- BOTTOM NAV -->
    <?php include 'guru_common_footer.php'; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
