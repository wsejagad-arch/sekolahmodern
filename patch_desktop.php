<?php
$guru = file_get_contents('pages/guru/guru_2026.php');

// We will replace everything from <!-- DESKTOP SIDEBAR --> to <!-- AKSI CEPAT -->
$pattern = '/<!-- DESKTOP SIDEBAR -->.*?<!-- AKSI CEPAT -->/s';

$replacement = '<!-- DESKTOP SIDEBAR -->
<div class="desktop-sidebar">
    <div class="desktop-logo">
        <i class="bi bi-layers-fill"></i> GuruKita
    </div>
    <div class="desktop-nav">
        <a href="?page=beranda" class="active"><i class="bi bi-house-door"></i> Dashboard</a>
        <a href="?page=kelas-saya"><i class="bi bi-calendar3"></i> Kelas Saya</a>
        <a href="inputnilai"><i class="bi bi-journal-check"></i> Nilai & Tugas</a>
        <a href="?page=siswa"><i class="bi bi-people"></i> Data Siswa</a>
        <a href="?page=materi"><i class="bi bi-book"></i> Materi</a>
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

<div class="app-shell">
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
                        <?php if($pendingIzinCount > 0): ?>
                            <a href="validasi-izin" class="notif-item unread">
                                <div class="notif-icon bg-danger text-white"><i class="bi bi-patch-check"></i></div>
                                <div class="notif-content">
                                    <div class="notif-title"><?= $pendingIzinCount ?> Menunggu Validasi Izin</div>
                                </div>
                            </a>
                        <?php endif; ?>
                        
                        <?php foreach($unfilledJadwal as $j): ?>
                            <a href="javascript:void(0)" onclick="openInputJurnal(<?= $j[\'id_mapel\'] ?>)" class="notif-item unread">
                                <div class="notif-icon bg-warning text-dark"><i class="bi bi-journal-x"></i></div>
                                <div class="notif-content">
                                    <div class="notif-title">Jurnal belum diisi: <?= htmlspecialchars($j[\'mapel\']) ?></div>
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
                <h2>Selamat Pagi,<br><?= $dataGuru["nama_guru"] ?>!</h2>
                <p><?= date("d F Y, H:i A") ?></p>
            </div>
            <div class="welcome-actions">
                <a href="?page=tambah-tugas" class="btn-dk blue">
                    <i class="bi bi-pencil-square"></i>
                    <span>Buat Tugas Baru <small>Create New Assignment</small></span>
                </a>
                <button type="button" class="btn-dk orange" onclick="openInputJurnal(<?= $nextJadwal ? $nextJadwal[\'id_mapel\'] : 0 ?>)">
                    <i class="bi bi-check-circle"></i>
                    <span>Mulai Presensi / Jurnal <small>Start Attendance</small></span>
                </button>
            </div>
        </div>
        
        <!-- Tugas Terbaru -->
        <div class="dk-widget">
            <div class="dk-widget-title">
                Tugas Terbaru
                <a href="?page=tugas">Lihat Semua</a>
            </div>
            <div class="dk-grading-list">
                <?php if(!empty($tugasTerbaru)): ?>
                    <?php foreach($tugasTerbaru as $t): ?>
                    <div class="dk-grading-card">
                        <div class="dk-grading-info">
                            <div class="dk-grading-icon"><i class="bi <?= htmlspecialchars($t[\'icon\']) ?>"></i></div>
                            <div>
                                <strong><?= htmlspecialchars($t[\'judul\']) ?> (<?= htmlspecialchars($t[\'kelas\']) ?>)</strong>
                                <span><?= htmlspecialchars($t[\'status\']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; color: #94a3b8; font-size: 0.85rem; padding: 12px;">Tidak ada tugas terbaru</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- COLUMN 2 -->
    <div style="display:flex; flex-direction:column; gap:24px;">
        <!-- Jadwal Hari Ini -->
        <div class="dk-widget">
            <div class="dk-widget-title">
                Jadwal Hari Ini
                <a href="?page=kelas-saya">Semua Jadwal</a>
            </div>
            <div class="dk-schedule-list">
                <?php if(!empty($jadwalHariIni)): ?>
                    <?php foreach($jadwalHariIni as $idx => $j): 
                        $colorClass = $idx % 3 == 0 ? \'blue\' : ($idx % 3 == 1 ? \'green\' : \'orange\');
                        $isOngoing = ($ongoingJadwal !== null && $ongoingJadwal[\'id_mapel\'] === $j[\'id_mapel\']);
                        if($isOngoing) $colorClass = \'orange\' . " border border-warning shadow-sm";
                    ?>
                    <a href="javascript:void(0)" onclick="openInputJurnal(<?= $j[\'id_mapel\'] ?>)" class="dk-schedule-card <?= $colorClass ?>" style="<?= $isOngoing ? \'background: #fffbeb;\' : \'\' ?>">
                        <div class="icon-box"><i class="bi bi-journal-text"></i></div>
                        <div class="dk-schedule-card-info">
                            <strong><?= htmlspecialchars($j[\'mapel\']) ?> - <?= htmlspecialchars($j[\'kelas\']) ?></strong>
                            <span><?= date(\'H:i\', strtotime($j[\'jam_mulai\'])) ?> - <?= date(\'H:i\', strtotime($j[\'jam_selesai\'])) ?></span>
                            <?php if($isOngoing): ?>
                                <span class="badge bg-warning text-dark ms-2" style="font-size: 0.6rem;">Berlangsung</span>
                            <?php endif; ?>
                        </div>
                        <div class="action-icon"><i class="bi bi-chevron-right"></i></div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; color: #94a3b8; font-size: 0.85rem; padding: 24px;">Tidak ada jadwal mengajar hari ini.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Statistik Ringkasan -->
        <div class="dk-widget">
            <div class="dk-widget-title">
                Statistik Mengajar
            </div>
            <div style="display: flex; gap: 24px;">
                <div style="flex:1;">
                    <div class="dk-chart-label">Total Siswa</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color:#1e293b;"><?= $totalSiswa ?> <span style="font-size:0.75rem; color:#64748b; font-weight:500;">siswa</span></div>
                    <div class="dk-chart-label" style="margin-top: 12px;">Kelas Ampu</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color:#1e293b;"><?= $totalKelasAmpu ?> <span style="font-size:0.75rem; color:#64748b; font-weight:500;">kelas</span></div>
                </div>
                <div style="flex:1;">
                    <div class="dk-chart-label">Kehadiran Hari Ini</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color:#1e293b;"><?= $hadirPct ?>%</div>
                    <div class="dk-chart-label" style="margin-top: 12px;">Progres Jurnal</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color:#1e293b;"><?= $jurnalProgress ?>%</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- COLUMN 3: MENU CEPAT (Copied from mobile Aksi Cepat) -->
    <div style="display:flex; flex-direction:column; gap:24px;">
        <!-- Aksi Cepat Desktop -->
        <div class="dk-widget" style="padding: 24px 16px;">
            <div class="dk-widget-title" style="padding: 0 8px;">Aksi Cepat</div>
            <div class="dk-quick-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 8px;">
                <!-- We inject the identical mobile icons here so they look consistent but styled for desktop grid -->
                <style>
                    .dk-quick-grid .quick-item {
                        display: flex; flex-direction: column; align-items: center; justify-content: flex-start;
                        text-align: center; text-decoration: none; padding: 12px 4px; border-radius: 12px; transition: all 0.2s;
                    }
                    .dk-quick-grid .quick-item:hover { background: #f8fafc; transform: translateY(-2px); }
                    .dk-quick-grid .quick-item i { font-size: 1.5rem; margin-bottom: 6px; }
                    .dk-quick-grid .quick-item span { font-size: 0.65rem; color: #475569; font-weight: 600; line-height: 1.2; word-wrap: break-word; }
                </style>
                
                <?php if ($isWaliKelas || $isGuruBK): ?>
                <a href="validasi-izin" class="quick-item" style="position:relative;">
                    <i class="bi bi-patch-check-fill" style="color:#dc2626;"></i>
                    <?php if ($pendingIzinCount > 0): ?>
                    <span style="position:absolute; top:2px; right:6px; background:#dc2626; color:#fff; font-size:9px; font-weight:800; min-width:16px; height:16px; border-radius:999px; display:flex; align-items:center; justify-content:center; line-height:1; padding:0 3px;"><?= $pendingIzinCount ?></span>
                    <?php endif; ?>
                    <span>Validasi<br>Izin</span>
                </a>
                <?php endif; ?>
                
                <?php if ($isPembinaLiterasi): ?>
                <a href="literasi.php" class="quick-item">
                    <i class="bi bi-book-half" style="color:#0ea5e9"></i>
                    <span>LENTERA<br>Literasi</span>
                </a>
                <?php endif; ?>
                
                <a href="?page=kehadiran" class="quick-item">
                    <i class="bi bi-calendar-check-fill" style="color:#10b981"></i>
                    <span>Data<br>Kehadiran</span>
                </a>
                <a href="?page=pelanggaran" class="quick-item">
                    <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b"></i>
                    <span>Catat<br>Pelanggaran</span>
                </a>
                
                <a href="?page=set-jadwal" class="quick-item">
                    <i class="bi bi-gear-fill" style="color:#6366f1"></i>
                    <span>Setting<br>Jadwal</span>
                </a>
                <a href="?page=materi" class="quick-item">
                    <i class="bi bi-journal-richtext" style="color:#8b5cf6"></i>
                    <span>Materi<br>Pembelajaran</span>
                </a>
                <a href="inputnilai" class="quick-item">
                    <i class="bi bi-ui-checks" style="color:#ec4899"></i>
                    <span>Nilai<br>Siswa</span>
                </a>
                
                <?php if ($isWaliKelas): ?>
                <a href="?page=walikelas" class="quick-item">
                    <i class="bi bi-person-lines-fill" style="color:#14b8a6"></i>
                    <span>Wali<br>Kelas</span>
                </a>
                <?php endif; ?>
                
                <a href="javascript:void(0)" onclick="openDashboardModal(\'#guruWaliModal\')" class="quick-item">
                    <i class="bi bi-people-fill" style="color:#f43f5e"></i>
                    <span>Guru<br>Wali</span>
                </a>
                
                <a href="monitoring_kelas" class="quick-item">
                    <i class="bi bi-display" style="color:#2563eb"></i>
                    <span>Monitoring<br>Kelas</span>
                </a>
                <a href="?page=ekstrakurikuler" class="quick-item">
                    <i class="bi bi-dribbble" style="color:#ea580c"></i>
                    <span>Ekstra<br>kurikuler</span>
                </a>
                <a href="leger" class="quick-item">
                    <i class="bi bi-clipboard-data" style="color:#059669"></i>
                    <span>Leger<br>Nilai</span>
                </a>
                <a href="file-ekin" class="quick-item">
                    <i class="bi bi-folder-fill" style="color:#4f46e5"></i>
                    <span>File<br>Ekin</span>
                </a>
                
                <a href="apresiasiguru" class="quick-item">
                    <i class="bi bi-award-fill" style="color:#f59e0b"></i>
                    <span>Apresiasi<br>Guru</span>
                </a>
                <a href="sertifikat.php" class="quick-item">
                    <i class="bi bi-patch-check-fill" style="color:#0284c7"></i>
                    <span>Piagam<br>7 KAIH</span>
                </a>
                <a href="info-wks.php" class="quick-item">
                    <i class="bi bi-info-circle-fill" style="color:#0891b2"></i>
                    <span>INFO<br>WKS</span>
                </a>
                <a href="data-siswa" class="quick-item">
                    <i class="bi bi-person-badge-fill" style="color:#64748b"></i>
                    <span>Data<br>Siswa</span>
                </a>
                <a href="?page=semua-menu" class="quick-item">
                    <i class="bi bi-grid-fill" style="color:#94a3b8"></i>
                    <span>Lainnya</span>
                </a>
            </div>
        </div>
    </div>
</div>
<!-- AKSI CEPAT -->';

$guru = preg_replace($pattern, $replacement, $guru);
file_put_contents('pages/guru/guru_2026.php', $guru);
echo "Successfully patched desktop elements to use PHP logic.\n";
