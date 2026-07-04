<?php
// Pastikan sudah ada koneksi database
if (!isset($conn)) {
    include "koneksi.php";
}

// Debug mode (set false setelah selesai debugging)
$debugMode = false;

// Ambil tanggal dari input (default = hari ini)
$tanggal = $_GET['tanggal'] ?? date("Y-m-d");
$filterGuru = $_GET['guru'] ?? '';

if ($debugMode) {
    echo "<!-- DEBUG: Tanggal = $tanggal, Filter Guru = $filterGuru -->";
}

// Konversi tanggal jadi nama hari (Senin, Selasa, dst)
$hari = date('l', strtotime($tanggal));

// Sesuaikan bahasa hari kalau di database pakai bahasa Indonesia
$hariMap = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];
$hariDb = $hariMap[$hari];

if ($debugMode) {
    echo "<!-- DEBUG: Hari Database = $hariDb -->";
}

// Ambil daftar guru yang punya jadwal di hari ini untuk dropdown
$sqlGuruListQuery = "SELECT DISTINCT g.no_induk, g.nama_guru
    FROM tbl_mapel_ampu ma
    INNER JOIN tbl_guru g ON ma.no_induk = g.no_induk
    WHERE ma.hari = '$hariDb'
    ORDER BY g.nama_guru ASC";

if ($debugMode) {
    echo "<!-- DEBUG Query Guru List: $sqlGuruListQuery -->";
}

$sqlGuruList = mysqli_query($conn, $sqlGuruListQuery);

if (!$sqlGuruList) {
    echo '<div class="container-fluid"><div class="alert alert-danger">Error mengambil daftar guru: ' . mysqli_error($conn) . '</div></div>';
    if ($debugMode) {
        echo '<div class="container-fluid"><div class="alert alert-info">Query: ' . htmlspecialchars($sqlGuruListQuery) . '</div></div>';
    }
}

// Query gabungan jadwal + jurnal dengan filter guru (optional)
$whereGuru = $filterGuru ? "AND g.no_induk = '".mysqli_real_escape_string($conn, $filterGuru)."'" : "";

$sqlMainQuery = "SELECT g.no_induk, g.nama_guru, ma.nama_mapel, ma.kelas, ma.jam_mulai, ma.jam_selesai, m.id_materi, ma.id_mapel, ma.hari
    FROM tbl_mapel_ampu ma
    INNER JOIN tbl_guru g ON ma.no_induk = g.no_induk
    LEFT JOIN tbl_materi m 
           ON ma.id_mapel = m.id_mapel
          AND ma.no_induk = m.no_induk
          AND ma.kelas = m.kelas
          AND m.tanggal = '$tanggal'
    WHERE ma.hari = '$hariDb' 
          AND ma.jam_mulai IS NOT NULL 
          AND ma.jam_mulai != '' 
          AND ma.jam_selesai IS NOT NULL 
          AND ma.jam_selesai != '' 
          $whereGuru
    ORDER BY g.nama_guru ASC, ma.jam_mulai ASC
    LIMIT 1000";

if ($debugMode) {
    echo "<!-- DEBUG Query Main: $sqlMainQuery -->";
}

$sql = mysqli_query($conn, $sqlMainQuery);

// Hitung total JP per guru berdasarkan JADWAL perminggu (tbl_mapel_ampu)
// Semua hari dijumlah, bukan hanya hari ini
$sqlWeeklyJP = "SELECT ma.no_induk,
    SUM(TIMESTAMPDIFF(MINUTE, ma.jam_mulai, ma.jam_selesai)) AS total_menit
FROM tbl_mapel_ampu ma
WHERE ma.jam_mulai IS NOT NULL AND ma.jam_mulai != ''
    AND ma.jam_selesai IS NOT NULL AND ma.jam_selesai != ''
    AND ma.jam_mulai < ma.jam_selesai
GROUP BY ma.no_induk";

