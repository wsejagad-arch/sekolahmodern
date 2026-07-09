<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');
$namaadmin = $_SESSION['nama'];
$id = $_GET['id'];
$noinduk = $_GET['no_induk'];
$tglskr = date('Y-m-d H:i:s');

// Pemrosesan form
if (isset($_POST['submit'])) {
    //definisikan variabel dulu
      $namamapel = mysqli_real_escape_string($conn, $_POST['nama_mapel']);
      $hari = mysqli_real_escape_string($conn, $_POST['hari']);
	  $mulai = $_POST['jammulai'];
	  $selesai = $_POST['jamselesai'];
	  $kelas = $_POST['kelas'];
	  $thnajaran = $_POST['thnajaran'];
	  
	  // masukkan dulu data inputan ke dalam array untk pengecekan bentrok jadwal
	  $jadwal_baru = array(
		  'hari' => $_POST['hari'],
		  'jam_mulai' => $_POST['jammulai'],
		  'jam_selesai' => $_POST['jamselesai'],
		  'kelas' => $_POST['kelas']
		);
	  
	  // ambil data dari database untk pengecekan bentrok jadwal
	  $query = mysqli_query($conn, "SELECT hari, jam_mulai, jam_selesai, kelas FROM tbl_mapel_ampu");
	  $jadwal_lama = array();
	  while ($row = mysqli_fetch_assoc($query)) {
		  $jadwal_lama[] = array(
			'hari' => $row['hari'],
			'jam_mulai' => $row['jam_mulai'],
			'jam_selesai' => $row['jam_selesai'],
			'kelas' => $row['kelas']
		  );
		}
		
	  // ambil data dari database untk pengecekan bentrok jadwal dengan nip yang diinput
	  $queryybs = mysqli_query($conn, "SELECT hari, jam_mulai, jam_selesai FROM tbl_mapel_ampu WHERE no_induk='$noinduk'");
	  $jadwal_ybs = array();
	  while ($row = mysqli_fetch_assoc($queryybs)) {
		  $jadwal_ybs[] = array(
		    'hari' => $row['hari'],
			'jam_mulai' => $row['jam_mulai'],
			'jam_selesai' => $row['jam_selesai']
		  );
		}
      
	  $isilog = "$namaadmin " . "menambah jadwal mengajar guru dengan Nomor Induk " . "$noinduk" . " di kelas " . "$kelas";
	  $cekybs = cek_jadwal_ybs($jadwal_baru, $jadwal_ybs);
	  $cekbentrok = cek_jadwal_bentrok($jadwal_baru, $jadwal_lama);
	  $cek = mapel_ampu($noinduk, $namamapel, $hari, $kelas, $thnajaran);
	  if($cek == True && $cekbentrok == True && $cekybs == True) {
		mysqli_query($conn, "INSERT INTO tbl_mapel_ampu(id_guru, no_induk, nama_mapel, hari, jam_mulai, jam_selesai, kelas, thn_ajaran) VALUES('$id', '$noinduk','$namamapel', '$hari','$mulai','$selesai','$kelas','$thnajaran')");
		mysqli_query($conn, "INSERT INTO tbl_log (waktu, isi_log) VALUES ('$tglskr','$isilog')");
		
		?>
		<script>
			  Swal.fire({
			  position: 'top-end',
			  icon: 'success',
			  title: 'Berhasil menambah jadwal guru!',
			  showConfirmButton: false,
			  timer: 1500
			  }).then(function(){
				  window.location.href = "?page=detail-guru&id=<?= $id; ?>&no_induk=<?= $noinduk; ?>";
			  })
		</script>
	<?php } else if($cekbentrok == False) { ?>
		<script>Swal.fire('Gagal', 'Jadwal hari <?= $hari; ?> antara pukul <?= $mulai; ?> s.d <?= $selesai; ?> di kelas <?= $kelas; ?> sudah ada!', 'error')</script>
	<?php } else if($cekybs == False) { ?>
		<script>Swal.fire('Gagal', 'Jadwal hari <?= $hari; ?> antara pukul <?= $mulai; ?> s.d <?= $selesai; ?> sudah ada untuk guru ini!', 'error')</script>
	<?php } else { ?>
		<script>Swal.fire('Gagal', 'Mapel <?= $namamapel; ?> di kelas <?= $kelas; ?> untuk jadwal hari <?= $hari; ?> sudah terdaftar untuk Guru ini!', 'error')</script>
	<?php }
}
?>

    <div class="container-fluid">
    <div class="container">
    <div class="alert" style="background-color: #ffffff; outline: 1px solid lightgrey">
        <h4>Tambah Jadwal Mengajar</h4>
    </div>

