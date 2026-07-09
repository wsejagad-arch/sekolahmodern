<?php
if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include "koneksi.php";
$idmapel = $_GET['id_mapel'];
$id = $_GET['id'];
$noinduk = $_GET['no_induk'];
// Pemrosesan form
if (isset($_POST['submit'])) {
    //definisikan variabel dulu
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
	  $query = mysqli_query($conn, "SELECT hari, jam_mulai, jam_selesai, kelas FROM tbl_mapel_ampu WHERE id_mapel != '$idmapel'");
	  $jadwal_lama = array();
	  while ($row = mysqli_fetch_assoc($query)) {
		  $jadwal_lama[] = array(
			'hari' => $row['hari'],
			'jam_mulai' => $row['jam_mulai'],
			'jam_selesai' => $row['jam_selesai'],
			'kelas' => $row['kelas']
		  );
		}
		
	  $cekbentrok = cek_jadwal_bentrok($jadwal_baru, $jadwal_lama);
	  if($cekbentrok == True) {
		$update = mysqli_query($conn, "UPDATE tbl_mapel_ampu SET hari='$hari', jam_mulai='$mulai', jam_selesai='$selesai', kelas='$kelas', thn_ajaran='$thnajaran' WHERE id_mapel='$idmapel'");
		?>
		<script>
			  Swal.fire({
			  position: 'top-end',
			  icon: 'success',
			  title: 'Berhasil merubah jadwal guru!',
			  showConfirmButton: false,
			  timer: 1500
			  }).then(function(){
				  window.location.href = "?page=detail-guru&id=<?= $id; ?>&no_induk=<?= $noinduk; ?>";
			  })
		</script>
	<?php } else { ?>
		<script>Swal.fire('Gagal', 'Jadwal hari <?= $hari; ?> antara pukul <?= $mulai; ?> s.d <?= $selesai; ?> di kelas <?= $kelas; ?> sudah ada!', 'error')</script>
	<?php }
}
?>

    <div class="container-fluid">
    <div class="container">
    <div class="alert" style="background-color: #ffffff; outline: 1px solid lightgrey">
        <h4>Ubah Jadwal Mengajar</h4>
    </div>

<div class="container rounded" style="background-color: #ffffff; outline: 1px solid lightgrey">
<form method="POST" action="" class="needs-validation" novalidate>

<?php
// tampilkan data dulu
$sql = mysqli_query($conn, "SELECT * FROM tbl_mapel_ampu WHERE id_mapel='$idmapel'");
$datasql = mysqli_fetch_array($sql); 
?>

<!-- Hari -->
<div class="form-group col-sm-2 pt-4">
    <label for="hari">Hari:</label>
    <select class="form-control" name="hari">
        <?php
			$hari = mysqli_query($conn, "SELECT * FROM tbl_hari ORDER BY id_hari ASC");
			while ($nmhari = mysqli_fetch_array($hari)) { 
				if($nmhari['nama_hari'] == $datasql['hari']) { ?>
					<option value="<?= $nmhari['nama_hari']; ?>" selected><?= $nmhari['nama_hari']; ?></option>
			<?php	
				} else { ?>
					<option value="<?= $nmhari['nama_hari']; ?>"><?= $nmhari['nama_hari']; ?></option>
			<?php		
				}	
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
    <input type="time" class="form-control" id="jammulai" name="jammulai" value="<?= $datasql['jam_mulai']; ?>" lang="id-ID" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Jam Mulai -->

<!-- Jam Selesai -->
<div class="form-group col-sm-2">
    <label for="jamselesai">Jam Selesai:</label>
    <input type="time" class="form-control" id="jamselesai" name="jamselesai" value="<?= $datasql['jam_selesai']; ?>" lang="id-ID" required>
    <div class="valid-feedback">Valid.</div>
    <div class="invalid-feedback">Harap diisi kolom ini.</div>
  </div>
<!-- Jam Selesai -->

<!-- Kelas -->
<div class="form-group col-sm-2">
    <label for="kelas">Kelas:</label>
    <select class="form-control" name="kelas">
        <?php
			$qkelas = mysqli_query($conn, "SELECT * FROM tbl_kelas ORDER BY kelas ASC");
			while ($nmkelas = mysqli_fetch_array($qkelas)) { 
				if($nmkelas['kelas'] == $datasql['kelas']) { ?>
					<option value="<?= $nmkelas['kelas']; ?>" selected><?= $nmkelas['kelas']; ?></option>
			<?php
				} else { ?>
					<option value="<?= $nmkelas['kelas']; ?>"><?= $nmkelas['kelas']; ?></option>
			<?php 
				}	
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
        <?php
			$qthn = mysqli_query($conn, "SELECT * FROM tbl_thn_ajaran ORDER BY tahun DESC");
			while ($thn = mysqli_fetch_array($qthn)) { 
				if($thn['tahun'] == $datasql['thn_ajaran']) { ?>
					<option value="<?= $thn['tahun']; ?>" selected><?= $thn['tahun']; ?></option>
			<?php	
				} else { ?>
					<option value="<?= $thn['tahun']; ?>"><?= $thn['tahun']; ?></option>
			<?php	
				}	
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
    <td><input type="submit" onclick="return confirm('Apakah jadwal sudah benar?');" class="btn btn-success" id="submit" name="submit" value="Simpan"></td>
    <td><a class="btn btn-warning" href="?page=detail-guru&id=<?= $id; ?>&no_induk=<?= $noinduk; ?>">Cancel</a></td>
</tr>
</table>
  </div>
<!-- end of submit dan cancel -->

</form>
</div>
</div>
</div>


