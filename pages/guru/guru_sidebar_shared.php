<?php
$is_root = (basename($_SERVER['PHP_SELF']) === 'home.php' || basename($_SERVER['PHP_SELF']) === 'index.php');
$prefix = $is_root ? 'pages/guru/' : '';
$home_link = $is_root ? '?page=beranda' : '../../home.php';
$img_prefix = $is_root ? '' : '../../';
$current_page = $is_root ? ($_GET['page'] ?? 'beranda') : basename($_SERVER['PHP_SELF'], '.php');
$lembaga = $lembaga ?? (function_exists('data_lembaga') ? data_lembaga() : []);
?>
<style>
@media (min-width: 768px) {
    body {
        padding-left: 260px !important;
    }
    .desktop-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 260px;
        height: 100vh;
        background: #3c58b9;
        display: flex !important;
        flex-direction: column;
        padding: 24px 0 24px 20px;
        z-index: 9999;
        box-shadow: 6px 0 30px rgba(60,88,185,0.1);
        border-right: 1px solid rgba(255,255,255,0.05);
        color: #fff;
    }
    .desktop-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-right: 20px;
        margin-bottom: 40px;
    }
    .desktop-logo img {
        width: 32px;
        height: 32px;
        object-fit: contain;
        background: #fff;
        border-radius: 8px;
        padding: 4px;
    }
    .desktop-logo i {
        font-size: 28px;
        color: #fff;
    }
    .desktop-logo span {
        font-size: 20px;
        font-weight: 800;
        letter-spacing: 0.5px;
    }
    .desktop-nav {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .desktop-nav a {
        text-decoration: none;
        color: rgba(255,255,255,0.7);
        padding: 14px 20px;
        border-radius: 12px 0 0 12px;
        font-weight: 600;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: all 0.3s ease;
        position: relative;
    }
    .desktop-nav a i {
        font-size: 18px;
    }
    .desktop-nav a:hover {
        color: #fff;
        background: rgba(255,255,255,0.1);
    }
    .desktop-nav a.active {
        color: #3c58b9;
        background: #ebf1f6;
    }
    .desktop-nav a.active::before {
        content: '';
        position: absolute;
        top: -20px;
        right: 0;
        width: 20px;
        height: 20px;
        background: transparent;
        border-bottom-right-radius: 20px;
        box-shadow: 0 10px 0 0 #ebf1f6;
    }
    .desktop-nav a.active::after {
        content: '';
        position: absolute;
        bottom: -20px;
        right: 0;
        width: 20px;
        height: 20px;
        background: transparent;
        border-top-right-radius: 20px;
        box-shadow: 0 -10px 0 0 #ebf1f6;
    }
    .desktop-logout-wrap {
        margin-top: auto;
        padding-right: 20px;
    }
    .btn-desktop-logout {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: rgba(255,255,255,0.1);
        color: #fff;
        text-decoration: none;
        padding: 12px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-desktop-logout:hover {
        background: #ef4444;
        color: #fff;
    }
}
@media (max-width: 767px) {
    .desktop-sidebar { display: none !important; }
}
</style>
<div class="desktop-sidebar">
    <div class="desktop-logo">
        <?php if (!empty($lembaga['logo'])): ?>
            <img src="<?= $img_prefix ?>img/<?= htmlspecialchars($lembaga['logo']) ?>" alt="Logo">
        <?php else: ?>
            <i class="bi bi-book-half"></i>
        <?php endif; ?>
        <span>SIMANIS</span>
    </div>
    <div class="desktop-nav">
        <a href="<?= $home_link ?>" class="<?= ($current_page === 'beranda' || $current_page === 'dashboard_guru') ? 'active' : '' ?>"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="<?= $prefix ?>setting-jadwal" class="<?= ($current_page === 'setting-jadwal') ? 'active' : '' ?>"><i class="bi bi-calendar3"></i> Kelas Saya</a>
        <a href="<?= $prefix ?>data-siswa" class="<?= ($current_page === 'data-siswa') ? 'active' : '' ?>"><i class="bi bi-people"></i> Data Siswa</a>
        <a href="<?= $prefix ?>nilai" class="<?= ($current_page === 'nilai') ? 'active' : '' ?>"><i class="bi bi-journal-check"></i> Nilai & Tugas</a>
        <a href="<?= $prefix ?>materi" class="<?= ($current_page === 'materi') ? 'active' : '' ?>"><i class="bi bi-book"></i> Materi</a>
        <a href="<?= $prefix ?>laporan-kelas" class="<?= ($current_page === 'laporan-kelas') ? 'active' : '' ?>"><i class="bi bi-cpu"></i> Laporan & AI</a>
        <a href="<?= $prefix ?>ekinerja" class="<?= ($current_page === 'ekinerja') ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> e-Kinerja</a>
        <a href="<?= $prefix ?>profil-guru" class="<?= ($current_page === 'profil-guru') ? 'active' : '' ?>"><i class="bi bi-gear"></i> Pengaturan</a>
    </div>
    <div class="desktop-logout-wrap">
        <a href="<?= $img_prefix ?>logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');" class="btn-desktop-logout">
            <i class="bi bi-box-arrow-right"></i> Log Out
        </a>
    </div>
</div>
