<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
	<script>
		window.location = '404.html';
	</script>
<?php }

include "koneksi.php";
$_chkJabatan = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'jabatan'");
if ($_chkJabatan && mysqli_num_rows($_chkJabatan) === 0) {
	@mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN jabatan VARCHAR(100) DEFAULT NULL AFTER status_kepegawaian");
}
// tampilkan data guru dari database
$id = $_GET['id'];
$noinduk = $_GET['no_induk'];
$sql = mysqli_query($conn, "SELECT * FROM tbl_guru WHERE id_guru='$id'");
$row = mysqli_fetch_array($sql);
$foto = $row['foto'];
$nip_guru = $row['nip_guru'];
$nip = $row['no_induk'];
$namaguru = $row['nama_guru'];
$statuskepegawaian = $row['status_kepegawaian'];
$jabatan = trim($row['jabatan'] ?? '');
$status = $row['status'];
?>

<div class="container-fluid">
	<div class="container">
		<div class="alert" style="background-color: #ffffff; outline: 1px solid lightgrey">
			<h4>Detail Data Guru</h4>
		</div>

		<div class="container rounded" style="background-color: #ffffff; outline: 1px solid lightgrey">

			<div class="row">
				<div class="col-sm-4 pt-4 pb-4">
					<?php
					if ($foto == "") { ?>
						<img src="img/no-photo.png" alt="<?= $namaguru; ?>" class="img-fluid rounded w-50 d-block mx-auto">
					<?php
					} else { ?>
						<img src="foto/<?= $foto; ?>" alt="<?= $namaguru; ?>" class="img-fluid rounded w-50 d-block mx-auto">
					<?php
					}
					?>
				</div>
				<div class="col-sm-8 pt-4 pb-4">
					<table class="table table-responsive">
						<tr>
							<td><strong>NAMA</strong></td>
							<td>:</td>
							<td><?= $namaguru; ?></td>
						</tr>
						<tr>
							<td><strong>NIP/NUPTK</strong></td>
							<td>:</td>
							<td><?= $nip; ?></td>
						</tr>
						<tr>
							<td><strong>STATUS KEPEGAWAIAN</strong></td>
							<td>:</td>
							<td><?= $statuskepegawaian; ?></td>
						</tr>
						<tr>
							<td><strong>JABATAN WKS</strong></td>
							<td>:</td>
							<td><?= $jabatan !== '' ? htmlspecialchars($jabatan) : 'Guru Biasa'; ?></td>
						</tr>
						<tr>
							<td><strong>STATUS KEAKTIFAN</strong></td>
							<td>:</td>
							<?php
							if ($status === "Aktif") { ?>
								<td><span class="badge badge-success"><?= $status; ?></span></td>
							<?php
							} else { ?>
								<td><span class="badge badge-danger"><?= $status; ?></span></td>
							<?php
							}
							?>
						</tr>
					</table>
				</div>
			</div>
		</div>


		<div class="alert mt-4" style="background-color: #ffffff; outline: 1px solid lightgrey">
			<h4>Jadwal Mengajar
				<?php
				if ($status === "Aktif") { ?>
					<span><a class="btn btn-sm btn-circle btn-success" href="?page=tambah-mapel-guru&id=<?= $id; ?>&no_induk=<?= $noinduk; ?>"><i class="fas fa-plus" title="Tambah Jadwal Mengajar"></i></a></span>
				<?php
				} else { ?>
					<span><button class="btn btn-sm btn-circle btn-secondary" title="Guru ini tidak aktif!" disabled><i class="fas fa-plus"></i></button></span>
				<?php
				}
				?>

				&nbsp;<span><a class="btn btn-sm btn-circle btn-danger" href="delete-all-jadwal.php?id=<?= $id; ?>&noinduk=<?= $noinduk; ?>" onclick="return confirm('Yakin mau mengosongkan jadwal guru ini? semua jadwal dan materi yang diupload akan dihapus dari sistem!');"><i class="fas fa-trash" title="Kosongkan Jadwal"></i></a></span>
			</h4>
		</div>

		<!-- disini menampilkan jadwal guru yang bersangkutan -->
		<div class="container rounded pt-4 pb-4 mt-4" style="background-color: #ffffff; outline: 1px solid lightgrey">
			<div class="table-responsive">
				<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0" nowrap>
					<thead>
						<tr class="backgroundna" style="color:#ffffff;">
							<th>NO.</th>
							<th>HARI</th>
							<th>MATA PELAJARAN</th>
							<th>KELAS</th>
							<th>JAM MULAI</th>
							<th>JAM SELESAI</th>
							<th>THN AJARAN</th>
							<th>&nbsp;</th>
						</tr>
					</thead>

					<tbody>
						<?php
						// ini isi dari tabel
						$nom = 1;
						$sql2 = mysqli_query($conn, "SELECT * FROM tbl_mapel_ampu WHERE no_induk='$nip'");
						while ($data = mysqli_fetch_array($sql2)) {
						?>
							<tr>
								<td class="text-center"><?= $nom++; ?></td>
								<td><?= $data['hari']; ?></td>
								<td><?= $data['nama_mapel']; ?></td>
								<td><?= $data['kelas']; ?></td>
								<td><?= $data['jam_mulai']; ?></td>
								<td><?= $data['jam_selesai']; ?></td>
								<td><?= $data['thn_ajaran']; ?></td>
								<td class="text-center"><a class="btn btn-sm btn-circle btn-info" href="?page=edit-mapel-guru&id_mapel=<?= $data['id_mapel']; ?>&id=<?= $id; ?>&no_induk=<?= $nip; ?>"><i class="fas fa-edit" title="Edit Mata Pelajaran"></i></a>&nbsp;<a class="btn btn-sm btn-circle btn-danger" href="delete-mapel-guru.php?id=<?= $id; ?>&id_mapel=<?= $data['id_mapel']; ?>&no_induk=<?= $data['no_induk']; ?>" onclick="return confirm('Yakin mau menghapus jadwal mapel <?= $data['nama_mapel']; ?> dari Guru ini?');"><i class="fas fa-trash" title="Hapus"></i></a></td>
							</tr>
						<?php
							// ini penutup while 
						}
						?>
					</tbody>

				</table>
			</div>
		</div>
		<!-- end of tampilan jadwal guru ybs -->


	</div>
</div>