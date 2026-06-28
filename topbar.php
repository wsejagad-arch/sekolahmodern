<!-- Topbar -->
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

  <!-- Sidebar Toggle (Topbar) -->
  <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" type="button" aria-label="Toggle sidebar" style="color:#1a3c6e;font-size:18px;padding:4px 10px;">
    <i class="fa fa-bars"></i>
  </button>

  <!-- School Name & Logo (Topbar Left) -->
  <div class="d-none d-sm-flex align-items-center flex-grow-1 ml-md-3 ms-md-3 my-2 my-md-0">
    <?php if (isset($lembaga) && is_array($lembaga) && !empty($lembaga['logo'])): ?>
      <img src="img/<?= htmlspecialchars($lembaga['logo']); ?>" width="38" height="38" class="mr-2" style="border-radius:8px; object-fit:contain; border:2px solid rgba(26,60,110,0.12);">
    <?php endif; ?>
    <div>
      <div style="font-size:15px; font-weight:800; color:#1a3c6e; line-height:1.2;">
        <?= isset($lembaga) && is_array($lembaga) && !empty($lembaga['nmsekolah']) ? htmlspecialchars($lembaga['nmsekolah']) : htmlspecialchars($lembaga['nama_aplikasi'] ?? 'Sistem Jurnal'); ?>
      </div>
      <div style="font-size:11px; color:#64748b; font-weight:500; letter-spacing:0.3px;">
        <i class="fas fa-map-marker-alt mr-1" style="color:#f0b429;font-size:10px;"></i>
        <?= isset($lembaga) && !empty($lembaga['alamat']) ? htmlspecialchars($lembaga['alamat']) : 'Sistem Manajemen Sekolah'; ?>
      </div>
    </div>
  </div>

  <!-- Topbar Navbar (Right) -->
  <ul class="navbar-nav ml-auto ms-auto align-items-center justify-content-end" style="flex: 0 0 auto;">

    <!-- Current Date/Time Info -->
    <li class="nav-item d-none d-lg-flex align-items-center mr-2">
      <div style="text-align:right;">
        <div style="font-size:12px; font-weight:700; color:#1a3c6e;" id="topbar-date"></div>
        <div style="font-size:11px; color:#64748b; font-weight:500;" id="topbar-time"></div>
      </div>
    </li>

    <div class="topbar-divider d-none d-lg-block"></div>

    <!-- Nav Item - Logout Button -->
    <li class="nav-item mr-1">
      <a class="nav-link" href="keluar" title="Keluar" style="color:#dc2626 !important; padding:8px 12px !important; border-radius:8px !important;">
        <i class="fas fa-sign-out-alt"></i>
        <span class="d-none d-lg-inline ml-1" style="font-size:12px; font-weight:600;">Keluar</span>
      </a>
    </li>

    <div class="topbar-divider d-none d-sm-block"></div>

    <!-- Fullscreen Toggle Button -->
    <li class="nav-item mr-2">
      <button class="btn btn-link nav-link" id="btn-fullscreen" title="Fullscreen" style="padding: 8px 12px !important; border-radius: 8px !important; border: none; background: transparent; cursor: pointer;">
        <i class="fas fa-expand" id="icon-fullscreen" style="color: #64748b; font-size: 16px;"></i>
      </button>
    </li>

    <div class="topbar-divider d-none d-sm-block"></div>

    <!-- Nav Item - User Information -->
    <li class="nav-item dropdown no-arrow">
      <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="padding: 6px 10px !important;">
        <div class="d-none d-lg-flex align-items-center mr-2" style="text-align:right;">
          <div>
            <div style="font-size:13px; font-weight:700; color:#1e293b; line-height:1.2;"><?php echo htmlspecialchars($nama); ?></div>
            <div style="font-size:11px; color:#64748b; font-weight:500;">
              <?php
              $roleLabels = array(1 => 'Admin Sekolah', 2 => 'Guru', 3 => 'Siswa', 9 => 'Admin Pusat');
              echo isset($roleLabels[$hakakses]) ? $roleLabels[$hakakses] : 'Pengguna';
              ?>
            </div>
          </div>
        </div>
        <div style="position:relative; display:inline-block;">
          <img class="img-profile rounded-circle" src="img/foto-profil.png" width="38" height="38" style="object-fit:cover; border:2px solid #e2e8f0; width:38px; height:38px;">
          <span style="position:absolute; bottom:1px; right:1px; width:9px; height:9px; background:#16a34a; border:2px solid #fff; border-radius:50%; display:block;"></span>
        </div>
      </a>
      <!-- Dropdown - User Information -->
      <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown" style="min-width:220px; border:none; border-radius:12px; box-shadow:0 8px 32px rgba(26,60,110,0.15); overflow:hidden; padding:8px 0;">
        <div style="padding:14px 18px 12px; border-bottom:1px solid #f0f4f8;">
          <div style="font-size:14px; font-weight:700; color:#1a3c6e;"><?php echo htmlspecialchars($nama); ?></div>
          <div style="font-size:12px; color:#64748b; margin-top:2px;"><?php echo htmlspecialchars($username); ?></div>
        </div>
        <a class="dropdown-item" href="ubah-password.php" style="padding:10px 18px; font-size:13.5px; font-weight:500; color:#1e293b; display:flex; align-items:center; gap:10px; transition:all .2s;">
          <i class="fas fa-key fa-sm text-warning" style="width:16px;"></i>
          Ganti Password
        </a>
        <div class="dropdown-divider" style="margin:4px 0; border-color:#f0f4f8;"></div>
        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal" style="padding:10px 18px; font-size:13.5px; font-weight:500; color:#dc2626; display:flex; align-items:center; gap:10px; transition:all .2s;">
          <i class="fas fa-sign-out-alt fa-sm" style="width:16px;"></i>
          Keluar
        </a>
      </div>
    </li>

  </ul>

