<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['username']) && !isset($_SESSION['no_induk'])) {
    header("location:index.php?haruslogin");
    exit();
}

require_once __DIR__ . '/../../koneksi.php';
require_once __DIR__ . '/../../functions.php';

$id_ekskul = isset($_GET['id_ekskul']) ? (int)$_GET['id_ekskul'] : 0;

if ($id_ekskul <= 0) {
    die("ID Ekstrakurikuler tidak valid.");
}

$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;

// Ambil data ekstrakurikuler & nama pembina
$q_ekskul = mysqli_query($conn, "SELECT e.*, g.nama_guru, g.nip_guru FROM tbl_ekskul e LEFT JOIN tbl_pembina_ekskul p ON e.id_ekskul = p.id_ekskul LEFT JOIN tbl_guru g ON p.no_induk_guru = g.no_induk WHERE e.id_ekskul = $id_ekskul AND e.id_sekolah = $idSekolah LIMIT 1");
$ekskul_data = mysqli_fetch_assoc($q_ekskul);

if (!$ekskul_data) {
    die("Data Ekstrakurikuler tidak ditemukan.");
}

// Ambil data sekolah
$settingQuery = "SELECT `id`, `nama_sekolah`, `alamat`, `logo`, `nama_pimpinan`, `nip_pimpinan` FROM `tbl_setting` WHERE id_sekolah = $idSekolah LIMIT 1";
$settingResult = mysqli_query($conn, $settingQuery);
$settingData = mysqli_fetch_assoc($settingResult);

// Jika tbl_setting tidak memiliki id_sekolah, coba tanpa where
if(!$settingData) {
    $settingQuery = "SELECT `id`, `nama_sekolah`, `alamat`, `logo`, `nama_pimpinan`, `nip_pimpinan` FROM `tbl_setting` LIMIT 1";
    $settingResult = mysqli_query($conn, $settingQuery);
    $settingData = mysqli_fetch_assoc($settingResult);
}

// Jika masih null, isi default
if(!$settingData) {
    $settingData = data_lembaga();
    $settingData['nama_pimpinan'] = $settingData['nmpimpinan'];
    $settingData['nip_pimpinan'] = $settingData['nippimpinan'];
    $settingData['nama_sekolah'] = $settingData['nmsekolah'];
}

$tanggalCetak = date('d F Y');

