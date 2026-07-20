<!-- Sidebar -->
<?php if ((int)($_SESSION['hak_akses'] ?? 0) !== 2): ?>
<ul class="navbar-nav backgroundna sidebar sidebar-dark accordion" id="accordionSidebar">

  <!-- Sidebar - Brand -->
  <a class="sidebar-brand d-flex align-items-center" href="home.php" style="text-decoration:none; padding: 16px 14px !important;">
    <div class="sidebar-brand-icon" style="width:40px; height:40px; min-width:40px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; background:var(--school-secondary); box-shadow:0 3px 10px rgba(240,180,41,0.35);">
      <?php if (isset($lembaga) && !empty($lembaga['logo'])): ?>
        <img src="<?= asset_url('img/' . htmlspecialchars($lembaga['logo'])); ?>" alt="Logo" width="28" height="28" style="width:28px; height:28px; object-fit:contain; display:block;">
      <?php else: ?>
        <i class="fas fa-graduation-cap" style="font-size:18px; color:#1a3c6e;"></i>
      <?php endif; ?>
    </div>
    <div class="sidebar-brand-text mx-2" style="overflow:hidden; min-width:0;">
      <div style="font-size:12px; font-weight:800; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; letter-spacing:0.3px;">
        <?= isset($lembaga) && !empty($lembaga['nmsekolah']) ? htmlspecialchars(mb_strtoupper($lembaga['nmsekolah'])) : htmlspecialchars(mb_strtoupper($lembaga['nama_aplikasi'] ?? 'SISTEM JURNAL')); ?>
      </div>
      <div style="font-size:10px; font-weight:400; color:rgba(255,255,255,0.65); letter-spacing:0.4px; margin-top:1px;">Sistem Manajemen Sekolah</div>
    </div>
  </a>

  <!-- Divider -->
  <hr class="sidebar-divider my-0">

  <!-- Nav Item - Dashboard -->
  <li class="nav-item active">
    <a class="nav-link" href="home.php">
      <i class="fas fa-fw fa-tachometer-alt"></i>
      <span>Dashboard</span></a>
  </li>

  <?php if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] == 2): ?>
  <!-- Nav Item - Lentera Guru (hanya untuk pembina literasi) -->
  <?php
    $sidebarNip = $_SESSION['no_induk'] ?? '';
    $sidebarNipEsc = mysqli_real_escape_string($conn, $sidebarNip);
    $sidebarIdSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
    $qSidebarLiterasi = @mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_literasi_ampuh WHERE no_induk_guru='$sidebarNipEsc' AND id_sekolah=$sidebarIdSekolah");
    $isSidebarPembina = false;
    if ($qSidebarLiterasi) {
        $rowSidebar = mysqli_fetch_assoc($qSidebarLiterasi);
        $isSidebarPembina = (int)($rowSidebar['total'] ?? 0) > 0;
    }
    if ($isSidebarPembina):
  ?>
  <li class="nav-item">
    <a class="nav-link" href="pages/guru/literasi.php">
      <i class="fas fa-fw fa-book-reader"></i>
      <span>LENTERA Literasi</span>
    </a>
  </li>
  <?php endif; ?>
  <?php endif; ?>

  <!-- Divider -->
  <hr class="sidebar-divider">

  <!-- Heading -->
  <div class="sidebar-heading">
    <i class="fas fa-users mr-1" style="font-size:9px;"></i> Data Guru &amp; Siswa
  </div>

  <!-- Nav Item - Pages Collapse Menu Data Guru -->
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
      <i class="fas fa-fw fa-chalkboard-teacher"></i>
      <span>Data Guru</span>
    </a>
    <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Rincian:</h6>
        <a class="collapse-item" href="home.php?page=data-guru"><i class="fas fa-list text-info mr-1" style="font-size:11px"></i>Lihat Data Guru</a>
        <a class="collapse-item" href="home.php?page=tambah-guru"><i class="fas fa-user-plus text-primary mr-1" style="font-size:11px"></i>Tambah Data Guru</a>
        <a class="collapse-item" href="home.php?page=import-guru"><i class="fas fa-file-excel text-success mr-1" style="font-size:11px"></i>Import dari Excel</a>
      </div>
    </div>
  </li>

  <!-- Nav Item - Pages Collapse Menu Data Siswa -->
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSiswa" aria-expanded="false" aria-controls="collapseSiswa">
      <i class="fas fa-fw fa-user-graduate"></i>
      <span>Data Siswa</span>
    </a>
    <div id="collapseSiswa" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Rincian:</h6>
        <a class="collapse-item" href="home.php?page=data-siswa"><i class="fas fa-list text-info mr-1" style="font-size:11px"></i>Lihat Data Siswa</a>
        <a class="collapse-item" href="home.php?page=tambah-siswa"><i class="fas fa-user-plus text-primary mr-1" style="font-size:11px"></i>Tambah Siswa</a>
        <a class="collapse-item" href="home.php?page=input-kelas"><i class="fas fa-chalkboard text-secondary mr-1" style="font-size:11px"></i>Input Kelas</a>
        <a class="collapse-item" href="home.php?page=kenaikan-kelas"><i class="fas fa-level-up-alt text-primary mr-1" style="font-size:11px"></i>Naik Kelas</a>
        <a class="collapse-item" href="home.php?page=data-alumni"><i class="fas fa-user-graduate text-success mr-1" style="font-size:11px"></i>Data Alumni</a>
        <a class="collapse-item" href="home.php?page=ketua-kelas"><i class="fas fa-crown text-warning mr-1" style="font-size:11px"></i>Setting Ketua Kelas</a>
      </div>
    </div>
  </li>
    <?php if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] == 1): ?>
      <!-- Nav Item - Wali Kelas under Data Guru dan Siswa -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseWaliKelas" aria-expanded="false" aria-controls="collapseWaliKelas">
          <i class="fas fa-fw fa-user-tie"></i>
          <span>Wali Kelas & Guru BK</span>
        </a>
        <div id="collapseWaliKelas" class="collapse" aria-labelledby="headingWaliKelas" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Wali Kelas:</h6>
            <a class="collapse-item" href="home.php?page=kelola-wali-kelas"><i class="fas fa-users-cog text-primary mr-1" style="font-size:11px"></i>Kelola Wali Kelas</a>
            <a class="collapse-item" href="home.php?page=data-wali-kelas"><i class="fas fa-address-book text-info mr-1" style="font-size:11px"></i>Data Wali Kelas</a>
            <h6 class="collapse-header mt-2">Bimbingan Konseling:</h6>
            <a class="collapse-item" href="home.php?page=data-guru-bk"><i class="fas fa-user-shield text-success mr-1" style="font-size:11px"></i>Data Guru BK</a>
          </div>
        </div>
      </li>
    <?php endif; ?>
  <!-- Nav Item - Pages Collapse Menu Monitoring -->
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMonitoring" aria-expanded="false" aria-controls="collapseMonitoring">
      <i class="fas fa-fw fa-chart-line"></i>
      <span>Monitoring</span>
    </a>
    <div id="collapseMonitoring" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Cek:</h6>
        <a class="collapse-item" href="home.php?page=jurnal"><i class="fas fa-book text-primary mr-1" style="font-size:11px"></i>Jurnal Guru</a>
        <a class="collapse-item" href="home.php?page=kelas"><i class="fas fa-chalkboard text-info mr-1" style="font-size:11px"></i>Jurnal Kelas</a>
        <a class="collapse-item" href="home.php?page=monitoring-guru"><i class="fas fa-eye text-warning mr-1" style="font-size:11px"></i>Monitoring Guru</a>
        <a class="collapse-item" href="home.php?page=lacak-siswa"><i class="fas fa-search-location text-success mr-1" style="font-size:11px"></i>Lacak Siswa</a>
        <a class="collapse-item" href="home.php?page=monitoring"><i class="fas fa-user-check text-secondary mr-1" style="font-size:11px"></i>Kehadiran Guru</a>
        <a class="collapse-item" href="home.php?page=cek-nilai"><i class="fas fa-star text-warning mr-1" style="font-size:11px"></i>Cek Nilai</a>
        <a class="collapse-item" href="home.php?page=nilai-perkembangan"><i class="fas fa-chart-line text-info mr-1" style="font-size:11px"></i>Nilai & Grafik Perkembangan</a>
        <a class="collapse-item" href="home.php?page=cetak-kehadiran-siswa&tab=kelas"><i class="fas fa-users text-primary mr-1" style="font-size:11px"></i>Kehadiran Siswa</a>
        <a class="collapse-item" href="home.php?page=monitoring-izin"><i class="fas fa-file-signature text-warning mr-1" style="font-size:11px"></i>Monitoring Izin</a>
        <?php
        $showAduanMenu = false;
        if (isset($_SESSION['hak_akses'])) {
            if ((int)$_SESSION['hak_akses'] === 1) {
                $showAduanMenu = true;
            } elseif ((int)$_SESSION['hak_akses'] === 2 && isset($_SESSION['no_induk']) && isset($conn)) {
                $sidebarNip = $_SESSION['no_induk'];
                $sidebarNipEsc = mysqli_real_escape_string($conn, $sidebarNip);
                $qTimAduan = @mysqli_query($conn, "SELECT is_tim_aduan FROM tbl_guru WHERE no_induk='$sidebarNipEsc' LIMIT 1");
                if ($qTimAduan && $rowTim = mysqli_fetch_assoc($qTimAduan)) {
                    $showAduanMenu = !empty($rowTim['is_tim_aduan']) && (int)$rowTim['is_tim_aduan'] === 1;
                }
            }
        }
        if ($showAduanMenu):
        ?>
        <a class="collapse-item" href="home.php?page=aduan-siswa"><i class="fas fa-shield-heart text-danger mr-1" style="font-size:11px"></i>Aduan Siswa</a>
        <?php endif; ?>
      </div>
    </div>
  </li>

  <!-- Divider -->
  <hr class="sidebar-divider">

  <!-- Heading -->
  <div class="sidebar-heading">
    <i class="fas fa-cog mr-1" style="font-size:9px;"></i> Manajemen
  </div>

  <?php if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] == 1): ?>
    <!-- Nav Item - User Online -->
    <li class="nav-item">
      <a class="nav-link" href="home.php?page=user-online">
        <i class="fas fa-fw fa-signal"></i>
        <span>User Online</span>
      </a>
    </li>
  <?php endif; ?>

  <!-- Nav Item - Admin -->
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
      <i class="fas fa-fw fa-address-book"></i>
      <span>Admin</span>
    </a>
    <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Data Admin:</h6>
        <a class="collapse-item" href="home.php?page=lihatuser"><i class="fas fa-users-cog text-info mr-1" style="font-size:11px"></i>Tampilkan Admin</a>
        <a class="collapse-item" href="home.php?page=tambahuser"><i class="fas fa-user-plus text-primary mr-1" style="font-size:11px"></i>Tambah Admin</a>
        <h6 class="collapse-header mt-2">LENTERA:</h6>
        <a class="collapse-item" href="home.php?page=literasi-admin"><i class="fas fa-book-reader text-warning mr-1" style="font-size:11px"></i>Mapping Guru Literasi</a>
      </div>
    </div>
  </li>



  <!-- Nav Item - Mata Pelajaran -->
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePagesMapel" aria-expanded="false" aria-controls="collapsePagesMapel">
      <i class="fas fa-fw fa-book"></i>
      <span>Mata Pelajaran</span>
    </a>
    <div id="collapsePagesMapel" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Mapel:</h6>
        <a class="collapse-item" href="home.php?page=tambah-data-mapel"><i class="fas fa-plus-circle text-primary mr-1" style="font-size:11px"></i>Tambah Mapel</a>
        <a class="collapse-item" href="import-jadwal.php"><i class="fas fa-calendar-alt text-success mr-1" style="font-size:11px"></i>Import Jadwal Guru</a>
      </div>
    </div>
  </li>

  <!-- Nav Item - Tahun Ajaran -->
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePagesTA" aria-expanded="false" aria-controls="collapsePagesTA">
      <i class="fas fa-fw fa-calendar-alt"></i>
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
      <i class="fas fa-fw fa-history"></i>
      <span>Log Sistem</span>
    </a>
    <div id="collapseLog" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Data Log Sistem:</h6>
        <a class="collapse-item" href="home.php?page=lihat-log">Lihat Log Sistem</a>
        <a class="collapse-item" href="home.php?page=cetak-log">Cetak Laporan Log</a>
        <h6 class="collapse-header mt-2">Pemeliharaan:</h6>
        <a class="collapse-item" href="home.php?page=clear-cache"><i class="fas fa-broom text-warning mr-1" style="font-size:11px"></i>Bersihkan Log &amp; Cache</a>
      </div>
    </div>
  </li>

  <!-- Nav Item - Pengaturan -->
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSetting" aria-expanded="false" aria-controls="collapseSetting">
      <i class="fas fa-fw fa-cogs"></i>
      <span>Pengaturan</span>
    </a>
    <div id="collapseSetting" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Setting</h6>
        <a class="collapse-item" href="home.php?page=setting"><i class="fas fa-school text-info mr-1" style="font-size:11px"></i>Data Sekolah</a>
        <a class="collapse-item" href="home.php?page=setting#geminiApiKey"><i class="fas fa-key text-warning mr-1" style="font-size:11px"></i>Setting API Key</a>
        <a class="collapse-item" href="home.php?page=presensi-settings"><i class="fas fa-user-clock text-secondary mr-1" style="font-size:11px"></i>Pengaturan Presensi</a>
        <a class="collapse-item" href="home.php?page=setting-sholat"><i class="fas fa-mosque text-success mr-1" style="font-size:11px"></i>Pengaturan Sholat</a>
        <a class="collapse-item" href="google-oauth-settings.php"><i class="fab fa-google text-danger mr-1" style="font-size:11px"></i>Login Gmail</a>
        <a class="collapse-item" href="pengaturan-wa.php"><i class="fab fa-whatsapp text-success mr-1" style="font-size:11px"></i>Notifikasi WhatsApp</a>
        <a class="collapse-item" href="home.php?page=broadcast-wa"><i class="fas fa-bullhorn text-primary mr-1" style="font-size:11px"></i>Broadcast WA</a>
        <?php if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] == 1): ?>
        <h6 class="collapse-header mt-2">Data Management:</h6>
        <a class="collapse-item" href="home.php?page=reset-semester"><i class="fas fa-trash-restore-alt text-danger mr-1" style="font-size:11px"></i>Reset Semester Baru</a>
        <?php endif; ?>
      </div>
    </div>
  </li>

  

  <?php if (isset($_SESSION['hak_akses']) && ($_SESSION['hak_akses'] == 1 || $_SESSION['hak_akses'] == 5)): ?>

    <?php
    // Hitung jumlah pengumuman aktif
    $unreadBadge = 0;
    if (isset($conn)) {
      $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
      @$result = mysqli_query($conn, "SELECT COUNT(*) as jml FROM tbl_pengumuman WHERE status = 'aktif' AND id_sekolah = $idSekolah");
      if ($result) {
        @$row = mysqli_fetch_assoc($result);
        if ($row) {
          $unreadBadge = (int)$row['jml'];
        }
      }
    }
    ?>

    <?php if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] == 5): ?>
    <!-- Divider -->
    <hr class="sidebar-divider">
    
    <!-- Heading -->
    <div class="sidebar-heading">
      <i class="fas fa-crown mr-1" style="font-size:9px;"></i> Ruang Kepala Sekolah
    </div>

    <!-- Nav Item - Kepsek Monitoring Jurnal -->
    <li class="nav-item">
      <a class="nav-link" href="home.php?page=monitoring-jurnal-kepsek">
        <i class="fas fa-fw fa-book-reader"></i>
        <span>Monitoring Jurnal Guru</span>
      </a>
    </li>

    <!-- Nav Item - Kepsek Monitoring Kehadiran -->
    <li class="nav-item">
      <a class="nav-link" href="home.php?page=monitoring-kehadiran-kepsek">
        <i class="fas fa-fw fa-users"></i>
        <span>Monitoring Kehadiran Siswa</span>
      </a>
    </li>

    <!-- Nav Item - Intervensi -->
    <li class="nav-item">
      <a class="nav-link" href="home.php?page=intervensi-kepsek">
        <i class="fas fa-fw fa-paper-plane"></i>
        <span>Kirim Intervensi / Notif</span>
      </a>
    </li>
    <?php endif; ?>

    <!-- Nav Item - Pengumuman (direct link) -->
    <li class="nav-item">
      <a class="nav-link" href="home.php?page=pengumuman">
        <i class="fas fa-fw fa-bullhorn"></i>
        <span>Pengumuman<?php if ($unreadBadge > 0): ?> <span class="badge badge-success badge-counter" style="font-size:10px; margin-left:6px;"><?= $unreadBadge; ?></span><?php endif; ?></span>
      </a>
    </li>

    <!-- Nav Item - Kelola Twibbon -->
    <li class="nav-item">
      <a class="nav-link" href="home.php?page=kelola-twibbon">
        <i class="fas fa-fw fa-camera-retro"></i>
        <span>Kelola Twibbon</span>
      </a>
    </li>

    <!-- Nav Item - Microsite Kurikulum -->
    <li class="nav-item">
      <a class="nav-link" href="home.php?page=kurikulum-microsite">
        <i class="fas fa-fw fa-th-large"></i>
        <span>Microsite Kurikulum</span>
      </a>
    </li>

    <!-- Nav Item - Edit Agenda -->
    <li class="nav-item">
      <a class="nav-link" href="home.php?page=agenda-sekolah">
        <i class="fas fa-fw fa-calendar-alt"></i>
        <span>Edit Agenda</span>
      </a>
    </li>

    <!-- Nav Item - Ekskul -->
    <li class="nav-item">
      <a class="nav-link" href="home.php?page=ekskul">
        <i class="fas fa-fw fa-basketball-ball"></i>
        <span>Ekstrakurikuler</span>
      </a>
    </li>

    <!-- Nav Item - Sinkron Ekskul e-Raport -->
    <li class="nav-item">
      <a class="nav-link" href="home.php?page=sync-eraport-ekskul">
        <i class="fas fa-fw fa-sync-alt"></i>
        <span>Sync e-Raport Ekskul</span>
      </a>
    </li>

    <!-- Nav Item - Pengaturan Profil Siswa -->
    <li class="nav-item">
      <a class="nav-link" href="home.php?page=data-siswa">
        <i class="fas fa-fw fa-id-card"></i>
        <span>Profil Siswa <?php
                            $__qProfil = @mysqli_query($conn, "SELECT COUNT(*) as c FROM tbl_siswa WHERE (alamat IS NULL OR alamat='') AND (status IS NULL OR UPPER(status)='AKTIF')");
                            $__rProfil = $__qProfil ? mysqli_fetch_assoc($__qProfil) : null;
                            $__belum   = $__rProfil ? (int)$__rProfil['c'] : 0;
                            if ($__belum > 0) echo '<span class="badge badge-warning badge-counter" style="font-size:10px;margin-left:6px;">' . $__belum . '</span>';
                            ?></span>
      </a>
    </li>

    <!-- Nav Item - Validasi Profil Guru -->
    <li class="nav-item">
      <a class="nav-link" href="home.php?page=validasi-profil-guru">
        <i class="fas fa-fw fa-user-check"></i>
        <span>Validasi Profil Guru <?php
                                    $__qPendingProfil = @mysqli_query($conn, "SELECT COUNT(*) as c FROM tbl_pengajuan_profil_guru WHERE status_pengajuan='Menunggu'");
                                    $__rPendingProfil = $__qPendingProfil ? mysqli_fetch_assoc($__qPendingProfil) : null;
                                    $__jmlPendingProfil = $__rPendingProfil ? (int)$__rPendingProfil['c'] : 0;
                                    if ($__jmlPendingProfil > 0) echo '<span class="badge badge-danger badge-counter" style="font-size:10px;margin-left:6px;">' . $__jmlPendingProfil . '</span>';
                                    ?></span>
      </a>
    </li>



  <?php endif; ?>

  <!-- Nav Item - Privacy Policy -->
  <li class="nav-item">
    <a class="nav-link" href="privacy-policy.php" target="_blank">
      <i class="fas fa-fw fa-shield-alt"></i>
      <span>Privacy Policy</span>
    </a>
  </li>

  <!-- Divider -->
  <hr class="sidebar-divider d-none d-md-block">

      <!-- Sidebar Toggler (Sidebar) -->
      <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
      </div>

    </ul>
    <!-- End of Sidebar -->