</nav>
<!-- End of Topbar -->

<script>
  (function() {
    var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    var days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    function updateClock() {
      var now = new Date();
      var dateEl = document.getElementById('topbar-date');
      var timeEl = document.getElementById('topbar-time');
      if (dateEl) dateEl.textContent = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
      if (timeEl) {
        var h = String(now.getHours()).padStart(2, '0');
        var m = String(now.getMinutes()).padStart(2, '0');
        var s = String(now.getSeconds()).padStart(2, '0');
        timeEl.textContent = h + ':' + m + ':' + s + ' WIB';
      }
    }
    updateClock();
    setInterval(updateClock, 1000);

    // Fullscreen Toggle Script
    var fsButton = document.getElementById('btn-fullscreen');
    var fsIcon = document.getElementById('icon-fullscreen');
    if (fsButton && fsIcon) {
      fsButton.addEventListener('click', function() {
        if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
          var docElm = document.documentElement;
          if (docElm.requestFullscreen) {
            docElm.requestFullscreen();
          } else if (docElm.mozRequestFullScreen) {
            docElm.mozRequestFullScreen();
          } else if (docElm.webkitRequestFullscreen) {
            docElm.webkitRequestFullscreen();
          } else if (docElm.msRequestFullscreen) {
            docElm.msRequestFullscreen();
          }
        } else {
          if (document.exitFullscreen) {
            document.exitFullscreen();
          } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
          } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
          } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
          }
        }
      });

      var changeHandler = function() {
        var isFullscreen = document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement || document.msFullscreenElement;
        if (isFullscreen) {
          fsIcon.classList.remove('fa-expand');
          fsIcon.classList.add('fa-compress');
        } else {
          fsIcon.classList.remove('fa-compress');
          fsIcon.classList.add('fa-expand');
        }
      };
      document.addEventListener('fullscreenchange', changeHandler);
      document.addEventListener('webkitfullscreenchange', changeHandler);
      document.addEventListener('mozfullscreenchange', changeHandler);
      document.addEventListener('MSFullscreenChange', changeHandler);
    }
  })();
</script>
