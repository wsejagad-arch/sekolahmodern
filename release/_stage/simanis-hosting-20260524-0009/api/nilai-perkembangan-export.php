<?php
require_once __DIR__ . '/../bootstrap.php';
require_admin();

if (!isset($conn) || !($conn instanceof mysqli)) {
    require __DIR__ . '/../koneksi.php';
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    echo 'Koneksi database tidak tersedia.';
    exit;
}

$format = (string)($_GET['format'] ?? 'csv');
if (!in_array($format, ['csv', 'print'], true)) {
    $format = 'csv';
}

$mode = (string)($_GET['mode'] ?? 'kelas');
if (!in_array($mode, ['kelas', 'individu'], true)) {
    $mode = 'kelas';
}

$kelas = trim((string)($_GET['kelas'] ?? ''));
$tglAwal = trim((string)($_GET['tgl_awal'] ?? ''));
$tglAkhir = trim((string)($_GET['tgl_akhir'] ?? ''));

$rawNoInduk = $_GET['no_induk_siswa'] ?? [];
$nisList = [];
if (is_array($rawNoInduk)) {
    foreach ($rawNoInduk as $nis) {
        $nis = trim((string)$nis);
        if ($nis !== '') {
            $nisList[] = $nis;
        }
    }
} else {
    $nis = trim((string)$rawNoInduk);
    if ($nis !== '') {
        $nisList[] = $nis;
    }
}
$nisList = array_values(array_unique($nisList));

if ($kelas === '') {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(400);
    echo 'Parameter kelas wajib diisi.';
    exit;
}
if ($mode === 'individu' && empty($nisList)) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(400);
    echo 'Mode individu membutuhkan minimal 1 siswa.';
    exit;
}

$where = [];
$where[] = "l.kelas='" . mysqli_real_escape_string($conn, $kelas) . "'";
$where[] = 'l.nilai_rerata IS NOT NULL';
if ($tglAwal !== '') {
    $where[] = "DATE(l.synced_at) >= '" . mysqli_real_escape_string($conn, $tglAwal) . "'";
}
if ($tglAkhir !== '') {
    $where[] = "DATE(l.synced_at) <= '" . mysqli_real_escape_string($conn, $tglAkhir) . "'";
}
if ($mode === 'individu') {
    $in = [];
    foreach ($nisList as $nis) {
        $in[] = "'" . mysqli_real_escape_string($conn, $nis) . "'";
    }
    $where[] = 'l.nis IN (' . implode(',', $in) . ')';
}

$whereSql = implode(' AND ', $where);
$rows = [];

if ($mode === 'individu' && count($nisList) > 1) {
    $q = mysqli_query($conn, "SELECT DATE(l.synced_at) AS tanggal, l.nis AS no_induk_siswa, ROUND(AVG(l.nilai_rerata),2) AS rata_nilai
                              FROM tbl_leger_siswa_eraport l
                              WHERE {$whereSql}
                              GROUP BY DATE(l.synced_at), l.nis
                              ORDER BY DATE(l.synced_at) ASC, l.nis ASC");
    if (!$q) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(500);
        echo 'Query gagal: ' . mysqli_error($conn);
        exit;
    }

    $namaMap = [];
    $inNama = [];
    foreach ($nisList as $nis) {
        $inNama[] = "'" . mysqli_real_escape_string($conn, $nis) . "'";
    }
    $qNama = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE no_induk IN (" . implode(',', $inNama) . ")");
    if ($qNama) {
        while ($n = mysqli_fetch_assoc($qNama)) {
            $namaMap[(string)$n['no_induk']] = (string)$n['nama_siswa'];
        }
    }

    while ($r = mysqli_fetch_assoc($q)) {
        $nis = (string)$r['no_induk_siswa'];
        $rows[] = [
            'tanggal' => (string)$r['tanggal'],
            'siswa' => ($namaMap[$nis] ?? $nis) . ' (' . $nis . ')',
            'rata_nilai' => (string)$r['rata_nilai'],
        ];
    }
} else {
    $q = mysqli_query($conn, "SELECT DATE(l.synced_at) AS tanggal, ROUND(AVG(l.nilai_rerata),2) AS rata_nilai, COUNT(l.id) AS jumlah_entri,
                          ROUND(MIN(l.nilai_rerata),2) AS nilai_min, ROUND(MAX(l.nilai_rerata),2) AS nilai_max
                      FROM tbl_leger_siswa_eraport l
                              WHERE {$whereSql}
                      GROUP BY DATE(l.synced_at)
                      ORDER BY DATE(l.synced_at) ASC");
    if (!$q) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(500);
        echo 'Query gagal: ' . mysqli_error($conn);
        exit;
    }

    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = [
            'tanggal' => (string)$r['tanggal'],
            'rata_nilai' => (string)$r['rata_nilai'],
            'jumlah_entri' => (string)$r['jumlah_entri'],
            'nilai_min' => (string)$r['nilai_min'],
            'nilai_max' => (string)$r['nilai_max'],
        ];
    }
}

if ($format === 'csv') {
    $filename = 'nilai_perkembangan_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    if ($mode === 'individu' && count($nisList) > 1) {
        fputcsv($out, ['Tanggal', 'Siswa', 'Rata-rata']);
        foreach ($rows as $row) {
            fputcsv($out, [$row['tanggal'], $row['siswa'], $row['rata_nilai']]);
        }
    } else {
        fputcsv($out, ['Tanggal', 'Rata-rata', 'Jumlah Entri', 'Min', 'Max']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['tanggal'],
                $row['rata_nilai'],
                $row['jumlah_entri'] ?? '',
                $row['nilai_min'] ?? '',
                $row['nilai_max'] ?? '',
            ]);
        }
    }

    fclose($out);
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Nilai Perkembangan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h2 {
            margin: 0 0 8px;
        }

        .meta {
            margin-bottom: 12px;
            color: #444;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background: #f4f4f4;
        }

        @media print {
            .noprint {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="noprint" style="margin-bottom:10px;">
        <button onclick="window.print()">Cetak / Simpan sebagai PDF</button>
    </div>

    <h2>Laporan Nilai Perkembangan</h2>
    <div class="meta">
        Mode: <?php echo htmlspecialchars($mode); ?> |
        Kelas: <?php echo htmlspecialchars($kelas); ?> |
        Sumber: Leger Siswa e-Raport |
        Periode: <?php echo htmlspecialchars($tglAwal !== '' ? $tglAwal : '-'); ?> s/d <?php echo htmlspecialchars($tglAkhir !== '' ? $tglAkhir : '-'); ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <?php if ($mode === 'individu' && count($nisList) > 1): ?>
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
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5">Tidak ada data.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['tanggal']); ?></td>
                        <?php if ($mode === 'individu' && count($nisList) > 1): ?>
                            <td><?php echo htmlspecialchars((string)$row['siswa']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['rata_nilai']); ?></td>
                        <?php else: ?>
                            <td><?php echo htmlspecialchars((string)$row['rata_nilai']); ?></td>
                            <td><?php echo htmlspecialchars((string)($row['jumlah_entri'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string)($row['nilai_min'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string)($row['nilai_max'] ?? '')); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>

</html>