// Fungsi deskripsi nilai
function grade_desc($grade) {
    switch($grade) {
        case 'A': return 'Sangat baik, hampir selalu hadir dan berpartisipasi aktif.';
        case 'B': return 'Baik, hadir sebagian besar hari dan cukup aktif.';
        case 'C': return 'Cukup, hadir cukup sering.';
        case 'D': return 'Kurang, sering tidak hadir.';
        default: return '';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Nilai Ekstrakurikuler - <?= htmlspecialchars($ekskul_data['nama_ekskul']); ?></title>
    <link rel="stylesheet" type="text/css" href="../../css/mycss.css">
    <style>
        .judul-utama {
            text-align: center;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .kop-surat {
            margin-bottom: 20px;
            margin-top: 20px;
        }
        .kop-surat div {
            margin: 5px 0;
            font-size: 14px;
        }
        .tanda-tangan {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .tanda-tangan div {
            text-align: center;
            width: 45%;
        }
        .tanda-tangan div p {
            margin: 5px 0;
        }
        .kop-container {
            border-bottom: 3px solid #000;
            padding-bottom: 5px;
            margin-bottom: 20px;
            border-bottom-style: double;
            border-bottom-width: 4px;
        }
    </style>
</head>
<body>
    <div class="laporan">
        <div class="kop-container">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="width: 15%; text-align: center; vertical-align: middle; border: none;">
                        <?php if (!empty($settingData['logo']) && file_exists('../../img/' . $settingData['logo'])): ?>
                            <img src="../../img/<?= $settingData['logo'] ?>" style="max-width: 90px; height: auto;" alt="Logo">
                        <?php endif; ?>
                    </td>
                    <td style="width: 85%; text-align: center; vertical-align: middle; border: none; padding-right: 15%;">
                        <h3 style="margin: 0; font-size: 18px; font-weight: normal; text-transform: uppercase;">PEMERINTAH PROVINSI JAWA TENGAH</h3>
                        <h3 style="margin: 0; font-size: 18px; font-weight: normal; text-transform: uppercase;">DINAS PENDIDIKAN DAN KEBUDAYAAN</h3>
                        <h2 style="margin: 3px 0; font-size: 22px; font-weight: bold; text-transform: uppercase;"><?= htmlspecialchars($settingData['nama_sekolah']); ?></h2>
                        <p style="margin: 0; font-size: 14px;"><?= htmlspecialchars($settingData['alamat'] ?? ''); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <h3 style="text-align: center; margin-bottom: 20px; text-decoration: underline;">DAFTAR NILAI EKSTRAKURIKULER</h3>

        <div class="kop-surat">
            <table style="width: 100%; border: none; font-size: 14px; margin-bottom: 15px;">
                <tr>
                    <td style="width: 20%; border: none;"><strong>Nama Ekstrakurikuler</strong></td>
                    <td style="width: 2%; border: none;">:</td>
                    <td style="width: 78%; border: none;"><?= htmlspecialchars($ekskul_data['nama_ekskul']); ?></td>
                </tr>
                <tr>
                    <td style="border: none;"><strong>Pembina</strong></td>
                    <td style="border: none;">:</td>
                    <td style="border: none;"><?= htmlspecialchars($ekskul_data['nama_guru'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td style="border: none;"><strong>Tahun Ajaran</strong></td>
                    <td style="border: none;">:</td>
                    <td style="border: none;"><?= date('Y') . '/' . (date('Y') + 1); ?></td>
                </tr>
            </table>
        </div>

        <table class="laporan-table">
            <tr>
                <th style="width: 5%;">NO.</th>
                <th style="width: 15%;">NIS</th>
                <th style="width: 40%;">NAMA SISWA</th>
                <th style="width: 15%;">NILAI AKHIR</th>
                <th style="width: 25%;">DESKRIPSI</th>
            </tr>
            <?php
            // Hitung total hari kehadiran untuk referensi nilai otomatis jika kosong
            $total_days_res = mysqli_query($conn, "SELECT COUNT(DISTINCT tanggal) as total FROM tbl_presensi_ekskul WHERE id_ekskul=$id_ekskul");
            $total_days_row = mysqli_fetch_assoc($total_days_res);
            $total_days = $total_days_row['total'] > 0 ? $total_days_row['total'] : 1;

            $q_anggota = mysqli_query($conn, "SELECT a.no_induk_siswa, a.nilai, s.nama_siswa FROM tbl_anggota_ekskul a JOIN tbl_siswa s ON a.no_induk_siswa = s.no_induk WHERE a.id_ekskul = $id_ekskul ORDER BY s.nama_siswa ASC");
            
            $no = 1;
            if (mysqli_num_rows($q_anggota) > 0) {
                while ($a = mysqli_fetch_assoc($q_anggota)) {
                    $nis = $a['no_induk_siswa'];
                    $presensi_res = mysqli_query($conn, "SELECT COUNT(*) as hadir FROM tbl_presensi_ekskul WHERE id_ekskul=$id_ekskul AND no_induk_siswa='$nis' AND status='Hadir'");
                    $present = mysqli_fetch_assoc($presensi_res)['hadir'];
                    $attendance_pct = round(($present / $total_days) * 100);
                    
                    if ($attendance_pct >= 90) $rec_grade = 'A';
                    elseif ($attendance_pct >= 80) $rec_grade = 'B';
                    elseif ($attendance_pct >= 70) $rec_grade = 'C';
                    else $rec_grade = 'D';

                    $final_grade = ($a['nilai'] !== '' && $a['nilai'] !== null) ? $a['nilai'] : $rec_grade;
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?></td>
                        <td class="text-center"><?= htmlspecialchars($nis); ?></td>
                        <td><?= htmlspecialchars($a['nama_siswa']); ?></td>
                        <td class="text-center" style="font-weight: bold;"><?= htmlspecialchars($final_grade); ?></td>
                        <td><?= grade_desc($final_grade); ?></td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="5" style="text-align:center;">Belum ada anggota.</td></tr>';
            }
            ?>
        </table>

        <div class="tanda-tangan">
            <div>
                <p>Mengetahui,</p>
                <p>Kepala Sekolah</p>
                <p style="margin-top: 60px;"><strong><?= htmlspecialchars($settingData['nama_pimpinan'] ?? '........................'); ?></strong></p>
                <p>NIP. <?= htmlspecialchars($settingData['nip_pimpinan'] ?? '........................'); ?></p>
            </div>
            <div>
               <p>Sumber, <?= $tanggalCetak; ?></p>
               <p>Pembina Ekstrakurikuler</p>
               <p style="margin-top: 60px;"><strong><?= htmlspecialchars($ekskul_data['nama_guru'] ?? '........................'); ?></strong></p>
               <p>NIP. <?= htmlspecialchars($ekskul_data['nip_guru'] ?? '........................'); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
<script>window.print();</script>