$resWeeklyJP = mysqli_query($conn, $sqlWeeklyJP);
$guruWeeklyMenit = [];
if ($resWeeklyJP) {
    while ($rw = mysqli_fetch_assoc($resWeeklyJP)) {
        $guruWeeklyMenit[$rw['no_induk']] = (int)$rw['total_menit'];
    }
}

// Deteksi guru BK berdasarkan nama_mapel mengandung 'BK' atau 'Bimbingan'
// Hitung total siswa yang diajar per guru BK dari semua kelasnya
$sqlBKSiswa = "SELECT ma.no_induk,
    COUNT(DISTINCT s.no_induk) AS total_siswa
FROM tbl_mapel_ampu ma
INNER JOIN tbl_siswa s ON s.kelas = ma.kelas
WHERE (ma.nama_mapel LIKE '%BK%' OR ma.nama_mapel LIKE '%Bimbingan%')
    AND (s.status = 'Aktif' OR s.status IS NULL)
GROUP BY ma.no_induk";

$resBKSiswa = mysqli_query($conn, $sqlBKSiswa);
$guruBKSiswa = []; // no_induk => total_siswa
if ($resBKSiswa) {
    while ($rb = mysqli_fetch_assoc($resBKSiswa)) {
        $guruBKSiswa[$rb['no_induk']] = (int)$rb['total_siswa'];
    }
}
$isBKGuru = array_keys($guruBKSiswa); // daftar no_induk guru BK

if (!$sql) {
    echo '<div class="container-fluid">';
    echo '<div class="alert alert-danger"><strong>Query Error:</strong> ' . mysqli_error($conn) . '</div>';
    echo '<div class="alert alert-info">';
    echo '<strong>Debugging Information:</strong><br>';
    echo '- Tanggal: ' . htmlspecialchars($tanggal) . '<br>';
    echo '- Hari: ' . htmlspecialchars($hariDb) . '<br>';
    echo '- Filter Guru: ' . ($filterGuru ? htmlspecialchars($filterGuru) : 'Semua') . '<br>';
    echo '<br><strong>Possible Issues:</strong><br>';
    echo '1. Tabel tidak ada: tbl_mapel_ampu, tbl_guru, atau tbl_materi<br>';
    echo '2. Kolom tidak sesuai: hari, nama_guru, nama_mapel, kelas, jam_mulai, jam_selesai<br>';
    echo '3. Koneksi database error<br>';
    echo '</div>';
    if ($debugMode) {
        echo '<div class="alert alert-warning">Query: <pre>' . htmlspecialchars($sqlMainQuery) . '</pre></div>';
    }
    echo '</div>';
    exit;
}

// ============================================================
// PRE-KOMPUTASI SEMUA DATA (sebelum HTML dirender)
// Sehingga variabel tersedia saat panel notif ditampilkan
// ============================================================

// Pre-fetch semua baris ke array
$allRows = [];
while ($r = mysqli_fetch_assoc($sql)) {
    $allRows[] = $r;
}

// Hitung total JP hari ini per guru
$guruTotalMenit = [];
foreach ($allRows as $r) {
    $nip = $r['no_induk'];
    if (!isset($guruTotalMenit[$nip])) $guruTotalMenit[$nip] = 0;
    if (!empty($r['jam_mulai']) && !empty($r['jam_selesai'])) {
        $mulai   = strtotime($r['jam_mulai']);
        $selesai = strtotime($r['jam_selesai']);
        if ($selesai > $mulai) {
            $guruTotalMenit[$nip] += ($selesai - $mulai) / 60;
        }
    }
}

// Fungsi prioritas untuk sorting
function getPriority($row, $guruWeeklyMenit, $guruBKSiswa, $isBKGuru) {
    $nip = $row['no_induk'];
    $isi = $row['id_materi'] ? 1 : 0;
    if (in_array($nip, $isBKGuru)) {
        $terpenuhi = ($guruBKSiswa[$nip] ?? 0) >= 150 ? 1 : 0;
    } else {
        $terpenuhi = ($guruWeeklyMenit[$nip] ?? 0) >= 540 ? 1 : 0;
    }
    // 0=kurang+belum, 1=kurang+sudah, 2=terpenuhi+belum, 3=terpenuhi+sudah
    return $terpenuhi * 2 + $isi;
}

