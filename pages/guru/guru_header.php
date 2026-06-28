<?php
/**
 * MODERN GURU HEADER & SIDEBAR
 * Digunakan oleh semua halaman guru
 */

if (!isset($notifikasiData)) { $notifikasiData = []; }
if (!isset($totalNotifikasi)) { $totalNotifikasi = count($notifikasiData); }

require_once __DIR__ . '/../../auth_helper.php';
if (isset($conn) && function_exists('track_user_online_status')) {
    track_user_online_status($conn);
}

require_once __DIR__ . '/layout_visibility_helper.php';
$currentGuruPage = basename($_SERVER['PHP_SELF'] ?? '');
$guruLayoutVisible = guru_should_show_layout($currentGuruPage);

if (!function_exists('guru_nav_url')) {
    function guru_nav_url(string $page): string
    {
        $safe = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $page));
        return php_sapi_name() === 'cli-server' ? $safe . '.php' : $safe;
    }
}

// Ambil data guru untuk header
$nipguru_header = $_SESSION['no_induk'];
$sqlguru_header = mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$nipguru_header'");
$dataguru_header = mysqli_fetch_array($sqlguru_header);
$lembaga = data_lembaga();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - SIMANIS' : 'Dashboard Guru' ?></title>
    
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Modern UI Style -->
    <link rel="stylesheet" href="guru-minimalist.css?v=<?php echo time(); ?>">
    
    <style>
        /* Fix for pages including this header */
        body { padding: 0 !important; margin: 0 !important; background: var(--bg); }
        .app-container { margin-top: 0 !important; padding-bottom: 0 !important; }
        
        /* Ensure sidebar link highlights correctly based on current page */
        <?php
        $activePage = str_replace('.php', '', $_GET['page'] ?? basename($_SERVER['PHP_SELF']));
        ?>
        .sidebar-link[href="<?= $activePage ?>"],
        .sidebar-link[href="<?= $activePage ?>.php"] {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.4);
        }
        .sidebar-link[href="<?= $activePage ?>"] i,
        .sidebar-link[href="<?= $activePage ?>.php"] i { color: white; }
      @keyframes pulse-red { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } } .notif-blink { animation: pulse-red 2s infinite; background-color: #ef4444 !important; }
</style>
</head>
<body class="modern-theme <?= $guruLayoutVisible ? '' : 'layout-hidden' ?>">
<?php
$is_wali_kelas_or_bk = false;
// Gunakan no_induk (NIP) dari session
$nip_check = $_SESSION['no_induk'] ?? ($_SESSION['username'] ?? '');
if (!empty($nip_check) && isset($conn)) {
    $nip_check_esc = mysqli_real_escape_string($conn, $nip_check);
    // Cek di tbl_kelas (kolom nip_wali)
    $qWk = mysqli_query($conn, "SELECT id_kelas FROM tbl_kelas WHERE nip_wali = '$nip_check_esc' LIMIT 1");
    if ($qWk && mysqli_num_rows($qWk) > 0) {
        $is_wali_kelas_or_bk = true;
    }
    // Cek di tbl_wali_kelas (relasi terpisah)
    if (!$is_wali_kelas_or_bk) {
        $qWk2 = mysqli_query($conn, "SELECT id FROM tbl_wali_kelas WHERE nip_wali = '$nip_check_esc' LIMIT 1");
        if ($qWk2 && mysqli_num_rows($qWk2) > 0) {
            $is_wali_kelas_or_bk = true;
        }
    }
    // Cek Guru BK
    if (!$is_wali_kelas_or_bk) {
        $qBk = mysqli_query($conn, "SELECT id_guru FROM tbl_guru WHERE no_induk = '$nip_check_esc' AND (jabatan LIKE '%BK%' OR is_guru_bk = 1) LIMIT 1");
        if ($qBk && mysqli_num_rows($qBk) > 0) {
            $is_wali_kelas_or_bk = true;
        }
    }
}
?>


    <?php if ($guruLayoutVisible): ?>
        <!-- Desktop Sidebar -->
        <div class="desktop-sidebar">
            <div class="sidebar-logo">
                <img src="../../img/<?php echo $lembaga['logo']; ?>" alt="Logo">
                <span>SIMANIS</span>
            </div>
            <nav class="sidebar-nav">
                <a href="<?= guru_nav_url('guru_legacy'); ?>" class="sidebar-link"><i class="bi bi-house-door"></i> <span>Dashboard</span></a>
                <?php if($is_wali_kelas_or_bk): ?><a href="<?= guru_nav_url('validasi-izin'); ?>" class="sidebar-link"><i class="bi bi-patch-check"></i> <span>Validasi Izin</span></a><?php endif; ?>
                <a href="<?= guru_nav_url('data-siswa'); ?>" class="sidebar-link"><i class="bi bi-people"></i> <span>Data Siswa</span></a>
                <a href="<?= guru_nav_url('nilai'); ?>" class="sidebar-link"><i class="bi bi-clipboard-data"></i> <span>Input Nilai</span></a>
                <a href="<?= guru_nav_url('cetak-jurnal-guru'); ?>" class="sidebar-link"><i class="bi bi-printer"></i> <span>Cetak Jurnal</span></a>
                <a href="<?= guru_nav_url('laporan-kelas'); ?>" class="sidebar-link"><i class="bi bi-bar-chart-line"></i> <span>Laporan Kelas</span></a>
                <a href="<?= guru_nav_url('apresiasi-guru'); ?>" class="sidebar-link"><i class="bi bi-award"></i> <span>Apresiasi Guru</span></a>
                <a href="<?= guru_nav_url('ekinerja'); ?>" class="sidebar-link"><i class="bi bi-file-earmark-bar-graph"></i> <span>E-Kinerja</span></a>
            </nav>
            <div class="sidebar-footer" style="padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: auto;">
                <a href="../../logout.php" class="sidebar-link" style="color: #fca5a5;" onclick="return confirm('Yakin mau logout?');">
                    <i class="bi bi-box-arrow-right"></i> <span>Keluar</span>
                </a>
            </div>
        </div>

        <div class="app-container">
            <!-- Header -->
            <header class="app-header">
                <div class="header-content">
                    <div class="header-brand d-lg-none">
                        <img src="../../img/<?php echo $lembaga['logo']; ?>" alt="Logo" class="header-school-logo">
                        <div>
                            <div class="header-title">SIMANIS</div>
                            <span class="header-sub"><?php echo htmlspecialchars($lembaga['nmsekolah'] ?? 'Portal Guru'); ?></span>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="hdr-btn hdr-btn-notif" aria-label="Notifikasi" onclick="toggleNotifDropdown()">
                            <i class="bi bi-bell-fill"></i>
                            <?php if ($totalNotifikasi > 0): ?>
                                <span class="notif-dot"></span>
                            <?php endif; ?>
                        </button>
                        
                        <a href="<?= guru_nav_url('profil-guru'); ?>" class="hdr-profile" title="Profil Saya">
                            <?php if (empty($dataguru_header['foto'])): ?>
                                <?= get_guru_avatar_svg(get_guru_gender((string)($dataguru_header['no_induk'] ?? $nipguru_header), (string)($dataguru_header['nama_guru'] ?? 'Guru'))); ?>
                            <?php else: ?>
                                <img src="../../foto/<?php echo htmlspecialchars($dataguru_header['foto']); ?>" alt="Profile">
                            <?php endif; ?>
                        </a>
                    </div>

                    <!-- Notification Dropdown (Modern) -->
                    <div class="notif-dropdown" id="notifDropdown" style="display:none; position: absolute; top: 70px; right: 40px; width: 320px; background: white; border-radius: 16px; box-shadow: var(--shadow-lg); border: 1px solid var(--border); z-index: 1000; padding: 10px;">
                        <div style="padding: 10px; border-bottom: 1px solid var(--border); font-weight: 700;">Notifikasi</div>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php if (!empty($notifikasiData)): ?>
                                <?php foreach ($notifikasiData as $notif): ?>
                                    <div onclick="window.location='<?= htmlspecialchars($notif['link']) ?>'" style="padding: 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; display: flex; gap: 12px; align-items: flex-start;">
                                        <div style="color: var(--primary); font-size: 1.2rem;"><i class="bi <?= $notif['icon'] ?>"></i></div>
                                        <div>
                                            <div style="font-size: 0.9rem; font-weight: 600;"><?= htmlspecialchars($notif['title']) ?></div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($notif['message']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding: 20px; text-align: center; color: var(--text-muted);">Tidak ada notifikasi</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </header>

            <main class="main-content">
    <?php endif; ?>
