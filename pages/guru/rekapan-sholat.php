<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../koneksi.php';
require_once __DIR__ . '/../../functions.php';

if (!isset($_SESSION['no_induk']) || !isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 2) {
    echo "<script>window.location='../../404.html';</script>";
    exit;
}

// Cek apakah Guru Agama
$isGuruAgama = false;
$nipGuru = $_SESSION['no_induk'];
$nipGuruEsc = mysqli_real_escape_string($conn, $nipGuru);
$idSekolahGuru = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$qGuruAgama = @mysqli_query($conn, "SELECT COUNT(*) as c FROM tbl_mapel_ampu WHERE no_induk='$nipGuruEsc' AND id_sekolah=$idSekolahGuru AND (LOWER(nama_mapel) LIKE '%agama%' OR LOWER(nama_mapel) LIKE '%pabp%' OR LOWER(nama_mapel) LIKE '%papb%' OR LOWER(nama_mapel) LIKE '%pa bp%' OR LOWER(nama_mapel) LIKE '%pai%')");
if ($qGuruAgama && $r = mysqli_fetch_assoc($qGuruAgama)) {
    if ((int)$r['c'] > 0) $isGuruAgama = true;
}

if (!$isGuruAgama) {
    echo "<!doctype html><html lang='id'><head><meta charset='utf-8'><title>Akses Ditolak</title><link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'></head><body class='bg-light'><div class='container mt-5'><div class='alert alert-danger'>Akses ditolak. Halaman ini hanya untuk Guru Agama.</div><a href='dashboard_guru.php' class='btn btn-primary'>Kembali ke Dashboard</a></div></body></html>";
    exit;
}

// Ambil info guru dan kepsek untuk TTD
$namaGuru = $_SESSION['nama'] ?? 'Guru Agama';
$qKepsek = @mysqli_query($conn, "SELECT nama_guru, nip_guru FROM tbl_guru WHERE id_sekolah=$idSekolahGuru AND hak_akses=5 LIMIT 1");
$namaKepsek = "_____________________";
$nipKepsek = "_____________________";
if ($qKepsek && $rKepsek = mysqli_fetch_assoc($qKepsek)) {
    $namaKepsek = $rKepsek['nama_guru'];
    $nipKepsek = $rKepsek['nip_guru'] ?: '_____________________';
}

$lembaga = function_exists('data_lembaga') ? data_lembaga() : [];

$bulanFilter = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$kelasFilter = isset($_GET['kelas']) ? $_GET['kelas'] : '';

// Data kelas untuk dropdown
$qKelas = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas WHERE id_sekolah=$idSekolahGuru AND kelas <> '' ORDER BY kelas ASC");

