<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); } 
if (!isset($_SESSION["no_induk"])) {
	header("location: ../../index.php?haruslogin");
	exit;
} else if($_SESSION['hak_akses'] != 2) {
	echo "<script>window.location='404.html';</script>";
	exit;
}

include "../../koneksi.php";
include "../../functions.php";
date_default_timezone_set('Asia/Jakarta');
$nipguru = $_SESSION['no_induk'];
$tglskr = date("Y-m-d");
$hariini = ubah_nama_hari($tglskr);
$lembaga = data_lembaga();
$sqlguru = mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$nipguru'");
$dataguru = mysqli_fetch_array($sqlguru);
// Ambil semua jadwal hari ini (untuk grid actions)
$jadwalHariIni = [];
$qJ = mysqli_query($conn, "SELECT m.id_mapel, m.kelas, m.nama_mapel, m.jam_mulai, m.jam_selesai, g.foto FROM tbl_mapel_ampu m JOIN tbl_guru g ON m.no_induk=g.no_induk WHERE m.no_induk='".$nipguru."' AND m.hari='".$hariini."' ORDER BY m.jam_mulai ASC");
while ($rowJ = mysqli_fetch_assoc($qJ)) { $jadwalHariIni[] = $rowJ; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Guru</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    body {
      background-color: #f8f9fa;
      padding-bottom: 70px;
    }
    .img-profile {
      width: 40px;
      height: 40px;
      object-fit: cover;
      border-radius: 50%;
    }
    .card-jadwal {
      border: none;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      padding: 1rem;
      background: #fff;
      margin-bottom: 1rem;
    }
    .footer-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #fff;
      border-top: 1px solid #ddd;
      display: flex;
      justify-content: space-around;
      padding: .5rem 0;
      z-index: 1050;
    }
    .footer-nav a {
      color: #6c757d;
      font-size: 0.9rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
    }
    .footer-nav a.active {
      color: #0d6efd;
    }
    .header-custom {
      background: linear-gradient(135deg, #0d6efd, #6610f2);
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 0 0 20px 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 1020;
    }
    .header-custom img {
      background: #fff;
      padding: 4px;
      border-radius: 50%;
      margin-right: 10px;
    }
    /* Quick Actions Grid */
    .quick-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 12px;
    }
    .quick-card {
      border: none;
      border-radius: 16px;
      padding: 16px;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.08);
      cursor: pointer;
      transition: transform .15s ease, box-shadow .15s ease;
      min-height: 76px;
    }
    .quick-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,0.12); }
    .qc-icon { font-size: 1.6rem; width: 42px; height: 42px; display:flex; align-items:center; justify-content:center; background: rgba(255,255,255,0.18); border-radius: 12px; }
    .qc-title { font-weight: 600; margin: 0; line-height: 1.2; }
    .qc-sub { font-size: .8rem; opacity: .9; margin: 2px 0 0; }
    .bg-grad-primary { background: linear-gradient(135deg,#0d6efd,#6610f2); }
    .bg-grad-success { background: linear-gradient(135deg,#20c997,#0ea5e9); }
    .bg-grad-warning { background: linear-gradient(135deg,#f59e0b,#ef4444); }
    .bg-grad-info { background: linear-gradient(135deg,#06b6d4,#3b82f6); }
    .bg-grad-secondary { background: linear-gradient(135deg,#64748b,#334155); }
    .bg-grad-pink { background: linear-gradient(135deg,#ec4899,#8b5cf6); }
    @media (min-width: 992px) {
      .quick-grid { grid-template-columns: repeat(3,1fr); }
    }
  </style>
</head>
<body>
<div class="container-fluid p-0">
  <div class="header-custom d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center">
      <img src="../../img/<?= $lembaga['logo']; ?>" width="50" alt="Logo">
      <div>
        <h6 class="mb-0 fw-bold"><?= $lembaga['nmsekolah']; ?></h6>
        <small><?= $lembaga['alamat']; ?></small>
      </div>
    </div>
    <div class="d-flex align-items-center">
      <?php if(empty($dataguru['foto'])) { ?>
        <img src="../../img/no-photo.png" alt="" class="img-profile">
      <?php } else { ?>
        <img src="../../foto/<?= $dataguru['foto']; ?>" alt="" class="img-profile">
      <?php } ?>
      <span class="ms-2">Hai, <?= htmlspecialchars($dataguru['nama_guru'] ?? ($_SESSION["nama_guru"] ?? 'Guru')); ?></span>
    </div>
  </div>

  <div class="container px-3">
    <?php
      if (isset($_GET["sukses"])) {
        echo '<div class="alert alert-success">Berhasil mengirim jurnal pembelajaran</div>';
      } else if (isset($_GET["gagal"])) {
        echo '<div class="alert alert-danger"><strong>Gagal!</strong> mengirim jurnal.</div>';
      } else if (isset($_GET["hapusmateri"])) {
        echo '<div class="alert alert-success"><strong>Berhasil!</strong> menghapus jurnal.</div>';
      } else if (isset($_GET["gagalhapusmateri"])) {
        echo '<div class="alert alert-danger"><strong>Gagal!</strong> menghapus jurnal.</div>';
      }
    ?>
    <!-- Quick Actions Grid -->
    <div class="mb-4">
      <div class="quick-grid">
        <div class="quick-card bg-grad-primary" id="qaInputJurnal" role="button" tabindex="0">
          <div class="qc-icon"><i class="bi bi-journal-text"></i></div>
          <div>
            <p class="qc-title">Input Jurnal</p>
            <p class="qc-sub">Isikan jurnal hari ini</p>
          </div>
        </div>
        <div class="quick-card bg-grad-warning" id="qaCetakJurnal" role="button" tabindex="0">
          <div class="qc-icon"><i class="bi bi-printer"></i></div>
          <div>
            <p class="qc-title">Cetak Jurnal</p>
            <p class="qc-sub">Lihat & cetak jurnal</p>
          </div>
        </div>
        <div class="quick-card bg-grad-success" id="qaInputNilai" role="button" tabindex="0">
          <div class="qc-icon"><i class="bi bi-pencil-square"></i></div>
          <div>
            <p class="qc-title">Input Nilai</p>
            <p class="qc-sub">Penilaian per pertemuan</p>
          </div>
        </div>
        <div class="quick-card bg-grad-info" id="qaDaftarNilai" role="button" tabindex="0">
          <div class="qc-icon"><i class="bi bi-bar-chart"></i></div>
          <div>
            <p class="qc-title">Daftar Nilai</p>
            <p class="qc-sub">Rekap nilai siswa</p>
          </div>
        </div>
        <div class="quick-card bg-grad-secondary" id="qaDaftarPresensi" role="button" tabindex="0">
          <div class="qc-icon"><i class="bi bi-people-check"></i></div>
          <div>
            <p class="qc-title">Daftar Presensi</p>
            <p class="qc-sub">Absensi siswa</p>
          </div>
        </div>
        <div class="quick-card bg-grad-pink" id="qaLaporanWali" role="button" tabindex="0">
          <div class="qc-icon"><i class="bi bi-file-earmark-text"></i></div>
          <div>
            <p class="qc-title">Laporan Wali Kelas</p>
            <p class="qc-sub">Cetak laporan kelas</p>
          </div>
        </div>
      </div>
    </div>

    <h5>Jadwal Hari Ini:</h5>
    <p class="text-primary"><?= $hariini; ?>, <?= tgl_indo($tglskr); ?></p>

  <div class="row" id="rowJadwal">
      <?php
      $sql = mysqli_query($conn, "SELECT * FROM tbl_mapel_ampu m JOIN tbl_guru g ON m.no_induk = g.no_induk WHERE m.no_induk='$nipguru' AND m.hari='$hariini' ORDER BY m.jam_mulai ASC");
      $cekmapel = mysqli_num_rows($sql);
      if ($cekmapel > 0) {
        while ($data = mysqli_fetch_array($sql)) {
          $idmapel = $data['id_mapel'];
      ?>
        <div class="col-12">
          <div class="card-jadwal" data-mulai="<?= htmlspecialchars($data['jam_mulai']); ?>" data-selesai="<?= htmlspecialchars($data['jam_selesai']); ?>" style="display:none;">
            <div class="d-flex align-items-center mb-2">
              <?php if($data['foto'] == "") { ?>
                <img src="../../img/no-photo.png" alt="" class="img-profile me-2">
              <?php } else { ?>
                <img src="../../foto/<?= $data['foto']; ?>" alt="" class="img-profile me-2">
              <?php } ?>
              <strong>Kelas <?= $data['kelas']; ?></strong>
            </div>
            <h6><?= $data['nama_mapel']; ?></h6>
            <p class="text-muted small"><?= $data['jam_mulai']; ?> - <?= $data['jam_selesai']; ?> WIB</p>

            <?php
            $mat = mysqli_query($conn, "SELECT * FROM tbl_materi WHERE id_mapel='$idmapel' AND `tanggal`='$tglskr'");
            if (mysqli_num_rows($mat) < 1) {
              echo '<p class="text-danger small">Belum ada file materi!</p>';
            } else {
              while ($dmat = mysqli_fetch_array($mat)) {
            ?>
              <div class="d-flex justify-content-between align-items-center">
                <a href="../../materi/<?= $dmat['file_materi']; ?>" class="text-decoration-none" target="_blank">
                  <i class="bi bi-file-earmark-pdf-fill text-danger"></i> <?= $dmat['file_materi']; ?>
                </a>
                <a href="delete-materi.php?id=<?= $dmat['id_materi']; ?>&file=<?= $dmat['file_materi']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin mau menghapus file jurnal ini?');">sudah diisi!! / Hapus</a>
              </div>
            <?php } } ?>

            <button class="btn btn-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#show" data-id="<?= $data['id_mapel']; ?>">Isi Jurnal</button>
            <button class="btn btn-outline-secondary w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalNilai" data-id="<?= $data['id_mapel']; ?>">Input Nilai</button>
          </div>
        </div>
      <?php } } else { ?>
        <div class="alert alert-warning">Belum ada jadwal untuk hari ini.</div>
      <?php } ?>
    </div>
  </div>
</div>

<!-- Modal Pilih Jadwal (untuk Input Jurnal/Nilai) -->
<div class="modal fade" id="selectJadwalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pilih Jadwal Hari Ini</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if (count($jadwalHariIni) === 0) { ?>
          <div class="alert alert-warning mb-0">Tidak ada jadwal untuk hari ini.</div>
        <?php } else { ?>
          <div class="list-group">
            <?php foreach ($jadwalHariIni as $j) { ?>
              <div class="list-group-item d-flex align-items-center justify-content-between">
                <div>
                  <div class="fw-semibold">Kelas <?= htmlspecialchars($j['kelas']); ?> • <?= htmlspecialchars($j['nama_mapel']); ?></div>
                  <div class="text-muted small"><?= htmlspecialchars($j['jam_mulai']); ?> - <?= htmlspecialchars($j['jam_selesai']); ?> WIB</div>
                </div>
                <div class="btn-group" role="group">
                  <button class="btn btn-sm btn-primary btn-pilih-jurnal" data-id="<?= (int)$j['id_mapel']; ?>">Input Jurnal</button>
                  <button class="btn btn-sm btn-outline-primary btn-pilih-nilai" data-id="<?= (int)$j['id_mapel']; ?>">Input Nilai</button>
                </div>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
  </div>

<!-- Modal Isi Jurnal -->
<div class="modal fade" id="show" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Isi Jurnal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="modal-data"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Input Nilai -->
<div class="modal fade" id="modalNilai" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Input Nilai</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="modal-nilai-body"></div>
      </div>
    </div>
  </div>
</div>
<!-- Modal Cetak Jurnal -->
<div class="modal fade" id="modalCetak" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Cetak Jurnal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <iframe src="" id="frameCetak" frameborder="0" style="width: 100%; height: 80vh;"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- Footer Navigation -->
<div class="footer-nav">
  <a href="#" class="active">
    <i class="bi bi-house-door-fill"></i>
    <small>Home</small>
  </a>
  <!-- Menu Detail Jadwal -->
  <a href="detail-jadwal.php?id=<?= htmlspecialchars($dataguru['id_guru'] ?? ''); ?>&no_induk=<?= htmlspecialchars($dataguru['no_induk'] ?? $nipguru); ?>">
    <i class="bi bi-calendar-check"></i>
    <small>Detail Jadwal</small>
  </a>
  <a href="../../logout.php" onclick="return confirm('Yakin mau logout?');">
    <i class="bi bi-box-arrow-right"></i>
    <small>Logout</small>
  </a>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
  // Embed jadwal for quick decision
  window.JADWAL_TODAY = <?= json_encode(array_map(function($x){return [
    'id_mapel'=>(int)$x['id_mapel'],
    'kelas'=>$x['kelas'],
    'nama_mapel'=>$x['nama_mapel'],
    'jam_mulai'=>$x['jam_mulai'],
    'jam_selesai'=>$x['jam_selesai']
  ];}, $jadwalHariIni)); ?>;

  function openInputJurnal(idmapel){
    if (!idmapel) return;
    $('.modal-data').html('<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>Memuat...</div>');
    var el = document.getElementById('show');
    var m = new bootstrap.Modal(el);
    m.show();
    $.post('detailmateri.php', { getDetail: idmapel }, function(data){
      $('.modal-data').html(data);
    }).fail(function(){
      $('.modal-data').html('<div class="alert alert-danger">Gagal memuat form jurnal.</div>');
    });
  }
  function openInputNilai(idmapel){
    if (!idmapel) return;
    $('.modal-nilai-body').html('<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>Memuat...</div>');
    var el = document.getElementById('modalNilai');
    var m = new bootstrap.Modal(el);
    m.show();
    $.post('inputnilai.php', { getDetail: idmapel }, function(data){
      $('.modal-nilai-body').html(data);
    }).fail(function(){
      $('.modal-nilai-body').html('<div class="alert alert-danger">Gagal memuat form nilai.</div>');
    });
  }

  // Quick Actions handlers
  $('#qaInputJurnal').on('click keypress', function(e){
    if (e.type==='click' || e.key==='Enter'){
      if (window.JADWAL_TODAY.length === 1) {
        openInputJurnal(window.JADWAL_TODAY[0].id_mapel);
      } else {
        var sm = new bootstrap.Modal(document.getElementById('selectJadwalModal'));
        sm.show();
      }
    }
  });
  $('#qaCetakJurnal').on('click keypress', function(e){
    if (e.type==='click' || e.key==='Enter'){
      $('#frameCetak').attr('src', 'cetak_jurnal.php');
      var m = new bootstrap.Modal(document.getElementById('modalCetak'));
      m.show();
    }
  });
  $('#qaInputNilai').on('click keypress', function(e){
    if (e.type==='click' || e.key==='Enter'){
      if (window.JADWAL_TODAY.length === 1) {
        openInputNilai(window.JADWAL_TODAY[0].id_mapel);
      } else {
        var sm = new bootstrap.Modal(document.getElementById('selectJadwalModal'));
        sm.show();
      }
    }
  });
  $('#qaDaftarNilai').on('click keypress', function(e){ if (e.type==='click' || e.key==='Enter'){ window.location = 'nilai.php'; }});
  $('#qaDaftarPresensi').on('click keypress', function(e){ if (e.type==='click' || e.key==='Enter'){ window.location = 'presensi.php'; }});
  $('#qaLaporanWali').on('click keypress', function(e){ if (e.type==='click' || e.key==='Enter'){ window.location = '../../kelas-cetak.php'; }});

  // Button in select modal
  $(document).on('click', '.btn-pilih-jurnal', function(){
    var id = $(this).data('id');
    openInputJurnal(id);
    $('#selectJadwalModal').modal('hide');
  });
  $(document).on('click', '.btn-pilih-nilai', function(){
    var id = $(this).data('id');
    openInputNilai(id);
    $('#selectJadwalModal').modal('hide');
  });

  $('#show').on('show.bs.modal', function (e) {
    // Jika modal dibuka secara programatik (tanpa relatedTarget) atau tanpa data-id, jangan auto-load lagi
    if (!e.relatedTarget || !$(e.relatedTarget).data('id')) { return; }
    var getDetail = $(e.relatedTarget).data('id');
    $.ajax({
      type : 'post',
      url : 'detailmateri.php',
      data :  'getDetail='+ getDetail,
      success : function(data){
        $('.modal-data').html(data);
      }
    });
  });

  $('#modalNilai').on('show.bs.modal', function (e) {
    // Hindari double-load jika dibuka programatik
    if (!e.relatedTarget || !$(e.relatedTarget).data('id')) { return; }
    var getDetail = $(e.relatedTarget).data('id');
    $.ajax({
      type : 'post',
      url : 'inputnilai.php',
      data :  'getDetail='+ getDetail,
      success : function(data){
        $('.modal-nilai-body').html(data);
      }
    });
  });

  // Toggle visibilitas jadwal: tampil saat waktu mulai tercapai, lalu tetap tampil hingga pergantian hari
  function parseHM(str){
    if(!str) return null;
    str = String(str);
    var m = str.match(/(\d{1,2})\D(\d{1,2})/);
    if (m) {
      var h = parseInt(m[1],10), mi = parseInt(m[2],10);
      if (!isNaN(h) && !isNaN(mi)) return h*60 + mi;
    }
    var m2 = str.match(/(\d{1,2})/g);
    if (m2 && m2.length>=2) {
      var h2 = parseInt(m2[0],10), mi2 = parseInt(m2[1],10);
      if (!isNaN(h2) && !isNaN(mi2)) return h2*60 + mi2;
    }
    return null;
  }
  function updateJadwalVisibility(){
    var now = new Date();
    var minutesNow = now.getHours()*60 + now.getMinutes();
    document.querySelectorAll('.card-jadwal').forEach(function(card){
      var mulai = parseHM(card.getAttribute('data-mulai'));
      if (mulai === null) { card.style.display = ''; return; }
      if (minutesNow >= mulai) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
  }
  updateJadwalVisibility();
  setInterval(updateJadwalVisibility, 30000); // cek tiap 30 detik
});
</script>
</body>
</html>

