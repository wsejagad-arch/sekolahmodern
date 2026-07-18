<?php
require_once __DIR__ . '/../../auth_helper.php';
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../functions.php';

// Cek akses guru
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 2) {
    echo "<script>window.location='../../404.html';</script>";
    exit;
}

$nip = $_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nip);
$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;

// Get guru info
$qGuru = mysqli_query($conn, "SELECT nama_guru FROM tbl_guru WHERE no_induk='$nipEsc' LIMIT 1");
$dataGuru = $qGuru ? mysqli_fetch_assoc($qGuru) : ['nama_guru' => 'Guru'];

// Get school setting
$qSetting = mysqli_query($conn, "SELECT nama_sekolah, alamat, logo, nama_pimpinan, nip_pimpinan FROM tbl_setting WHERE id_sekolah=$idSekolah LIMIT 1");
$settingData = $qSetting ? mysqli_fetch_assoc($qSetting) : [
    'nama_sekolah' => 'Nama Sekolah',
    'alamat' => 'Alamat Sekolah',
    'logo' => '',
    'nama_pimpinan' => 'Kepala Sekolah',
    'nip_pimpinan' => '-'
];

// Get filters
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'minggu';
$startDate = isset($_GET['start']) ? $_GET['start'] : '';
$endDate = isset($_GET['end']) ? $_GET['end'] : '';
$filterKelas = isset($_GET['kelas']) ? $_GET['kelas'] : 'semua';

// Calculate dates if not custom
if ($filterType !== 'custom' || empty($startDate) || empty($endDate)) {
    if ($filterType == 'bulan') {
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
    } elseif ($filterType == 'semua') {
        $startDate = '';
        $endDate = '';
    } else {
        // default: minggu
        $dayOfWeek = date('w') == 0 ? 7 : date('w');
        $startDate = date('Y-m-d', strtotime('-' . ($dayOfWeek - 1) . ' days'));
        $endDate = date('Y-m-d', strtotime('+' . (7 - $dayOfWeek) . ' days'));
    }
}

// Build query
$where = "no_induk = '$nipEsc'";
if (!empty($startDate) && !empty($endDate)) {
    $startEsc = mysqli_real_escape_string($conn, $startDate);
    $endEsc = mysqli_real_escape_string($conn, $endDate);
    $where .= " AND tanggal BETWEEN '$startEsc' AND '$endEsc'";
}

if ($filterKelas !== 'semua') {
    $kelasEsc = mysqli_real_escape_string($conn, $filterKelas);
    $where .= " AND kelas = '$kelasEsc'";
}