$rekapan = [];
if (!empty($kelasFilter)) {
    $klsEsc = mysqli_real_escape_string($conn, $kelasFilter);
    $blnEsc = mysqli_real_escape_string($conn, $bulanFilter);

    // Ambil data siswa
    $qSiswa = @mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas='$klsEsc' AND status='Aktif' AND id_sekolah=$idSekolahGuru ORDER BY nama_siswa ASC");
    
    // Ambil sholat sekolah
    $sholatSekolah = [];
    $qSekolah = @mysqli_query($conn, "SELECT no_induk, type, COUNT(*) as cnt FROM tbl_absen_sholat WHERE DATE(created_at) LIKE '$blnEsc%' AND status='hadir' GROUP BY no_induk, type");
    if ($qSekolah) {
        while ($rs = mysqli_fetch_assoc($qSekolah)) {
            $sholatSekolah[$rs['no_induk']][$rs['type']] = $rs['cnt'];
        }
    }

    // Ambil sholat rumah (7 KAIH)
    $sholatRumah = [];
    $qRumah = @mysqli_query($conn, "SELECT no_induk, prayer_key, COUNT(*) as cnt FROM tbl_7kih_jurnal WHERE tanggal LIKE '$blnEsc%' AND habit_key='beribadah' AND timeliness_status IN ('tepat_waktu', 'terlambat') GROUP BY no_induk, prayer_key");
    if ($qRumah) {
        while ($rr = mysqli_fetch_assoc($qRumah)) {
            if (!isset($sholatRumah[$rr['no_induk']])) $sholatRumah[$rr['no_induk']] = 0;
            $sholatRumah[$rr['no_induk']] += (int)$rr['cnt'];
        }
    }

    if ($qSiswa) {
        while ($s = mysqli_fetch_assoc($qSiswa)) {
            $ni = $s['no_induk'];
            $rekapan[] = [
                'no_induk' => $ni,
                'nama' => $s['nama_siswa'],
                'dzuhur' => $sholatSekolah[$ni]['dzuhur'] ?? 0,
                'jumat' => $sholatSekolah[$ni]['jumat'] ?? 0,
                'rumah' => $sholatRumah[$ni] ?? 0
            ];
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekapan Sholat - SIMANIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/guru-desktop.css?v=<?= time() ?>">
    <!-- STYLE UNTUK PRINT -->
<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printArea, #printArea * {
        visibility: visible;
    }
    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: white;
        padding: 20px;
        color: black !important;
    }
    .no-print {
        display: none !important;
    }
    .kop-surat {
        text-align: center;
        border-bottom: 3px solid black;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .kop-surat img {
        width: 80px;
        position: absolute;
        left: 30px;
    }
    .kop-surat h2, .kop-surat h3, .kop-surat p {
        margin: 0;
        line-height: 1.2;
    }
    .ttd-container {
        display: flex;
        justify-content: space-between;
        margin-top: 50px;
        padding: 0 50px;
    }
    .ttd-box {
        text-align: center;
    }
    .ttd-box p {
        margin: 0;
    }
    .ttd-space {
        height: 80px;
    }
    table.print-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    table.print-table th, table.print-table td {
        border: 1px solid black !important;
        padding: 8px;
        text-align: left;
        color: black;
    }
    table.print-table th {
        background-color: #f2f2f2 !important;
        -webkit-print-color-adjust: exact;
    }
}
</style>
</head>
<body>

<?php include 'guru_sidebar_shared.php'; ?>

<div class="app-shell data-siswa-shell">
    <div class="desktop-center-column">
        <div class="container-fluid mt-4 mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <h4 class="m-0 text-gray-800" style="font-weight: 800;"><i class="bi bi-mosque text-success mr-2"></i> Rekapan Sholat Siswa</h4>
                <?php if (!empty($rekapan)): ?>
                <button onclick="window.print()" class="btn btn-primary" style="border-radius: 12px; font-weight: 600;"><i class="bi bi-printer"></i> Cetak Rekapan</button>
                <?php endif; ?>
            </div>

    <!-- FILTER CARD -->
    <div class="card shadow mb-4 no-print">
        <div class="card-body">
            <form method="GET" action="" class="row">
                <div class="col-md-4 mb-3">
                    <label>Pilih Kelas</label>
                    <select name="kelas" class="form-control" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php if($qKelas): while($k = mysqli_fetch_assoc($qKelas)): ?>
                            <option value="<?= $k['kelas'] ?>" <?= ($kelasFilter == $k['kelas']) ? 'selected' : '' ?>><?= htmlspecialchars($k['kelas']) ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Pilih Bulan</label>
                    <input type="month" name="bulan" class="form-control" value="<?= $bulanFilter ?>" required>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-search"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- PRINT AREA -->
    <?php if (!empty($kelasFilter)): ?>
    <div id="printArea" class="card shadow">
        <div class="card-body">
            
            <!-- KOP SURAT (Hanya tampil saat print) -->
            <div class="kop-surat d-none d-print-block">
                <?php 
                $logo = (isset($lembaga['logo']) && !empty($lembaga['logo'])) ? 'img/' . $lembaga['logo'] : 'img/logo.png';
                ?>
                <img src="<?= asset_url($logo) ?>" alt="Logo">
                <h2>PEMERINTAH PROVINSI <?= strtoupper($lembaga['provinsi'] ?? 'JAWA TIMUR') ?></h2>
                <h3>DINAS PENDIDIKAN</h3>
                <h2><strong><?= strtoupper($lembaga['nmsekolah'] ?? 'SMAN 1 SUMBER') ?></strong></h2>
                <p><?= $lembaga['alamat'] ?? 'Jalan Raya Sumber' ?></p>
            </div>

            <div class="text-center mb-4 mt-3">
                <h5 class="font-weight-bold" style="color:black;">REKAPITULASI IBADAH SHOLAT SISWA</h5>
                <p class="m-0" style="color:black;">Bulan: <?= date('F Y', strtotime($bulanFilter.'-01')) ?> &nbsp; | &nbsp; Kelas: <?= htmlspecialchars($kelasFilter) ?></p>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered print-table" style="color:black; width:100%;">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th>Nama Siswa</th>
                            <th width="15%" class="text-center">Sholat Dzuhur<br><small>(Sekolah)</small></th>
                            <th width="15%" class="text-center">Sholat Jumat<br><small>(Sekolah)</small></th>
                            <th width="15%" class="text-center">Sholat 5 Waktu<br><small>(Rumah / 7 KAIH)</small></th>
                            <th width="15%" class="text-center">Total Poin / Skor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rekapan)): ?>
                        <tr><td colspan="6" class="text-center">Data tidak ditemukan.</td></tr>
                        <?php else: ?>
                            <?php $no=1; foreach($rekapan as $r): 
                                $total = $r['dzuhur'] + $r['jumat'] + $r['rumah'];
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($r['nama']) ?></td>
                                <td class="text-center"><?= $r['dzuhur'] ?> kali</td>
                                <td class="text-center"><?= $r['jumat'] ?> kali</td>
                                <td class="text-center"><?= $r['rumah'] ?> kali</td>
                                <td class="text-center"><strong><?= $total ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- TANDA TANGAN (Hanya tampil saat print) -->
            <div class="ttd-container d-none d-print-flex">
                <div class="ttd-box">
                    <p>Mengetahui,</p>
                    <p>Kepala Sekolah</p>
                    <div class="ttd-space"></div>
                    <p><strong><u><?= $namaKepsek ?></u></strong></p>
                    <p>NIP. <?= $nipKepsek ?></p>
                </div>
                <div class="ttd-box">
                    <p><?= $lembaga['kecamatan'] ?? 'Sumber' ?>, <?= date('d F Y') ?></p>
                    <p>Guru Pendidikan Agama</p>
                    <div class="ttd-space"></div>
                    <p><strong><u><?= htmlspecialchars($namaGuru) ?></u></strong></p>
                    <p>NIP. <?= htmlspecialchars($nipGuru) ?></p>
                </div>
            </div>

        </div>
    </div>
        <?php endif; ?>
        </div> <!-- end container-fluid -->
    </div> <!-- end desktop-center-column -->
</div> <!-- end app-shell -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
