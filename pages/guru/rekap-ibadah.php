<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["no_induk"])) { header("location: ../../index.php?haruslogin"); exit; }
if($_SESSION['hak_akses'] != 2) { echo '<script>window.location="../../404.html";</script>'; exit; }

include '../../koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$nipguru = $_SESSION['no_induk'];
$nipguruEsc = mysqli_real_escape_string($conn, $nipguru);

// Cari agama guru
$qGuru = @mysqli_query($conn, "SELECT nama_siswa as nama, agama FROM tbl_guru WHERE no_induk='$nipguruEsc' LIMIT 1");
$guruData = $qGuru ? mysqli_fetch_assoc($qGuru) : [];
$agamaGuru = strtolower(trim((string)($guruData['agama'] ?? '')));
$isGuruIslam = strpos($agamaGuru, 'islam') !== false;
$isGuruKatolik = strpos($agamaGuru, 'katolik') !== false;

$targetAgama = isset($_GET['agama']) ? $_GET['agama'] : ($isGuruIslam ? 'islam' : ($isGuruKatolik ? 'katolik' : 'islam'));
$targetAgamaEsc = mysqli_real_escape_string($conn, $targetAgama);

$tglAwal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d');
$tglAkhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';

$tglAwalEsc = mysqli_real_escape_string($conn, $tglAwal);
$tglAkhirEsc = mysqli_real_escape_string($conn, $tglAkhir);
$kelasEsc = mysqli_real_escape_string($conn, $kelas);

$kelasOpts = [];
$qK = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_siswa WHERE status='Aktif' ORDER BY kelas ASC");
while ($qK && ($r = mysqli_fetch_assoc($qK))) { $kelasOpts[] = $r['kelas']; }

$where = "j.habit_key = 'beribadah' AND j.tanggal >= '$tglAwalEsc' AND j.tanggal <= '$tglAkhirEsc'";
if ($kelas !== '') {
    $where .= " AND j.kelas = '$kelasEsc'";
}

$query = "
    SELECT j.*, s.agama 
    FROM tbl_7kih_jurnal j 
    JOIN tbl_siswa s ON j.no_induk = s.no_induk 
    WHERE $where AND LOWER(s.agama) LIKE '%$targetAgamaEsc%'
    ORDER BY j.tanggal DESC, j.kelas ASC, j.nama_siswa ASC, j.submitted_at ASC
";
$qData = @mysqli_query($conn, $query);
$data = [];
while ($qData && ($r = mysqli_fetch_assoc($qData))) {
    $data[] = $r;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Presensi Ibadah - Guru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800">

<div class="max-w-6xl mx-auto p-4 md:p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-900"><i class="fa-solid fa-praying-hands text-emerald-600 mr-2"></i>Rekap Presensi Ibadah 7 KAIH</h1>
            <p class="text-sm text-slate-500 mt-1">Laporan harian aktivitas ibadah siswa.</p>
        </div>
        <a href="guru.php" class="bg-white border border-slate-200 px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-arrow-left mr-2"></i>Kembali</a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Agama</label>
                <select name="agama" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    <option value="islam" <?= $targetAgama==='islam'?'selected':'' ?>>Islam</option>
                    <option value="katolik" <?= $targetAgama==='katolik'?'selected':'' ?>>Katolik</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Dari Tanggal</label>
                <input type="date" name="tgl_awal" value="<?= htmlspecialchars($tglAwal) ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Sampai Tanggal</label>
                <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tglAkhir) ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Kelas</label>
                <select name="kelas" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelasOpts as $k): ?>
                        <option value="<?= htmlspecialchars($k) ?>" <?= $kelas===$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl px-4 py-2 text-sm font-bold transition-colors"><i class="fa-solid fa-filter mr-2"></i>Filter</button>
                <button type="button" onclick="window.print()" class="bg-slate-800 hover:bg-slate-900 text-white rounded-xl px-4 py-2 text-sm font-bold transition-colors" title="Cetak/Simpan PDF"><i class="fa-solid fa-print"></i></button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase font-bold text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Siswa</th>
                        <th class="px-4 py-3">Ibadah</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3">Waktu Kirim</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Bukti</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($data)): ?>
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Tidak ada data ibadah pada filter yang dipilih.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data as $row): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($row['nama_siswa']) ?></div>
                                    <div class="text-xs text-slate-500"><?= htmlspecialchars($row['kelas']) ?></div>
                                </td>
                                <td class="px-4 py-3 font-medium text-emerald-700"><?= htmlspecialchars(str_replace('Beribadah - ', '', $row['habit_label'])) ?></td>
                                <td class="px-4 py-3 max-w-[200px] truncate text-slate-600" title="<?= htmlspecialchars($row['keterangan']) ?>"><?= htmlspecialchars($row['keterangan']) ?: '-' ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= date('H:i', strtotime($row['submitted_at'])) ?></td>
                                <td class="px-4 py-3">
                                    <?php 
                                    $st = $row['timeliness_status'];
                                    $col = $st === 'sangat_tepat' ? 'bg-emerald-100 text-emerald-700' : ($st === 'tepat' ? 'bg-blue-100 text-blue-700' : ($st === 'terlambat' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'));
                                    ?>
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider <?= $col ?>"><?= str_replace('_', ' ', $st) ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($row['photo_path']): ?>
                                        <a href="../../<?= htmlspecialchars($row['photo_path']) ?>" target="_blank" class="text-emerald-600 hover:text-emerald-800"><i class="fa-solid fa-image"></i> Lihat</a>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs">Tidak ada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @media print {
        body { background: white; }
        .max-w-6xl { max-width: 100%; padding: 0; }
        form, .bg-white.rounded-2xl.shadow-sm.mb-6, a[href="guru.php"], button { display: none !important; }
        .shadow-sm { box-shadow: none !important; }
        .border-slate-200 { border-color: #000 !important; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000 !important; padding: 6px !important; }
    }
</style>

</body>
</html>