usort($allRows, function($a, $b) use ($guruWeeklyMenit, $guruBKSiswa, $isBKGuru) {
    $pa = getPriority($a, $guruWeeklyMenit, $guruBKSiswa, $isBKGuru);
    $pb = getPriority($b, $guruWeeklyMenit, $guruBKSiswa, $isBKGuru);
    if ($pa !== $pb) return $pa - $pb;
    return strcmp($a['nama_guru'], $b['nama_guru']);
});

// Statistik untuk panel notif (dihitung sebelum HTML)
$guruBelumIsiKurang = [];
$guruSudahIsiKurang = [];
foreach ($allRows as $r) {
    $nip  = $r['no_induk'];
    $isBK = in_array($nip, $isBKGuru);
    if ($isBK) {
        $kurang = ($guruBKSiswa[$nip] ?? 0) < 150;
    } else {
        $kurang = ($guruWeeklyMenit[$nip] ?? 0) < 540;
    }
    if ($kurang) {
        if (!$r['id_materi']) {
            $guruBelumIsiKurang[$nip] = $r['nama_guru'];
        } else {
            $guruSudahIsiKurang[$nip] = $r['nama_guru'];
        }
    }
}

$hasData   = count($allRows) > 0;
$totalRows = count($allRows);

// ─ Fetch konfirmasi ketua kelas untuk tanggal ini ──────────────────────────────
// Buat tabel jika belum ada
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

$konfirmasiMonitor = []; // keyed by id_mapel
$_tblKonf = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_konfirmasi_kehadiran_guru'");
if ($_tblKonf && mysqli_num_rows($_tblKonf) > 0) {
    $tanggalMonEsc = mysqli_real_escape_string($conn, $tanggal);
    $whereGuruKonf = $filterGuru ? "AND no_induk_guru = '".mysqli_real_escape_string($conn, $filterGuru)."'" : '';
    $_konfSql = mysqli_query($conn,
        "SELECT id_mapel, kelas, status, nama_ketua, catatan, updated_at
         FROM tbl_konfirmasi_kehadiran_guru
         WHERE tanggal = '$tanggalMonEsc' $whereGuruKonf"
    );
    if ($_konfSql) {
        while ($_kr = mysqli_fetch_assoc($_konfSql)) {
            $konfirmasiMonitor[(int)$_kr['id_mapel']] = $_kr;
        }
    }
}

?>

