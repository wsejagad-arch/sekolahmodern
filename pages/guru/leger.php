<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('Location: ../../login.php?haruslogin');
    exit;
}

require_once __DIR__ . '/../../koneksi.php';
require_once __DIR__ . '/../../functions.php';
require_once __DIR__ . '/../../eraport_helper.php';

date_default_timezone_set('Asia/Jakarta');
if (!isset($conn) || !($conn instanceof mysqli)) {
    exit('Koneksi database tidak tersedia.');
}
eraport_ensure_leger_tables($conn);

function leger_page_route(string $page): string
{
    $safe = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $page));
    return php_sapi_name() === 'cli-server' ? $safe . '.php' : $safe;
}

function leger_table_exists(mysqli $conn, string $table): bool
{
    $safe = mysqli_real_escape_string($conn, $table);
    $q = @mysqli_query($conn, "SHOW TABLES LIKE '{$safe}'");
    return (bool)($q && mysqli_num_rows($q) > 0);
}

$nipGuru = (string)$_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nipGuru);
$namaGuru = (string)($_SESSION['nama_guru'] ?? $_SESSION['nama'] ?? 'Guru');

$kelasOptions = [];
$qAmpu = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='{$nipEsc}' AND kelas <> '' ORDER BY kelas ASC");
while ($qAmpu && ($row = mysqli_fetch_assoc($qAmpu))) {
    $kelasOptions[] = (string)$row['kelas'];
}
if (leger_table_exists($conn, 'tbl_wali_kelas') && leger_table_exists($conn, 'tbl_kelas')) {
    $qWali = @mysqli_query(
        $conn,
        "SELECT DISTINCT k.kelas
         FROM tbl_wali_kelas wk
         JOIN tbl_kelas k ON k.id_kelas=wk.id_kelas
         WHERE wk.nip_wali='{$nipEsc}' AND k.kelas <> ''
         ORDER BY k.kelas ASC"
    );
    while ($qWali && ($row = mysqli_fetch_assoc($qWali))) {
        $kelasOptions[] = (string)$row['kelas'];
    }
}
if (leger_table_exists($conn, 'tbl_kelas')) {
    $qCol = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_kelas LIKE 'nip_wali'");
    if ($qCol && mysqli_num_rows($qCol) > 0) {
        $qWaliLegacy = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas WHERE nip_wali='{$nipEsc}' AND kelas <> '' ORDER BY kelas ASC");
        while ($qWaliLegacy && ($row = mysqli_fetch_assoc($qWaliLegacy))) {
            $kelasOptions[] = (string)$row['kelas'];
        }
    }
}
$qLegerKelas = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_leger_nilai_raport_siswa WHERE kelas <> '' ORDER BY kelas ASC");
while ($qLegerKelas && ($row = mysqli_fetch_assoc($qLegerKelas))) {
    if (in_array((string)$row['kelas'], $kelasOptions, true)) {
        $kelasOptions[] = (string)$row['kelas'];
    }
}
$kelasOptions = array_values(array_unique(array_filter($kelasOptions)));
sort($kelasOptions);

$kelas = trim((string)($_GET['kelas'] ?? ''));
if ($kelas === '' && !empty($kelasOptions)) {
    $kelas = $kelasOptions[0];
}
$kelasEsc = mysqli_real_escape_string($conn, $kelas);
$selectedNis = trim((string)($_GET['nis'] ?? ''));
$selectedMapel = trim((string)($_GET['mapel'] ?? ''));
$activeTab = (string)($_GET['tab'] ?? 'data');
if (!in_array($activeTab, ['input', 'data'], true)) {
    $activeTab = 'data';
}

$studentOptions = [];
$mapelOptions = [];
$latestRows = [];
$chartLabels = ['Smt1', 'Smt2', 'Smt3', 'Smt4', 'Smt5', 'Smt6'];
$chartDatasets = [];
$stats = ['students' => 0, 'mapel' => 0, 'nilai' => 0, 'avg' => null, 'last_upload' => '-'];

