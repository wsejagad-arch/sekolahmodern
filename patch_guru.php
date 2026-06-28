<?php
$guru = file_get_contents('pages/guru/guru_2026.php');
$css = file_get_contents('pages/guru/css/guru-desktop.css');

// 1. Inject CSS
$guru = str_replace('</head>', "<style>\n" . $css . "\n</style>\n</head>", $guru);

// 2. Define Sidebar HTML
$sidebar = '
<!-- DESKTOP SIDEBAR -->
<div class="desktop-sidebar">
    <div class="desktop-logo">
        <i class="bi bi-layers-fill"></i> GuruKita
    </div>
    <div class="desktop-nav">
        <a href="?page=beranda" class="active"><i class="bi bi-house-door"></i> Beranda</a>
        <a href="?page=kelas-saya"><i class="bi bi-calendar3"></i> Kelas Saya</a>
        <a href="?page=siswa"><i class="bi bi-people"></i> Siswa</a>
        <a href="inputnilai"><i class="bi bi-journal-check"></i> Nilai & Tugas</a>
        <a href="?page=materi"><i class="bi bi-book"></i> Materi Pembelajaran</a>
        <a href="?page=pesan"><i class="bi bi-chat-dots"></i> Pesan</a>
        <a href="?page=pengaturan"><i class="bi bi-gear"></i> Pengaturan</a>
    </div>
    <div class="desktop-profile">
        <?php $fotoAvatar = !empty($dataGuru["foto"]) ? "../../foto/" . $dataGuru["foto"] : "../../img/avatar.png"; ?>
        <img src="<?= htmlspecialchars($fotoAvatar) ?>" alt="Profile">
        <div class="desktop-profile-info">
            <strong><?= htmlspecialchars($dataGuru["nama_guru"]) ?></strong>
            <span><?= htmlspecialchars($lembaga["nama_instansi"]) ?></span>
        </div>
    </div>
</div>
';

// Inject Sidebar before app-shell
$guru = str_replace('<div class="app-shell">', $sidebar . "\n<div class=\"app-shell\">", $guru);

// 3. Define Topbar & Grid HTML
$topbarAndGrid = '
<!-- DESKTOP TOPBAR -->
<div class="desktop-topbar">
    <h1>Dashboard</h1>
    <div class="desktop-topbar-actions">
        <a href="?page=tambah-tugas" class="topbar-btn"><i class="bi bi-calendar-plus"></i></a>
        
        <div class="notif-bell" onclick="toggleNotif(event)" style="background: #fff; color: #64748b; border: 1px solid #e2e8f0; width: 40px; height: 40px;">
            <i class="bi bi-bell"></i>
            <?php if($totalNotifCount > 0): ?>
                <span class="notif-badge"><?= $totalNotifCount ?></span>
            <?php endif; ?>
            
            <div class="notif-dropdown" id="notifDropdownDesktop" onclick="event.stopPropagation()">
                <div class="notif-header">
                    <span>Notifikasi Anda</span>
                    <?php if($totalNotifCount > 0): ?>
                        <span class="notif-badge-inline"><?= $totalNotifCount ?> Baru</span>
                    <?php endif; ?>
                </div>
                <div class="notif-list">
                    <?php if($totalNotifCount > 0): ?>
                        <?php foreach($notifList as $n): ?>
                            <a href="?page=notifikasi" class="notif-item <?= $n["is_read"] == 0 ? "unread" : "" ?>">
                                <div class="notif-icon <?= $n["is_read"] == 0 ? "bg-primary text-white" : "bg-light text-secondary" ?>">
                                    <i class="bi bi-info-circle"></i>
                                </div>
                                <div class="notif-content">
                                    <div class="notif-title"><?= htmlspecialchars($n["judul"]) ?></div>
                                    <div class="notif-time"><?= date("d M H:i", strtotime($n["created_at"])) ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 24px; text-align: center; color: #94a3b8; font-size: 0.85rem;">
                            <i class="bi bi-bell-slash" style="font-size: 2rem; margin-bottom: 8px; display: block; opacity: 0.5;"></i>
                            Belum ada notifikasi baru
                        </div>
                    <?php endif; ?>
                </div>
                <a href="?page=notifikasi" class="notif-footer">
                    Lihat Semua Notifikasi <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
        
        <img src="<?= htmlspecialchars($fotoAvatar) ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
    </div>
