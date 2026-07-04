<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['username'])) {
    header("location:index.php?haruslogin");
    exit();
}

if($_SESSION['hak_akses'] != 1) {
    header("location:404.html");
    exit();
}

include "koneksi.php";
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

function formatTanggalIndonesia($tanggal) {
    $tanggalArray = explode('-', $tanggal);
    return $tanggalArray[2] . '-' . $tanggalArray[1] . '-' . $tanggalArray[0];
}

$tglAwal = isset($_GET['tglAwal']) ? $_GET['tglAwal'] : '';
$tglAkhir = isset($_GET['tglAkhir']) ? $_GET['tglAkhir'] : '';
$guru = isset($_GET['guru']) ? $_GET['guru'] : '';
$kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';


$sql = "SELECT * FROM tbl_materi 
    INNER JOIN tbl_guru ON tbl_materi.no_induk = tbl_guru.no_induk 
    INNER JOIN tbl_mapel_ampu ON tbl_materi.id_mapel = tbl_mapel_ampu.id_mapel
    WHERE tbl_materi.tanggal BETWEEN '$tglAwal' AND '$tglAkhir'";

if (!empty($guru)) {
    $sql .= " AND tbl_guru.no_induk = '$guru'";
}

if (!empty($kelas)) {
    $sql .= " AND tbl_materi.kelas = '$kelas'";
}
    
 if (!empty($mapel)) {
    $sql .= " AND tbl_materi.nama_mapel = '$mapel'";
}   

$result = mysqli_query($conn, $sql) or die(mysqli_error($conn));

$sqlKelas = "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk = '$guru'";
$resultKelas = mysqli_query($conn, $sqlKelas) or die(mysqli_error($conn));

$dataArray = [];
while ($data = mysqli_fetch_array($result)) {
    $dataArray[] = $data;
}

$settingQuery = "SELECT `id`, `nama_sekolah`, `alamat`, `logo`, `nama_pimpinan`, `nip_pimpinan` FROM `tbl_setting` WHERE 1";
$settingResult = mysqli_query($conn, $settingQuery);
$settingData = mysqli_fetch_assoc($settingResult);

$guruQuery = "SELECT `id_guru`, `no_induk`, `nama_guru`, `status_kepegawaian`, `nip_guru`, `foto`, `status` FROM `tbl_guru` WHERE `no_induk` = '$guru'";
$guruResult = mysqli_query($conn, $guruQuery);
$guruData = mysqli_fetch_assoc($guruResult);

$tanggalCetak = date('d F Y');



$mapelQuery = "SELECT `nama_mapel` FROM `tbl_mapel_ampu` WHERE `no_induk` = '$guru'";
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
            
            <div>Nama Guru: <?= $guruData['nama_guru']; ?></div>
           <div>Mata Pelajaran: <?php
$uniqueMapel = [];
foreach ($dataArray as $data) {
    if (!in_array($data['nama_mapel'], $uniqueMapel)) {
        $uniqueMapel[] = $data['nama_mapel'];
    }
}
?>

        <?php
        $no = 1;
        foreach ($uniqueMapel as $mapel) { ?>
            <?= $mapel; ?>
            
        <?php } ?>
    </div>

<div>Kelas: 
  <?= $kelas; ?>
</div>

           <div>Hari / Tanggal: <?= tgl_indo($tglAwal); ?> s.d <?= tgl_indo($tglAkhir); ?></div>
        </div>
        <table class="laporan-table">
            <tr>
                <th>NO.</th>
                <th>MATERI</th>
                <th>JAM KE</th>
                <th>KEGIATAN</th>
                <th>SISWA ABSEN</th>
            </tr>
                        <?php 
            foreach ($dataArray as $data) { ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= $data['materi']; ?></td>
                    <td><?= $data['jam_mulai']; ?> WIB s.d <?= $data['jam_selesai']; ?> WIB</td>
                    <td><?= $data['kegiatan']; ?></td>
                    <td><?= $data['absen']; ?></td>
                    </tr>
            <?php } ?>
            
        </table>
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


<script>window.print();</script>
