<?php
require_once __DIR__ . '/../../auth_helper.php';
require_once __DIR__ . '/../../bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('Location: ../../login.php?haruslogin');
    exit;
}

$nip = $_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nip);
$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;

$kelas = $_GET['kelas'] ?? '';
if (empty($kelas)) {
    die("Pilih kelas terlebih dahulu.");
}
$kelasEsc = mysqli_real_escape_string($conn, $kelas);

// Get All Tugas for this class by this teacher
$qTugas = mysqli_query($conn, "SELECT id, judul FROM tbl_literasi_tugas WHERE no_induk_guru='$nipEsc' AND kelas='$kelasEsc' AND id_sekolah=$idSekolah ORDER BY created_at ASC");
$tugasList = [];
while ($t = mysqli_fetch_assoc($qTugas)) {
    $tugasList[] = $t;
}

// Get All Siswa in this class
$qSiswa = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas='$kelasEsc' AND id_sekolah=$idSekolah AND (status IS NULL OR UPPER(status)='AKTIF') ORDER BY nama_siswa ASC");
$siswaList = [];
while ($s = mysqli_fetch_assoc($qSiswa)) {
    $siswaList[] = $s;
}

// Get Progress for all students and all tasks
$qProg = mysqli_query($conn, "SELECT p.no_induk_siswa, p.id_tugas, p.skor_literasi, p.status 
                              FROM tbl_literasi_progress p
                              JOIN tbl_literasi_tugas t ON p.id_tugas = t.id
                              WHERE t.kelas='$kelasEsc' AND t.no_induk_guru='$nipEsc'");
$progress = [];
while ($p = mysqli_fetch_assoc($qProg)) {
    $progress[$p['no_induk_siswa']][$p['id_tugas']] = $p;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Raport Literasi - <?= htmlspecialchars($kelas) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .text-left { text-align: left; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2, .header h3 { margin: 5px 0; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 8px 15px; cursor: pointer;">Cetak</button>
        <button onclick="window.close()" style="padding: 8px 15px; cursor: pointer;">Tutup</button>
    </div>

    <div class="header">
        <h2>Raport Evaluasi Literasi Siswa</h2>
        <h3>Kelas: <?= htmlspecialchars($kelas) ?></h3>
    </div>

    <?php if (empty($tugasList)): ?>
        <p style="text-align:center;">Belum ada tugas literasi untuk kelas ini.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 30px;">No</th>
                    <th rowspan="2" class="text-left">Nama Siswa</th>
                    <th colspan="<?= count($tugasList) ?>">Tugas Literasi (Skor)</th>
                    <th rowspan="2">Rata-Rata</th>
                </tr>
                <tr>
                    <?php foreach ($tugasList as $t): ?>
                        <th><?= htmlspecialchars($t['judul']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($siswaList as $s): 
                    $totalSkor = 0;
                    $jumlahTugasSelesai = 0;
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="text-left"><?= htmlspecialchars($s['nama_siswa']) ?></td>
                    <?php foreach ($tugasList as $t): 
                        $skor = '-';
                        if (isset($progress[$s['no_induk']][$t['id']])) {
                            $progSiswa = $progress[$s['no_induk']][$t['id']];
                            if ($progSiswa['status'] === 'selesai') {
                                $skor = $progSiswa['skor_literasi'];
                                $totalSkor += $skor;
                                $jumlahTugasSelesai++;
                            } else {
                                $skor = 'Belum';
                            }
                        }
                    ?>
                        <td><?= $skor ?></td>
                    <?php endforeach; ?>
                    
                    <td>
                        <strong><?= $jumlahTugasSelesai > 0 ? round($totalSkor / count($tugasList), 1) : '-' ?></strong>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
