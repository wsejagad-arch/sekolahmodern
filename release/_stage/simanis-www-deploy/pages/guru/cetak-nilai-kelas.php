<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['no_induk'])) {
    header('Location: ../../index.php?haruslogin');
    exit;
}

if (!isset($_SESSION['hak_akses']) || (int) $_SESSION['hak_akses'] !== 2) {
    http_response_code(403);
    exit('Akses ditolak.');
}

require_once __DIR__ . '/../../koneksi.php';
require_once __DIR__ . '/../../functions.php';

date_default_timezone_set('Asia/Jakarta');

function cnk_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cnk_format_score($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    $num = (float) $value;
    return abs($num - round($num)) < 0.001 ? (string) (int) round($num) : number_format($num, 1, ',', '.');
}

function cnk_average(array $values): ?float
{
    $numbers = [];
    foreach ($values as $value) {
        if ($value !== null && $value !== '' && is_numeric($value)) {
            $numbers[] = (float) $value;
        }
    }
    if (empty($numbers)) {
        return null;
    }
    return array_sum($numbers) / count($numbers);
}

function cnk_predicate(?float $score): string
{
    if ($score === null) {
        return '';
    }
    if ($score >= 90) {
        return 'A';
    }
    if ($score >= 80) {
        return 'B';
    }
    if ($score >= 75) {
        return 'C';
    }
    return 'D';
}

function cnk_bucket(string $code): string
{
    $code = strtoupper(trim($code));
    if (strpos($code, 'PTS') !== false || strpos($code, 'STS') !== false) {
        return 'pts';
    }
    foreach (['PAS', 'PAT', 'ASAT', 'ASAS', 'SAS', 'SAT'] as $needle) {
        if (strpos($code, $needle) !== false) {
            return 'pas';
        }
    }
    if (strpos($code, 'UH') !== false || strpos($code, 'PH') !== false) {
        return 'uh';
    }
    return 'tugas';
}

$nipGuru = (string) $_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nipGuru);
$idMapel = (int) ($_GET['idmapel'] ?? 0);
$kelasParam = trim((string) ($_GET['kelas'] ?? ''));
$kkm = (int) ($_GET['kkm'] ?? 75);
if ($kkm <= 0) {
    $kkm = 75;
}

$qMapel = mysqli_query(
    $conn,
    "SELECT id_mapel, nama_mapel, kelas, thn_ajaran
     FROM tbl_mapel_ampu
     WHERE id_mapel={$idMapel} AND no_induk='{$nipEsc}'
     LIMIT 1"
);
$mapel = $qMapel ? mysqli_fetch_assoc($qMapel) : null;

if (!$mapel) {
    http_response_code(404);
    exit('Mata pelajaran tidak ditemukan atau Anda tidak memiliki akses.');
}

$kelas = (string) $mapel['kelas'];
if ($kelasParam !== '' && $kelasParam !== $kelas) {
    http_response_code(403);
    exit('Filter kelas tidak sesuai dengan mata pelajaran.');
}

$kelasEsc = mysqli_real_escape_string($conn, $kelas);
$mapelNama = (string) $mapel['nama_mapel'];
$tahunAjaran = (string) ($mapel['thn_ajaran'] ?? '');
$namaGuru = (string) ($_SESSION['nama_guru'] ?? $_SESSION['nama'] ?? 'Guru');

