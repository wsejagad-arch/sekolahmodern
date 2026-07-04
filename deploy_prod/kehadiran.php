<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }
?>

<!-- Begin Page Content -->
<div class="container-fluid">

<!-- Page Heading -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Monitoring Kehadiran Guru <?= $lembaga['nmsekolah']; ?></h6>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered dt-responsive display nowrap" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>NO.</th>
						<th>TANGGAL</th>
						<th>JAM</th>
                        <th>NAMA GURU</th>
						<th>BIDANG STUDI</th>
						<th>KELAS</th>
						<th>STATUS KEHADIRAN</th>
						<th>NAMA KETUA KELAS</th>
						<th>KETERANGAN</th>
                    </tr>
                </thead>

                <tbody>
                   <?php
                    // ini isi dari tabel
                    include "koneksi.php";
                    $no = 1;
                    $sql = mysqli_query($conn, "SELECT * FROM tbl_kehadiran ORDER BY tanggal DESC");
                    while ($data = mysqli_fetch_array($sql)) {
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= $data['tanggal']; ?></td>
                                <td><?= $data['jam_mulai']; ?> WIB s.d <?= $data['jam_selesai']; ?> WIB</td>
								<td><?= $data['nama_guru']; ?></td>
								<td><?= $data['nama_mapel']; ?></td>
								<td><?= $data['kelas']; ?></td>
							    <?php
									if($data['status_kehadiran'] == 1) { ?>
								<td><span class="badge badge-success">Hadir</span></td>
								<?php
									} else { ?>
								<td><span class="badge badge-danger">Tidak Hadir</span></td>
								<?php
									}
								?>
								<td><?= $data['nama_ketua_kelas']; ?></td>
								<td><?= $data['catatan']; ?></td>
                            </tr>
                    <?php
                    // ini penutup while 
                    }
                    ?>
                </tbody>

            </table>
        </div>
		</div>
</div>

</div>
