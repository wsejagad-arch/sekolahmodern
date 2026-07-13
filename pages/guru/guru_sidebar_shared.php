<?php
$is_root = (basename($_SERVER['PHP_SELF']) === 'home.php' || basename($_SERVER['PHP_SELF']) === 'index.php');
$prefix = $is_root ? 'pages/guru/' : '';
$home_link = $is_root ? '?page=beranda' : '../../home.php';
$img_prefix = $is_root ? '' : '../../';
$current_page = $is_root ? ($_GET['page'] ?? 'beranda') : basename($_SERVER['PHP_SELF'], '.php');
$lembaga = $lembaga ?? (function_exists('data_lembaga') ? data_lembaga() : []);
?>
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
        
        <?php
        $isGuruAgama = false;
        if (isset($conn) && isset($_SESSION['no_induk'])) {
            $nipGuru = $_SESSION['no_induk'];
            $nipGuruEsc = mysqli_real_escape_string($conn, $nipGuru);
            $idSekolahGuru = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
            $qGuruAgama = @mysqli_query($conn, "SELECT COUNT(*) as c FROM tbl_mapel_ampu WHERE no_induk='$nipGuruEsc' AND id_sekolah=$idSekolahGuru AND (LOWER(nama_mapel) LIKE '%agama%' OR LOWER(nama_mapel) LIKE '%pabp%' OR LOWER(nama_mapel) LIKE '%papb%' OR LOWER(nama_mapel) LIKE '%pa bp%' OR LOWER(nama_mapel) LIKE '%pai%')");
            if ($qGuruAgama && $r = mysqli_fetch_assoc($qGuruAgama)) {
                if ((int)$r['c'] > 0) $isGuruAgama = true;
            }
        }
        if ($isGuruAgama):
        ?>
        <a href="<?= $prefix ?>rekapan-sholat" class="<?= ($current_page === 'rekapan-sholat') ? 'active' : '' ?>"><i class="fas fa-praying-hands"></i> Rekapan Sholat</a>
        <?php endif; ?>

        <a href="<?= $prefix ?>ekinerja" class="<?= ($current_page === 'ekinerja') ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> e-Kinerja</a>
        <a href="<?= $prefix ?>profil-guru" class="<?= ($current_page === 'profil-guru') ? 'active' : '' ?>"><i class="bi bi-gear"></i> Pengaturan</a>
        <a href="<?= $img_prefix ?>privacy-policy.php" target="_blank"><i class="bi bi-shield-check"></i> Kebijakan Privasi</a>
    </div>
    <div class="desktop-logout-wrap">
        <a href="<?= $img_prefix ?>logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');" class="btn-desktop-logout">
            <i class="bi bi-box-arrow-right"></i> Log Out
        </a>
    </div>
</div>