<style>
@keyframes blink-warning {
    0%, 50%, 100% { opacity: 1; }
    25%, 75% { opacity: 0.3; }
}
.badge-blink {
    animation: blink-warning 1.5s ease-in-out infinite;
}
@keyframes blink-orange {
    0%, 50%, 100% { opacity: 1; background-color: #fd7e14; }
    25%, 75% { opacity: 0.4; background-color: #ffc107; }
}
.badge-blink-orange {
    animation: blink-orange 1.5s ease-in-out infinite;
    color: #fff !important;
}
.filter-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.filter-card label {
    color: white;
    font-weight: 600;
    margin-bottom: 5px;
}
.filter-card .form-control, .filter-card .form-select {
    border-radius: 8px;
    border: 2px solid rgba(255,255,255,0.3);
}
</style>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">
        <i class="fas fa-chart-line"></i> Monitoring Jurnal Guru
    </h1>

    <!-- Filter Card -->
    <div class="card filter-card">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="monitoring-guru">
            
            <div class="col-md-4">
                <label class="form-label">
                    <i class="fas fa-calendar-day"></i> Pilih Tanggal:
                </label>
                <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($tanggal) ?>" required>
            </div>
            
            <div class="col-md-4">
                <label class="form-label">
                    <i class="fas fa-user-tie"></i> Filter Guru:
                </label>
                <select name="guru" class="form-control">
                    <option value="">-- Semua Guru --</option>
                    <?php 
                    while($rowGuru = mysqli_fetch_assoc($sqlGuruList)) {
                        $selected = ($filterGuru == $rowGuru['no_induk']) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($rowGuru['no_induk'])."' $selected>".htmlspecialchars($rowGuru['nama_guru'])."</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <button type="submit" class="btn btn-light btn-block">
                    <i class="fas fa-search"></i> Tampilkan Data
                </button>
            </div>
        </form>
    </div>

    <!-- Info Card -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 
        Menampilkan data untuk: <strong><?= $hariDb ?>, <?= date('d F Y', strtotime($tanggal)) ?></strong>
        <?php if($filterGuru): ?>
            - Filter: <strong><?php 
                $sqlNamaGuru = mysqli_query($conn, "SELECT nama_guru FROM tbl_guru WHERE no_induk = '".mysqli_real_escape_string($conn, $filterGuru)."'");
                if($rowNamaGuru = mysqli_fetch_assoc($sqlNamaGuru)) {
                    echo htmlspecialchars($rowNamaGuru['nama_guru']);
                }
            ?></strong>
        <?php endif; ?>
    </div>

    <!-- Panel Notifikasi -->
    <?php if (!empty($guruBelumIsiKurang) || !empty($guruSudahIsiKurang)): ?>
    <div class="row mb-3">
        <?php if (!empty($guruBelumIsiKurang)): ?>
        <div class="col-md-6 mb-3">
            <div class="card border-left-danger shadow">
                <div class="card-header bg-danger text-white py-2">
                    <strong><i class="fas fa-exclamation-triangle"></i>
                        <span class="badge-blink d-inline-block ml-1" style="animation:blink-warning 1.5s infinite;">&#9632;</span>
                        Jam Kurang &amp; Belum Isi Jurnal (<?= count($guruBelumIsiKurang) ?>)
                    </strong>
                </div>
                <div class="card-body py-2">
                    <ul class="mb-0 pl-3">
                        <?php foreach ($guruBelumIsiKurang as $nip => $nama): ?>
                        <li class="text-danger font-weight-bold">
                            <?= htmlspecialchars($nama) ?>
                            <small class="text-muted">(<?= number_format(($guruWeeklyMenit[$nip] ?? 0) / 45, 1) ?> JP/minggu)</small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($guruSudahIsiKurang)): ?>
        <div class="col-md-6 mb-3">
            <div class="card border-left-warning shadow">
                <div class="card-header bg-warning text-dark py-2">
                    <strong><i class="fas fa-clock"></i>
                        <span class="badge-blink-orange d-inline-block ml-1 px-1" style="animation:blink-orange 1.5s infinite;border-radius:3px;">&#9632;</span>
                        Jam Kurang &amp; Sudah Isi Jurnal (<?= count($guruSudahIsiKurang) ?>)
                    </strong>
                </div>
                <div class="card-body py-2">
                    <ul class="mb-0 pl-3">
                        <?php foreach ($guruSudahIsiKurang as $nip => $nama): ?>
                        <li class="text-dark font-weight-bold">
                            <?= htmlspecialchars($nama) ?>
                            <small class="text-muted">(<?= number_format(($guruWeeklyMenit[$nip] ?? 0) / 45, 1) ?> JP/minggu)</small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Export Buttons -->
    <div class="mb-3">
        <button type="button" class="btn btn-danger" onclick="exportBelumIsiPDF()">
            <i class="fas fa-file-pdf"></i> Export PDF - Guru Belum Isi Jurnal
        </button>
        <button type="button" class="btn btn-primary ml-2" onclick="exportBelumIsiTXT()">
            <i class="fas fa-file-alt"></i> Export TXT - Guru Belum Isi Jurnal
        </button>
    </div>

    <!-- Tabel Monitoring -->

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-table"></i> Daftar Jadwal & Status Jurnal
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th width="50">No</th>
                            <th width="100">Hari</th>
                            <th width="200">Nama Guru</th>
                            <th>Mata Pelajaran</th>
                            <th width="100">Kelas</th>
                            <th width="120">Jam Mengajar</th>
                            <th width="220">Status</th>
                            <th width="180">Konfirmasi Ketua Kelas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no       = 1;
                        $sudahIsi = 0;
                        $belumIsi = 0;

                        foreach ($allRows as $row) {
                            $nip      = $row['no_induk'];
                            $isBK     = in_array($nip, $isBKGuru);
                            $totalJP  = ($guruTotalMenit[$nip] ?? 0) / 45;
                            $weeklyJP = ($guruWeeklyMenit[$nip] ?? 0) / 45;
                            $jpHari   = number_format($totalJP, 1);
                            $jpMinggu = number_format($weeklyJP, 1);

                            if ($isBK) {
                                // --- GURU BK: hitung dari jumlah siswa ---
                                $totalSiswa = $guruBKSiswa[$nip] ?? 0;
                                $terpenuhi  = $totalSiswa >= 150;
                                $infoText   = "<i class='fas fa-users'></i> Siswa: {$totalSiswa} dari semua kelas";

                                if ($terpenuhi) {
                                    $badgeKuota = "<span class='badge badge-success badge-pill' style='font-size:11px;'>
                                                      <i class='fas fa-check-circle'></i> Siswa Terpenuhi ({$totalSiswa} siswa)
                                                   </span>";
                                } else {
                                    $badgeKuota = "<span class='badge badge-pill badge-blink-orange' style='font-size:11px;'>
                                                      <i class='fas fa-users'></i> Kurang Siswa ({$totalSiswa}/150)
                                                   </span>";
                                }
                            } else {
                                // --- GURU REGULER: hitung dari JP per minggu ---
                                $terpenuhi = ($guruWeeklyMenit[$nip] ?? 0) >= 540;
                                $infoText  = "<i class='fas fa-stopwatch'></i> Hari ini: {$jpHari} JP &nbsp;|&nbsp; Per minggu: {$jpMinggu} JP";

                                if ($terpenuhi) {
                                    $badgeKuota = "<span class='badge badge-success badge-pill' style='font-size:11px;'>
                                                      <i class='fas fa-check-circle'></i> Jam Terpenuhi ({$jpMinggu} JP/minggu)
                                                   </span>";
                                } else {
                                    $badgeKuota = "<span class='badge badge-pill badge-blink-orange' style='font-size:11px;'>
                                                      <i class='fas fa-clock'></i> Jam Kurang ({$jpMinggu} JP/minggu)
                                                   </span>";
                                }
                            }

                            if ($row['id_materi']) {
                                $sudahIsi++;
                                $badgeJurnal = "<span class='badge badge-primary badge-pill' style='font-size:11px;'>
                                                   <i class='fas fa-check-circle'></i> Sudah Isi Jurnal
                                                </span>";
                                $rowClass = $terpenuhi ? '' : 'table-warning';
                            } else {
                                $belumIsi++;
                                $badgeJurnal = "<span class='badge badge-danger badge-pill badge-blink' style='font-size:11px;'>
                                                   <i class='fas fa-exclamation-triangle'></i> Belum Isi Jurnal
                                                </span>";
                                $rowClass = $terpenuhi ? '' : 'table-danger';
                            }

                            $labelBK = $isBK ? "<span class='badge badge-info badge-pill ml-1' style='font-size:10px;'>BK</span>" : '';

                            $konfirmMonRow = $konfirmasiMonitor[$row['id_mapel']] ?? null;
                            if ($konfirmMonRow) {
                                $kStatus = htmlspecialchars($konfirmMonRow['status']);
                                $kKetua  = htmlspecialchars($konfirmMonRow['nama_ketua']);
                                $kTime   = date('H:i', strtotime($konfirmMonRow['updated_at']));
                                $kStyleMap = ['Hadir'=>'background:#d1fae5;color:#065f46','Telat'=>'background:#fef9c3;color:#713f12','Izin'=>'background:#dbeafe;color:#1e3a5f','Tidak Hadir Tanpa Tugas'=>'background:#fee2e2;color:#7f1d1d','Tidak Hadir Ada Tugas'=>'background:#ffedd5;color:#7c2d12'];
                                $kStyle  = $kStyleMap[$konfirmMonRow['status']] ?? 'background:#f3f4f6;color:#374151';
                                $badgeKonfirm = "<span class='badge badge-pill' style='font-size:10px;{$kStyle}'>
                                    <i class='fas fa-user-check'></i> {$kStatus}
                                </span><br><small class='text-muted' style='font-size:10px;'>{$kKetua} &middot; {$kTime}</small>";
                            } else {
                                $badgeKonfirm = "<span class='text-muted' style='font-size:11px;'><i class='fas fa-clock text-muted'></i> Belum dikonfirmasi</span>";
                            }

                            echo "<tr class='$rowClass'>
                                    <td class='text-center'>".$no++."</td>
                                    <td class='text-center'><span class='badge badge-secondary'>".htmlspecialchars($row['hari'])."</span></td>
                                    <td><strong>".htmlspecialchars($row['nama_guru'])."</strong>{$labelBK}<br>
                                        <small class='text-muted'>{$infoText}</small>
                                    </td>
                                    <td>".htmlspecialchars($row['nama_mapel'])."</td>
                                    <td class='text-center'><span class='badge badge-primary'>".htmlspecialchars($row['kelas'])."</span></td>
                                    <td class='text-center'>
                                        <span class='badge badge-info badge-pill'>".htmlspecialchars($row['jam_mulai'])." - ".htmlspecialchars($row['jam_selesai'])."</span>
                                    </td>
                                    <td>{$badgeJurnal}<br class='mt-1'>{$badgeKuota}</td>
                                    <td>{$badgeKonfirm}</td>
                                  </tr>";
                        }

                        if (!$hasData) {
                            echo "<tr><td colspan='8' class='text-center text-muted py-4'>
                                    <i class='fas fa-info-circle fa-3x mb-3 text-muted'></i><br>
                                    <h5>Tidak ada jadwal</h5>
                                    <p>Tidak ada guru yang memiliki jadwal mengajar pada hari <strong>$hariDb</strong> tanggal <strong>$tanggal</strong></p>
                                  </td></tr>";
                        }
                        ?>
                    </tbody>
                    <?php if($hasData): ?>
                    <tfoot>
                        <tr class="bg-light font-weight-bold">
                            <td colspan="7" class="text-right">Total:</td>
                            <td class="text-center">
                                <span class="badge badge-success mr-2"><?= $sudahIsi ?> Sudah</span>
                                <span class="badge badge-danger"><?= $belumIsi ?> Belum</span>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <?php if($hasData): ?>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Jadwal</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $sudahIsi + $belumIsi ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sudah Isi Jurnal</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $sudahIsi ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Belum Isi Jurnal</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $belumIsi ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function exportBelumIsiPDF() {
    var tanggal = '<?= htmlspecialchars($tanggal) ?>';
    var hari = '<?= htmlspecialchars($hariDb) ?>';
    window.open('export_belum_isi_pdf.php?tanggal=' + tanggal + '&hari=' + hari, '_blank');
}

function exportBelumIsiTXT() {
    var tanggal = '<?= htmlspecialchars($tanggal) ?>';
    var hari = '<?= htmlspecialchars($hariDb) ?>';
    window.open('export_belum_isi_txt.php?tanggal=' + tanggal + '&hari=' + hari, '_blank');
}
</script>

