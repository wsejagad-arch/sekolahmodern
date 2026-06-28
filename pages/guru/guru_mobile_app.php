<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["no_induk"])) {
    header("location: ../../index.php?haruslogin");
    exit;
} else if ($_SESSION['hak_akses'] != 2) {
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

// Ambil semua jadwal hari ini
$jadwalHariIni = [];
$qJ = mysqli_query($conn, "SELECT m.id_mapel, m.kelas, m.nama_mapel, m.jam_mulai, m.jam_selesai, g.foto FROM tbl_mapel_ampu m JOIN tbl_guru g ON m.no_induk=g.no_induk WHERE m.no_induk='" . $nipguru . "' AND m.hari='" . $hariini . "' ORDER BY m.jam_mulai ASC");
while ($rowJ = mysqli_fetch_assoc($qJ)) {
    $jadwalHariIni[] = $rowJ;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0d6efd">
    <title>Dashboard Guru - Mobile</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <style>
        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-user-select: none;
            user-select: none;
        }

        body,
        html {
            height: 100%;
            margin: 0;
            padding: 0;
            background: linear-gradient(to bottom, #f0f4ff, #f8f9fa);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            overflow-x: hidden;
            padding-bottom: 80px;
        }

        /* ========== HEADER ========== */
        .app-header {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: linear-gradient(135deg, #0d6efd 0%, #0856ca 100%);
            padding: 1rem 1rem;
            color: white;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 60px;
        }

        .app-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .app-header-logo {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .app-header-logo img {
            width: 32px;
            height: 32px;
            object-fit: contain;
            border-radius: 6px;
        }

        .app-header-info {
            flex: 1;
        }

        .app-header-title {
            font-size: 0.85rem;
            font-weight: 600;
            margin: 0;
            line-height: 1.2;
        }

        .app-header-subtitle {
            font-size: 0.7rem;
            opacity: 0.85;
            margin: 2px 0 0;
            line-height: 1;
        }

        .app-header-right {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .app-header-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .app-header-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ========== CONTENT ========== */
        .app-container {
            padding: 12px;
        }

        /* Alert Messages */
        .app-alert {
            border: none;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .app-alert i {
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        /* ========== GREETING CARD ========== */
        .greeting-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.2);
        }

        .greeting-text {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .greeting-subtext {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }

        /* ========== TODAY INFO ========== */
        .today-info {
            background: white;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .today-day {
            font-weight: 600;
            color: #0d6efd;
            font-size: 0.95rem;
        }

        .today-date {
            font-size: 0.85rem;
            color: #6c757d;
        }

        /* ========== QUICK ACTIONS ========== */
        .quick-actions-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #212529;
        }

        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-bottom: 24px;
        }

        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px 12px;
            border: none;
            border-radius: 14px;
            color: white;
            font-weight: 600;
            font-size: 0.75rem;
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .quick-action-btn:active {
            transform: scale(0.95);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .quick-action-btn i {
            font-size: 1.6rem;
        }

        .qa-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0856ca 100%);
        }

        .qa-success {
            background: linear-gradient(135deg, #20c997 0%, #0ca678 100%);
        }

        .qa-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .qa-info {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        }

        .qa-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }

        .qa-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        /* ========== SCHEDULE SECTION ========== */
        .schedule-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #212529;
        }

        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .schedule-card {
            background: white;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #0d6efd;
            transition: all 0.2s ease;
        }

        .schedule-card:active {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .schedule-card.schedule-hidden {
            display: none;
        }

        .schedule-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .schedule-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd, #0856ca);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }

        .schedule-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .schedule-header-info {
            flex: 1;
        }

        .schedule-class {
            font-weight: 600;
            font-size: 0.95rem;
            color: #212529;
            margin: 0;
        }

        .schedule-subject {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 4px 0 0;
        }

        .schedule-time {
            font-size: 0.8rem;
            color: #999;
            font-weight: 500;
        }

        .schedule-body {
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
        }

        .schedule-body p {
            margin: 0 0 8px;
            font-size: 0.85rem;
        }

        .schedule-file {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .schedule-file a {
            color: #dc3545;
            text-decoration: none;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .schedule-file a:active {
            opacity: 0.7;
        }

        .schedule-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .schedule-action-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .schedule-action-btn:active {
            transform: scale(0.98);
        }

        .sab-primary {
            background: #0d6efd;
            color: white;
        }

        .sab-secondary {
            background: #e9ecef;
            color: #212529;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .empty-state p {
            margin: 0;
            font-size: 0.95rem;
        }

        /* ========== BOTTOM NAVIGATION ========== */
        .app-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 8px 0;
            z-index: 1040;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08);
            height: 68px;
        }

        .app-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 8px 12px;
            color: #6c757d;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 600;
            flex: 1;
            transition: all 0.2s ease;
            border-radius: 10px;
        }

        .app-nav-item:active {
            background: #f0f4ff;
        }

        .app-nav-item.active {
            color: #0d6efd;
        }

        .app-nav-item i {
            font-size: 1.5rem;
        }

        /* ========== MODALS ========== */
        .modal-content {
            border: none;
            border-radius: 16px 16px 0 0;
        }

        .modal-header {
            border: none;
            padding: 16px;
            background: linear-gradient(135deg, #0d6efd 0%, #0856ca 100%);
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .modal-body {
            padding: 16px;
        }

        /* Utility Classes */
        .text-primary-strong {
            color: #0d6efd;
            font-weight: 600;
        }

        /* Responsif */
        @media (min-width: 768px) {
            .app-container {
                max-width: 600px;
                margin: 0 auto;
            }

            .quick-actions-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>

<body>

    <!-- ===== HEADER ===== -->
    <div class="app-header">
        <div class="app-header-left">
            <div class="app-header-logo">
                <img src="../../img/<?= $lembaga['logo']; ?>" alt="Logo">
            </div>
            <div class="app-header-info">
                <div class="app-header-title"><?= substr($lembaga['nmsekolah'], 0, 25); ?></div>
                <div class="app-header-subtitle"><?= substr($lembaga['alamat'], 0, 30); ?></div>
            </div>
        </div>
        <div class="app-header-right">
            <div class="app-header-avatar">
                <?php if (empty($dataguru['foto'])) { ?>
                    <img src="../../img/no-photo.png" alt="Profile">
                <?php } else { ?>
                    <img src="../../foto/<?= $dataguru['foto']; ?>" alt="Profile">
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- ===== CONTENT ===== -->
    <div class="app-container">
        <!-- Alert Messages -->
        <?php
        if (isset($_GET["sukses"])) {
            echo '<div class="app-alert alert-success" role="alert">';
            echo '<i class="bi bi-check-circle"></i>';
            echo '<span>Berhasil mengirim jurnal pembelajaran</span>';
            echo '</div>';
        } else if (isset($_GET["gagal"])) {
            echo '<div class="app-alert alert-danger" role="alert">';
            echo '<i class="bi bi-x-circle"></i>';
            echo '<span>Gagal mengirim jurnal</span>';
            echo '</div>';
        } else if (isset($_GET["hapusmateri"])) {
            echo '<div class="app-alert alert-success" role="alert">';
            echo '<i class="bi bi-check-circle"></i>';
            echo '<span>Berhasil menghapus jurnal</span>';
            echo '</div>';
        } else if (isset($_GET["gagalhapusmateri"])) {
            echo '<div class="app-alert alert-danger" role="alert">';
            echo '<i class="bi bi-x-circle"></i>';
            echo '<span>Gagal menghapus jurnal</span>';
            echo '</div>';
        }
        ?>

        <!-- Greeting Card -->
        <div class="greeting-card">
            <p class="greeting-text">Hai, <?= htmlspecialchars(substr($dataguru['nama_guru'] ?? ($_SESSION["nama_guru"] ?? 'Guru'), 0, 20)); ?> ðŸ‘‹</p>
            <p class="greeting-subtext">Kelola pembelajaran Anda dengan mudah</p>
        </div>

        <!-- Today Info -->
        <div class="today-info">
            <div class="today-day">
                <i class="bi bi-calendar3-event"></i>
                <?= $hariini; ?>
            </div>
            <div class="today-date"><?= tgl_indo($tglskr); ?></div>
        </div>

        <!-- Quick Actions -->
        <h6 class="quick-actions-title">Aksi Cepat</h6>
        <div class="quick-actions-grid">
            <button class="quick-action-btn qa-primary" id="qaInputJurnal">
                <i class="bi bi-journal-text"></i>
                Input Jurnal
            </button>
            <button class="quick-action-btn qa-warning" id="qaCetakJurnal">
                <i class="bi bi-printer"></i>
                Cetak Jurnal
            </button>
            <button class="quick-action-btn qa-success" id="qaInputNilai">
                <i class="bi bi-pencil-square"></i>
                Input Nilai
            </button>
            <button class="quick-action-btn qa-info" id="qaDaftarNilai">
                <i class="bi bi-bar-chart"></i>
                Daftar Nilai
            </button>
            <button class="quick-action-btn qa-secondary" id="qaDaftarPresensi">
                <i class="bi bi-clipboard2-data"></i>
                Kehadiran
            </button>
            <button class="quick-action-btn qa-danger" id="qaLaporanWali">
                <i class="bi bi-person-vcard"></i>
                Walikelas
            </button>
        </div>

        <!-- Schedule Section -->
        <h6 class="schedule-title">Jadwal Hari Ini</h6>
        <div class="schedule-list">
            <?php
            $sql = mysqli_query($conn, "SELECT * FROM tbl_mapel_ampu m JOIN tbl_guru g ON m.no_induk = g.no_induk WHERE m.no_induk='$nipguru' AND m.hari='$hariini' ORDER BY m.jam_mulai ASC");
            $cekmapel = mysqli_num_rows($sql);

            if ($cekmapel > 0) {
                while ($data = mysqli_fetch_array($sql)) {
                    $idmapel = $data['id_mapel'];
            ?>
                    <div class="schedule-card schedule-hidden" data-mulai="<?= htmlspecialchars($data['jam_mulai']); ?>" data-selesai="<?= htmlspecialchars($data['jam_selesai']); ?>">
                        <div class="schedule-header">
                            <div class="schedule-avatar">
                                <?php if ($data['foto'] == "") { ?>
                                    <img src="../../img/no-photo.png" alt="Guru">
                                <?php } else { ?>
                                    <img src="../../foto/<?= $data['foto']; ?>" alt="Guru">
                                <?php } ?>
                            </div>
                            <div class="schedule-header-info">
                                <p class="schedule-class">Kelas <?= htmlspecialchars($data['kelas']); ?></p>
                                <p class="schedule-subject"><?= htmlspecialchars($data['nama_mapel']); ?></p>
                                <p class="schedule-time"><?= $data['jam_mulai']; ?> - <?= $data['jam_selesai']; ?> WIB</p>
                            </div>
                        </div>

                        <div class="schedule-body">
                            <?php
                            $mat = mysqli_query($conn, "SELECT * FROM tbl_materi WHERE id_mapel='$idmapel' AND `tanggal`='$tglskr'");
                            if (mysqli_num_rows($mat) < 1) {
                                echo '<p style="color: #dc3545; margin-bottom: 12px;"><i class="bi bi-exclamation-circle"></i> Belum ada file materi</p>';
                            } else {
                                while ($dmat = mysqli_fetch_array($mat)) {
                            ?>
                                    <div class="schedule-file">
                                        <i class="bi bi-file-earmark-pdf-fill"></i>
                                        <a href="../../materi/<?= $dmat['file_materi']; ?>" target="_blank">
                                            <?= htmlspecialchars(substr($dmat['file_materi'], 0, 30)); ?>
                                        </a>
                                        <a href="delete-materi?id=<?= $dmat['id_materi']; ?>&file=<?= $dmat['file_materi']; ?>"
                                            class="btn btn-sm" style="padding: 2px 6px; font-size: 0.7rem; background: #fff3cd; color: #856404;"
                                            onclick="return confirm('Hapus file jurnal ini?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                            <?php }
                            } ?>

                            <div class="schedule-actions">
                                <button class="schedule-action-btn sab-primary" data-bs-toggle="modal" data-bs-target="#show" data-id="<?= $data['id_mapel']; ?>">
                                    <i class="bi bi-pencil-fill"></i> Isi Jurnal
                                </button>
                                <button class="schedule-action-btn sab-secondary" data-bs-toggle="modal" data-bs-target="#modalNilai" data-id="<?= $data['id_mapel']; ?>">
                                    <i class="bi bi-bar-chart"></i> Input Nilai
                                </button>
                            </div>
                        </div>
                    </div>
                <?php }
            } else { ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <p>Belum ada jadwal untuk hari ini</p>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- ===== MODALS ===== -->

    <!-- Modal Pilih Jadwal -->
    <div class="modal fade" id="selectJadwalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Pilih Jadwal Hari Ini</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (count($jadwalHariIni) === 0) { ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <p>Tidak ada jadwal untuk hari ini</p>
                        </div>
                    <?php } else { ?>
                        <div class="list-group">
                            <?php foreach ($jadwalHariIni as $j) { ?>
                                <div class="list-group-item" style="border: none; padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                                    <div style="margin-bottom: 8px;">
                                        <div style="font-weight: 600; color: #212529;">Kelas <?= htmlspecialchars($j['kelas']); ?> â€¢ <?= htmlspecialchars($j['nama_mapel']); ?></div>
                                        <div style="font-size: 0.85rem; color: #6c757d; margin-top: 4px;"><?= htmlspecialchars($j['jam_mulai']); ?> - <?= htmlspecialchars($j['jam_selesai']); ?> WIB</div>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <button class="btn btn-sm btn-primary btn-pilih-jurnal" data-id="<?= (int)$j['id_mapel']; ?>" style="flex: 1; font-size: 0.85rem;">Jurnal</button>
                                        <button class="btn btn-sm btn-outline-primary btn-pilih-nilai" data-id="<?= (int)$j['id_mapel']; ?>" style="flex: 1; font-size: 0.85rem;">Nilai</button>
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
        <div class="modal-dialog modal-fullscreen-sm-down modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Isi Jurnal</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-data" style="text-align: center; padding: 20px;">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                        <span class="text-muted">Memuat...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Input Nilai -->
    <div class="modal fade" id="modalNilai" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Input Nilai</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-nilai-body" style="text-align: center; padding: 20px;">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                        <span class="text-muted">Memuat...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cetak Jurnal -->
    <div class="modal fade" id="modalCetak" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Cetak Jurnal</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe src="" id="frameCetak" frameborder="0" style="width: 100%; height: 80vh; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== BOTTOM NAVIGATION ===== -->
    <div class="app-bottom-nav">
        <a href="#" class="app-nav-item active">
            <i class="bi bi-house-door-fill"></i>
            <span>Beranda</span>
        </a>
        <a href="detail-jadwal?id=<?= htmlspecialchars($dataguru['id_guru'] ?? ''); ?>&no_induk=<?= htmlspecialchars($dataguru['no_induk'] ?? $nipguru); ?>" class="app-nav-item">
            <i class="bi bi-calendar-check"></i>
            <span>Jadwal</span>
        </a>
        <a href="#" class="app-nav-item" onclick="document.querySelector('.app-container').scrollIntoView({behavior:'smooth'});">
            <i class="bi bi-arrow-up"></i>
            <span>Atas</span>
        </a>
        <a href="ekskul" class="app-nav-item">
            <i class="bi bi-dribbble"></i>
            <span>Ekskul</span>
        </a>
        <a href="../../logout.php" onclick="return confirm('Yakin mau logout?');" class="app-nav-item">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Embedded jadwal data
            window.JADWAL_TODAY = <?= json_encode(array_map(function ($x) {
                                        return [
                                            'id_mapel' => (int)$x['id_mapel'],
                                            'kelas' => $x['kelas'],
                                            'nama_mapel' => $x['nama_mapel'],
                                            'jam_mulai' => $x['jam_mulai'],
                                            'jam_selesai' => $x['jam_selesai']
                                        ];
                                    }, $jadwalHariIni)); ?>;

            // Open Input Jurnal
            function openInputJurnal(idmapel) {
                if (!idmapel) return;
                $('.modal-data').html('<div style="text-align: center; padding: 20px;"><div class="spinner-border spinner-border-sm text-primary me-2"></div><span class="text-muted">Memuat...</span></div>');
                var m = new bootstrap.Modal(document.getElementById('show'));
                m.show();
                $.post('detailmateri.php', {
                    getDetail: idmapel
                }, function(data) {
                    $('.modal-data').html(data);
                }).fail(function() {
                    $('.modal-data').html('<div class="alert alert-danger">Gagal memuat form jurnal.</div>');
                });
            }

            // Open Input Nilai
            function openInputNilai(idmapel) {
                if (!idmapel) return;
                $('.modal-nilai-body').html('<div style="text-align: center; padding: 20px;"><div class="spinner-border spinner-border-sm text-primary me-2"></div><span class="text-muted">Memuat...</span></div>');
                var m = new bootstrap.Modal(document.getElementById('modalNilai'));
                m.show();
                $.post('inputnilai.php', {
                    getDetail: idmapel
                }, function(data) {
                    $('.modal-nilai-body').html(data);
                }).fail(function() {
                    $('.modal-nilai-body').html('<div class="alert alert-danger">Gagal memuat form nilai.</div>');
                });
            }

            // Quick Actions Handlers
            $('#qaInputJurnal').on('click', function() {
                if (window.JADWAL_TODAY.length === 1) {
                    openInputJurnal(window.JADWAL_TODAY[0].id_mapel);
                } else if (window.JADWAL_TODAY.length > 1) {
                    var sm = new bootstrap.Modal(document.getElementById('selectJadwalModal'));
                    sm.show();
                }
            });

            $('#qaCetakJurnal').on('click', function() {
                $('#frameCetak').attr('src', 'cetak_jurnal');
                var m = new bootstrap.Modal(document.getElementById('modalCetak'));
                m.show();
            });

            $('#qaInputNilai').on('click', function() {
                if (window.JADWAL_TODAY.length === 1) {
                    openInputNilai(window.JADWAL_TODAY[0].id_mapel);
                } else if (window.JADWAL_TODAY.length > 1) {
                    var sm = new bootstrap.Modal(document.getElementById('selectJadwalModal'));
                    sm.show();
                }
            });

            $('#qaDaftarNilai').on('click', function() {
                window.location = 'nilai';
            });
            $('#qaDaftarPresensi').on('click', function() {
                window.location = 'rekap-kehadiran';
            });
            $('#qaLaporanWali').on('click', function() {
                window.location = 'walikelas';
            });

            // Select Jadwal Modal Buttons
            $(document).on('click', '.btn-pilih-jurnal', function() {
                var id = $(this).data('id');
                openInputJurnal(id);
                bootstrap.Modal.getInstance(document.getElementById('selectJadwalModal')).hide();
            });

            $(document).on('click', '.btn-pilih-nilai', function() {
                var id = $(this).data('id');
                openInputNilai(id);
                bootstrap.Modal.getInstance(document.getElementById('selectJadwalModal')).hide();
            });

            // Modal Loading Handler
            $('#show').on('show.bs.modal', function(e) {
                if (!e.relatedTarget || !$(e.relatedTarget).data('id')) return;
                var getDetail = $(e.relatedTarget).data('id');
                $.post('detailmateri.php', {
                    getDetail: getDetail
                }, function(data) {
                    $('.modal-data').html(data);
                });
            });

            $('#modalNilai').on('show.bs.modal', function(e) {
                if (!e.relatedTarget || !$(e.relatedTarget).data('id')) return;
                var getDetail = $(e.relatedTarget).data('id');
                $.post('inputnilai.php', {
                    getDetail: getDetail
                }, function(data) {
                    $('.modal-nilai-body').html(data);
                });
            });

            // Schedule Visibility - show jadwal setelah waktu mulai tercapai
            function parseHM(str) {
                if (!str) return null;
                str = String(str);
                var m = str.match(/(\d{1,2})\D(\d{1,2})/);
                if (m) {
                    var h = parseInt(m[1], 10),
                        mi = parseInt(m[2], 10);
                    if (!isNaN(h) && !isNaN(mi)) return h * 60 + mi;
                }
                var m2 = str.match(/(\d{1,2})/g);
                if (m2 && m2.length >= 2) {
                    var h2 = parseInt(m2[0], 10),
                        mi2 = parseInt(m2[1], 10);
                    if (!isNaN(h2) && !isNaN(mi2)) return h2 * 60 + mi2;
                }
                return null;
            }

            function updateScheduleVisibility() {
                var now = new Date();
                var minutesNow = now.getHours() * 60 + now.getMinutes();
                document.querySelectorAll('.schedule-card').forEach(function(card) {
                    var mulai = parseHM(card.getAttribute('data-mulai'));
                    if (mulai === null) {
                        card.classList.remove('schedule-hidden');
                        return;
                    }
                    if (minutesNow >= mulai) {
                        card.classList.remove('schedule-hidden');
                    } else {
                        card.classList.add('schedule-hidden');
                    }
                });
            }

            updateScheduleVisibility();
            setInterval(updateScheduleVisibility, 30000);
        });
    </script>

<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>

</html>