<?php else: ?>
    <!-- DESKTOP SIDEBAR FOR GURU -->
    <link rel='stylesheet' href='pages/guru/css/guru-2026-scoped.css?v=<?= time() ?>'>
    <link rel='stylesheet' href='pages/guru/css/guru-desktop.css?v=<?= time() ?>'>
    <?php include 'pages/guru/guru_sidebar_shared.php'; ?>
<?php endif; ?>

<!-- Mobile overlay — dipakai untuk menutup sidebar ketika klik di luar -->
<div class="sb-overlay" id="sbOverlay" onclick="document.body.classList.remove('sidebar-open');"></div>

<script>
  (function() {
    // Auto-detect active page and highlight sidebar menu
    var params = new URLSearchParams(window.location.search);
    var currentPage = params.get('page') || '';

    // Map: page value -> collapse target id
    var pageToCollapse = {
      'data-guru': 'collapseTwo',
      'tambah-guru': 'collapseTwo',
      'import-guru': 'collapseTwo',
      'detail-guru': 'collapseTwo',
      'data-siswa': 'collapseSiswa',
      'tambah-siswa': 'collapseSiswa',
      'input-kelas': 'collapseSiswa',
      'kenaikan-kelas': 'collapseSiswa',
      'data-alumni': 'collapseSiswa',
      'ketua-kelas': 'collapseSiswa',
      'kelola-wali-kelas': 'collapseWaliKelas',
      'data-wali-kelas': 'collapseWaliKelas',
      'data-guru-bk': 'collapseWaliKelas',
      'jurnal': 'collapseMonitoring',
      'kelas': 'collapseMonitoring',
      'monitoring-guru': 'collapseMonitoring',
      'lacak-siswa': 'collapseMonitoring',
      'monitoring': 'collapseMonitoring',
      'cek-nilai': 'collapseMonitoring',
      'nilai-perkembangan': 'collapseMonitoring',
      'cetak-kehadiran-guru': 'collapseMonitoring',
      'cetak-kehadiran-siswa': 'collapseMonitoring',
      'monitoring-izin': 'collapseMonitoring',
      'aduan-siswa': 'collapseMonitoring',
      'lihatuser': 'collapsePages',
      'tambahuser': 'collapsePages',
      'literasi-admin': 'collapsePages',
      'ekskul': 'collapsePages',
      'sync-eraport-ekskul': 'collapseSetting',
      'tambah-data-mapel': 'collapsePagesMapel',
      'tambah-tahun-ajaran': 'collapsePagesTA',
      'lihat-log': 'collapseLog',
      'cetak-log': 'collapseLog',
      'clear-cache': 'collapseLog',
      'setting': 'collapseSetting',
      'setting-sholat': 'collapseSetting',
      'presensi-settings': 'collapseSetting',
      'broadcast-wa': 'collapseSetting',
      'reset-semester': 'collapseSetting',
      'buat-laporan': 'collapseLaporan',
      'cetak-jurnal-guru': 'collapseLaporan',
      'monitoring-jurnal-kepsek': 'collapseMonitoring',
      'monitoring-kehadiran-kepsek': 'collapseMonitoring',
      'intervensi-kepsek': 'collapseMonitoring'
    };

    if (currentPage && pageToCollapse[currentPage]) {
      var collapseId = pageToCollapse[currentPage];
      var collapseEl = document.getElementById(collapseId);
      if (collapseEl) {
        collapseEl.classList.add('show');
        var trigger = document.querySelector('[data-target="#' + collapseId + '"]');
        if (trigger) trigger.classList.remove('collapsed');
      }
    }

    // Highlight active collapse-item link
    var allLinks = document.querySelectorAll('#accordionSidebar .collapse-item, #accordionSidebar .nav-link:not([data-toggle])');
    allLinks.forEach(function(link) {
      var href = link.getAttribute('href') || '';
      var pagePart = href.split('page=')[1] || '';
      pagePart = pagePart.split('&')[0];
      if (pagePart && pagePart === currentPage) {
        link.style.background = 'linear-gradient(90deg, rgba(26,60,110,0.12), rgba(26,60,110,0.05))';
        link.style.color = '#1a3c6e';
        link.style.fontWeight = '700';
        link.style.borderLeft = '3px solid #1a3c6e';
        link.style.paddingLeft = '17px';
      }
      // Highlight direct nav links
      if (!href.includes('?') && window.location.pathname.endsWith(href.split('/').pop())) {
        link.closest('.nav-item') && link.closest('.nav-item').classList.add('active');
      }
    });
  })();
</script>
