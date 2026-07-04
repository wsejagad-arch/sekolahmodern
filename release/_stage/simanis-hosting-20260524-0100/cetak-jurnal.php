<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['username']) && !isset($_SESSION['no_induk'])) {
    header("location:index.php?haruslogin");
    exit();
}

// Izinkan admin (1) dan guru (2)
if(!isset($_SESSION['hak_akses']) || ($_SESSION['hak_akses'] != 1 && $_SESSION['hak_akses'] != 2)) {
    header("location:404.html");
    exit();
}

// Param tambahan kontrol perilaku
$forceLocal = isset($_GET['forceLocal']);
$debug = isset($_GET['debug']);
$suppressPrint = isset($_GET['print']) && $_GET['print'] == '0';

include "koneksi.php"; // boleh gagal; kita pasang fallback eksplisit

// Fallback paksa atau jika koneksi utama null
if ($forceLocal || !isset($conn) || !$conn) {
    if ($debug) echo "<!-- USING FORCED LOCAL CONNECTION -->";
    $localConn = @mysqli_connect('localhost','root','', 'jurnal', 3307);
    if ($localConn) {
        $conn = $localConn;
        mysqli_set_charset($conn,'utf8');
        date_default_timezone_set('Asia/Jakarta');
    } else {
        die("Tidak bisa konek database (fallback gagal)");
    }
}
include "functions.php";
$no = 1;

function namaHariIndonesia($hariInggris) {
    $hariInggris = strtolower($hariInggris);
    switch ($hariInggris) {
        case 'monday':
            return 'Senin';
        case 'tuesday':
            return 'Selasa';
        case 'wednesday':
            return 'Rabu';
        case 'thursday':
            return 'Kamis';
        case 'friday':
            return 'Jumat';
        case 'saturday':
            return 'Sabtu';
        case 'sunday':
            return 'Minggu';
        default:
            return 'Tidak diketahui';
    }
}

function namaHariInggris($hariIndonesia) {
    $hariIndonesia = strtolower($hariIndonesia);
    switch ($hariIndonesia) {
        case 'senin':
            return 'Monday';
        case 'selasa':
            return 'Tuesday';
        case 'rabu':
            return 'Wednesday';
        case 'kamis':
            return 'Thursday';
        case 'jumat':
            return 'Friday';
        case 'sabtu':
            return 'Saturday';
        case 'minggu':
            return 'Sunday';
        default:
            return 'Unknown';
    }
}

function formatTanggalIndonesia($tanggal) {
    $tanggalArray = explode('-', $tanggal);
    return $tanggalArray[2] . '-' . $tanggalArray[1] . '-' . $tanggalArray[0];
}

function columnExists($conn, $table, $column) {
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $columnEsc = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$columnEsc}'");
    return $res && mysqli_num_rows($res) > 0;
}

function resolveGuruNoInduk($conn, $value) {
    if (empty($value)) {
        return null;
    }
    $valEsc = mysqli_real_escape_string($conn, $value);
    $clauses = ["`no_induk`='{$valEsc}'", "`nama_guru`='{$valEsc}'"];
    if (columnExists($conn, 'tbl_guru', 'nip_guru')) {
        $clauses[] = "`nip_guru`='{$valEsc}'";
    }
    $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
    $sql = "SELECT `no_induk`, `nama_guru` FROM `tbl_guru` WHERE (" . implode(' OR ', $clauses) . ") AND id_sekolah = $idSekolah LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        return mysqli_fetch_assoc($res);
    }
    return null;
}

$tglAwal = isset($_GET['tglAwal']) ? $_GET['tglAwal'] : '';
$tglAkhir = isset($_GET['tglAkhir']) ? $_GET['tglAkhir'] : '';
$guru = isset($_GET['guru']) ? $_GET['guru'] : '';
$kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Normalisasi format tanggal (pastikan YYYY-MM-DD)
foreach (['tglAwal','tglAkhir'] as $tgParam) {
    if (!empty($$tgParam) && preg_match('~^(\d{2})-(\d{2})-(\d{4})$~', $$tgParam, $m)) {
        // convert DD-MM-YYYY -> YYYY-MM-DD jika user masukkan format lain
        $$tgParam = $m[3].'-'.$m[2].'-'.$m[1];
    }
}

