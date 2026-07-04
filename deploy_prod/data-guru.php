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
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <a href="guru_jurnal.php class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Tambah Data</a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Data Guru <?= $lembaga['nmsekolah']; ?></h6>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>NO.</th>
                        <th>NIP/NUPTK</th>
						<th>NAMA GURU</th>
						<th>STATUS KEPEGAWAIAN</th>
						<th>STATUS KEAKTIFAN</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    // ini isi dari tabel
                    include "koneksi.php";
                    $no = 1;
                    $sql = mysqli_query($conn, "SELECT * FROM tbl_guru ORDER BY nama_guru ASC");
                    while ($data = mysqli_fetch_array($sql)) {
						$sttaktif = $data['status'];
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= $data['no_induk']; ?></td>
								<td><?= $data['nama_guru']; ?></td>
								<td><?= $data['status_kepegawaian']; ?></td>
								<?php if($sttaktif === "Aktif") { ?>
								<td><span class="badge badge-success"><?= $sttaktif; ?></span></td>
								<?php 
								} else { ?>
								<td><span class="badge badge-danger"><?= $sttaktif; ?></span></td>
								<?php } ?>
                                <td class="text-center"><a class="btn btn-sm btn-circle btn-primary" href="?page=detail-guru&id=<?= $data['id_guru']; ?>&no_induk=<?= $data['no_induk']; ?>"><i class="fas fa-info" title="Lihat Info Detail"></i></a>&nbsp;
								<?php
									if($sttaktif === "Aktif") { ?>
										<a class="btn btn-sm btn-circle btn-success" href="?page=tambah-mapel-guru&id=<?= $data['id_guru']; ?>&no_induk=<?= $data['no_induk']; ?>"><i class="fas fa-plus" title="Tambah Jadwal Mengajar"></i></a>
								<?php
									} else { ?>
										<button class="btn btn-sm btn-circle btn-secondary" disabled><i class="fas fa-plus" title="Guru ini tidak aktif!"></i></button>
								<?php
									}
								?>
								
								&nbsp;<a class="btn btn-sm btn-circle btn-info" href="?page=edit-guru&id_guru=<?= $data['id_guru']; ?>"><i class="fas fa-edit" title="Edit Keaktifan"></i></a></td>
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
