<?php
require_once 'config/database.php';

$query = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

// Ambil setting sekolah
$setRes = $conn->query("SELECT * FROM settings WHERE id=1");
$setting = $setRes->fetch_assoc();

$post_results = [];
$guru_results = [];

if (!empty($query)) {
    // Search in posts
    $postRes = $conn->query("SELECT * FROM posts WHERE title LIKE '%$query%' OR content LIKE '%$query%' ORDER BY created_at DESC");
    while($row = $postRes->fetch_assoc()) {
        $post_results[] = $row;
    }

    // Search in teachers
    $guruRes = $conn->query("SELECT g.*, m.nama_mapel 
                             FROM tbl_guru g 
                             LEFT JOIN tbl_mapel m ON g.id_mapel = m.id_mapel 
                             WHERE g.nama_guru LIKE '%$query%' OR g.jabatan LIKE '%$query%' OR m.nama_mapel LIKE '%$query%'
                             ORDER BY g.nama_guru ASC");
    while($row = $guruRes->fetch_assoc()) {
        $guru_results[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian: "<?= htmlspecialchars($query) ?>" - <?= htmlspecialchars($setting['site_name']) ?></title>
    <link rel="icon" type="image/png" href="uploads/favicon.png">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body>
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 10px; text-align: left; flex: 1;">
                <?php if(!empty($setting['logo']) && file_exists("uploads/" . $setting['logo'])): ?>
                    <img src="uploads/<?= htmlspecialchars($setting['logo']) ?>" alt="Logo" style="height: 40px; width: auto; object-fit: contain;">
                <?php endif; ?>
                <div style="display: flex; flex-direction: column; line-height: 1.1;">
                    <span style="font-size: 1.2rem; font-weight: 800; white-space: nowrap;"><?= htmlspecialchars($setting['site_name']) ?></span>
                    <span style="font-size: 0.6rem; font-weight: 400; color: #94a3b8; white-space: nowrap;"><?= htmlspecialchars($setting['site_subtitle']) ?></span>
                </div>
            </a>
            
            <div class="mobile-header-extras" style="display: none; gap: 10px; align-items: center;">
                <a href="javascript:void(0)" onclick="toggleSearch()" style="color: white; background: #3b82f6; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;" title="Cari di Situs">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                    </svg>
                </a>
                <a href="https://wa.me/<?= preg_replace('/\D/', '', $setting['phone']) ?>" style="color: white; background: #22c55e; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;" title="Contact Person">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.061 3.972L0 16l4.14-1.086A7.98 7.98 0 0 0 7.994 14.5c4.366 0 7.99-3.557 7.994-7.926a7.86 7.86 0 0 0-2.387-5.248zM7.994 13.255a6.62 6.62 0 0 1-3.371-.92l-.24-.143-2.513.658.67-2.457-.156-.248a6.622 6.622 0 0 1-1.034-3.52c.002-3.649 2.987-6.632 6.64-6.632a6.62 6.62 0 0 1 4.67 1.944 6.613 6.613 0 0 1 1.969 4.675c-.004 3.65-2.988 6.633-6.64 6.633z"/>
                    </svg>
                </a>
            </div>

            <nav>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <a href="index.php">Beranda</a>
                    <a href="guru.php">Profil Guru</a>
                    <a href="kegiatan.php">Kegiatan</a>
                </div>
                <div class="nav-contact">
                    <form action="search.php" method="GET" class="search-form-desktop" style="position: relative; display: flex; align-items: center; margin-right: 0.5rem;">
                        <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Cari..." style="padding: 0.5rem 1rem 0.5rem 2.2rem; border-radius: 12px; border: 1px solid var(--border); background: #f1f5f9; font-size: 0.85rem; width: 150px; transition: all 0.3s ease;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="var(--text-muted)" viewBox="0 0 16 16" style="position: absolute; left: 0.8rem;">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                    </form>
                    <a href="https://wa.me/<?= preg_replace('/\D/', '', $setting['phone']) ?>" class="contact-link wa-badge" target="_blank" title="Chat WhatsApp">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M12.166 8.94c-.524-.232-1.864-.92-2.111-1.012-.247-.092-.426-.138-.606.138-.18.276-.693.874-.847 1.058-.155.183-.31.206-.834-.025-1.147-.573-2.019-1.339-2.74-2.592-.183-.313-.025-.483.13-.637.14-.139.31-.362.466-.544.155-.182.206-.31.31-.516.104-.206.052-.387-.026-.544-.078-.157-.606-1.459-.83-2-.22-.524-.442-.452-.606-.46L5.34 1.5c-.18 0-.473.068-.72.338-.247.27-1.002.978-1.002 2.39 0 1.41 1.026 2.775 1.17 2.96 1.156 1.554 2.168 2.302 3.513 2.87.82.35 1.458.558 1.956.716.824.262 1.572.225 2.165.137.66-.1 1.864-.761 2.127-1.46.264-.7.264-1.3.186-1.46-.078-.158-.283-.251-.807-.483z"/>
                        </svg>
                        <span><?= htmlspecialchars($setting['phone']) ?></span>
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container" style="display: block; padding-top: 4rem; padding-bottom: 4rem;">
        <div class="section-header" style="margin-bottom: 3rem;">
            <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;">Hasil Pencarian</h1>
            <p style="color: var(--text-muted);">Menampilkan hasil untuk: <strong>"<?= htmlspecialchars($query) ?>"</strong></p>
        </div>

        <?php if (empty($post_results) && empty($guru_results)): ?>
            <div style="text-align: center; padding: 5rem 2rem; background: white; border-radius: 24px; border: 1px solid var(--border);">
                <div style="font-size: 4rem; margin-bottom: 1.5rem;">🔍</div>
                <h2>Tidak ditemukan hasil.</h2>
                <p style="color: var(--text-muted);">Coba kata kunci lain atau periksa ejaan Anda.</p>
                <a href="index.php" class="btn" style="display: inline-block; margin-top: 2rem;">Kembali ke Beranda</a>
            </div>
        <?php else: ?>
            
            <?php if (!empty($post_results)): ?>
                <section style="margin-bottom: 4rem;">
                    <h2 class="section-title" style="font-size: 1.5rem; margin-bottom: 2rem;">Berita & Artikel (<?= count($post_results) ?>)</h2>
                    <div class="main-content">
                        <?php foreach($post_results as $row): ?>
                            <article class="post-card" style="margin-bottom: 2rem;">
                                <div class="post-content">
                                    <span class="post-date"><?= date('d M Y', strtotime($row['created_at'])) ?></span>
                                    <h3 class="post-title" style="font-size: 1.5rem;"><?= htmlspecialchars($row['title']) ?></h3>
                                    <p class="post-excerpt"><?= substr(strip_tags($row['content']), 0, 200) ?>...</p>
                                    <a href="index.php#berita" class="btn-text">Selengkapnya &rarr;</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($guru_results)): ?>
                <section>
                    <h2 class="section-title" style="font-size: 1.5rem; margin-bottom: 2rem;">Tenaga Pendidik & Staf (<?= count($guru_results) ?>)</h2>
                    <div class="teacher-grid">
                        <?php foreach($guru_results as $row): ?>
                            <div class="teacher-card">
                                <div class="teacher-photo-wrapper">
                                    <?php if(!empty($row['foto']) && file_exists("uploads/" . $row['foto'])): ?>
                                        <img src="uploads/<?= htmlspecialchars($row['foto']) ?>" alt="<?= htmlspecialchars($row['nama_guru']) ?>" class="teacher-photo">
                                    <?php else: ?>
                                        <div class="teacher-photo-placeholder">Foto Belum Tersedia</div>
                                    <?php endif; ?>
                                </div>
                                <div class="teacher-info">
                                    <h3 class="teacher-name"><?= htmlspecialchars($row['nama_guru']) ?></h3>
                                    <span class="teacher-subject">
                                        <?= $row['jabatan'] != 'Guru' ? htmlspecialchars($row['jabatan']) : htmlspecialchars($row['nama_mapel'] ?: 'Umum') ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <footer>
        <p><?= $setting['site_footer'] ?></p>
    </footer>

    <?php include 'includes/mobile_nav.php'; ?>
</body>
</html>
