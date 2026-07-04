<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if(!isset($_SESSION['no_induk'])) {
	header("location:index.php?haruslogin");
	exit();
} else if($_SESSION['hak_akses'] != 2) { ?>
	<script>window.location='404.html';</script>
<?php
	exit();
}

try {
	include "../../koneksi.php";
	include "../../functions.php";
	include_once '../../nocache.php';
} catch (Exception $e) {
	echo '<div class="alert alert-danger">Error loading dependencies: ' . $e->getMessage() . '</div>';
	exit();
}
date_default_timezone_set('Asia/Jakarta');
$nipguru = $_SESSION['no_induk'];
$tglskr = date("Y-m-d");
$hariini = ubah_nama_hari($tglskr);

if(isset($_POST['getDetail'])) {
  try {
    $id = isset($_POST['getDetail']) ? (int)$_POST['getDetail'] : 0;
    
    if ($id <= 0) {
      echo '<div class="alert alert-danger">ID jadwal tidak valid.</div>';
      exit;
    }
    
    // Keamanan: batasi pada jadwal guru yang sedang login - gunakan query biasa untuk kompatibilitas
    $id_escaped = mysqli_real_escape_string($conn, $id);
    $nipguru_escaped = mysqli_real_escape_string($conn, $nipguru);
    $query = "SELECT * FROM tbl_mapel_ampu WHERE id_mapel = '$id_escaped' AND no_induk = '$nipguru_escaped' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
      echo '<div class="alert alert-danger">Error executing query: ' . mysqli_error($conn) . '</div>';
      exit;
    }
    
    $dat = mysqli_fetch_assoc($result);
    
    if (!$dat) {
      echo '<div class="alert alert-danger d-flex align-items-center" role="alert">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Jadwal tidak ditemukan atau Anda tidak berhak mengaksesnya. (ID: ' . $id . ', Guru: ' . $nipguru . ')
          </div>';
      exit;
    }
  } catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    exit;
  }
  // Cek apakah jurnal untuk jadwal ini sudah diisi hari ini - gunakan query biasa untuk kompatibilitas
  $id_escaped = mysqli_real_escape_string($conn, $id);
  $tglskr_escaped = mysqli_real_escape_string($conn, $tglskr);
  $queryJ = "SELECT id_materi, file_materi, materi, kegiatan, keterangan FROM tbl_materi WHERE id_mapel = '$id_escaped' AND tanggal = '$tglskr_escaped'";
  $resJ = mysqli_query($conn, $queryJ);
  
  if ($resJ && mysqli_num_rows($resJ) > 0) {
    // Sudah ada jurnal hari ini. Izinkan input lagi hanya jika jam sekarang >= jam_selesai.
    $now = date('H:i');
    $jamSelesai = $dat['jam_selesai'] ?? '';
    $masihDalamSesi = ($jamSelesai !== '' && $now < $jamSelesai);

    echo '<div class="card mb-3"><div class="card-header bg-light">Ringkasan Jurnal Hari Ini</div><div class="card-body">';
    while ($mj = mysqli_fetch_assoc($resJ)) {
      $idMateri = (int)$mj['id_materi'];
      $file = $mj['file_materi'];
      echo '<div class="mb-3">';
      if (!empty($file)) {
        echo '<div class="mb-1"><i class="bi bi-file-earmark-pdf text-danger"></i> '
          .'<a target="_blank" href="../../materi/'.htmlspecialchars($file).'">'.htmlspecialchars($file).'</a></div>';
      }
      if (!empty($mj['materi'])) echo '<div><strong>Materi:</strong> '.htmlspecialchars($mj['materi']).'</div>';
      if (!empty($mj['kegiatan'])) echo '<div><strong>Kegiatan:</strong> '.htmlspecialchars($mj['kegiatan']).'</div>';
      if (!empty($mj['keterangan'])) echo '<div><strong>Catatan:</strong> '.htmlspecialchars($mj['keterangan']).'</div>';
      echo '<div class="mt-2">'
        .'<a class="btn btn-sm btn-outline-danger" href="delete-materi.php?id='.$idMateri.($file?('&file='.rawurlencode($file)):'').'" onclick="return confirm(\'Yakin mau menghapus isian jurnal ini?\');">Hapus Isian Jurnal</a>'
        .'</div>';
      echo '</div><hr class="my-2">';
    }
    echo '</div></div>';

    if ($masihDalamSesi) {
      echo '<div class="alert alert-success d-flex align-items-start" role="alert">'
        .'<div class="me-2"><i class="bi bi-check2-circle fs-5"></i></div>'
        .'<div>'
        .'<div class="fw-semibold mb-1">Anda sudah mengisi jurnal untuk sesi ini.</div>'
        .'<div class="small">Anda dapat mengisi jurnal kembali ketika sesi berikutnya sudah dimulai atau setelah menghapus isian saat ini.</div>'
        .'</div>'
        .'</div>';
      echo '<div class="text-end"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>';
      exit; // stop render form input selama sesi berjalan
    } else {
      echo '<div class="alert alert-info d-flex align-items-start" role="alert">'
        .'<div class="me-2"><i class="bi bi-info-circle fs-5"></i></div>'
        .'<div>'
        .'<div class="fw-semibold mb-1">Sesi berikutnya telah dimulai.</div>'
        .'<div class="small">Anda dapat mengisi jurnal baru untuk sesi berikutnya. Isian sebelumnya tetap tersimpan.</div>'
        .'</div>'
        .'</div>';
      // Lanjut render form input jurnal untuk entri berikutnya
    }
  }
  ?>

