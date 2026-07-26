<?php
// Ambil setting
$setRes = $conn->query("SELECT * FROM settings WHERE id=1");
$setting = $setRes->fetch_assoc();
?>
<aside class="admin-sidebar">
    <div class="sidebar-header" style="display: flex; align-items: center; gap: 10px;">
        <?php if(!empty($setting['logo']) && file_exists("../uploads/" . $setting['logo'])): ?>
            <img src="../uploads/<?= htmlspecialchars($setting['logo']) ?>" alt="Logo" style="height: 35px; width: auto; object-fit: contain;">
        <?php endif; ?>
        <a href="../index.php" class="logo" style="color:white; font-size:1.3rem; text-decoration:none; font-weight:bold;"><?= htmlspecialchars($setting['site_name']) ?></a>
    </div>
    
    <nav class="sidebar-nav">
        <a href="../index.php" target="_blank">
            <span class="menu-icon">🌐</span>
            <span class="menu-text">Lihat Tampilan Web</span>
        </a>
        <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <span class="menu-icon">📊</span>
            <span class="menu-text">Lihat Statistik / Dashboard</span>
        </a>
        <a href="posts.php" class="<?= basename($_SERVER['PHP_SELF']) == 'posts.php' ? 'active' : '' ?>">
            <span class="menu-icon">📝</span>
            <span class="menu-text">Kelola Postingan</span>
        </a>
        
        <?php if(isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin'): ?>
        <a href="pages.php" class="<?= basename($_SERVER['PHP_SELF']) == 'pages.php' ? 'active' : '' ?>">
            <span class="menu-icon">📄</span>
            <span class="menu-text">Halaman Statis</span>
        </a>
        <a href="teachers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'teachers.php' ? 'active' : '' ?>">
            <span class="menu-icon">👨‍🏫</span>
            <span class="menu-text">Data Guru</span>
        </a>
        <a href="mapel.php" class="<?= basename($_SERVER['PHP_SELF']) == 'mapel.php' ? 'active' : '' ?>">
            <span class="menu-icon">📚</span>
            <span class="menu-text">Mata Pelajaran</span>
        </a>
        <a href="announcements.php" class="<?= basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : '' ?>">
            <span class="menu-icon">📢</span>
            <span class="menu-text">Kelola Pengumuman</span>
        </a>
        <a href="#" class="<?= basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : '' ?>">
            <span class="menu-icon">🎓</span>
            <span class="menu-text">Kelola Siswa</span>
        </a>
        <a href="#" class="<?= basename($_SERVER['PHP_SELF']) == 'bot.php' ? 'active' : '' ?>">
            <span class="menu-icon">🤖</span>
            <span class="menu-text">Menu Admin Otomatis</span>
        </a>
        <a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
            <span class="menu-icon">⚙️</span>
            <span class="menu-text">Pengaturan Web</span>
        </a>
        <a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
            <span class="menu-icon">👥</span>
            <span class="menu-text">Kelola Admin</span>
        </a>
        <?php endif; ?>

        <a href="logout.php" style="color: #ef4444; margin-top: 10px;">
            <span class="menu-icon">🚪</span>
            <span class="menu-text">Logout</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="contact-info">
            <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                <span style="margin-right:10px; font-size:1.2rem;">📞</span>
                <div>
                    <div style="font-size:0.75rem; color:#94a3b8;">Telepon</div>
                    <div style="font-size:0.9rem; font-weight:bold; color:white;"><?= htmlspecialchars($setting['phone']) ?></div>
                </div>
            </div>
            <div style="display: flex; align-items: start;">
                <span style="margin-right:10px; font-size:1.2rem;">📍</span>
                <div>
                    <div style="font-size:0.75rem; color:#94a3b8;"><?= htmlspecialchars($setting['address_name']) ?></div>
                    <div style="font-size:0.8rem; color:#cbd5e1; line-height:1.2;"><?= htmlspecialchars($setting['address_text']) ?></div>
                </div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            Logout
        </a>
    </div>
</aside>
