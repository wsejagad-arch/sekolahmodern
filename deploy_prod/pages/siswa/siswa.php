<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } 
if (!isset($_SESSION["no_induk"])) {
	header("location: ../../index.php?haruslogin");
	exit;
} else if($_SESSION['hak_akses'] != 3) { ?>
	<script>window.location='404.html';</script>
<?php
}

include "../../koneksi.php";
include "../../functions.php";
date_default_timezone_set('Asia/Jakarta');
$kls = $_SESSION['kelas'];
$tglskr = date("Y-m-d");
$hariini = ubah_nama_hari($tglskr);
$lembaga = data_lembaga();
$stat = "Aktif";
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="icon" href="../../img/logo-man-2.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/bootstrap.min.css" />
    <link rel="stylesheet" href="../../css/index.css" />
    <title>Laporan Siswa</title>
	<script src="../../vendor/jquery/jquery.min.js"></script>
  </head>
  <body>
    <main class="containner" id="dashboard">
      <div class="sidebar d-none d-md-none d-lg-block position-fixed">
        <div class="header p-4">
          <div class="list-item">
            <a href="#" class="p-3">
              <img src="../../img/<?= $lembaga['logo']; ?>" alt="" class="icon" />
              <span class="text-judul"><?= $lembaga['nmsekolah']; ?></span>
            </a>

            <div class="main">
              <ul class="list p-3">
                <li class="menu">Menu</li>
                <li class="menu-das">
                  <a href="#">
                    <img src="../../img/logo dash.png" alt="" class="icon2" />
                    <span class="text-das align-text-top">Dashboard</span>
                  </a>
                </li>
              </ul>
            </div>
          </div>
          <footer>
            <a href="../../logout.php" class="p-3" onclick="return confirm('Yakin mau logout?');">
              <img src="../../img/logout.png" alt="" class="icon-logout" />
              <span class="text-logout">logout</span>
            </a>
          </footer>
        </div>
      </div>

      <div class="main-content">
        <nav class="navbar navbar-light bg-white navbar-expand fixed-bottom d-lg-none p-0">
          <ul class="navbar-nav nav-justified w-100">
            <li class="nav-item">
              <a href="#" class="nav-link text-center">
                <img src="../../img/das-nav.svg" alt="" class="" />
                <span class="small d-block">Dashboard</span>
              </a>
            </li>

            <li class="nav-item dropup">
              <a href="../../logout.php" class="nav-link text-center" role="button" id="dropdownMenuProfile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <img src="../../img/log-nav.svg" alt="" class="" />
                <span class="small d-block">Logout</span>
              </a>
            </li>
          </ul>
        </nav>

        <!-- end -->
        <div class="container-fluid d-flex justify-content-between mt-2">
          <span class="d-lg-none img-tab p-3">
            <img src="../../img/logo-man-2.png" alt="" />
          </span>
          <p class="navbar-brand-title p-4 pt-4 text-decoration-none d-none d-sm-none d-lg-block">Dashboard</p>
          <a class="navbar-brand p-4 pt-4 d-flex" href="#">
            <img src="../../img/foto-profil.png" alt="" class="d-inline-block img-profile"/>
            <p class="text-profile align-text-top d-none d-sm-none d-md-block">Hai, <?= $_SESSION["nama_siswa"]; ?></p>
          </a>
        </div>
        <hr class="mt-0 mx-5" />

        <section id="Jadwal">
          <div class="container">
            <div class="row">
              <div class="col-12">
			  
			  <?php
				if (isset($_GET["sukses"])) { 
                    echo '<div class="alert alert-success" id="error-alert">
						Laporan terkirim
                    </div>';
                } else if (isset($_GET["gagal"])) { 
                    echo '<div class="alert alert-danger" id="error-alert">
                    <strong>Gagal!</strong> mengirim laporan.
                    </div>';
                }
			  ?>
			  
                <h4 class="title-card">Jadwal hari ini: </h4>
				<h4 class="title-card text-success"><?= $hariini; ?>, <?= tgl_indo($tglskr); ?></h4>
              </div>
            </div>
            <div class="row mb-5">
			
			<?php
				// tampilkan data jadwal dan data siswa
				$sql = mysqli_query($conn, "SELECT * FROM tbl_mapel_ampu m JOIN tbl_guru g ON m.no_induk = g.no_induk WHERE m.kelas='$kls' AND m.hari='$hariini' AND g.status='$stat' ORDER BY m.jam_mulai ASC");
				$cekmapel = mysqli_num_rows($sql);
				if($cekmapel > 0) {
					while ($data = mysqli_fetch_array($sql)) { 
					$nip = $data['no_induk'];
					$idmapel = $data['id_mapel'];
				?>
				
				<div class="col-md-6 col-lg-5 col-xl-4 m-0"> <!-- ini awal div col -->
                <div class="card-jadwal"> <!-- ini card jadwal -->
                  <div class="card-content mx-auto">
                    <div class="d-flex card-profile" href="#">
					<?php
						if($data['foto'] == "") { ?>
						<img src="../../img/no-photo.png" alt="<?= $data['nama_guru']; ?>" class="d-inline-block img-profile card-image rounded-circle"/>
					<?php
						} else { ?>
						<img src="../../foto/<?= $data['foto']; ?>" alt="<?= $data['nama_guru']; ?>" class="d-inline-block img-profile card-image rounded-circle"/>
					<?php	
						}
					?>
                      <p class="pt-1"><?= $data['nama_guru']; ?></p>
                    </div>

                    <p class="card-matkul my-2"><?= $data['nama_mapel']; ?></p>

                    <div class="card-unduh my-2 pt-2">
                      <p class="p"><?= $data['jam_mulai']; ?> WIB s.d <?= $data['jam_selesai']; ?> WIB</p>
                      
					 
				
					  
                    </div>
                  </div>

                  <!-- modal -->
                  <!-- Button trigger modal -->
				  <?php
					$absen = cek_kehadiran($tglskr, $kls, $nip);
					if($absen == "true") { ?>
					<button type="button" class="btn btn-lapor w-100" disabled>Laporan terkirim</button>
					<?php 	
					} else { ?>
					<button type="button" class="btn btn-lapor w-100" data-bs-toggle="modal" data-bs-target="#show" data-id="<?= $data['id_mapel']; ?>">Laporan</button>
					<?php	
					}
				  ?>    
                </div> <!-- ini batas penutup card jadwal -->
              </div> <!-- ini penutup div col -->
				
			<?php 	
				}
			
			} else { ?>
				<div class="alert alert-danger mt-4">
					Hai <strong><?= $_SESSION['nama_siswa']; ?></strong>, belum ada jadwal untuk kelas <?= $kls; ?> hari ini. 
				</div>
			<?php	
			}
			?>	
			
            </div> <!-- div penutup row -->
          </div>
        </section>
      </div>
    </main>
	
	
	<!-- Modal -->
	  <div class="modal fade" id="show" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
		<div class="modal-dialog">
		  <div class="modal-content p-3">
			<div class="modal-header">
			  <span class="modal-title" id="">Laporan Ketua kelas</span>
			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>

			<div class="modal-body">
			  <div class="modal-data">
				
			  </div>
		  </div>
		</div>
	  </div>
	  <!-- end of modal -->

