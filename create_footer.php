<?php
$content = '<?php
$currentPage = basename($_SERVER[\'PHP_SELF\']);
?>
<style>
/* BOTTOM NAV (GREEN THEME) */
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: calc(70px + env(safe-area-inset-bottom));
  background: linear-gradient(135deg, #10b981, #059669);
  box-shadow: 0 -4px 30px rgba(16, 185, 129, 0.3);
  display: flex;
  align-items: flex-start;
  justify-content: space-around;
  padding-top: 12px;
  padding-bottom: env(safe-area-inset-bottom);
  z-index: 100;
  border-radius: 24px 24px 0 0;
}
.bnav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  color: rgba(255, 255, 255, 0.7) !important;
  text-decoration: none !important;
  font-weight: 600;
  transition: all 0.3s ease;
  flex: 1;
}
.bnav-item i {
  font-size: 1.25rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.bnav-label {
  font-size: 0.7rem;
  opacity: 0.8;
  transition: all 0.3s ease;
}
.bnav-item:hover, .bnav-item.active {
  color: #ffffff !important;
}
.bnav-item.active i {
  transform: translateY(-4px) scale(1.15);
  text-shadow: 0 4px 12px rgba(255,255,255,0.4);
}
.bnav-item.active .bnav-label {
  opacity: 1;
  font-weight: 800;
}
</style>

<nav class="bottom-nav">
  <a href="siswa.php" class="bnav-item <?= $currentPage == \'siswa.php\' ? \'active\' : \'\' ?>">
    <i class="fas fa-home"></i>
    <span class="bnav-label">Beranda</span>
  </a>
  <a href="presensi.php" class="bnav-item <?= $currentPage == \'presensi.php\' ? \'active\' : \'\' ?>">
    <i class="fas fa-fingerprint"></i>
    <span class="bnav-label">Presensi</span>
  </a>
  <a href="profil.php" class="bnav-item <?= $currentPage == \'profil.php\' ? \'active\' : \'\' ?>">
    <i class="far fa-user"></i>
    <span class="bnav-label">Profil</span>
  </a>
</nav>
';
file_put_contents('c:\xampp\htdocs\jurnal\pages\siswa\siswa_footer.php', $content);
echo "Created siswa_footer.php";
?>
