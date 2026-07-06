<?php

/**
 * SIMANIS Header & Footer Component
 * Include this file di setiap page untuk konsistensi tampilan
 * 
 * Usage: 
 * <?php include '../../components/header_footer.php'; ?>
 * 
 * Kemudian gunakan:
 * - render_app_header($firstName, $totalNotifikasi, $notifikasiData) untuk header
 * - render_profile_card($sapaan, $namaLengkap, $nipguru, $mapelText, $fotoProfil) untuk profile card
 * - render_bottom_nav() untuk footer
 */

function render_app_header($firstName, $totalNotifikasi = 0, $notifikasiData = [])
{
?>
    <header class="app-header">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle" title="Menu">
                <i class="bi bi-list"></i>
            </button>
            <div class="header-title">SIMANIS</div>
        </div>
        <div class="header-right">
            <!-- Notification Bell -->
            <div class="notif-wrapper">
                <button class="header-icon" id="notifBell" title="Notifikasi" onclick="toggleNotifDropdown()">
                    <i class="bi bi-bell"></i>
                    <?php if ($totalNotifikasi > 0): ?>
                        <span class="notif-badge"><?= $totalNotifikasi ?></span>
                    <?php endif; ?>
                </button>

                <!-- Notification Dropdown -->
                <div class="notif-dropdown" id="notifDropdown">
                    <?php if (!empty($notifikasiData)): ?>
                        <?php foreach ($notifikasiData as $notif): ?>
                            <div class="notif-item" onclick="window.location='<?= htmlspecialchars($notif['link']) ?>'">
                                <div class="notif-icon" style="color: <?php
                                                                        echo match ($notif['color']) {
                                                                            'warning' => '#ffc107',
                                                                            'info' => '#0dcaf0',
                                                                            'danger' => '#dc3545',
                                                                            default => '#667eea'
                                                                        };
                                                                        ?>">
                                    <i class="bi <?= $notif['icon'] ?>"></i>
                                </div>
                                <div class="notif-content">
                                    <div class="notif-title"><?= htmlspecialchars($notif['title']) ?></div>
                                    <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                                </div>
                                <span class="notif-count"><?= $notif['count'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notif-empty">
                            <i class="bi bi-check-circle" style="font-size: 2rem; color: #28a745;"></i>
                            <p style="margin-top: 10px;">Tidak ada notifikasi</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <button class="header-icon" title="Logout" type="button" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>
    </header>
<?php
}

function render_profile_card($sapaan, $namaLengkap, $nipguru, $mapelText, $fotoProfil)
{
    $hariini = ubah_nama_hari(date("Y-m-d"));
?>
    <div class="profile-card">
        <img src="<?= htmlspecialchars($fotoProfil) ?>" alt="<?= htmlspecialchars($namaLengkap) ?>" class="profile-photo" onerror="this.src='https://via.placeholder.com/100?text=<?= urlencode(explode(' ', $namaLengkap)[0]) ?>'">
        <div class="profile-info">
            <div class="profile-greeting"><?= $sapaan ?> <?= htmlspecialchars($namaLengkap) ?></div>
            <div class="profile-details">
                <div class="profile-detail-row">
                    <span class="profile-label">NIP:</span>
                    <span class="profile-value"><?= htmlspecialchars($nipguru) ?></span>
                </div>
                <div class="profile-detail-row">
                    <span class="profile-label">Mapel:</span>
                    <span class="profile-value"><?= htmlspecialchars($mapelText) ?></span>
                </div>
                <div class="profile-detail-row">
                    <span class="profile-label">Hari ini:</span>
                    <span class="profile-value"><?= $hariini . ', ' . date('d F Y') ?></span>
                </div>
            </div>
        </div>
    </div>
<?php
}

function render_bottom_nav()
{
?>
    <nav class="bottom-nav">
        <a href="guru.php" class="nav-item active" data-page="home">
            <i class="bi bi-house-fill nav-icon"></i>
            <span>Beranda</span>
        </a>
        <a href="guru_jurnal.php" class="nav-item" data-page="jurnal">
            <i class="bi bi-journal-text nav-icon"></i>
            <span>Jurnal</span>
        </a>
        <a href="nilai.php" class="nav-item" data-page="nilai">
            <i class="bi bi-file-earmark-spreadsheet nav-icon"></i>
            <span>Nilai</span>
        </a>
        <a href="presensi.php" class="nav-item" data-page="presensi">
            <i class="bi bi-clipboard-check nav-icon"></i>
            <span>Presensi</span>
        </a>
        <a href="inputtugas.php" class="nav-item" data-page="tugas">
            <i class="bi bi-clipboard-list nav-icon"></i>
            <span>Tugas</span>
        </a>
    </nav>
<?php
}

function render_header_footer_styles()
{
?>
    <style>
        /* ===== HEADER ===== */
        .app-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            color: white;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            border-bottom: 3px solid #06b6d4;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
        }

        .menu-toggle:hover {
            opacity: 0.8;
            transform: scale(1.1);
        }

        .header-title {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .header-right {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .header-icon {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .header-icon:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* ===== PROFILE CARD ===== */
        .profile-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            color: white;
            padding: 2rem 1.5rem;
            border-radius: 0;
            margin: 0 -1.5rem 0 -1.5rem;
            margin-top: 70px;
            width: calc(100% + 3rem);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
            border-bottom: 2px solid #06b6d4;
        }

        .profile-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.1);
        }

        .profile-info {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .profile-greeting {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: 0.3px;
        }

        .profile-details {
            font-size: 0.85rem;
            opacity: 0.85;
            line-height: 1.4;
        }

        .profile-detail-row {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 3px;
        }

        .profile-label {
            min-width: 50px;
            opacity: 0.75;
        }

        .profile-value {
            flex-grow: 1;
            opacity: 0.95;
            word-break: break-word;
        }

        /* ===== BOTTOM NAVIGATION ===== */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            border-top: 3px solid #06b6d4;
            display: flex;
            justify-content: space-around;
            padding: 8px 0;
            box-shadow: 0 -4px 20px rgba(16, 185, 129, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            z-index: 100;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .bottom-nav::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        }

        .bottom-nav::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            margin-top: -100px;
            margin-left: -50px;
        }

        .nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px 5px;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s;
            font-size: 0.75rem;
            gap: 4px;
            font-weight: 500;
            position: relative;
            z-index: 2;
        }

        .nav-item:hover,
        .nav-item.active {
            color: white;
            transform: translateY(-3px);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .nav-item:hover::before {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 20%;
            right: 20%;
            height: 3px;
            background: white;
            border-radius: 2px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                width: 0;
                left: 50%;
                right: 50%;
            }

            to {
                left: 20%;
                right: 20%;
            }
        }

        .nav-icon {
            font-size: 1.5rem;
        }

        body {
            padding-top: 70px;
            padding-bottom: 80px;
        }

        @media (min-width: 768px) {
            .bottom-nav {
                display: none;
            }
        }
    </style>
<?php
}
?>