<!-- Script mengambil data modal dinamis -->	
<script type="text/javascript">
    $(document).ready(function(){
        $('#show').on('show.bs.modal', function (e) {
            var getDetail = $(e.relatedTarget).data('id');
            /* fungsi AJAX untuk melakukan fetch data */
            $.ajax({
                type : 'post',
                url : 'detail.php',
                /* detail per identifier ditampung pada berkas detail.php */
                data :  'getDetail='+ getDetail,
                /* memanggil fungsi getDetail dan mengirimkannya */
                success : function(data){
                $('.modal-data').html(data);
                /* menampilkan data dalam bentuk dokumen HTML */
                }
            });
         });
    });
  </script>
<!-- end of script modal dinamis -->

<!-- Alert jquery -->
  <script>
  $("#error-alert").fadeTo(4000, 500).slideUp(500, function(){
    $("#error-alert").slideUp(500);
});
</script>
  <!-- End Alert Jquery -->
  <!-- jQuery (wajib untuk Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#absen').select2({
        placeholder: "Pilih siswa hadir",
        allowClear: true,
        width: '100%'
    });
});
</script>
</body>

  </body>
  <script src="../../vendor/jquery/jquery.min.js"></script>
  <script src="../../js/bootstrap.bundle.js"></script>
</html>