// Jika tidak ada parameter tanggal, set default periode tahun ajaran
if (empty($tglAwal) || empty($tglAkhir)) {
    $tahunAjaran = date('Y');
    $tglAwal = $tahunAjaran . '-07-01'; // 1 Juli
    $tglAkhir = ($tahunAjaran + 1) . '-06-30'; // 30 Juni tahun depan
}

// Jika tidak ada guru yang dipilih, ambil dari session
if (empty($guru)) {
    // Untuk guru yang login, ambil no_induk dari session
    $guru = $_SESSION['no_induk'] ?? $_SESSION['username'] ?? '';
}

// Pastikan guru valid (bisa input berupa nama/NIP). Jika tidak valid, coba dari session.
$guruRaw = $guru;
$guruInfo = resolveGuruNoInduk($conn, $guru);
if (!$guruInfo && !empty($_SESSION['no_induk'])) {
    $guruInfo = resolveGuruNoInduk($conn, $_SESSION['no_induk']);
}
if (!$guruInfo && !empty($_SESSION['username']) && $_SESSION['hak_akses'] == 2) {
    $guruInfo = resolveGuruNoInduk($conn, $_SESSION['username']);
}
if ($guruInfo) {
    $guru = $guruInfo['no_induk'];
}
$guruValid = $guruInfo ? true : false;

// DEBUG: Tampilkan informasi debug
echo "<!-- DEBUG: Guru Raw = $guruRaw -->\n";
echo "<!-- DEBUG: Guru Resolved (no_induk) = $guru -->\n";
echo "<!-- DEBUG: Kelas Filter = $kelas -->\n";
echo "<!-- DEBUG: Periode = $tglAwal sampai $tglAkhir -->\n";

// Ambil data jadwal mengajar dari tbl_mapel_ampu
$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$jadwalQuery = "SELECT * FROM tbl_mapel_ampu WHERE no_induk = '$guru' AND id_sekolah = $idSekolah";
if (!empty($kelas)) {
    $jadwalQuery .= " AND kelas = '$kelas'";
}
$jadwalQuery .= " ORDER BY hari, jam_mulai";

echo "<!-- DEBUG: Query Jadwal = $jadwalQuery -->\n";

$jadwalResult = mysqli_query($conn, $jadwalQuery);
if (!$jadwalResult) {
    echo "<!-- DEBUG: Query Jadwal ERROR = " . mysqli_error($conn) . " -->\n";
}
$jadwalArray = [];
while ($jadwal = mysqli_fetch_assoc($jadwalResult)) {
    $jadwalArray[] = $jadwal;
    echo "<!-- DEBUG: Jadwal found: Kelas=" . $jadwal['kelas'] . ", Mapel=" . $jadwal['nama_mapel'] . ", Hari=" . $jadwal['hari'] . " -->\n";
}

echo "<!-- DEBUG: Found " . count($jadwalArray) . " schedules in database -->\n";

// Ambil data jurnal yang sudah diisi dari tbl_materi
$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$jurnalQuery = "SELECT * FROM tbl_materi
    WHERE no_induk = '$guru'
    AND id_sekolah = $idSekolah
    AND tanggal BETWEEN '$tglAwal' AND '$tglAkhir'";

if (!empty($kelas)) {
    $jurnalQuery .= " AND kelas = '$kelas'";
}

echo "<!-- DEBUG: Query Jurnal = $jurnalQuery -->\n";

$jurnalResult = mysqli_query($conn, $jurnalQuery);
if (!$jurnalResult) {
    echo "<!-- DEBUG: Query Jurnal ERROR = " . mysqli_error($conn) . " -->\n";
}
$jurnalArray = [];
while ($jurnal = mysqli_fetch_assoc($jurnalResult)) {
    $jurnalArray[$jurnal['tanggal'] . '_' . $jurnal['id_mapel']] = $jurnal;
    echo "<!-- DEBUG: Jurnal found: Tanggal=" . $jurnal['tanggal'] . ", Kelas=" . $jurnal['kelas'] . ", Mapel=" . $jurnal['nama_mapel'] . " -->\n";
}

