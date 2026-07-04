<?php
$hakAksesNilai = (int)($_SESSION['hak_akses'] ?? 0);
if (!in_array($hakAksesNilai, [1, 2], true)) {
    echo '<div class="container-fluid"><div class="alert alert-danger">Akses ditolak. Halaman ini hanya untuk admin/guru.</div></div>';
    return;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    require __DIR__ . '/../../koneksi.php';
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    echo '<div class="container-fluid"><div class="alert alert-danger">Koneksi database tidak tersedia.</div></div>';
    return;
}

require_once __DIR__ . '/../../eraport_helper.php';

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_leger_siswa_eraport (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        run_id VARCHAR(40) NOT NULL,
        synced_at DATETIME NOT NULL,
        semester VARCHAR(30) DEFAULT NULL,
        kelas VARCHAR(80) NOT NULL,
        nis VARCHAR(40) DEFAULT NULL,
        nama_siswa VARCHAR(200) DEFAULT NULL,
        nilai_rerata DECIMAL(6,2) DEFAULT NULL,
        raw_row LONGTEXT,
        PRIMARY KEY (id),
        KEY idx_run (run_id),
        KEY idx_kelas (kelas),
        KEY idx_nis (nis),
        KEY idx_synced_at (synced_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$mode = (string)($_GET['mode'] ?? 'kelas');
if (!in_array($mode, ['kelas', 'individu'], true)) {
    $mode = 'kelas';
}

$kelas = trim((string)($_GET['kelas'] ?? ''));
$kelasEraport = trim((string)($_GET['kelas_eraport'] ?? ''));
$idMapel = (int)($_GET['idmapel'] ?? 0);
$rawNoIndukSiswa = $_GET['no_induk_siswa'] ?? [];
$noIndukSiswaList = [];
if (is_array($rawNoIndukSiswa)) {
    foreach ($rawNoIndukSiswa as $nis) {
        $nis = trim((string)$nis);
        if ($nis !== '') {
            $noIndukSiswaList[] = $nis;
        }
    }
} else {
    $nis = trim((string)$rawNoIndukSiswa);
    if ($nis !== '') {
        $noIndukSiswaList[] = $nis;
    }
}
$noIndukSiswaList = array_values(array_unique($noIndukSiswaList));
$noIndukSiswa = $noIndukSiswaList[0] ?? '';

$tglAwal = trim((string)($_GET['tgl_awal'] ?? ''));
$tglAkhir = trim((string)($_GET['tgl_akhir'] ?? ''));
$ambil = isset($_GET['ambil']);

$kelasOptions = [];
$idSekolah = mt_current_school_id();
$qKelas = mysqli_query($conn, "
    SELECT kelas FROM (
        SELECT DISTINCT kelas FROM tbl_siswa WHERE id_sekolah=$idSekolah AND kelas IS NOT NULL AND kelas <> ''
        UNION
        SELECT DISTINCT kelas FROM tbl_siswa_eraport WHERE id_sekolah=$idSekolah AND kelas IS NOT NULL AND kelas <> ''
        UNION
        SELECT DISTINCT kelas FROM tbl_leger_siswa_eraport WHERE id_sekolah=$idSekolah AND kelas IS NOT NULL AND kelas <> ''
    ) t
    ORDER BY kelas ASC
");
if ($qKelas) {
    while ($r = mysqli_fetch_assoc($qKelas)) {
        $kelasOptions[] = (string)$r['kelas'];
    }
}

$mapelOptions = [];

$siswaOptions = [];
if ($kelas !== '') {
    $idSekolah = mt_current_school_id();
    $kelasEsc = mysqli_real_escape_string($conn, $kelas);
    $qSiswa = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE id_sekolah=$idSekolah AND kelas='{$kelasEsc}' AND status='Aktif' ORDER BY nama_siswa ASC");
    if ($qSiswa) {
        while ($r = mysqli_fetch_assoc($qSiswa)) {
            $siswaOptions[] = $r;
        }
    }
}

$chartLabels = [];
$chartValues = [];
$chartDatasets = [];
$tableRows = [];
$summary = [
    'records' => 0,
    'avg' => null,
    'max' => null,
    'min' => null,
];
$errorMsg = '';
$syncInfo = '';

if ($ambil) {
    if ($kelas === '') {
        $errorMsg = 'Silakan pilih kelas terlebih dahulu.';
    } elseif ($mode === 'individu' && empty($noIndukSiswaList)) {
        $errorMsg = 'Untuk mode individu, silakan pilih siswa.';
    } else {
        if ($kelasEraport !== '') {
            $sync = eraport_sync_leger_kelas_to_local($conn, $kelas, ['kelas_eraport' => $kelasEraport]);
        } else {
            $sync = eraport_sync_leger_kelas_to_local($conn, $kelas);
        }
        if (!empty($sync['success'])) {
            $syncSummary = $sync['summary'] ?? [];
            $syncInfo = 'Sinkron leger terbaru: fetched ' . (int)($syncSummary['fetched'] ?? 0) . ', inserted ' . (int)($syncSummary['inserted'] ?? 0);
            if (!empty($syncSummary['kelas_eraport'])) {
                $syncInfo .= ' | Kelas e-Raport: ' . (string)$syncSummary['kelas_eraport'];
            }
        } else {
            $syncInfo = 'Sinkron leger gagal: ' . (string)($sync['message'] ?? 'unknown error');
        }

        $idSekolah = mt_current_school_id();
        $where = [];
        $kelasEsc = mysqli_real_escape_string($conn, $kelas);
        $where[] = "l.id_sekolah=$idSekolah";
        $where[] = "l.kelas='{$kelasEsc}'";
        $where[] = 'l.nilai_rerata IS NOT NULL';

        if ($mode === 'individu') {
            $nisEscList = [];
            foreach ($noIndukSiswaList as $nis) {
                $nisEscList[] = "'" . mysqli_real_escape_string($conn, $nis) . "'";
            }
            $where[] = 'l.nis IN (' . implode(',', $nisEscList) . ')';
        }

        if ($tglAwal !== '') {
            $tglAwalEsc = mysqli_real_escape_string($conn, $tglAwal);
            $where[] = "DATE(l.synced_at) >= '{$tglAwalEsc}'";
        }
        if ($tglAkhir !== '') {
            $tglAkhirEsc = mysqli_real_escape_string($conn, $tglAkhir);
            $where[] = "DATE(l.synced_at) <= '{$tglAkhirEsc}'";
        }

        $whereSql = implode(' AND ', $where);

        if ($mode === 'individu' && count($noIndukSiswaList) > 1) {
            $sqlTrend = "SELECT DATE(l.synced_at) AS tanggal, l.nis AS no_induk_siswa, ROUND(AVG(l.nilai_rerata), 2) AS rata_nilai
                         FROM tbl_leger_siswa_eraport l
                         WHERE {$whereSql}
                         GROUP BY DATE(l.synced_at), l.nis
                         ORDER BY DATE(l.synced_at) ASC";
            $qTrend = mysqli_query($conn, $sqlTrend);

            if (!$qTrend) {
                $errorMsg = 'Gagal mengambil data nilai: ' . mysqli_error($conn);
            } else {
                $namaByNoInduk = [];
                if (!empty($noIndukSiswaList)) {
                    $inNama = [];
                    foreach ($noIndukSiswaList as $nis) {
                        $inNama[] = "'" . mysqli_real_escape_string($conn, $nis) . "'";
                    }
                    $idSekolah = mt_current_school_id();
                    $qNamaMap = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE id_sekolah=$idSekolah AND no_induk IN (" . implode(',', $inNama) . ")");
                    if ($qNamaMap) {
                        while ($nr = mysqli_fetch_assoc($qNamaMap)) {
                            $namaByNoInduk[(string)$nr['no_induk']] = (string)$nr['nama_siswa'];
                        }
                    }
                }

                $dateMap = [];
                $rowsBySiswa = [];
                while ($row = mysqli_fetch_assoc($qTrend)) {
                    $tgl = (string)$row['tanggal'];
                    $nis = (string)$row['no_induk_siswa'];
                    $val = (float)$row['rata_nilai'];
                    $dateMap[$tgl] = true;
                    if (!isset($rowsBySiswa[$nis])) {
                        $rowsBySiswa[$nis] = [];
                    }
                    $rowsBySiswa[$nis][$tgl] = $val;
                }

                $chartLabels = array_keys($dateMap);
                sort($chartLabels);

                $palette = [
                    ['#1cc88a', 'rgba(28, 200, 138, 0.12)'],
                    ['#4e73df', 'rgba(78, 115, 223, 0.12)'],
                    ['#e74a3b', 'rgba(231, 74, 59, 0.12)'],
                    ['#f6c23e', 'rgba(246, 194, 62, 0.12)'],
                    ['#36b9cc', 'rgba(54, 185, 204, 0.12)'],
                ];

                $allVals = [];
                $iColor = 0;
                foreach ($noIndukSiswaList as $nis) {
                    $rowSiswa = $rowsBySiswa[$nis] ?? [];
                    $data = [];
                    foreach ($chartLabels as $tgl) {
                        $data[] = isset($rowSiswa[$tgl]) ? $rowSiswa[$tgl] : null;
                        if (isset($rowSiswa[$tgl])) {
                            $allVals[] = (float)$rowSiswa[$tgl];
                            $tableRows[] = [
                                'tanggal' => $tgl,
                                'siswa' => ($namaByNoInduk[$nis] ?? $nis) . ' (' . $nis . ')',
                                'rata_nilai' => round((float)$rowSiswa[$tgl], 2),
                            ];
                        }
                    }

                    $color = $palette[$iColor % count($palette)];
                    $iColor++;
                    $chartDatasets[] = [
                        'label' => ($namaByNoInduk[$nis] ?? $nis) . ' (' . $nis . ')',
                        'data' => $data,
                        'borderColor' => $color[0],
                        'backgroundColor' => $color[1],
                        'fill' => false,
                        'tension' => 0.25,
                        'pointRadius' => 3,
                        'pointHoverRadius' => 5,
                    ];
                }

                if (!empty($allVals)) {
                    $summary['records'] = count($allVals);
                    $summary['avg'] = round(array_sum($allVals) / count($allVals), 2);
                    $summary['max'] = max($allVals);
                    $summary['min'] = min($allVals);
                }
            }
        } else {
            $sqlTrend = "SELECT DATE(l.synced_at) AS tanggal, ROUND(AVG(l.nilai_rerata), 2) AS rata_nilai, COUNT(l.id) AS jumlah_entri,
                        ROUND(MIN(l.nilai_rerata), 2) AS nilai_min, ROUND(MAX(l.nilai_rerata), 2) AS nilai_max
                    FROM tbl_leger_siswa_eraport l
                         WHERE {$whereSql}
                    GROUP BY DATE(l.synced_at)
                    ORDER BY DATE(l.synced_at) ASC";
            $qTrend = mysqli_query($conn, $sqlTrend);

            if (!$qTrend) {
                $errorMsg = 'Gagal mengambil data nilai: ' . mysqli_error($conn);
            } else {
                while ($row = mysqli_fetch_assoc($qTrend)) {
                    $tableRows[] = $row;
                    $chartLabels[] = (string)$row['tanggal'];
                    $chartValues[] = (float)$row['rata_nilai'];
                }

                if (!empty($chartValues)) {
                    $summary['records'] = count($chartValues);
                    $summary['avg'] = round(array_sum($chartValues) / count($chartValues), 2);
                    $summary['max'] = max($chartValues);
                    $summary['min'] = min($chartValues);
                }

                $chartDatasets[] = [
                    'label' => 'Rata-rata Nilai',
                    'data' => $chartValues,
                    'borderColor' => '#1cc88a',
                    'backgroundColor' => 'rgba(28, 200, 138, 0.15)',
                    'fill' => true,
                    'tension' => 0.25,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                ];
            }
        }
    }
}

$namaSiswaTerpilih = '';
if ($noIndukSiswa !== '') {
    $idSekolah = mt_current_school_id();
    $nisEsc = mysqli_real_escape_string($conn, $noIndukSiswa);
    $qNama = mysqli_query($conn, "SELECT nama_siswa FROM tbl_siswa WHERE id_sekolah=$idSekolah AND no_induk='{$nisEsc}' LIMIT 1");
    if ($qNama && ($rNama = mysqli_fetch_assoc($qNama))) {
        $namaSiswaTerpilih = (string)$rNama['nama_siswa'];
    }
}

$exportQuery = [
    'mode' => $mode,
    'kelas' => $kelas,
    'kelas_eraport' => $kelasEraport,
    'tgl_awal' => $tglAwal,
    'tgl_akhir' => $tglAkhir,
];
foreach ($noIndukSiswaList as $nisExp) {
    $exportQuery['no_induk_siswa'][] = $nisExp;
}
$exportCsvUrl = 'api/nilai-perkembangan-export.php?' . http_build_query(array_merge($exportQuery, ['format' => 'csv']));
$exportPrintUrl = 'api/nilai-perkembangan-export.php?' . http_build_query(array_merge($exportQuery, ['format' => 'print']));
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Nilai Siswa & Grafik Perkembangan</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Ambil Data Nilai Siswa</h6>
        </div>
        <div class="card-body">
            <form method="get" class="row">
                <input type="hidden" name="page" value="nilai-perkembangan">
                <input type="hidden" name="ambil" value="1">

                <div class="col-md-3 mb-3">
                    <label class="small text-muted">Mode Analisis</label>
                    <select name="mode" id="modeAnalisis" class="form-control form-control-sm">
                        <option value="kelas" <?php echo $mode === 'kelas' ? 'selected' : ''; ?>>Per Kelas</option>
                        <option value="individu" <?php echo $mode === 'individu' ? 'selected' : ''; ?>>Per Individu</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="small text-muted">Kelas</label>
                    <select name="kelas" id="kelasSelect" class="form-control form-control-sm" required>
                        <option value="">Pilih kelas</option>
                        <?php foreach ($kelasOptions as $kelasOpt): ?>
                            <option value="<?php echo htmlspecialchars($kelasOpt); ?>" <?php echo $kelas === $kelasOpt ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kelasOpt); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="small text-muted d-flex justify-content-between">
                        <span>Kelas e-Raport (opsional)</span>
                        <button type="button" id="btnLoadKelasEraport" class="btn btn-link btn-sm p-0">Muat Kelas e-Raport</button>
                    </label>
                    <select name="kelas_eraport" id="kelasEraportSelect" class="form-control form-control-sm">
                        <option value="">Auto cocokkan dari kelas lokal</option>
                        <?php if ($kelasEraport !== ''): ?>
                            <option value="<?php echo htmlspecialchars($kelasEraport); ?>" selected><?php echo htmlspecialchars($kelasEraport); ?></option>
                        <?php endif; ?>
                    </select>
                    <small id="kelasEraportInfo" class="text-muted">Gunakan ini jika nama kelas lokal berbeda dengan e-Raport.</small>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="small text-muted">Sumber Data</label>
                    <select name="idmapel" class="form-control form-control-sm">
                        <option value="0" selected>Leger Siswa e-Raport</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3" id="siswaFieldWrap">
                    <label class="small text-muted">Siswa (mode individu, bisa multi pilih)</label>
                    <select name="no_induk_siswa[]" id="siswaSelect" class="form-control form-control-sm" multiple size="5">
                        <?php foreach ($siswaOptions as $so): ?>
                            <?php $optNoInduk = (string)$so['no_induk']; ?>
                            <option value="<?php echo htmlspecialchars($optNoInduk); ?>" <?php echo in_array($optNoInduk, $noIndukSiswaList, true) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$so['nama_siswa'] . ' (' . (string)$so['no_induk'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Tips: tahan Ctrl untuk pilih lebih dari satu siswa.</small>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="small text-muted">Tanggal Awal</label>
                    <input type="date" name="tgl_awal" value="<?php echo htmlspecialchars($tglAwal); ?>" class="form-control form-control-sm">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="small text-muted">Tanggal Akhir</label>
                    <input type="date" name="tgl_akhir" value="<?php echo htmlspecialchars($tglAkhir); ?>" class="form-control form-control-sm">
                </div>

                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-download mr-1"></i>Ambil Data Nilai
                    </button>
                </div>
            </form>

            <?php if ($errorMsg !== ''): ?>
                <div class="alert alert-warning mb-0"><?php echo htmlspecialchars($errorMsg); ?></div>
            <?php endif; ?>
            <?php if ($syncInfo !== ''): ?>
                <div class="alert alert-info mt-2 mb-0"><?php echo htmlspecialchars($syncInfo); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($ambil && $errorMsg === ''): ?>
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Jumlah Titik Tren</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo (int)$summary['records']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Rata-rata Umum</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $summary['avg'] === null ? '-' : htmlspecialchars((string)$summary['avg']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Nilai Tertinggi Tren</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $summary['max'] === null ? '-' : htmlspecialchars((string)$summary['max']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Nilai Terendah Tren</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $summary['min'] === null ? '-' : htmlspecialchars((string)$summary['min']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-success">
                    Grafik Perkembangan Nilai
                    <?php if ($mode === 'kelas'): ?>
                        <span class="text-muted font-weight-normal">(Kelas <?php echo htmlspecialchars($kelas); ?>)</span>
                    <?php else: ?>
                        <span class="text-muted font-weight-normal">(<?php echo htmlspecialchars($namaSiswaTerpilih !== '' ? $namaSiswaTerpilih : $noIndukSiswa); ?>)</span>
                    <?php endif; ?>
                </h6>
                <?php if (!empty($chartLabels)): ?>
                    <div>
                        <a href="<?php echo htmlspecialchars($exportCsvUrl); ?>" class="btn btn-outline-success btn-sm" target="_blank">
                            <i class="fas fa-file-csv mr-1"></i>Export CSV
                        </a>
                        <a href="<?php echo htmlspecialchars($exportPrintUrl); ?>" class="btn btn-outline-danger btn-sm" target="_blank">
                            <i class="fas fa-file-pdf mr-1"></i>Cetak PDF
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($chartLabels)): ?>
                    <div class="alert alert-info mb-0">Belum ada data nilai untuk filter tersebut.</div>
                <?php else: ?>
                    <div style="height:360px;">
                        <canvas id="nilaiTrendChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($tableRows)): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-secondary">Detail Data Tren Nilai</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <?php if ($mode === 'individu' && count($noIndukSiswaList) > 1): ?>
                                        <th>Siswa</th>
                                        <th>Rata-rata</th>
                                    <?php else: ?>
                                        <th>Rata-rata</th>
                                        <th>Jumlah Entri</th>
                                        <th>Min</th>
                                        <th>Max</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tableRows as $tr): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string)$tr['tanggal']); ?></td>
                                        <?php if ($mode === 'individu' && count($noIndukSiswaList) > 1): ?>
                                            <td><?php echo htmlspecialchars((string)$tr['siswa']); ?></td>
                                            <td><?php echo htmlspecialchars((string)$tr['rata_nilai']); ?></td>
                                        <?php else: ?>
                                            <td><?php echo htmlspecialchars((string)$tr['rata_nilai']); ?></td>
                                            <td><?php echo htmlspecialchars((string)$tr['jumlah_entri']); ?></td>
                                            <td><?php echo htmlspecialchars((string)$tr['nilai_min']); ?></td>
                                            <td><?php echo htmlspecialchars((string)$tr['nilai_max']); ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    (function() {
        var modeEl = document.getElementById('modeAnalisis');
        var siswaWrap = document.getElementById('siswaFieldWrap');
        var btnLoadKelasEraport = document.getElementById('btnLoadKelasEraport');
        var kelasEraportSelect = document.getElementById('kelasEraportSelect');
        var kelasEraportInfo = document.getElementById('kelasEraportInfo');

        function syncModeField() {
            if (!modeEl || !siswaWrap) {
                return;
            }
            var isIndividu = modeEl.value === 'individu';
            siswaWrap.style.display = isIndividu ? '' : 'none';
        }

        if (modeEl) {
            modeEl.addEventListener('change', syncModeField);
        }

        if (btnLoadKelasEraport) {
            btnLoadKelasEraport.addEventListener('click', function() {
                btnLoadKelasEraport.disabled = true;
                kelasEraportInfo.textContent = 'Memuat daftar kelas e-Raport...';

                fetch('api/eraport-leger-kelas-options.php', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(data) {
                        if (!data || !data.success) {
                            kelasEraportInfo.textContent = data && data.message ? data.message : 'Gagal memuat daftar kelas e-Raport.';
                            return;
                        }

                        var selectedOld = kelasEraportSelect.value || '';
                        var html = '<option value="">Auto cocokkan dari kelas lokal</option>';
                        var list = Array.isArray(data.classes) ? data.classes : [];
                        list.forEach(function(item) {
                            var n = String(item && item.name ? item.name : '');
                            if (!n) {
                                return;
                            }
                            var selected = n === selectedOld ? ' selected' : '';
                            html += '<option value="' + n.replace(/"/g, '&quot;') + '"' + selected + '>' +
                                n.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
                                '</option>';
                        });
                        kelasEraportSelect.innerHTML = html;
                        kelasEraportInfo.textContent = 'Berhasil memuat ' + list.length + ' kelas e-Raport.';
                    })
                    .catch(function(err) {
                        kelasEraportInfo.textContent = 'Error: ' + (err && err.message ? err.message : err);
                    })
                    .finally(function() {
                        btnLoadKelasEraport.disabled = false;
                    });
            });
        }

        syncModeField();
    })();
</script>

<?php if ($ambil && $errorMsg === '' && !empty($chartLabels)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        /* global Chart */
        (function() {
            var ctx = document.getElementById('nilaiTrendChart');
            if (!ctx) {
                return;
            }

            var labels = <?php echo json_encode($chartLabels, JSON_UNESCAPED_UNICODE); ?>;
            var datasets = <?php echo json_encode($chartDatasets, JSON_UNESCAPED_UNICODE); ?>;

            new window.Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Nilai'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Tanggal'
                            }
                        }
                    }
                }
            });
        })();
    </script>
<?php endif; ?>