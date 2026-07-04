<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include "koneksi.php";

// Pemrosesan form
if (isset($_POST['submit'])) {
    //definisikan variabel dulu
      $ta = mysqli_real_escape_string($conn, $_POST['tahunajaran']);
	  $sql = mysqli_query($conn, "SELECT * FROM tbl_thn_ajaran WHERE tahun='$ta'");
	  $jum = mysqli_num_rows($sql);
	  
	  if($jum < 1) {
		$input = mysqli_query($conn, "INSERT INTO tbl_thn_ajaran(tahun) VALUES('$ta')");
		?>
		<script>
			  Swal.fire({
			  position: 'top-end',
			  icon: 'success',
			  title: 'Berhasil menyimpan data!',
			  showConfirmButton: false,
			  timer: 1500
			  }).then(function(){
				  window.location.href = "?page=tambah-tahun-ajaran";
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
        <h4>Tambah Data Master Tahun Ajaran</h4>
    </div>

<div class="container rounded" style="background-color: #ffffff; outline: 1px solid lightgrey">
<!-- ROW BARU -->
<div class="row">
<div class="col-sm-6 pt-4"> <!-- kolom untuk form input TA -->
<form method="POST" action="" class="needs-validation" novalidate>
<!-- Tahun Ajaran -->
<div class="form-group col-sm-4">
    <label for="tahun_ajaran">Tahun Ajaran:</label>
    <input type="text" class="form-control" id="tahunAjaran" name="tahunajaran" pattern="\d{4}/\d{4}" placeholder="YYYY/YYYY" required>
	<div id="errorMessage" class="text-danger" style="display: none;">Format salah!</div>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Tahun Ajaran -->


<!-- Tombol Submit dan cancel -->
<div class="form-group col-sm-2 pb-4">
<table style="border: none;">
<tr>
    <td><input type="submit" class="btn btn-success" id="submit" name="submit" value="Simpan"></td>
    <td><a class="btn btn-warning" href="?page=tambah-tahun-ajaran">Cancel</a></td>
</tr>
</table>
  </div>
<!-- end of submit dan cancel -->
</form>
</div> <!-- div penutup kolom input TA -->

<div class="col-sm-6 pt-4"> <!-- div kolom penampil data TA -->
<div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>NO.</th>
                        <th>TAHUN AJARAN</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    // ini isi dari tabel
                    $no = 1;
                    $sql = mysqli_query($conn, "SELECT * FROM tbl_thn_ajaran ORDER BY id_thn DESC");
                    while ($data = mysqli_fetch_array($sql)) {
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= $data['tahun']; ?></td>
                                <td class="text-center"><a class="btn btn-sm btn-circle btn-danger" href="delete-ta.php?id_thn=<?= $data['id_thn']; ?>" onclick="return confirm('Yakin mau menghapus data ini? Jika dihapus, data ini akan mempengaruhi data yang lain!');"><i class="fas fa-trash" title="Hapus"></i></a></td>
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

<script>
  const inputField = document.getElementById("tahunAjaran");
  const errorMessage = document.getElementById("errorMessage");

  inputField.addEventListener("input", function() {
    if (inputField.validity.patternMismatch) {
      errorMessage.style.display = "block";
    } else {
      errorMessage.style.display = "none";
    }
  });
</script>

</div>
</div>