$qGuru = mysqli_query($conn, "SELECT nama_guru FROM tbl_guru WHERE no_induk='{$nipEsc}' LIMIT 1");
if ($qGuru && ($guruRow = mysqli_fetch_assoc($qGuru)) && !empty($guruRow['nama_guru'])) {
    $namaGuru = (string) $guruRow['nama_guru'];
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_penilaian_item (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    id_mapel INT NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    mapel VARCHAR(100) NOT NULL,
    no_induk_guru VARCHAR(50) NOT NULL,
    kode_penilaian VARCHAR(20) NOT NULL,
    materi VARCHAR(255) NOT NULL,
    UNIQUE KEY uniq_item (tanggal, id_mapel, kode_penilaian)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_nilai_item (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_item INT NOT NULL,
    no_induk_siswa VARCHAR(50) NOT NULL,
    nilai FLOAT DEFAULT 0,
    UNIQUE KEY uniq_nilai_item (id_item, no_induk_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$items = [];
$qItems = mysqli_query(
    $conn,
    "SELECT id, tanggal, kode_penilaian, materi
     FROM tbl_penilaian_item
     WHERE id_mapel={$idMapel} AND kelas='{$kelasEsc}' AND no_induk_guru='{$nipEsc}'
     ORDER BY tanggal ASC, id ASC"
);
while ($qItems && ($row = mysqli_fetch_assoc($qItems))) {
    $items[] = $row;
}

$students = [];
$qStudents = mysqli_query(
    $conn,
    "SELECT no_induk, nama_siswa
     FROM tbl_siswa
     WHERE kelas='{$kelasEsc}' AND status='Aktif'
     ORDER BY nama_siswa ASC"
);
while ($qStudents && ($row = mysqli_fetch_assoc($qStudents))) {
    $students[] = $row;
}

$nilaiMap = [];
if (!empty($items)) {
    $ids = array_map(static function ($item): int {
        return (int) $item['id'];
    }, $items);
    $idList = implode(',', $ids);
    $qNilai = mysqli_query($conn, "SELECT id_item, no_induk_siswa, nilai FROM tbl_nilai_item WHERE id_item IN ({$idList})");
    while ($qNilai && ($row = mysqli_fetch_assoc($qNilai))) {
        $nilaiMap[(int) $row['id_item']][(string) $row['no_induk_siswa']] = $row['nilai'];
    }
}

$notes = [];
foreach ($items as $item) {
    $notes[] = trim((string) $item['kode_penilaian']) . ' = ' . trim((string) $item['materi']);
}
$noteText = empty($notes) ? 'Belum ada item penilaian.' : implode('; ', $notes);
$printDate = date('d/m/Y H:i:s');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DAFTAR NILAI KELAS - Kelas <?= cnk_h($kelas); ?> <?= cnk_h($mapelNama); ?></title>
    <style>
        :root {
            --ink: #111827;
            --muted: #4b5563;
            --line: #1f2937;
            --soft-line: #d1d5db;
            --header-bg: #e5e7eb;
            --paper: #ffffff;
            --screen-bg: #f3f4f6;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--ink);
            background: var(--screen-bg);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.35;
        }
        .toolbar {
            max-width: 330mm;
            margin: 16px auto 8px;
            padding: 0 12px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .btn-print {
            border: 1px solid var(--line);
            background: #ffffff;
            color: var(--ink);
            border-radius: 8px;
            padding: 8px 14px;
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
        }
        .btn-print:hover { background: #f9fafb; }
        .page {
            width: 330mm;
            min-height: 215mm;
            margin: 0 auto 24px;
            padding: 11mm 12mm 10mm;
            background: var(--paper);
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.14);
        }
        .letterhead {
            padding-bottom: 8px;
            border-bottom: 2px solid var(--line);
            margin-bottom: 10px;
            text-align: center;
        }
        .letterhead h1 {
            margin: 0;
            font-size: 19px;
            letter-spacing: 0.8px;
            line-height: 1.15;
        }
        .letterhead .subtitle {
            margin-top: 3px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1.25fr 1fr;
            gap: 8px 14px;
            margin-bottom: 10px;
        }
        .meta-box {
            border: 1px solid var(--soft-line);
            border-radius: 6px;
            padding: 7px 9px;
            min-height: 42px;
        }
        .meta-label {
            display: block;
            color: var(--muted);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.45px;
            margin-bottom: 2px;
        }
        .meta-value {
            font-weight: 700;
            font-size: 12px;
        }
        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid var(--line);
            padding: 4px 5px;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }
        thead th {
            background: var(--header-bg);
            text-align: center;
            font-weight: 700;
            font-size: 11px;
        }
        tbody td {
            height: 23px;
            font-size: 11px;
        }
        .col-no, .num, .center { text-align: center; }
        .name {
            text-align: left;
            font-weight: 600;
        }
        .status-ok, .status-no { font-weight: 700; }
        .note-signature {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 24px;
            margin-top: 9px;
            align-items: start;
        }
        .notes { font-size: 11px; }
        .notes strong {
            display: inline-block;
            min-width: 76px;
        }
        .signature {
            text-align: center;
            font-size: 11px;
        }
        .signature-space { height: 54px; }
        .signature-name {
            display: inline-block;
            min-width: 220px;
            border-bottom: 1px solid var(--line);
            padding-bottom: 2px;
            font-weight: 700;
        }
        .print-date {
            margin-top: 6px;
            color: var(--muted);
            font-size: 10px;
        }
        @page {
            size: 330mm 215mm;
            margin: 8mm;
        }
        @media print {
            body {
                background: #ffffff;
                font-size: 11px;
            }
            .no-print { display: none !important; }
            .page {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            .table-wrap { overflow: visible; }
            table { page-break-inside: auto; }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
        @media screen and (max-width: 900px) {
            .toolbar, .page { width: 100%; }
            .page {
                min-height: auto;
                padding: 16px;
                margin-bottom: 0;
            }
            .meta-grid, .note-signature { grid-template-columns: 1fr; }
            table { min-width: 1080px; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <a class="btn-print" href="data-siswa?kelas=<?= rawurlencode($kelas); ?>&idmapel=<?= (int) $idMapel; ?>">Kembali</a>
        <button class="btn-print" type="button" onclick="window.print()">Cetak F4 Landscape</button>
    </div>

    <main class="page">
        <header class="letterhead">
            <h1>DAFTAR NILAI KELAS</h1>
            <div class="subtitle">KELAS <?= cnk_h(strtoupper($kelas)); ?> - <?= cnk_h(strtoupper($mapelNama)); ?></div>
        </header>

        <section class="meta-grid" aria-label="Identitas daftar nilai">
            <div class="meta-box">
                <span class="meta-label">Tanggal</span>
                <span class="meta-value"><?= cnk_h(date('d/m/Y')); ?></span>
            </div>
            <div class="meta-box">
                <span class="meta-label">Guru Mata Pelajaran</span>
                <span class="meta-value"><?= cnk_h($namaGuru); ?></span>
            </div>
            <div class="meta-box">
                <span class="meta-label">Tahun Pelajaran / KKM</span>
                <span class="meta-value"><?= cnk_h($tahunAjaran !== '' ? $tahunAjaran : '-'); ?> / <?= (int) $kkm; ?></span>
            </div>
        </section>

        <div class="table-wrap">
            <table aria-label="Daftar nilai kelas">
                <colgroup>
                    <col style="width: 4%;">
                    <col style="width: 29%;">
                    <col style="width: 8%;">
                    <col style="width: 10%;">
                    <col style="width: 8%;">
                    <col style="width: 10%;">
                    <col style="width: 7%;">
                    <col style="width: 7%;">
                    <col style="width: 9%;">
                    <col style="width: 8%;">
                    <col style="width: 10%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa</th>
                        <th>Tugas</th>
                        <th>Rata-rata Tugas</th>
                        <th>UH</th>
                        <th>Rata-rata UH</th>
                        <th>PTS</th>
                        <th>PAS</th>
                        <th>Nilai Akhir</th>
                        <th>Predikat</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="11" class="center">Belum ada siswa aktif pada kelas ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $index => $student): ?>
                            <?php
                            $nis = (string) $student['no_induk'];
                            $scores = ['tugas' => [], 'uh' => [], 'pts' => [], 'pas' => []];
                            foreach ($items as $item) {
                                $bucket = cnk_bucket((string) $item['kode_penilaian']);
                                $value = $nilaiMap[(int) $item['id']][$nis] ?? null;
                                if ($value !== null && $value !== '' && is_numeric($value)) {
                                    $scores[$bucket][] = (float) $value;
                                }
                            }
                            $avgTugas = cnk_average($scores['tugas']);
                            $avgUh = cnk_average($scores['uh']);
                            $pts = cnk_average($scores['pts']);
                            $pas = cnk_average($scores['pas']);
                            $finalParts = array_values(array_filter([$avgTugas, $avgUh, $pts, $pas], static function ($value): bool {
                                return $value !== null;
                            }));
                            $finalScore = empty($finalParts) ? null : array_sum($finalParts) / count($finalParts);
                            $predicate = cnk_predicate($finalScore);
                            $status = $finalScore === null ? '' : ($finalScore >= $kkm ? 'Tuntas' : 'Belum Tuntas');
                            $statusClass = $status === 'Tuntas' ? 'status-ok' : ($status === 'Belum Tuntas' ? 'status-no' : '');
                            $displayList = static function (array $values): string {
                                if (empty($values)) {
                                    return '-';
                                }
                                return implode(', ', array_map('cnk_format_score', $values));
                            };
                            ?>
                            <tr>
                                <td class="col-no"><?= $index + 1; ?></td>
                                <td class="name"><?= cnk_h($student['nama_siswa']); ?></td>
                                <td class="num"><?= cnk_h($displayList($scores['tugas'])); ?></td>
                                <td class="num"><?= cnk_h(cnk_format_score($avgTugas)); ?></td>
                                <td class="num"><?= cnk_h($displayList($scores['uh'])); ?></td>
                                <td class="num"><?= cnk_h(cnk_format_score($avgUh)); ?></td>
                                <td class="num"><?= cnk_h(cnk_format_score($pts)); ?></td>
                                <td class="num"><?= cnk_h(cnk_format_score($pas)); ?></td>
                                <td class="num"><strong><?= cnk_h(cnk_format_score($finalScore)); ?></strong></td>
                                <td class="center"><?= cnk_h($predicate); ?></td>
                                <td class="center <?= cnk_h($statusClass); ?>"><?= cnk_h($status); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <section class="note-signature">
            <div class="notes">
                <div><strong>Keterangan</strong>: <?= cnk_h($noteText); ?></div>
                <div><strong>Catatan</strong>: Kolom NIS/NISN telah dihilangkan.</div>
                <div class="print-date">Dicetak pada: <?= cnk_h($printDate); ?></div>
            </div>
            <div class="signature">
                <div>Mengetahui,</div>
                <div>Guru Mata Pelajaran</div>
                <div class="signature-space"></div>
                <div class="signature-name"><?= cnk_h($namaGuru); ?></div>
            </div>
        </section>
    </main>
</body>
</html>
