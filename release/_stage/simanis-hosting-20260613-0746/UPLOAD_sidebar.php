<!-- Sidebar --> 
<ul class="navbar-nav backgroundna sidebar sidebar-dark accordion" id="accordionSidebar">

<!-- Sidebar - Brand -->
<a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
  <div class="sidebar-brand-icon rotate-n-15">
    <i class="fas fa-user-circle"></i>
  </div>
  <div class="sidebar-brand-text mx-3">Ruang Admin</div>
</a>

<!-- Divider -->
<hr class="sidebar-divider my-0">

<!-- Nav Item - Dashboard -->
<li class="nav-item active">
  <a class="nav-link" href="home.php">
    <i class="fas fa-fw fa-tachometer-alt"></i>
    <span>Dashboard</span></a>
</li>

<!-- Divider -->
<hr class="sidebar-divider">

<!-- Heading -->
<div class="sidebar-heading">
  Data Guru dan Siswa
</div>

<!-- Nav Item - Pages Collapse Menu Data Guru -->
<li class="nav-item">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
    <i class="fas fa-fw fa-table"></i>
    <span>Data Guru</span>
  </a>
  <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <h6 class="collapse-header">Rincian:</h6>
      <a class="collapse-item" href="home.php?page=data-guru">Lihat Data Guru</a>
      <a class="collapse-item" href="home.php?page=tambah-guru">Tambah Data Guru</a>
      <a class="collapse-item" href="home.php?page=import-guru"><i class="fas fa-file-excel text-success"></i> Import dari Excel</a>
    </div>
  </div>
</li>

<!-- Nav Item - Pages Collapse Menu Data Siswa -->
<li class="nav-item">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSiswa" aria-expanded="false" aria-controls="collapseSiswa">
    <i class="fas fa-fw fa-table"></i>
    <span>Data Siswa</span>
  </a>
  <div id="collapseSiswa" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <h6 class="collapse-header">Rincian:</h6>
      <a class="collapse-item" href="home.php?page=data-siswa">Lihat Data Siswa</a>
      <a class="collapse-item" href="home.php?page=tambah-siswa">Tambah Siswa</a>
      <a class="collapse-item" href="home.php?page=input-kelas">Input Kelas</a>
      <a class="collapse-item" href="home.php?page=hapus-kelas-2">🗑️ Hapus Kelas 2</a>
    </div>
  </div>
</li>
<?php if(isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] == 1): ?>
<!-- Nav Item - Wali Kelas under Data Guru dan Siswa -->
<li class="nav-item">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseWaliKelas" aria-expanded="false" aria-controls="collapseWaliKelas">
    <i class="fas fa-fw fa-user-tie"></i>
    <span>Wali Kelas</span>
  </a>
  <div id="collapseWaliKelas" class="collapse" aria-labelledby="headingWaliKelas" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <h6 class="collapse-header">Kelola:</h6>
      <a class="collapse-item" href="home.php?page=kelola-wali-kelas">Kelola Wali Kelas</a>
      <a class="collapse-item" href="home.php?page=data-wali-kelas">Data Wali Kelas</a>
    </div>
  </div>
</li>
<?php endif; ?>
<!-- Nav Item - Pages Collapse Menu Monitoring -->
<li class="nav-item">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMonitoring" aria-expanded="false" aria-controls="collapseMonitoring">
    <i class="fas fa-fw fa-table"></i>
    <span>Monitoring</span>
  </a>
  <div id="collapseMonitoring" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <h6 class="collapse-header">Cek:</h6>
       <a class="collapse-item" href="home.php?page=jurnal">Jurnal Guru</a>
       <a class="collapse-item" href="home.php?page=kelas">Jurnal Kelas</a>
        <a class="collapse-item" href="home.php?page=monitoring-guru">Monitoring Guru</a>
      <a class="collapse-item" href="home.php?page=monitoring">Kehadiran Guru</a>
      <a class="collapse-item" href="home.php?page=cek-nilai">Cek Nilai</a>
      <a class="collapse-item" href="home.php?page=cetak-kehadiran-guru">Cetak Laporan</a>
    </div>
  </div>
</li>

<!-- Divider -->
<hr class="sidebar-divider">

<!-- Heading -->
<div class="sidebar-heading">
  Manajemen
</div>

<!-- Nav Item - Admin -->
<li class="nav-item">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
    <i class="fas fa-fw fa-address-book"></i>
    <span>Admin</span>
  </a>
  <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <h6 class="collapse-header">Data Admin:</h6>
      <a class="collapse-item" href="home.php?page=lihatuser">Tampilkan Admin</a>
      <a class="collapse-item" href="home.php?page=tambahuser">Tambah Admin</a>
    </div>
  </div>