</div>

<!-- DESKTOP GRID LAYOUT -->
<div class="desktop-grid">
    <!-- COLUMN 1 -->
    <div style="display:flex; flex-direction:column; gap:24px;">
        <!-- Welcome Widget -->
        <div class="dk-widget welcome-widget">
            <div class="welcome-text">
                <h2>Selamat Pagi,<br><?= $dataGuru["nama_guru"] ?: "Ibu Sari" ?>!</h2>
                <p><?= date("d F Y, H:i A") ?></p>
            </div>
            <div class="welcome-actions">
                <a href="?page=tambah-tugas" class="btn-dk blue">
                    <i class="bi bi-pencil-square"></i>
                    <span>Buat Tugas Baru <small>Create New Assignment</small></span>
                </a>
                <a href="?page=jurnal" class="btn-dk orange">
                    <i class="bi bi-check-circle"></i>
                    <span>Mulai Presensi <small>Start Attendance</small></span>
                </a>
            </div>
        </div>
        
        <!-- Perlu Dinilai -->
        <div class="dk-widget">
            <div class="dk-widget-title">
                Perlu Dinilai
                <a href="#">Sein all</a>
            </div>
            <div class="dk-grading-list">
                <div class="dk-grading-card">
                    <div class="dk-grading-info">
                        <div class="dk-grading-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <div>
                            <strong>Tugas Pecahan (5A)</strong>
                            <span>Unread submissions</span>
                        </div>
                    </div>
                    <div class="dk-badge-red">12</div>
                </div>
                <div class="dk-grading-card">
                    <div class="dk-grading-info">
                        <div class="dk-grading-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <div>
                            <strong>Laporan Praktikum (4B)</strong>
                            <span>Unread submissions</span>
                        </div>
                    </div>
                    <div class="dk-badge-red">8</div>
                </div>
                <div class="dk-grading-card">
                    <div class="dk-grading-info">
                        <div class="dk-grading-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <div>
                            <strong>Puisi (5A)</strong>
                            <span>Unread submissions</span>
                        </div>
                    </div>
                    <div class="dk-badge-red">15</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- COLUMN 2 -->
    <div style="display:flex; flex-direction:column; gap:24px;">
        <!-- Jadwal Hari Ini -->
        <div class="dk-widget">
            <div class="dk-widget-title">
                Jadwal Hari Ini
                <a href="#">Menui All</a>
            </div>
            <div class="dk-schedule-list">
                <a href="#" class="dk-schedule-card blue">
                    <div class="icon-box"><i class="bi bi-calculator"></i></div>
                    <div class="dk-schedule-card-info">
                        <strong>Matematika - Kelas 5A</strong>
                        <span>08:00 - 09:30</span>
                    </div>
                    <div class="action-icon"><i class="bi bi-chevron-right"></i></div>
                </a>
                <a href="#" class="dk-schedule-card green">
                    <div class="icon-box"><i class="bi bi-flower1"></i></div>
                    <div class="dk-schedule-card-info">
                        <strong>IPA - Kelas 4B</strong>
                        <span>10:00 - 11:30</span>
                    </div>
                    <div class="action-icon"><i class="bi bi-gear-fill"></i></div>
                </a>
                <a href="#" class="dk-schedule-card orange">
                    <div class="icon-box"><i class="bi bi-book"></i></div>
                    <div class="dk-schedule-card-info">
                        <strong>Bahasa Indonesia - Kelas 5A</strong>
                        <span>13:00 - 14:30</span>
                    </div>
                    <div class="action-icon"><i class="bi bi-clock"></i></div>
                </a>
            </div>
        </div>
        
        <!-- Ringkasan Kelas -->
        <div class="dk-widget">
            <div class="dk-widget-title">
                Ringkasan Kelas
                <a href="#">Sein all</a>
            </div>
            <div style="display: flex; gap: 24px;">
                <div style="flex:1;">
                    <div class="dk-chart-label">Rata-rata Nilai</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color:#1e293b;">68.5 <span style="font-size:0.75rem; color:#64748b; font-weight:500;">per Klas</span></div>
                    <div class="dk-chart-container">
                        <svg viewBox="0 0 100 40" style="width:100%; height:40px; stroke:#3b82f6; stroke-width:2; fill:rgba(59,130,246,0.1);"><path d="M0,40 L0,30 Q20,10 40,30 T80,10 L100,5 L100,40 Z"></path></svg>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-top:4px; font-size:0.65rem; color:#94a3b8;">
                        <span>5A</span><span>5B</span><span>5A</span><span>5A</span>
                    </div>
                </div>
                <div style="flex:1;">
                    <div class="dk-chart-label">Kehadiran Siswa</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color:#1e293b;">90%</div>
                    <div class="dk-chart-container">
                        <div class="dk-chart-col"><div class="dk-bar orange" style="height: 60%"></div></div>
                        <div class="dk-chart-col"><div class="dk-bar" style="height: 40%"></div></div>
                        <div class="dk-chart-col"><div class="dk-bar orange" style="height: 80%"></div></div>
                        <div class="dk-chart-col"><div class="dk-bar" style="height: 50%"></div></div>
                        <div class="dk-chart-col"><div class="dk-bar orange" style="height: 70%"></div></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-top:4px; font-size:0.65rem; color:#94a3b8; padding: 0 4px;">
                        <span>5A</span><span>4B</span><span>5A</span><span>5A</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- COLUMN 3 -->
    <div style="display:flex; flex-direction:column; gap:24px;">
        <!-- Aktivitas Terbaru -->
        <div class="dk-widget">
            <div class="dk-widget-title">
                Aktivitas Terbaru
                <a href="#"><i class="bi bi-three-dots"></i></a>
            </div>
            <div class="dk-activity-list">
                <div class="dk-activity-item">
                    <img src="../../img/avatar.png" class="dk-activity-avatar" alt="Avatar">
                    <div class="dk-activity-info">
                        <p><strong>Budi Santoso</strong> mengumpulkan <strong>Tugas IPA</strong></p>
                        <span>2 minutes ago</span>
                    </div>
                </div>
                <div class="dk-activity-item">
                    <img src="../../img/avatar.png" class="dk-activity-avatar" alt="Avatar">
                    <div class="dk-activity-info">
                        <p><strong>Siti Rahma</strong> mengirim pesan Siti Rahma.</p>
                        <span>2 minutes ago</span>
                    </div>
                </div>
                <div class="dk-activity-item">
                    <div class="dk-activity-avatar purple"><i class="bi bi-chat-text"></i></div>
                    <div class="dk-activity-info">
                        <p><strong>Diskusi baru di Kelas 5A</strong><br>Diskusi baru nergumpulkan di Kelas 5A</p>
                        <span>2 days ago</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pengumuman Sekolah -->
        <div class="dk-widget">
            <div class="dk-widget-title">Pengumuman Sekolah</div>
            <div class="dk-announcement-card">
                <div class="dk-announcement-icon"><i class="bi bi-megaphone-fill"></i></div>
                <div class="dk-announcement-info">
                    <strong>Pengumuman Sekolah</strong>
                    <p>Pengumuman sekolah mengutrukan berbikam...</p>
                    <div style="margin-top: 8px; text-align: center;"><i class="bi bi-chevron-down" style="color: #94a3b8;"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>
';

// Inject Topbar & Grid right after <header class="hero-header">
// Wait, we can inject it right before <header class="hero-header">
$guru = str_replace('<header class="hero-header">', $topbarAndGrid . "\n<header class=\"hero-header\">", $guru);

file_put_contents('pages/guru/guru_2026.php', $guru);
echo "Successfully patched guru_2026.php\n";