<!-- data asli upload materi -->	
	<div>
		<p class="text-absen mt-3">Silahkan isi jurnal pembelajaran untuk hari ini.</p>
  <form id="formJurnal" method="POST" action="/pages/guru/simpanmateri.php" enctype="multipart/form-data">
  <input type="hidden" name="tanggal" value="<?= $tglskr; ?>">
  <input type="hidden" name="nip" value="<?= $nipguru; ?>">
  <input type="hidden" name="idmapel" value="<?= $id; ?>">
  <input type="hidden" name="namamapel" value="<?= htmlspecialchars($dat['nama_mapel']); ?>">
  <input type="hidden" name="kelas" value="<?= htmlspecialchars($dat['kelas']); ?>">
	<!--<input type="file" name="file" id="file" class="mb-4" required> -->
		<div class="mb-4" >
		    
	            <label for="message-text" class="col-form-label" >Materi</label>
            <textarea type="text" name="materi" id="materi" class="form-control" required></textarea>
            
          </div>
        <div class="mb-3">
            <label for="message-text" class="col-form-label">Kegiatan</label>
            <textarea type="text" class="form-control" name="kegiatan" id="kegiatan" placeholder="Contoh: Diskusi, praktikum, evaluasi, dll." required></textarea>
          </div>  
       <div class="mb-3">
    <label for="absen" class="col-form-label d-block">Presensi Siswa</label>
    <div class="small text-muted mb-2">Legend: <span class="me-2">(H) Hadir</span><span class="me-2">(I) Ijin</span><span class="me-2">(S) Sakit</span><span class="me-2">(D) Dispen</span><span class="me-2">(A) Alpha</span></div>
    <?php
$kelas = $dat['kelas'] ?? '';
$tanggal = $tglskr;

// ambil semua siswa di kelas
$siswaQuery = null;
if ($kelas !== '') {
  // Gunakan query biasa untuk kompatibilitas hosting
  $kelas_escaped = mysqli_real_escape_string($conn, $kelas);
  $queryS = "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas = '$kelas_escaped' AND status='Aktif' ORDER BY nama_siswa ASC";
  $siswaQuery = mysqli_query($conn, $queryS);
}

