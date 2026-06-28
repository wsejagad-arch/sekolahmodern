<?php
// guru_2026_view.php – Versi tampilan dashboard guru.
// Menyajikan tata letak dan menu yang sama seperti guru_2026.php.
// Dapat diakses melalui http://localhost/jurnal/pages/guru/guru_2026_view.php

require_once '../../koneksi_local.php';
require_once '../../topbar.php';
require_once '../../footer.php';

// Menangkap output dari halaman asli agar UI tetap sama.
ob_start();
include 'guru_2026.php';
$pageContent = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard Guru – Tampilan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="css/guru-2026.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Sidebar desktop, hanya muncul pada layar lebar (≥992px) -->
    <aside class="desktop-sidebar d-none d-lg-flex flex-column">
        <div class="sidebar-header">
            <a href="profil-guru" class="sidebar-profile">
                <?php if ($dataGuru['foto']): ?>
                    <img src="../../foto/<?= $dataGuru['foto'] ?>" alt="Profile" class="sidebar-avatar"/>
                <?php else: ?>
                    <?= get_guru_avatar_svg(get_guru_gender($dataGuru['no_induk'], $dataGuru['nama_guru'])) ?>
                <?php endif; ?>
                <span class="sidebar-name"><?= $dataGuru['nama_guru'] ?: 'Bu Amanda' ?></span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="rekap-kehadiran" class="sidebar-item"><i class="bi bi-clipboard2-data-fill"></i> Kehadiran</a>
            <?php if ($isWaliKelas): ?>
                <a href="walikelas" class="sidebar-item"><i class="bi bi-person-vcard-fill"></i> Wali Kelas</a>
            <?php endif; ?>
            <a href="laporan-kelas" class="sidebar-item"><i class="bi bi-bar-chart-fill"></i> Laporan Kelas</a>
            <a href="apresiasi-guru" class="sidebar-item"><i class="bi bi-award-fill"></i> Apresiasi</a>
            <a href="piagam-7kih" class="sidebar-item"><i class="bi bi-patch-check-fill"></i> Piagam 7 KAIH (Tujuh Kebiasaan Anak Indonesia Hebat)</a>
            <a href="wks" class="sidebar-item"><i class="bi bi-diagram-3-fill"></i> WKS</a>
            <a href="materi" class="sidebar-item"><i class="bi bi-book-half"></i> Materi</a>
            <a href="setting-jadwal" class="sidebar-item"><i class="bi bi-calendar-week-fill"></i> Pengaturan Jadwal</a>
            <a href="ekskul" class="sidebar-item"><i class="bi bi-dribbble"></i> Ekstrakurikuler</a>
            <?php if ($isPembinaLiterasi): ?>
                <a href="literasi" class="sidebar-item"><i class="bi bi-book-half"></i> LENTERA Literasi</a>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($kelasDetailUrl, ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-item"><i class="bi bi-person-badge-fill"></i> Data Siswa</a>
            <a href="nilai" class="sidebar-item"><i class="bi bi-table"></i> Nilai</a>
            <a href="leger" class="sidebar-item"><i class="bi bi-file-earmark-spreadsheet-fill"></i> Leger</a>
            <a href="#" class="sidebar-item btn-open-guru-wali"><i class="bi bi-person-workspace"></i> Guru Wali</a>
            <a href="#" class="sidebar-item btn-open-pelanggaran"><i class="bi bi-exclamation-triangle-fill"></i> Pelanggaran</a>
            <a href="../../logout.php" class="sidebar-item logout"><i class="bi bi-box-arrow-right"></i> Keluar</a>
        </nav>
    </aside>
    <!-- Konten utama dibungkus agar menyesuaikan lebar sidebar -->
    <div class="desktop-main">
        <?php echo $pageContent; ?>
    </div>
</body>
</html>
