<?php
include "koneksi.php";

$no = 1;
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantMateriAlias = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_materi', 'id_sekolah') ? "tbl_materi.id_sekolah={$tenantId}" : "1=1";
$tenantGuruAlias = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_guru', 'id_sekolah') ? "tbl_guru.id_sekolah={$tenantId}" : "1=1";
$tenantMapelAlias = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_mapel_ampu', 'id_sekolah') ? "tbl_mapel_ampu.id_sekolah={$tenantId}" : "1=1";
$tenantGuru = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_guru', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$tenantMapel = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_mapel_ampu', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";

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

$sql = "SELECT tbl_materi.*, tbl_guru.*, tbl_mapel_ampu.*, DAYNAME(tbl_materi.tanggal) as hari 
        FROM tbl_materi 
        INNER JOIN tbl_guru ON tbl_materi.no_induk = tbl_guru.no_induk 
        INNER JOIN tbl_mapel_ampu ON tbl_materi.id_mapel = tbl_mapel_ampu.id_mapel 
        WHERE {$tenantMateriAlias} AND {$tenantGuruAlias} AND {$tenantMapelAlias}
        AND tbl_materi.tanggal BETWEEN '$tglAwal' AND '$tglAkhir'";

if (!empty($kelas)) {
    $sql .= " AND tbl_materi.kelas = '$kelas'";
}

if (!empty($guru)) {
    $sql .= " AND tbl_guru.no_induk = '$guru'";
}

$result = mysqli_query($conn, $sql) or die(mysqli_error($conn));

if (!isset($_SESSION["username"])) {
    header("location: index.php?haruslogin");
    exit;
} else if ($hakakses != 1) { ?>
    <script>window.location='404.html';</script>
<?php } ?>


<!-- Begin Page Content -->
<div class="container-fluid">

<!-- Page Heading -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Jurnal Kelas <?= $lembaga['nmsekolah']; ?></h6>
    </div>
    <div class="card-body">
        
             
             
<form method="GET" action="home.php?page=kelas" class="row g-3" id="filterForm">
    

    <div class="col-md-6">
        <label for="tglAwal">Dari Tanggal:</label>
        <input type="date" class="form-control" id="tglAwal" name="tglAwal" value="<?= isset($_GET['tglAwal']) ? $_GET['tglAwal'] : ''; ?>">
    </div>
    <div class="col-md-6">
        <label for="tglAkhir">Sampai Tanggal:</label>
        <input type="date" class="form-control" id="tglAkhir" name="tglAkhir" value="<?= isset($_GET['tglAkhir']) ? $_GET['tglAkhir'] : ''; ?>">
    </div>

<div class="col-md-6">
        <label for="guru">Nama Guru:</label>
        <select class="form-control" id="guru" name="guru" >
            <option value="" selected disabled>-- pilih --</option>
            <?php
            include "koneksi.php";
            $sqlGuru = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru WHERE {$tenantGuru} ORDER BY nama_guru ASC");
            while($dataGuru = mysqli_fetch_array($sqlGuru)) {
                $selected = (isset($_GET['guru']) && $_GET['guru'] == $dataGuru['no_induk']) ? 'selected' : '';
                ?>
                <option type="submit" value="<?= $dataGuru['no_induk']; ?>" <?= $selected; ?>>
                    <?= $dataGuru['nama_guru']; ?>
                </option>
            <?php } ?>
        </select>
    </div>

<div class="col-md-6">
    <label for="kelas">Nama Kelas:</label>
    <select class="form-control" id="kelas" name="kelas">
        <option value="" selected disabled>-- pilih --</option>
        <?php
        $kelasArray = array();
        $sqlKelas = "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE {$tenantMapel} ORDER BY kelas ASC";
        $resultKelas = mysqli_query($conn, $sqlKelas);
        while ($dataKelas = mysqli_fetch_array($resultKelas)) {
            $kelasArray[] = $dataKelas['kelas']; 
            $selected = (isset($_GET['kelas']) && $_GET['kelas'] == $dataKelas['kelas']) ? 'selected' : '';
            ?>
            <option value="<?= $dataKelas['kelas']; ?>" <?= $selected; ?>>
                <?= $dataKelas['kelas']; ?>
            </option>
        <?php } ?>
    </select>
</div>





<div class="col-md-12">
        <div class="row">
            <div class="col-md-6">
                <button type="submit" class="btn btn-primary mt-3">Lihat Data</button>
            </div>
            <div class="col-md-6 text-right">
                <a href="cetak-kelas.php?tglAwal=<?= $tglAwal ?>&tglAkhir=<?= $tglAkhir ?>&guru=<?= $guru ?>&kelas=<?= $kelas ?>" target="_blank" class="btn btn-success mt-3">Cetak</a>
            </div>
        </div>
    </div>
    
</form>

              
              
        
        <div class="table-responsive">
            <table class="table table-bordered dt-responsive display nowrap" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>NO.</th>
                        <th>HARI</th>
                        <th>TANGGAL</th>
                        <th>NAMA GURU</th>
                        <th>JAM</th>
						<th>MATERI</th>
						<th>KEGIATAN</th>
						<th>SISWA ABSEN</th>
						<th>CATATAN</th>
                    </tr>
                </thead>

                <tbody>
                    
                   <?php while ($data = mysqli_fetch_array($result)) { ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= namaHariIndonesia($data['hari']); ?></td>
                    <td><?= formatTanggalIndonesia($data['date']); ?></td>
                    <td><?= $data['nama_guru']; ?></td>
                    <td><?= $data['jam_mulai']; ?> WIB s.d <?= $data['jam_selesai']; ?> WIB</td>
                    <td><?= $data['materi']; ?></td>
                    <td><?= $data['kegiatan']; ?></td>
                    <td><?= $data['absen']; ?></td>
                    <td><?= $data['keterangan']; ?></td>
                </tr>
            <?php } ?>
                    
                    
                    
                    
                </tbody>
<!-- Tombol cetak jurnal -->
<div class="form-group col-sm-4 pb-4">

  </div>
<!-- end of Tombol cetak jurnal -->
            </table>
        </div>
		</div>
</div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    
    document.getElementById('filterForm').addEventListener('submit', function(event) {
        event.preventDefault(); 
        
        let tglAwal = document.getElementById('tglAwal').value;
        let tglAkhir = document.getElementById('tglAkhir').value;
        let guru = document.getElementById('guru').value;
        let kelas = document.getElementById('kelas').value;
        
        let url = `home.php?page=kelas&tglAwal=${tglAwal}&tglAkhir=${tglAkhir}&guru=${guru}&kelas=${kelas}`;
        
        window.open(url, '_parent');
    });
});
</script>