echo "<!-- DEBUG: Found " . count($jurnalArray) . " journal entries -->\n";

// Generate data untuk ditampilkan
$dataArray = [];
$currentDate = strtotime($tglAwal);
$endDate = strtotime($tglAkhir);

while ($currentDate <= $endDate) {
    $tanggal = date('Y-m-d', $currentDate);
    $namaHariInggris = date('l', $currentDate); // Monday, Tuesday, etc.
    $namaHariIndonesia = namaHariIndonesia($namaHariInggris); // Senin, Selasa, etc.

    // Untuk setiap jadwal di hari tersebut
    foreach ($jadwalArray as $jadwal) {
        // Bandingkan nama hari (case insensitive)
        if (strtolower($jadwal['hari']) == strtolower($namaHariIndonesia)) {
            $key = $tanggal . '_' . $jadwal['id_mapel'];

            if (isset($jurnalArray[$key])) {
                // Ada data jurnal, gunakan data aktual
                $data = $jurnalArray[$key];
                $data['tanggal'] = $tanggal;
                $data['nama_hari'] = $namaHariIndonesia;
                $data['status'] = 'Sudah Mengisi Jurnal';
            } else {
                // Tidak ada data jurnal, buat data default dengan status berdasarkan tanggal
                $hariIni = date('Y-m-d');
                $besok = date('Y-m-d', strtotime('+1 day'));

                if ($tanggal < $hariIni) {
                    $status = 'Belum Mengisi Jurnal';
                    $statusColor = 'red';
                } elseif ($tanggal == $hariIni) {
                    $status = 'Hari Ini (Belum Diisi)';
                    $statusColor = 'orange';
                } else {
                    $status = 'Jadwal Akan Datang';
                    $statusColor = 'blue';
                }

                $data = [
                    'tanggal' => $tanggal,
                    'nama_hari' => $namaHariIndonesia,
                    'nama_mapel' => $jadwal['nama_mapel'],
                    'kelas' => $jadwal['kelas'],
                    'jam_mulai' => $jadwal['jam_mulai'],
                    'jam_selesai' => $jadwal['jam_selesai'],
                    'materi' => '-',
                    'kegiatan' => '-',
                    'absen' => '-',
                    'status' => $status,
                    'status_color' => $statusColor
                ];
            }

            // Terapkan filter status jika ada
            if ($statusFilter === 'filled' && $data['status'] !== 'Sudah Mengisi Jurnal') {
                // skip non-filled
            } elseif ($statusFilter === 'empty' && $data['status'] === 'Sudah Mengisi Jurnal') {
                // skip filled
            } else {
                $dataArray[] = $data;
            }
        }
    }

    $currentDate = strtotime('+1 day', $currentDate);
}

echo "<!-- DEBUG: Total entries generated = " . count($dataArray) . " -->\n";

// Kita TIDAK lagi keluar; tabel harus tetap dirender meski kosong
$noDataReason = '';
if (!$guruValid) {
    $noDataReason = 'Guru tidak ditemukan atau tidak valid. Pastikan parameter guru memakai no_induk yang benar.';
} elseif (empty($jadwalArray)) {
    $noDataReason = 'Tidak ada jadwal mengajar untuk guru ini.';
} elseif (empty($dataArray)) {
    $noDataReason = 'Tidak ada tanggal yang mengandung jadwal dalam periode ini.';
}

// Hitung statistik
$totalJadwal = count($dataArray);
$jadwalTerisi = 0;
$jadwalKosong = 0;

foreach ($dataArray as $data) {
    if ($data['status'] == 'Sudah Mengisi Jurnal') {
        $jadwalTerisi++;
    } else {
        $jadwalKosong++;
    }
}

$percentage = $totalJadwal ? round(($jadwalTerisi/$totalJadwal)*100, 1) : 0;
echo "<!-- SUMMARY: Total=$totalJadwal, Filled=$jadwalTerisi, Empty=$jadwalKosong, Percentage={$percentage}% -->\n";