</li>

<!-- Nav Item - Mata Pelajaran -->
<li class="nav-item">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePagesMapel" aria-expanded="false" aria-controls="collapsePagesMapel">
    <i class="fas fa-fw fa-calendar"></i>
    <span>Mata Pelajaran</span>
  </a>
  <div id="collapsePagesMapel" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <h6 class="collapse-header">Mapel:</h6>
      <a class="collapse-item" href="home.php?page=tambah-data-mapel">Tambah Mapel</a>
    </div>
  </div>
</li>

<!-- Nav Item - Tahun Ajaran -->
<li class="nav-item">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePagesTA" aria-expanded="false" aria-controls="collapsePagesTA">
    <i class="fas fa-fw fa-calendar"></i>
    <span>Tahun Ajaran</span>
  </a>
  <div id="collapsePagesTA" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <h6 class="collapse-header">TA:</h6>
      <a class="collapse-item" href="home.php?page=tambah-tahun-ajaran">Tambah Tahun Ajaran</a>
    </div>
  </div>
</li>

<!-- Nav Item - Log Sistem -->
<li class="nav-item">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLog" aria-expanded="false" aria-controls="collapseLog">
    <i class="fas fa-fw fa-calendar"></i>
    <span>Log Sistem</span>
  </a>
  <div id="collapseLog" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <h6 class="collapse-header">Data Log Sistem:</h6>
      <a class="collapse-item" href="home.php?page=lihat-log">Lihat Log Sistem</a>
      <a class="collapse-item" href="home.php?page=cetak-log">Cetak Laporan Log</a>
    </div>
  </div>
</li>

<!-- Nav Item - Setting -->
<li class="nav-item">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSetting" aria-expanded="false" aria-controls="collapseSetting">
    <i class="fas fa-fw fa-calendar"></i>
    <span>Setting</span>
  </a>
  <div id="collapseSetting" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <h6 class="collapse-header">Setting</h6>
      <a class="collapse-item" href="home.php?page=setting">Data Sekolah</a>
      <a class="collapse-item" href="home.php?page=presensi-settings">Pengaturan Presensi</a>
    </div>
  </div>
</li>

<!-- Nav Item - Laporan -->
<li class="nav-item">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLaporan" aria-expanded="false" aria-controls="collapseLaporan">
    <i class="fas fa-fw fa-file-alt"></i>
    <span>Laporan</span>
  </a>
  <div id="collapseLaporan" class="collapse" aria-labelledby="headingLaporan" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <h6 class="collapse-header">Laporan:</h6>
      <a class="collapse-item" href="home.php?page=buat-laporan">Input Laporan</a>
      <a class="collapse-item" href="home.php?page=cetak-jurnal-guru">📊 Cetak Jurnal Guru</a>
    </div>
  </div>
</li>

<?php if(isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] == 1): ?>

<?php
  // Hitung jumlah pengumuman aktif
  $unreadBadge = 0;
  if(isset($conn)) {
      @$result = mysqli_query($conn, "SELECT COUNT(*) as jml FROM pengumuman WHERE status = 'aktif'");
      if($result) {
          @$row = mysqli_fetch_assoc($result);
          if($row) {
              $unreadBadge = (int)$row['jml'];
          }
      }
  }
?>

<!-- Nav Item - Pengumuman (direct link) -->
<li class="nav-item">
  <a class="nav-link" href="home.php?page=pengumuman">
    <i class="fas fa-fw fa-bullhorn"></i>
    <span>Pengumuman<?php if($unreadBadge>0): ?> <span class="badge badge-success badge-counter" style="font-size:10px; margin-left:6px;"><?= $unreadBadge; ?></span><?php endif; ?></span>
  </a>
</li>

<!-- Nav Item - Quotes daftar -->
<li class="nav-item">
  <a class="nav-link" href="home.php?page=kelola-quotes">
    <i class="fas fa-fw fa-quote-left"></i>
    <span>Kelola Quotes</span>
  </a>
</li>

<!-- Nav Item - Quotes tambah -->
<li class="nav-item">
  <a class="nav-link" href="home.php?page=tambah-quotes">
    <i class="fas fa-fw fa-plus-circle"></i>
    <span>Tambah Quotes</span>
  </a>
</li>

<?php endif; ?>

<!-- Divider -->
<hr class="sidebar-divider d-none d-md-block">

<!-- Sidebar Toggler (Sidebar) -->
<div class="text-center d-none d-md-inline">
  <button class="rounded-circle border-0" id="sidebarToggle"></button>
</div>

</ul>
<!-- End of Sidebar -->
