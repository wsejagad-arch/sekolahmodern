<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
/* GLOBAL SISWA FOOTER (GREEN THEME) */
.siswa-footer-nav {
  position: fixed !important;
  bottom: 0 !important;
  left: 0 !important;
  right: 0 !important;
  width: 100% !important;
  max-width: 100% !important;
  height: calc(70px + env(safe-area-inset-bottom)) !important;
  background: linear-gradient(135deg, #10b981, #059669) !important;
  box-shadow: 0 -4px 30px rgba(16, 185, 129, 0.3) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: space-around !important;
  padding: 0 !important;
  padding-bottom: env(safe-area-inset-bottom) !important;
  z-index: 9999 !important;
  border-radius: 24px 24px 0 0 !important;
  transform: none !important;
  margin: 0 !important;
  box-sizing: border-box !important;
}
.sfooter-item {
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  gap: 4px !important;
  color: rgba(255, 255, 255, 0.7) !important;
  text-decoration: none !important;
  font-weight: 600 !important;
  transition: all 0.3s ease !important;
  flex: 1 !important;
  background: transparent !important;
  padding: 0 !important;
  margin: 0 !important;
}
/* Middle floating item */
.sfooter-item.floating {
  position: relative !important;
  top: -25px !important;
  flex: none !important;
  width: 70px !important;
}
.sfooter-item.floating .icon-wrapper {
  background: #ffffff !important;
  color: #059669 !important;
  width: 60px !important;
  height: 60px !important;
  border-radius: 50% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  box-shadow: 0 8px 20px rgba(5, 150, 105, 0.4) !important;
  margin-bottom: 2px !important;
  transition: all 0.3s ease !important;
  border: none !important;
}
.sfooter-item.floating.active .icon-wrapper {
  background: #059669 !important;
  color: #ffffff !important;
  border: 4px solid #ffffff !important;
  transform: translateY(-4px) !important;
}
.sfooter-item.floating i {
  font-size: 1.8rem !important;
  margin: 0 !important;
}
.sfooter-item.floating .sfooter-label {
  color: #ffffff !important;
  opacity: 1 !important;
  font-size: 0.7rem !important;
  margin: 0 !important;
}

.sfooter-item:not(.floating) i {
  font-size: 1.25rem !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
  margin: 0 !important;
}
.sfooter-item:not(.floating) .sfooter-label {
  font-size: 0.7rem !important;
  opacity: 0.8 !important;
  transition: all 0.3s ease !important;
  margin: 0 !important;
}
.sfooter-item:not(.floating):hover, .sfooter-item:not(.floating).active {
  color: #ffffff !important;
}
.sfooter-item:not(.floating).active i {
  transform: translateY(-4px) scale(1.15) !important;
  text-shadow: 0 4px 12px rgba(255,255,255,0.4) !important;
}
.sfooter-item:not(.floating).active .sfooter-label {
  opacity: 1 !important;
  font-weight: 800 !important;
}
</style>

<nav class="siswa-footer-nav">
  <a href="<?= siswa_page('siswa') ?>" class="sfooter-item <?= $currentPage == 'siswa.php' ? 'active' : '' ?>">
    <i class="fas fa-home"></i>
    <span class="sfooter-label">Beranda</span>
  </a>
  <a href="<?= siswa_page('presensi') ?>" class="sfooter-item floating <?= $currentPage == 'presensi.php' ? 'active' : '' ?>">
    <div class="icon-wrapper">
      <i class="fas fa-fingerprint"></i>
    </div>
    <span class="sfooter-label">Presensi</span>
  </a>
  <a href="<?= siswa_page('profil') ?>" class="sfooter-item <?= $currentPage == 'profil.php' ? 'active' : '' ?>">
    <i class="far fa-user"></i>
    <span class="sfooter-label">Profil</span>
  </a>
</nav>