if ($kelas !== '') {
    $qStudents = @mysqli_query(
        $conn,
        "SELECT COALESCE(NULLIF(nis,''), NULLIF(nisn,''), nama_siswa) AS student_key, nis, nisn, nama_siswa
         FROM tbl_leger_nilai_raport_siswa
         WHERE kelas='{$kelasEsc}'
         GROUP BY student_key, nis, nisn, nama_siswa
         ORDER BY nama_siswa ASC"
    );
    while ($qStudents && ($row = mysqli_fetch_assoc($qStudents))) {
        $key = (string)($row['nis'] ?: ($row['nisn'] ?: $row['nama_siswa']));
        $studentOptions[] = [
            'key' => $key,
            'nis' => (string)$row['nis'],
            'nisn' => (string)$row['nisn'],
            'nama_siswa' => (string)$row['nama_siswa'],
        ];
    }
    if ($selectedNis !== '') {
        $selectedNisExists = false;
        foreach ($studentOptions as $student) {
            if ($student['key'] === $selectedNis) {
                $selectedNisExists = true;
                break;
            }
        }
        if (!$selectedNisExists) {
            $selectedNis = '';
        }
    }

    $qMapel = @mysqli_query($conn, "SELECT DISTINCT mapel FROM tbl_leger_nilai_raport_siswa WHERE kelas='{$kelasEsc}' ORDER BY mapel ASC");
    while ($qMapel && ($row = mysqli_fetch_assoc($qMapel))) {
        $mapelOptions[] = (string)$row['mapel'];
    }

    $qStats = @mysqli_query(
        $conn,
        "SELECT COUNT(DISTINCT COALESCE(NULLIF(nis,''), NULLIF(nisn,''), nama_siswa)) AS students,
                COUNT(DISTINCT mapel) AS mapel,
                COUNT(*) AS nilai,
                ROUND(AVG(nilai),2) AS avg_nilai,
                MAX(uploaded_at) AS last_upload
         FROM tbl_leger_nilai_raport_siswa
         WHERE kelas='{$kelasEsc}'"
    );
    if ($qStats && ($row = mysqli_fetch_assoc($qStats))) {
        $stats = [
            'students' => (int)($row['students'] ?? 0),
            'mapel' => (int)($row['mapel'] ?? 0),
            'nilai' => (int)($row['nilai'] ?? 0),
            'avg' => $row['avg_nilai'] !== null ? (float)$row['avg_nilai'] : null,
            'last_upload' => !empty($row['last_upload']) ? date('d M Y H:i', strtotime((string)$row['last_upload'])) : '-',
        ];
    }

    $where = ["d.kelas='{$kelasEsc}'"];
    if ($selectedNis !== '') {
        $nisEsc = mysqli_real_escape_string($conn, $selectedNis);
        $where[] = "(d.nis='{$nisEsc}' OR d.nisn='{$nisEsc}' OR d.nama_siswa='{$nisEsc}')";
    }
    if ($selectedMapel !== '') {
        $mapelEsc = mysqli_real_escape_string($conn, $selectedMapel);
        $where[] = "d.mapel='{$mapelEsc}'";
    }
    $whereSql = implode(' AND ', $where);
    $qLatest = @mysqli_query(
        $conn,
        "SELECT d.*
         FROM tbl_leger_nilai_raport_siswa d
         JOIN (
             SELECT MAX(id) AS id
             FROM tbl_leger_nilai_raport_siswa
             WHERE kelas='{$kelasEsc}'
             GROUP BY COALESCE(NULLIF(nis,''), NULLIF(nisn,''), nama_siswa), mapel, komponen
         ) latest ON latest.id=d.id
         WHERE {$whereSql}
         ORDER BY d.nama_siswa ASC, d.mapel ASC, FIELD(d.komponen,'Smt1','Smt2','Smt3','Smt4','Smt5','Smt6','rerata'), d.komponen ASC
         LIMIT 1200"
    );
    while ($qLatest && ($row = mysqli_fetch_assoc($qLatest))) {
        $latestRows[] = $row;
    }

    $series = [];
    $seriesCounts = [];
    foreach ($latestRows as $row) {
        $komponen = (string)$row['komponen'];
        if (!in_array($komponen, $chartLabels, true)) {
            continue;
        }
        $mapel = (string)$row['mapel'];
        if (!isset($series[$mapel])) {
            $series[$mapel] = array_fill_keys($chartLabels, 0.0);
            $seriesCounts[$mapel] = array_fill_keys($chartLabels, 0);
        }
        $series[$mapel][$komponen] += (float)$row['nilai'];
        $seriesCounts[$mapel][$komponen]++;
    }
    $colors = ['#2563eb', '#16a34a', '#dc2626', '#9333ea', '#ea580c', '#0891b2', '#4f46e5', '#be123c', '#65a30d', '#0f766e', '#7c3aed', '#ca8a04'];
    $i = 0;
    foreach ($series as $mapel => $values) {
        foreach ($values as $komponen => $total) {
            $count = $seriesCounts[$mapel][$komponen] ?? 0;
            $values[$komponen] = $count > 0 ? round($total / $count, 2) : null;
        }
        $chartDatasets[] = [
            'label' => $mapel,
            'data' => array_values($values),
            'borderColor' => $colors[$i % count($colors)],
            'backgroundColor' => $colors[$i % count($colors)],
            'tension' => 0.35,
            'spanGaps' => true,
        ];
        $i++;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Leger Nilai - SIMANIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { min-height: 100vh; margin: 0; background: linear-gradient(135deg, #ecfdf5, #eef2ff); color: #0f172a; padding-bottom: 120px; font-family: "Segoe UI", Arial, sans-serif; }
        .page-shell { max-width: 1180px; margin: 0 auto; padding: 22px; }
        .hero { background: linear-gradient(135deg, #064e3b, #2563eb); color: #fff; border-radius: 24px; padding: 24px; box-shadow: 0 20px 50px rgba(15,23,42,.18); }
        .panel { background: rgba(255,255,255,.94); border: 1px solid #dbeafe; border-radius: 18px; box-shadow: 0 14px 34px rgba(15,23,42,.08); }
        .metric-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .metric-card { padding: 16px; }
        .metric-card small { color: #64748b; font-weight: 700; }
        .metric-card strong { display: block; font-size: 1.45rem; margin-top: 4px; }
        .nav-pills .nav-link { border-radius: 999px; font-weight: 700; color: #475569; }
        .nav-pills .nav-link.active { background: #059669; }
        .chart-box { min-height: 360px; }
        .table th { white-space: nowrap; font-size: .78rem; text-transform: uppercase; color: #64748b; }
        @media (max-width: 900px) { .metric-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .page-shell { padding: 14px; } .metric-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main class="page-shell">
    <section class="hero mb-3">
        <a href="<?= htmlspecialchars(leger_page_route('../../home.php'), ENT_QUOTES, 'UTF-8'); ?>" class="text-white-50 text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
        <h1 class="mt-3 mb-1">Leger Nilai Siswa</h1>
        <p class="mb-0 text-white-50">Upload leger Excel, simpan historis nilai, dan pantau grafik perkembangan semua mata pelajaran dari semester 1 sampai semester terakhir.</p>
    </section>

    <section class="panel p-3 p-md-4 mb-3">
        <ul class="nav nav-pills gap-2">
            <li class="nav-item"><a class="nav-link <?= $activeTab === 'input' ? 'active' : ''; ?>" href="?tab=input&kelas=<?= urlencode($kelas); ?>"><i class="bi bi-upload me-1"></i> Input Leger</a></li>
            <li class="nav-item"><a class="nav-link <?= $activeTab === 'data' ? 'active' : ''; ?>" href="?tab=data&kelas=<?= urlencode($kelas); ?>"><i class="bi bi-graph-up-arrow me-1"></i> Data Leger</a></li>
        </ul>
    </section>

    <?php if ($activeTab === 'input'): ?>
        <section class="panel p-3 p-md-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Input Leger dari Excel</h5>
            <form method="post" action="<?= htmlspecialchars(leger_page_route('upload_leger_raport'), ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data" id="formUploadLeger" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kelas</label>
                    <select name="kelas" class="form-select">
                        <?php foreach ($kelasOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt); ?>" <?= $kelas === $opt ? 'selected' : ''; ?>><?= htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($kelasOptions)): ?><option value="">Kelas dari file Excel</option><?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Batch/Periode Upload</label>
                    <input type="text" name="semester" class="form-control" placeholder="Contoh: Leger Mei 2026">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">File Leger (.xlsx)</label>
                    <input type="file" name="leger_file" class="form-control" accept=".xlsx" required>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-success w-100" type="submit"><i class="bi bi-cloud-upload me-1"></i> Upload</button>
                </div>
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        Sistem membaca kolom seperti contoh Excel: identitas siswa, mata pelajaran, Smt1-Smt6, dan rerata. Upload berikutnya akan memperbarui nilai dengan kunci yang sama dan menambahkan nilai baru tanpa menghapus data lama.
                    </div>
                    <div id="uploadLegerStatus" class="small text-muted mt-2"></div>
                </div>
            </form>
        </section>
    <?php else: ?>
        <section class="metric-grid mb-3">
            <div class="panel metric-card"><small>Siswa</small><strong><?= (int)$stats['students']; ?></strong></div>
            <div class="panel metric-card"><small>Mata Pelajaran</small><strong><?= (int)$stats['mapel']; ?></strong></div>
            <div class="panel metric-card"><small>Rata-rata Nilai</small><strong><?= $stats['avg'] !== null ? number_format((float)$stats['avg'], 1, ',', '.') : '-'; ?></strong></div>
            <div class="panel metric-card"><small>Update Terakhir</small><strong style="font-size:1rem;"><?= htmlspecialchars($stats['last_upload']); ?></strong></div>
        </section>

        <section class="panel p-3 p-md-4 mb-3">
            <form method="get" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="data">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Kelas</label>
                    <select name="kelas" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($kelasOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt); ?>" <?= $kelas === $opt ? 'selected' : ''; ?>><?= htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Filter Siswa</label>
                    <select name="nis" class="form-select" onchange="this.form.submit()">
                        <option value="" <?= $selectedNis === '' ? 'selected' : ''; ?>>Semua Siswa</option>
                        <?php foreach ($studentOptions as $student): ?>
                            <option value="<?= htmlspecialchars($student['key']); ?>" <?= $selectedNis === $student['key'] ? 'selected' : ''; ?>><?= htmlspecialchars($student['nama_siswa'] . ' - ' . $student['key']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Filter Mapel</label>
                    <select name="mapel" class="form-select" onchange="this.form.submit()">
                        <option value="" <?= $selectedMapel === '' ? 'selected' : ''; ?>>Semua Mata Pelajaran</option>
                        <?php foreach ($mapelOptions as $mapel): ?>
                            <option value="<?= htmlspecialchars($mapel); ?>" <?= $selectedMapel === $mapel ? 'selected' : ''; ?>><?= htmlspecialchars($mapel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <a class="btn btn-outline-secondary" href="?tab=data&kelas=<?= urlencode($kelas); ?>"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </section>

        <section class="panel p-3 p-md-4 mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-graph-up text-primary me-2"></i><?= $selectedNis === '' ? 'Grafik Rata-rata Nilai Semua Siswa' : 'Grafik Perkembangan Nilai Siswa'; ?></h5>
                <span class="badge text-bg-light border"><?= htmlspecialchars($kelas); ?></span>
            </div>
            <div class="chart-box">
                <canvas id="legerChart"></canvas>
            </div>
        </section>

        <section class="panel p-3 p-md-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-table me-2 text-success"></i>Data Leger Terbaru</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>NIS</th>
                            <th>Mapel</th>
                            <th>Komponen</th>
                            <th class="text-end">Nilai</th>
                            <th>Batch</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($latestRows)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data leger untuk filter ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($latestRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$row['nama_siswa']); ?></td>
                                    <td><?= htmlspecialchars((string)($row['nis'] ?: $row['nisn'])); ?></td>
                                    <td><?= htmlspecialchars((string)$row['mapel']); ?></td>
                                    <td><span class="badge text-bg-light border"><?= htmlspecialchars((string)$row['komponen']); ?></span></td>
                                    <td class="text-end fw-bold"><?= number_format((float)$row['nilai'], 1, ',', '.'); ?></td>
                                    <td><?= htmlspecialchars((string)$row['semester']); ?></td>
                                    <td><?= !empty($row['uploaded_at']) ? date('d M Y H:i', strtotime((string)$row['uploaded_at'])) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>

<script>
document.getElementById('formUploadLeger')?.addEventListener('submit', async function (event) {
    event.preventDefault();
    const status = document.getElementById('uploadLegerStatus');
    status.textContent = 'Membaca dan menyimpan file leger...';
    const btn = this.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    try {
        const response = await fetch(this.action, { method: 'POST', body: new FormData(this) });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Upload leger gagal.');
        }
        const s = data.summary || {};
        status.innerHTML = '<span class="text-success fw-semibold">Berhasil.</span> Siswa: ' + (s.students || 0) + ', nilai baru: ' + (s.details || 0) + ', nilai diperbarui: ' + (s.updated_details || 0) + '.';
    } catch (err) {
        status.innerHTML = '<span class="text-danger fw-semibold">Gagal:</span> ' + (err.message || err);
    } finally {
        if (btn) btn.disabled = false;
    }
});

const chartEl = document.getElementById('legerChart');
if (chartEl) {
    const datasets = <?= json_encode($chartDatasets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    new Chart(chartEl, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels); ?>,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + (ctx.parsed.y ?? '-') } }
            },
            scales: {
                y: { min: 0, max: 100, ticks: { stepSize: 10 } }
            }
        }
    });
}
</script>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