if($siswaQuery && mysqli_num_rows($siswaQuery) > 0) {
  echo "<div class='table-responsive'>
      <table class='table table-hover align-middle table-sm mb-0'>
      <thead class='table-light'>
        <tr>
          <th>Nama Siswa</th>
          <th class='text-center' title='Hadir'>(H)</th>
          <th class='text-center' title='Ijin'>(I)</th>
          <th class='text-center' title='Sakit'>(S)</th>
          <th class='text-center' title='Dispen'>(D)</th>
          <th class='text-center' title='Alpha'>(A)</th>
          <th class='text-center'>Reset</th>
        </tr>
      </thead>
      <tbody>";
while($s = mysqli_fetch_assoc($siswaQuery)) {
    $no_induk = $s['no_induk'];

    echo "<tr>
            <td>".htmlspecialchars($s['nama_siswa'])."</td>
            <td class='text-center'><input class='form-check-input' type='radio' name='absen[".$no_induk."]' value='Hadir' aria-label='Hadir'></td>
            <td class='text-center'><input class='form-check-input' type='radio' name='absen[".$no_induk."]' value='Ijin' aria-label='Ijin'></td>
            <td class='text-center'><input class='form-check-input' type='radio' name='absen[".$no_induk."]' value='Sakit' aria-label='Sakit'></td>
            <td class='text-center'><input class='form-check-input' type='radio' name='absen[".$no_induk."]' value='Dispen' aria-label='Dispen'></td>
            <td class='text-center'><input class='form-check-input' type='radio' name='absen[".$no_induk."]' value='Alpha' aria-label='Alpha'></td>
            <td class='text-center'><button type='button' class='btn btn-sm btn-outline-secondary reset-absen' data-noinduk='".$no_induk."' title='Reset isian'><i class='bi bi-arrow-counterclockwise'></i></button></td>
          </tr>";

    }
    echo "</tbody></table></div>";
} else {
  echo "<div class='alert alert-warning d-flex align-items-center' role='alert'>
      <i class='bi bi-info-circle me-2'></i>
      <div>⚠ Tidak ada siswa di kelas ".htmlspecialchars($kelas ?: '-').". Anda tetap dapat menyimpan jurnal tanpa data presensi.</div>
      </div>";
}
?>
    </div>
  </div>
 
		<div class="mb-3">
            <label for="message-text" class="col-form-label">Catatan</label>
            <textarea class="form-control" name="keterangan" id="keterangan" placeholder="Opsional: catatan tambahan"></textarea>
          </div>
    <button type="submit" name="submit" id="btnSimpanJurnal" class="btn btn-primary w-100" onclick="document.getElementById('formJurnal').submit();">Simpan Jurnal</button>
		</form>
	</div>
	

	
<?php
}
?>

<!-- Script handling upload file & reset absen per siswa -->
<script type="text/javascript">
// Batasi ukuran file (jika field ada)
(function(){
  var uploadField = document.getElementById('file');
  if (uploadField) {
    uploadField.onchange = function() {
      if(this.files[0].size > 2000000) {
          alert('Ukuran file maksimal 2 MB!');
          this.value = '';
      } else if(this.files[0].type !== 'application/pdf') {
          alert('File yang diizinkan hanya bertipe PDF!');
          this.value= '';
      }
    };
  }
})();

// Reset absen per siswa
(function(){
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.reset-absen');
    if (!btn) return;
    var noinduk = btn.getAttribute('data-noinduk');
    if (!noinduk) return;
    var radios = document.querySelectorAll("input[type='radio'][name='absen["+noinduk+"]']");
    radios.forEach(function(r){ r.checked = false; });
  });
})();
</script>
<!-- End scripts -->

<!-- Ensure modal form submits reliably -->
<script>
(function(){
  var form = document.getElementById('formJurnal');
  if (form) {
    form.addEventListener('submit', function(e){
      var btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.setAttribute('disabled','disabled');
        btn.textContent = 'Menyimpan...';
      }
    });
  }
})();
</script>
