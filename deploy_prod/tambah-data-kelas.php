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
      $kls = mysqli_real_escape_string($conn, $_POST['kelas']);
	  $jurusan = mysqli_real_escape_string($conn, $_POST['jurusan']);
	  $rombel = mysqli_real_escape_string($conn, $_POST['rombel']);
	  if($jurusan == "") {
		  $kelas = $kls." ".$rombel;
	  } else {
		  $kelas = $kls." ".$jurusan." ".$rombel;
	  }
	  $sql = mysqli_query($conn, "SELECT * FROM tbl_kelas WHERE kelas='$kelas'");
	  $jum = mysqli_num_rows($sql);
	  $isilog = "$nama "."menambah master data kelas "."$kelas";
	  
	  if($jum < 1) {
		mysqli_query($conn, "INSERT INTO tbl_kelas(kelas) VALUES('$kelas')");
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
				  window.location.href = "?page=tambah-data-kelas";
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
        <h4>Tambah Master Data Kelas</h4>
    </div>

<div class="container rounded mb-4" style="background-color: #ffffff; outline: 1px solid lightgrey">
<!-- ROW BARU -->
<div class="row">
<div class="col-sm-6 pt-4"> <!-- kolom untuk form input Kelas -->
<form method="POST" action="" class="needs-validation" novalidate>
<table style="border:none;">
<tr>
<td>
<!-- Kelas -->
<div class="form-group col-sm-12">
    <label for="kelas">Kelas:</label>
    <select class="form-control" name="kelas" required>
        <option selected disabled>-- pilih --</option>
        <option value="X">X</option>
        <option value="XI">XI</option>
		<option value="XII">XII</option>
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Kelas -->
</td>

<td>
<!-- Peminatan -->
<div class="form-group col-sm-12">
    <label for="jurusan">Fase:</label>
    <select class="form-control" name="jurusan" required>
        <option selected disabled>-- pilih --</option>
	<!--	<option value="">Fase</option>  -->
        <option value="E">E</option>
        <option value="F">F</option>
	<!--	<option value="IPS">IPS</option> -->
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Peminatan -->	
</td>

<td>
<!-- Rombel -->
<div class="form-group col-sm-12">
    <label for="rombel">Rombel:</label>
    <select class="form-control" name="rombel" required>
        <option selected disabled>-- pilih --</option>
        <option value="1">1</option>
        <option value="2">2</option>
		<option value="3">3</option>
		<option value="4">4</option>
        <option value="5">5</option>
		<option value="6">6</option>
		<option value="7">7</option>
        <option value="8">8</option>
		<option value="9">9</option>
		<option value="10">10</option>
        <option value="11">11</option>
		<option value="12">12</option>
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Rombel -->
</td>
</tr>
</table>


<!-- Tombol Submit dan cancel -->
<div class="form-group col-sm-2 pb-4">
<table style="border: none;">
<tr>
    <td><input type="submit" class="btn btn-success" id="submit" name="submit" value="Simpan"></td>
    <td><a class="btn btn-warning" href="?page=tambah-data-kelas">Cancel</a></td>
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
                        <th>KELAS</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    // ini isi dari tabel
                    $no = 1;
                    $sql = mysqli_query($conn, "SELECT * FROM tbl_kelas ORDER BY kelas ASC");
                    while ($data = mysqli_fetch_array($sql)) {
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= $data['kelas']; ?></td>
                                <td class="text-center"><a class="btn btn-sm btn-circle btn-danger" href="delete-kelas.php?id_kelas=<?= $data['id_kelas']; ?>&kelas=<?= $data['kelas']; ?>" onclick="return confirm('Yakin mau menghapus kelas <?= $data['kelas']; ?> dari daftar?');"><i class="fas fa-trash" title="Hapus"></i></a></td>
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

