<?php
include "../../koneksi.php";

$tglAwal = isset($_GET['tglAwal']) ? $_GET['tglAwal'] : '';
$tglAkhir = isset($_GET['tglAkhir']) ? $_GET['tglAkhir'] : '';
$guru = isset($_GET['guru']) ? $_GET['guru'] : '';
$kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';

$sql = "SELECT * FROM tbl_materi 
    INNER JOIN tbl_guru ON tbl_materi.no_induk = tbl_guru.no_induk 
    INNER JOIN tbl_mapel_ampu ON tbl_materi.id_mapel = tbl_mapel_ampu.id_mapel
    WHERE tbl_materi.tanggal BETWEEN '$tglAwal' AND '$tglAkhir'";

if (!empty($kelas)) {
    $sql .= " AND tbl_materi.kelas = '$kelas'";
}

if (!empty($guru)) {
    $sql .= " AND tbl_guru.no_induk = '$guru'";
}

$result = mysqli_query($conn, $sql) or die(mysqli_error($conn));


$sqlKelas = "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk = '$guru'";
$resultKelas = mysqli_query($conn, $sqlKelas) or die(mysqli_error($conn));

if (!isset($_SESSION["username"])) {
    header("location: index.php?haruslogin");
    exit;
} else if ($hakakses != 2) { ?>
    <script>window.location='404.html';</script>
<?php } ?>






<!-- Begin Page Content -->
<div class="container-fluid">

<!-- Page Heading -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Jurnal Harian Guru <?= $lembaga['nmsekolah']; ?></h6>
    </div>
    <div class="card-body">
        
    <form method="GET" action=<?= guru_page('guru_jurnal') ?> class="row g-3" id="filterForm">
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
        <select class="form-control" id="guru" name="guru">
            <option value="" selected disabled>-- pilih --</option>
            <?php
            // koneksi sudah di-include di atas
            $sqlGuru = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru");
            while($dataGuru = mysqli_fetch_array($sqlGuru)) {
                $selected = (isset($_GET['guru']) && $_GET['guru'] == $dataGuru['no_induk']) ? 'selected' : '';
                echo '<option value="' . $dataGuru['no_induk'] . '" ' . $selected . '>' . $dataGuru['nama_guru'] . '</option>';
            }
            ?>
        </select>
    </div>

   
<div class="col-md-6">
    <label for="kelas">Nama Kelas:</label>
    <select class="form-control" id="kelas" name="kelas">
        <option value="" selected disabled>-- pilih --</option>
        <?php
        while ($dataKelas = mysqli_fetch_array($resultKelas)) {
            $selected = ($kelas == $dataKelas['kelas']) ? 'selected' : '';
            echo '<option value="' . $dataKelas['kelas'] . '" ' . $selected . '>' . $dataKelas['kelas'] . '</option>';
        }
        ?>
    </select>
</div>
    
    
    
<div class="col-md-12">
        <div class="row">
            <div class="col-md-6">
                <button type="submit" class="btn btn-primary mt-3">Lihat Data</button>
            </div>
            <div class="col-md-6 text-right">
                <a href="cetak-jurnal.php?tglAwal=<?= $tglAwal ?>&tglAkhir=<?= $tglAkhir ?>&guru=<?= $guru ?>&kelas=<?= $kelas ?>" target="_blank" class="btn btn-success mt-3">Cetak</a>
            </div>
        </div>
    </div>
</form>


<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filterForm').addEventListener('submit', function(event) {
        event.preventDefault();
        
        let tglAwal = document.getElementById('tglAwal').value;
        let tglAkhir = document.getElementById('tglAkhir').value;
        let guru = document.getElementById('guru').value;
        let kelas = document.getElementById('kelas').value;
        
        let url = `home.php?page=jurnal&tglAwal=${tglAwal}&tglAkhir=${tglAkhir}&guru=${guru}&kelas=${kelas}`;
        
        window.open(url, '_parent');
    });
});
</script>



<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateFormAction() {
        let form = document.getElementById('filterForm');
        let pageInput = document.createElement('input');
        pageInput.type = 'hidden';
        pageInput.name = 'page';
        pageInput.value = 'jurnal';
        
        let existingPageInput = form.querySelector('input[name="page"]');
        if (existingPageInput) {
            form.removeChild(existingPageInput);
        }
        
        form.appendChild(pageInput);
        
        form.action = 'home.php';
    }

    updateFormAction();

    document.querySelectorAll('#filterForm select').forEach(function(select) {
        select.addEventListener('change', function() {
            updateFormAction(); 
            document.getElementById('filterForm').submit();
        });
    });

    document.getElementById('submitBtn').addEventListener('click', function(event) {
        event.preventDefault();
        
        let tglAwal = document.getElementById('tglAwal').value;
        let tglAkhir = document.getElementById('tglAkhir').value;
        let guru = document.getElementById('guru').value;
        let kelas = document.getElementById('kelas').value;
        
        let url = `home.php?page=jurnal&tglAwal=${tglAwal}&tglAkhir=${tglAkhir}&guru=${guru}&kelas=${kelas}`;
        
        window.open(url, '_parent');
    });
});

</script>
              
        
        
        
        <div class="table-responsive">
            <table class="table table-bordered dt-responsive display nowrap" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>NO.</th>
                        <th>JAM KE</th>
						<th>KELAS</th>
                        <th>NAMA GURU</th>
						<th>MATA PELAJARAN</th>
						<th>MATERI</th>
						<th>SISWA ABSEN</th>
						<th>CATATAN</th>
                    </tr>
                </thead>

                <tbody>
                 
                 <?php while ($data = mysqli_fetch_array($result)) { 
                            // Format absen list
                            $absenRaw = trim($data['absen']);
                            if (empty($absenRaw) || $absenRaw === '-' || $absenRaw === 'Nihil') {
                                $absenHtml = "-";
                            } else {
                                $absenList = explode(',', $absenRaw);
                                $absenHtml = '<ul style="margin: 0; padding-left: 15px; text-align: left;">';
                                foreach ($absenList as $abs) {
                                    $abs = trim($abs);
                                    if (!empty($abs)) {
                                        $absenHtml .= '<li>' . htmlspecialchars($abs) . '</li>';
                                    }
                                }
                                $absenHtml .= '</ul>';
                            }
                 ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= $data['jam_mulai']; ?> WIB s.d <?= $data['jam_selesai']; ?> WIB</td>
								<td><?= $data['kelas']; ?></td>
								<td><?= $data['nama_guru']; ?></td>
								<td><?= $data['nama_mapel']; ?></td>
							    <td><?= $data['materi']; ?></td>
								<td><?= $absenHtml; ?></td>
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