$qHistory = mysqli_query($conn, "SELECT tanggal, nama_mapel, kelas, materi, kegiatan, absen FROM tbl_materi WHERE $where ORDER BY tanggal DESC, id_materi DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak History Pertemuan - <?= htmlspecialchars($dataGuru['nama_guru']) ?></title>
    <style>
        /* Base styles */
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 11pt;
        }

        /* F4 Paper size */
        @page {
            size: 215.9mm 330.2mm; /* F4 size */
            margin: 20mm;
        }

        /* A4 Fallback via media print if selected */
        @media print {
            body {
                background: none;
            }
            .no-print {
                display: none !important;
            }
        }

        .container {
            width: 100%;
            max-width: 215.9mm;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Kop Surat Resmi */
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 2px;
        }
        .kop-surat-inner {
            border-bottom: 1px solid #000;
            margin-bottom: 20px;
        }
        .kop-logo {
            width: 80px;
            height: auto;
            margin-right: 20px;
        }
        .kop-text {
            flex: 1;
            text-align: center;
        }
        .kop-text h2 {
            margin: 0;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .kop-text h1 {
            margin: 0;
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .kop-text p {
            margin: 5px 0 0;
            font-size: 10pt;
        }

        /* Judul Dokumen */
        .judul-dokumen {
            text-align: center;
            margin: 20px 0 30px;
        }
        .judul-dokumen h3 {
            margin: 0;
            font-size: 14pt;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .judul-dokumen p {
            margin: 5px 0 0;
        }

        /* Tabel Jurnal */
        table.tabel-jurnal {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.tabel-jurnal th, table.tabel-jurnal td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }
        table.tabel-jurnal th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        /* Tanda Tangan */
        .ttd-container {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .ttd-box {
            width: 40%;
            text-align: center;
        }
        .ttd-box .nama {
            margin-top: 70px;
            font-weight: bold;
            text-decoration: underline;
        }

        /* Print Button */
        .print-controls {
            text-align: center;
            margin: 20px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .btn-print {
            padding: 10px 20px;
            font-size: 16px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-print:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

    <div class="print-controls no-print">
        <p>Silakan sesuaikan pengaturan kertas (A4 atau F4/Legal) pada jendela cetak browser Anda.</p>
        <button class="btn-print" onclick="window.print()">🖨️ Cetak Jurnal</button>
        <button class="btn-print" style="background:#6c757d; margin-left:10px;" onclick="window.close()">Tutup</button>
    </div>

    <div class="container">
        <!-- Kop Surat -->
        <div class="kop-surat-inner">
            <div class="kop-surat">
                <?php if (!empty($settingData['logo'])): ?>
                    <img src="../../img/<?= htmlspecialchars($settingData['logo']) ?>" class="kop-logo" alt="Logo">
                <?php else: ?>
                    <div class="kop-logo" style="display:flex;align-items:center;justify-content:center;border:1px solid #ccc;height:80px;font-size:10px;text-align:center;">LOGO<br>SEKOLAH</div>
                <?php endif; ?>
                <div class="kop-text">
                    <h2>PEMERINTAH PROVINSI JAWA TENGAH</h2>
                    <h2>DINAS PENDIDIKAN DAN KEBUDAYAAN</h2>
                    <h1><?= htmlspecialchars($settingData['nama_sekolah']) ?></h1>
                    <p><?= htmlspecialchars($settingData['alamat']) ?></p>
                </div>
            </div>
        </div>

        <!-- Judul -->
        <div class="judul-dokumen">
            <h3>JURNAL MENGAJAR GURU</h3>
            <?php if (!empty($startDate) && !empty($endDate)): ?>
                <p>Periode: <?= tgl_indo($startDate) ?> s.d <?= tgl_indo($endDate) ?></p>
            <?php else: ?>
                <p>Periode: Semua Waktu</p>
            <?php endif; ?>
            <?php if ($filterKelas !== 'semua'): ?>
                <p style="margin-top:2px;">Kelas: <?= htmlspecialchars($filterKelas) ?></p>
            <?php endif; ?>
        </div>

        <!-- Tabel Data -->
        <table class="tabel-jurnal">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 15%;">Hari, Tanggal</th>
                    <th style="width: 10%;">Kelas</th>
                    <th style="width: 15%;">Mata Pelajaran</th>
                    <th style="width: 25%;">Materi / Kegiatan</th>
                    <th style="width: 30%;">Siswa Absen (Hanya Tidak Masuk)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($qHistory && mysqli_num_rows($qHistory) > 0) {
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($qHistory)) {
                        $hari = ubah_nama_hari($row['tanggal']);
                        $tanggal = tgl_indo($row['tanggal']);
                        
                        // Format materi & kegiatan
                        $materiKegiatan = "<strong>Materi:</strong> " . htmlspecialchars($row['materi']) . "<br>";
                        $materiKegiatan .= "<strong>Kegiatan:</strong> " . htmlspecialchars($row['kegiatan']);

                        // Format absen: Only show if not empty
                        $absen = htmlspecialchars($row['absen']);
                        if (empty(trim($absen)) || trim($absen) === '-') {
                            $absen = "Nihil";
                        }

                        echo "<tr>
                            <td style='text-align:center;'>" . $no++ . "</td>
                            <td>{$hari}, {$tanggal}</td>
                            <td style='text-align:center;'>" . htmlspecialchars($row['kelas']) . "</td>
                            <td>" . htmlspecialchars($row['nama_mapel']) . "</td>
                            <td>{$materiKegiatan}</td>
                            <td>{$absen}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center; padding:20px;'>Tidak ada data pada periode ini.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Tanda Tangan -->
        <div class="ttd-container">
            <div class="ttd-box">
                <p>Mengetahui,</p>
                <p>Kepala Sekolah</p>
                <div class="nama"><?= htmlspecialchars($settingData['nama_pimpinan']) ?></div>
                <p>NIP. <?= htmlspecialchars($settingData['nip_pimpinan']) ?></p>
            </div>
            <div class="ttd-box">
                <p>&nbsp;</p>
                <p>Guru Mata Pelajaran</p>
                <div class="nama"><?= htmlspecialchars($dataGuru['nama_guru']) ?></div>
                <p>NIP. <?= htmlspecialchars($nip) ?></p>
            </div>
        </div>
    </div>

</body>
</html>