<div class="container rounded" style="background-color: #ffffff; outline: 1px solid lightgrey">
<form method="POST" action="" class="needs-validation" novalidate>

<!-- Nama Mapel -->
<div class="form-group col-sm-4 pt-4">
    <label for="nama_mapel">Nama Mata Pelajaran:</label>
    <select class="form-control" name="nama_mapel">
        <option selected disabled>-- pilih --</option>
        <?php
			$mapel = mysqli_query($conn, "SELECT * FROM tbl_mapel ORDER BY nama_mapel ASC");
			while ($data = mysqli_fetch_array($mapel)) { ?>
			<option value="<?= $data['nama_mapel']; ?>"><?= $data['nama_mapel']; ?></option>
		<?php	
			}
		?>
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Nama Mapel -->

<!-- Hari -->
<div class="form-group col-sm-2">
    <label for="hari">Hari:</label>
    <select class="form-control" name="hari">
        <option selected disabled>-- pilih --</option>
        <?php
			$hari = mysqli_query($conn, "SELECT * FROM tbl_hari ORDER BY id_hari ASC");
			while ($nmhari = mysqli_fetch_array($hari)) { ?>
			<option value="<?= $nmhari['nama_hari']; ?>"><?= $nmhari['nama_hari']; ?></option>
		<?php	
			}
		?>
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Hari -->

<!-- Jam Mulai -->
<div class="form-group col-sm-2">
    <label for="jammulai">Jam Mulai:</label>
    <input type="time" class="form-control" id="jammulai" name="jammulai" lang="id-ID" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Jam Mulai -->

<!-- Jam Selesai -->
<div class="form-group col-sm-2">
    <label for="jamselesai">Jam Selesai:</label>
    <input type="time" class="form-control" id="jamselesai" name="jamselesai" lang="id-ID" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Jam Selesai -->

<!-- Kelas -->
<div class="form-group col-sm-2">
    <label for="kelas">Kelas:</label>
    <select class="form-control" name="kelas">
        <option selected disabled>-- pilih --</option>
        <?php
			$qkelas = mysqli_query($conn, "SELECT * FROM tbl_kelas ORDER BY kelas ASC");
			while ($nmkelas = mysqli_fetch_array($qkelas)) { ?>
			<option value="<?= $nmkelas['kelas']; ?>"><?= $nmkelas['kelas']; ?></option>
		<?php	
			}
		?>
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Kelas -->

<!-- Tahun Ajaran -->
<div class="form-group col-sm-2">
    <label for="thnajaran">Tahun Ajaran:</label>
    <select class="form-control" name="thnajaran">
        <option selected disabled>-- pilih --</option>
        <?php
			$qthn = mysqli_query($conn, "SELECT * FROM tbl_thn_ajaran ORDER BY tahun DESC");
			while ($thn = mysqli_fetch_array($qthn)) { ?>
			<option value="<?= $thn['tahun']; ?>"><?= $thn['tahun']; ?></option>
		<?php	
			}
		?>
    </select>    
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Tahun Ajaran -->

<!-- Tombol Submit dan cancel -->
<div class="form-group col-sm-2 pb-4">
<table style="border: none;">
<tr>
    <td><input type="submit" onclick="return confirm('Apakah data jadwal sudah benar?');" class="btn btn-success" id="submit" name="submit" value="Simpan"></td>
    <td><a class="btn btn-warning" href="?page=detail-guru&id=<?= $id; ?>&no_induk=<?= $noinduk; ?>">Cancel</a></td>
</tr>
</table>
  </div>
<!-- end of submit dan cancel -->

</form>
</div>
</div>
</div>


