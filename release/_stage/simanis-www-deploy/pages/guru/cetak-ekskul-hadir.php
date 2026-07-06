<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['username']) && !isset($_SESSION['no_induk'])) {
    header("location:index.php?haruslogin");
    exit();
}

require_once __DIR__ . '/../../koneksi.php';
require_once __DIR__ . '/../../functions.php';

$id_ekskul = isset($_REQUEST['id_ekskul']) ? (int)$_REQUEST['id_ekskul'] : 0;
$tglAwal = isset($_REQUEST['tglAwal']) ? $_REQUEST['tglAwal'] : date('Y-m-01');
$tglAkhir = isset($_REQUEST['tglAkhir']) ? $_REQUEST['tglAkhir'] : date('Y-m-t');

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

function formatTanggalIndonesia($tanggal) {
    $tanggalArray = explode('-', $tanggal);
    return $tanggalArray[2] . '-' . $tanggalArray[1] . '-' . $tanggalArray[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Rekap Kehadiran Ekstrakurikuler - <?= htmlspecialchars($ekskul_data['nama_ekskul']); ?></title>
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

        <h3 style="text-align: center; margin-bottom: 20px; text-decoration: underline;">REKAP DAFTAR HADIR EKSTRAKURIKULER</h3>

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
                    <td style="border: none;"><strong>Periode</strong></td>
                    <td style="border: none;">:</td>
                    <td style="border: none;"><?= formatTanggalIndonesia($tglAwal); ?> s.d <?= formatTanggalIndonesia($tglAkhir); ?></td>
                </tr>
            </table>
        </div>

        <?php
        // Hitung total pertemuan di rentang waktu
        $q_pertemuan = mysqli_query($conn, "SELECT COUNT(DISTINCT tanggal) as total FROM tbl_presensi_ekskul WHERE id_ekskul=$id_ekskul AND tanggal BETWEEN '$tglAwal' AND '$tglAkhir'");
        $total_pertemuan = mysqli_fetch_assoc($q_pertemuan)['total'];
        ?>
        <p style="font-size: 14px; font-weight: bold;">Total Pertemuan: <?= $total_pertemuan; ?> Kali</p>

        <table class="laporan-table">
            <tr>
                <th style="width: 5%;">NO.</th>
                <th style="width: 15%;">NIS</th>
                <th style="width: 35%;">NAMA SISWA</th>
                <th style="width: 10%;">HADIR</th>
                <th style="width: 10%;">IZIN</th>
                <th style="width: 10%;">SAKIT</th>
                <th style="width: 10%;">ALPA</th>
                <th style="width: 5%;">%</th>
            </tr>
            <?php
            $q_anggota = mysqli_query($conn, "SELECT a.no_induk_siswa, s.nama_siswa FROM tbl_anggota_ekskul a JOIN tbl_siswa s ON a.no_induk_siswa = s.no_induk WHERE a.id_ekskul = $id_ekskul ORDER BY s.nama_siswa ASC");
            
            $no = 1;
            if (mysqli_num_rows($q_anggota) > 0) {
                while ($a = mysqli_fetch_assoc($q_anggota)) {
                    $nis = $a['no_induk_siswa'];
                    
                    // Hitung Hadir
                    $q_h = mysqli_query($conn, "SELECT COUNT(*) as j FROM tbl_presensi_ekskul WHERE id_ekskul=$id_ekskul AND no_induk_siswa='$nis' AND status='Hadir' AND tanggal BETWEEN '$tglAwal' AND '$tglAkhir'");
                    $hadir = mysqli_fetch_assoc($q_h)['j'];
                    
                    // Hitung Izin
                    $q_i = mysqli_query($conn, "SELECT COUNT(*) as j FROM tbl_presensi_ekskul WHERE id_ekskul=$id_ekskul AND no_induk_siswa='$nis' AND status='Izin' AND tanggal BETWEEN '$tglAwal' AND '$tglAkhir'");
                    $izin = mysqli_fetch_assoc($q_i)['j'];
                    
                    // Hitung Sakit
                    $q_s = mysqli_query($conn, "SELECT COUNT(*) as j FROM tbl_presensi_ekskul WHERE id_ekskul=$id_ekskul AND no_induk_siswa='$nis' AND status='Sakit' AND tanggal BETWEEN '$tglAwal' AND '$tglAkhir'");
                    $sakit = mysqli_fetch_assoc($q_s)['j'];
                    
                    // Hitung Alpa (Absen)
                    $q_a = mysqli_query($conn, "SELECT COUNT(*) as j FROM tbl_presensi_ekskul WHERE id_ekskul=$id_ekskul AND no_induk_siswa='$nis' AND status='Alpa' AND tanggal BETWEEN '$tglAwal' AND '$tglAkhir'");
                    $alpa = mysqli_fetch_assoc($q_a)['j'];

                    $pct = ($total_pertemuan > 0) ? round(($hadir / $total_pertemuan) * 100) : 0;
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?></td>
                        <td class="text-center"><?= htmlspecialchars($nis); ?></td>
                        <td><?= htmlspecialchars($a['nama_siswa']); ?></td>
                        <td class="text-center"><?= $hadir; ?></td>
                        <td class="text-center"><?= $izin; ?></td>
                        <td class="text-center"><?= $sakit; ?></td>
                        <td class="text-center"><?= $alpa; ?></td>
                        <td class="text-center"><?= $pct; ?>%</td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="8" style="text-align:center;">Belum ada anggota.</td></tr>';
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
