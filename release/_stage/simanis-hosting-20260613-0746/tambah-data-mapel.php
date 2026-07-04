<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');
$tglskr = date('Y-m-d H:i:s');
// Pemrosesan form
if (isset($_POST['submit'])) {
    //definisikan variabel dulu
      $mpl = mysqli_real_escape_string($conn, $_POST['mapel']);
	  $sql = mysqli_query($conn, "SELECT * FROM tbl_mapel WHERE nama_mapel='$mpl'");
	  $jum = mysqli_num_rows($sql);
	  $isilog = "$nama "."menambah master data mata pelajaran "."$mpl"." ke dalam sistem";
	  
	  if($jum < 1) {
		mysqli_query($conn, "INSERT INTO tbl_mapel(nama_mapel) VALUES('$mpl')");
		mysqli_query($conn, "INSERT INTO tbl_log(waktu, isi_log) VALUES('$tglskr', '$isilog')");
		?>
		<script>
			  Swal.fire({
			  position: 'top-end',
			  icon: 'success',
			  title: 'Berhasil menyimpan data!',
			  showConfirmButton: false,
			  timer: 1500
			  }).then(function(){
				  window.location.href = "?page=tambah-data-mapel";
			  })
		</script>
	<?php } else { ?>
		<script>Swal.fire('Gagal', 'Data sudah ada di dalam daftar!', 'error')</script>
	<?php }
}
?>

    <div class="container-fluid">
    <div class="container">
    <div class="alert" style="background-color: #ffffff; outline: 1px solid lightgrey">
        <h4>Tambah Master Data Mata Pelajaran</h4>
    </div>

<div class="container rounded mb-4" style="background-color: #ffffff; outline: 1px solid lightgrey">
<!-- ROW BARU -->
<div class="row">
<div class="col-sm-6 pt-4"> <!-- kolom untuk form input Kelas -->
<form method="POST" action="" class="needs-validation" novalidate>
<!-- Mapel -->
<div class="form-group col-sm-6">
    <label for="mapel">MATA PELAJARAN:</label>
    <input type="text" class="form-control" id="mapel" name="mapel" oninput="uppercase(this)" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Mapel -->


<!-- Tombol Submit dan cancel -->
<div class="form-group col-sm-2 pb-4">
<table style="border: none;">
<tr>
    <td><input type="submit" class="btn btn-success" id="submit" name="submit" value="Simpan"></td>
    <td><a class="btn btn-warning" href="?page=tambah-data-mapel">Cancel</a></td>
</tr>
</table>
  </div>
<!-- end of submit dan cancel -->
</form>
</div> <!-- div penutup kolom input Kelas -->

<div class="col-sm-6 pt-4 pb-4"> <!-- div kolom penampil data Kelas -->
<div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="5%">NO.</th>
                        <th>NAMA MATA PELAJARAN</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    // ini isi dari tabel
                    $no = 1;
                    $sql = mysqli_query($conn, "SELECT * FROM tbl_mapel ORDER BY id_mapel ASC");
                    while ($data = mysqli_fetch_array($sql)) {
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= $data['nama_mapel']; ?></td>
                                <td class="text-center"><a class="btn btn-sm btn-circle btn-danger" href="delete-mapel.php?id_mapel=<?= $data['id_mapel']; ?>&nm=<?= $data['nama_mapel']; ?>" onclick="return confirm('Yakin mau menghapus mapel <?= $data['nama_mapel']; ?> dari daftar?');"><i class="fas fa-trash" title="Hapus"></i></a></td>
                            </tr>
                    <?php
                    // ini penutup while 
                    }
                    ?>
                </tbody>

            </table>
        </div>
</div> <!-- Penutup div penampil data TA -->

</div> <!-- Ini akhir dari ROW BARU -->
</div>
</div>
</div>

<script>
	function uppercase(input) {
		input.value = input.value.toUpperCase();
	}
</script>