$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$sqlKelas = "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk = '$guru' AND id_sekolah = $idSekolah";
$resultKelas = mysqli_query($conn, $sqlKelas) or die(mysqli_error($conn));

$settingQuery = "SELECT `id`, `nama_sekolah`, `alamat`, `logo`, `nama_pimpinan`, `nip_pimpinan` FROM `tbl_setting` WHERE id_sekolah = $idSekolah";
$settingResult = mysqli_query($conn, $settingQuery);
$settingData = mysqli_fetch_assoc($settingResult);

$guruQuery = "SELECT `id_guru`, `no_induk`, `nama_guru`, `status_kepegawaian`, `nip_guru`, `foto`, `status` FROM `tbl_guru` WHERE `no_induk` = '$guru' AND id_sekolah = $idSekolah";
$guruResult = mysqli_query($conn, $guruQuery);
$guruData = mysqli_fetch_assoc($guruResult);

$tanggalCetak = date('d F Y');

$mapelQuery = "SELECT `nama_mapel` FROM `tbl_mapel_ampu` WHERE `no_induk` = '$guru' AND id_sekolah = $idSekolah";
$mapelResult = mysqli_query($conn, $mapelQuery);
$mapelData = array();
while ($row = mysqli_fetch_assoc($mapelResult)) {
    $mapelData[] = $row['nama_mapel'];
}
$mapelString = implode(', ', $mapelData);




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Jurnal Mengajar Guru</title>
    <link rel="stylesheet" type="text/css" href="css/mycss.css">
