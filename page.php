<?php
require_once 'config/database.php';

$slug = isset($_GET['slug']) ? $conn->real_escape_string($_GET['slug']) : '';
if(empty($slug)) {
    header("Location: index.php");
    exit;
}

$pageRes = $conn->query("SELECT * FROM pages WHERE slug = '$slug'");
if($pageRes->num_rows == 0) {
    header("Location: index.php");
    exit;
}
$page = $pageRes->fetch_assoc();

// Ambil setting sekolah
$setRes = $conn->query("SELECT * FROM settings WHERE id=1");
$setting = $setRes->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title']) ?> - <?= htmlspecialchars($setting['site_name']) ?></title>
    <?php if(!empty($setting['seo_keywords'])): ?>
    <meta name="keywords" content="<?= htmlspecialchars($setting['seo_keywords']) ?>">
    <?php endif; ?>
    <?php if(!empty($setting['seo_description'])): ?>
    <meta name="description" content="<?= htmlspecialchars($setting['seo_description']) ?>">
    <?php endif; ?>
    <link rel="icon" type="image/png" href="uploads/favicon.png">
    <link rel="stylesheet" href="css/style.css?v=1.1">
    <style>
        .page-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            max-width: 1000px;
        }
        .page-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .page-banner {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .page-title {
            font-size: 2.5rem;
            color: #1e293b;
            margin-bottom: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 10px; text-align: left; flex: 1;">
                <?php if(!empty($setting['logo']) && file_exists("uploads/" . $setting['logo'])): ?>
                    <img src="uploads/<?= htmlspecialchars($setting['logo']) ?>" alt="Logo" loading="lazy" style="height: 40px; width: auto; object-fit: contain;">
                <?php endif; ?>
                <div style="display: flex; flex-direction: column; line-height: 1.1;">
                    <span style="font-size: 1.2rem; font-weight: 800; white-space: nowrap;"><?= htmlspecialchars($setting['site_name']) ?></span>
                    <span style="font-size: 0.6rem; font-weight: 400; color: #94a3b8; white-space: nowrap;"><?= htmlspecialchars($setting['site_subtitle']) ?></span>
                </div>
            </a>
            
            <nav>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <a href="index.php">Beranda</a>
                    <a href="guru.php">Profil Guru</a>
                    <a href="kegiatan.php">Kegiatan</a>
                    <?php 
                    // Tampilkan menu halaman dinamis
                    $menuRes = $conn->query("SELECT title, slug FROM pages WHERE show_in_menu = 1 ORDER BY id ASC");
                    if($menuRes && $menuRes->num_rows > 0) {
                        while($m = $menuRes->fetch_assoc()) {
                            $activeClass = ($slug == $m['slug']) ? 'class="active"' : '';
                            echo '<a href="page.php?slug='.$m['slug'].'" '.$activeClass.'>'.htmlspecialchars($m['title']).'</a>';
                        }
                    }
                    ?>
                </div>
            </nav>
            <div class="menu-toggle" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </header>

    <div class="mobile-menu-overlay" id="mobileMenu">
        <a href="index.php">Beranda</a>
        <a href="guru.php">Profil Guru</a>
        <a href="kegiatan.php">Kegiatan</a>
        <?php 
        if($menuRes && $menuRes->num_rows > 0) {
            $menuRes->data_seek(0);
            while($m = $menuRes->fetch_assoc()) {
                $activeClass = ($slug == $m['slug']) ? 'class="active"' : '';
                echo '<a href="page.php?slug='.$m['slug'].'" '.$activeClass.'>'.htmlspecialchars($m['title']).'</a>';
            }
        }
        ?>
    </div>

    <main class="container" style="padding-top: 100px; min-height: 70vh;">
        <div class="page-content">
            <?php if(!empty($page['image']) && file_exists("uploads/pages/" . $page['image'])): ?>
                <img src="uploads/pages/<?= htmlspecialchars($page['image']) ?>" alt="Banner Halaman" class="page-banner">
            <?php endif; ?>
            
            <h1 class="page-title"><?= htmlspecialchars($page['title']) ?></h1>
            
            <div class="content-body" style="font-size: 1.1rem; line-height: 1.8; color: #475569;">
                <?= $page['content'] ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container text-center">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($setting['site_name']) ?>. All rights reserved.</p>
            <div style="margin-top: 10px; font-size: 0.9rem;">
                <a href="privacy.php" style="color: #94a3b8; text-decoration: none; margin: 0 10px;">Kebijakan Privasi</a>
                <a href="admin/login.php" style="color: #94a3b8; text-decoration: none; margin: 0 10px;">Login Admin</a>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('active');
        }
    </script>
</body>
</html>