<style>
      /* Hide elements with .no-print class during printing */
      .no-print { display:block; }
      @media print { .no-print { display:none !important; } }
      <style>
        .judul-utama {
            text-align: center;
            margin-bottom: 20px;
        }
        .kop-surat {
            margin-bottom: 20px;
        }
        .kop-surat div {
            margin: 5px 0;
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
    </style>
</head>
<body>
    <div class="laporan">
        <h3 class="judul-utama">Jurnal Mengajar Guru</h3>
        <h3 class="judul-utama"><?= $settingData['nama_sekolah']; ?></h3>
        
        <div class="kop-surat">
            
            <div>Nama Guru: <?= $guruData && !empty($guruData['nama_guru']) ? htmlspecialchars($guruData['nama_guru']) : 'Tidak ditemukan'; ?></div>
            <div>Mata Pelajaran: <?= $mapelString ? htmlspecialchars($mapelString) : '-'; ?></div>

<div>Kelas: <?= $kelas ? htmlspecialchars($kelas) : 'Semua'; ?></div>

           <div>Hari / Tanggal: <?= tgl_indo($tglAwal); ?> s.d <?= tgl_indo($tglAkhir); ?></div>
        </div>
        <table class="laporan-table">
            <tr>
                <th>NO.</th>
                <th>TANGGAL</th>
                <th>JAM</th>
                <th>KELAS</th>
                <th>MATA PELAJARAN</th>
                <th>MATERI</th>
                <th>SISWA ABSEN</th>
                <th>CATATAN</th>
            </tr>
            <?php if ($noDataReason): ?>
                <tr>
                    <td colspan="8" style="text-align:center;color:red;"><?= htmlspecialchars($noDataReason); ?><br>Kriteria: Guru: <?= htmlspecialchars($guru); ?>, Periode: <?= formatTanggalIndonesia($tglAwal); ?> - <?= formatTanggalIndonesia($tglAkhir); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($dataArray as $data): ?>
                    <?php
                        $status = $data['status'];
                        $catatan = '';
                        if ($status == 'Sudah Mengisi Jurnal') {
                            $catatan = 'Sudah Mengisi Jurnal';
                        } elseif ($status == 'Hari Ini (Belum Diisi)') {
                            $catatan = 'Hari ini belum diisi';
                        } elseif ($status == 'Jadwal Akan Datang') {
                            $catatan = 'Belum waktunya';
                        } else {
                            $catatan = 'Belum Mengisi Jurnal';
                        }
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?></td>
                        <td><?= formatTanggalIndonesia($data['tanggal']); ?></td>
                        <td><?= $data['jam_mulai']; ?> WIB s.d <?= $data['jam_selesai']; ?> WIB</td>
                        <td><?= $data['kelas']; ?></td>
                        <td><?= $data['nama_mapel']; ?></td>
                        <td><?= $data['materi']; ?></td>
                        <td><?= $data['absen']; ?></td>
                        <td>
                            <?php
                                if ($status == 'Sudah Mengisi Jurnal') {
                                    echo '<span style="color:green">✅ '.$catatan.'</span>';
                                } elseif ($status == 'Hari Ini (Belum Diisi)') {
                                    echo '<span style="color:orange">🟡 '.$catatan.'</span>';
                                } elseif ($status == 'Jadwal Akan Datang') {
                                    echo '<span style="color:blue">🔵 '.$catatan.'</span>';
                                } else {
                                    echo '<span style="color:red">❌ '.$catatan.'</span>';
                                }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>

        <?php if (!$noDataReason): ?>
            <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                <h4>Ringkasan Jurnal Mengajar</h4>
                <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                    <div><strong>Total Jadwal:</strong> <?= $totalJadwal; ?></div>
                    <div><strong>Sudah Terisi:</strong> <span style="color: green;"><?= $jadwalTerisi; ?></span></div>
                    <div><strong>Belum Terisi:</strong> <span style="color: red;"><?= $jadwalKosong; ?></span></div>
                    <div><strong>Persentase:</strong> <?= $totalJadwal ? round(($jadwalTerisi/$totalJadwal)*100, 1) : 0; ?>%</div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($debug): ?>
            <div class="no-print" style="margin-top:25px;font-size:12px;font-family:monospace;background:#eef;padding:10px;">
                <strong>DEBUG MODE</strong><br>
                Guru: <?= htmlspecialchars($guru); ?> | Periode: <?= $tglAwal; ?> s/d <?= $tglAkhir; ?><br>
                Jadwal ditemukan: <?= count($jadwalArray); ?> | Jurnal terisi: <?= count($jurnalArray); ?> | Generated: <?= count($dataArray); ?><br>
                <?php if ($noDataReason): ?>Alasan kosong: <?= htmlspecialchars($noDataReason); ?><br><?php endif; ?>
                Contoh Jadwal (max 3):<br>
                <?php for($i=0;$i<min(3,count($jadwalArray));$i++){ $j=$jadwalArray[$i]; echo '- '.$j['hari'].' '.$j['nama_mapel'].' '.$j['kelas'].' '.$j['jam_mulai'].'-'.$j['jam_selesai'].'<br>'; } ?>
                Contoh Data (max 3):<br>
                <?php for($i=0;$i<min(3,count($dataArray));$i++){ $d=$dataArray[$i]; echo '- '.$d['tanggal'].' '.$d['nama_mapel'].' '.$d['kelas'].' '.$d['status'].'<br>'; } ?>
            </div>
        <?php endif; ?>
          <div class="tanda-tangan">
            <div>
                <p>Sumber, <?= $tanggalCetak; ?></p>
                <p>Mengetahui</p>
                <p>Kepala Sekolah</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>(<?= $settingData['nama_pimpinan']; ?>)</p>
                <p>NIP. <?= $settingData['nip_pimpinan']; ?></p>
                
            </div>
            <div>
               <p>&nbsp;</p>
               <p>&nbsp;</p>
                <p>Guru Mata Pelajaran</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <?php if (!empty($guruData['nama_guru'])) : ?>
                    <p>(<?= $guruData['nama_guru']; ?>)</p>
                <?php else : ?>
                    <p style="margin-top: 20px;">&nbsp;</p>
                <?php endif; ?>
                <p>NIP. <?= $guruData['nip_guru']; ?></p>
               
            </div>
        </div>
    </div>
</body>

</html>


<?php if (!$suppressPrint): ?>
<script>window.print();</script>
<?php endif; ?>
