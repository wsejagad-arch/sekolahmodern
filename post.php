<?php
require_once 'config/database.php';

if(!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if(!$post) {
    header('Location: index.php');
    exit;
}

// Ambil setting sekolah
$setRes = $conn->query("SELECT * FROM settings WHERE id=1");
$setting = $setRes->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title'] ?? '') ?> - <?= htmlspecialchars($setting['site_name'] ?? '') ?></title>
    <?php if(!empty($setting['seo_keywords'])): ?>
    <meta name="keywords" content="<?= htmlspecialchars($setting['seo_keywords']) ?>">
    <?php endif; ?>
    <?php if(!empty($setting['seo_description'])): ?>
    <meta name="description" content="<?= htmlspecialchars($setting['seo_description']) ?>">
    <?php endif; ?>
    <link rel="icon" type="image/png" href="uploads/favicon.png">
    <link rel="stylesheet" href="css/style.css?v=1.1">
    <style>
        .post-detail-container {
            max-width: 800px;
            margin: 4rem auto;
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .post-detail-header {
            margin-bottom: 2rem;
        }
        .post-detail-title {
            font-size: 2.5rem;
            color: #0f172a;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        .post-detail-meta {
            color: #64748b;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .post-detail-image {
            width: 100%;
            height: auto;
            max-height: 500px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .post-detail-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #334155;
        }
        .post-detail-content p {
            margin-bottom: 1.5rem;
        }
        .post-detail-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 1.5rem 0;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: all 0.2s;
        }
        .back-btn:hover {
            transform: translateX(-5px);
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
            
            <div class="mobile-header-extras" style="display: none; gap: 10px; align-items: center;">
                <a href="https://wa.me/<?= preg_replace('/\D/', '', $setting['phone']) ?>" style="color: white; background: #22c55e; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;" title="Contact Person">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.061 3.972L0 16l4.14-1.086A7.98 7.98 0 0 0 7.994 14.5c4.366 0 7.99-3.557 7.994-7.926a7.86 7.86 0 0 0-2.387-5.248zM7.994 13.255a6.62 6.62 0 0 1-3.371-.92l-.24-.143-2.513.658.67-2.457-.156-.248a6.622 6.622 0 0 1-1.034-3.52c.002-3.649 2.987-6.632 6.64-6.632a6.62 6.62 0 0 1 4.67 1.944 6.613 6.613 0 0 1 1.969 4.675c-.004 3.65-2.988 6.633-6.64 6.633z"/>
                    </svg>
                </a>
                <a href="https://www.google.com/maps?q=<?= urlencode($setting['address_text']) ?>" target="_blank" style="color: white; background: #ef4444; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;" title="Alamat Sekolah">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                    </svg>
                </a>
            </div>
            <nav>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <a href="index.php">Beranda</a>
                    <a href="guru.php">Profil Guru</a>
                    <a href="kegiatan.php">Kegiatan</a>
                    <?php 
                    $menuRes = $conn->query("SELECT title, slug FROM pages WHERE show_in_menu = 1 ORDER BY id ASC");
                    if($menuRes && $menuRes->num_rows > 0) {
                        while($m = $menuRes->fetch_assoc()) {
                            echo '<a href="page.php?slug='.$m['slug'].'">'.htmlspecialchars($m['title']).'</a>';
                        }
                    }
                    ?>
                </div>
            </nav>
        </div>
    </header>

    <div class="container" style="display: block;">
        <div class="post-detail-container">
            <a href="index.php" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
                Kembali ke Beranda
            </a>

            <div class="post-detail-header">
                <div class="post-detail-meta">
                    <?php
                    $timestamp = strtotime($post['created_at']);
                    $date_str = ($timestamp !== false && $timestamp > 0) ? date('d F Y', $timestamp) : 'Baru saja';
                    ?>
                    <span>📅 <?= $date_str ?></span>
                    <span>✍️ Oleh Admin</span>
                </div>
                <h1 class="post-detail-title"><?= htmlspecialchars($post['title'] ?? '') ?></h1>
            </div>

            <?php 
            $image_src = '';
            if(!empty($post['image'])) {
                if(filter_var($post['image'], FILTER_VALIDATE_URL)) {
                    $image_src = $post['image'];
                } elseif(file_exists("uploads/posts/" . $post['image'])) {
                    $image_src = "uploads/posts/" . $post['image'];
                } elseif(file_exists("uploads/" . $post['image'])) {
                    $image_src = "uploads/" . $post['image'];
                }
            }
            if(!empty($image_src)): 
            ?>
                <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($post['title'] ?? '') ?>" loading="lazy" class="post-detail-image">
            <?php endif; ?>

            <div class="post-detail-content">
                <?= $post['content'] // Tampilkan HTML langsung karena ini dari CKEditor/WP ?>
            </div>

            <div class="ad-space" style="margin-top: 3rem; padding: 0; background: #f8fafc; border: 1px solid #e2e8f0; overflow: hidden; display: flex; align-items: center; justify-content: center; min-height: 200px; border-radius: 12px;">
                <?php if(!empty($setting['ads_code'])): ?>
                    <?= $setting['ads_code'] ?>
                <?php else: ?>
                    <div style="width: 100%; min-height: 200px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; display: flex; align-items: center; justify-content: space-between; padding: 2rem; gap: 2rem;">
                        <div style="flex: 1;">
                            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; color: white;"><?= htmlspecialchars($setting['ppdb_title']) ?></h3>
                            <p style="font-size: 0.9rem; opacity: 0.9;"><?= nl2br(htmlspecialchars($setting['ppdb_subtitle'])) ?></p>
                        </div>
                        <a href="https://wa.me/<?= preg_replace('/\D/', '', $setting['phone']) ?>" class="btn" style="background: white; color: #6366f1; white-space: nowrap;"><?= htmlspecialchars($setting['ppdb_btn_text']) ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer style="margin-top: 4rem;">
        <div class="footer-social" style="display: flex; justify-content: center; gap: 2rem; margin-bottom: 2rem; flex-wrap: wrap;">
            <a href="<?= htmlspecialchars($setting['fb_link']) ?>" class="social-item" style="display: flex; align-items: center; gap: 8px; color: white; text-decoration: none; font-size: 0.9rem;" target="_blank">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/></svg>
                <span>@sman1sumber.rembang</span>
            </a>
            <a href="<?= htmlspecialchars($setting['tiktok_link']) ?>" class="social-item" style="display: flex; align-items: center; gap: 8px; color: white; text-decoration: none; font-size: 0.9rem;" target="_blank">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M9 0h1.98c.144.715.54 1.617 1.235 2.512C12.895 3.389 13.797 4 15 4v2c-1.753 0-3.07-.814-4-1.829V11a5 5 0 1 1-5-5v2a3 3 0 1 0 3 3V0Z"/></svg>
                <span>@sman1sumber.rembang</span>
            </a>
            <a href="<?= htmlspecialchars($setting['threads_link']) ?>" class="social-item" style="display: flex; align-items: center; gap: 8px; color: white; text-decoration: none; font-size: 0.9rem;" target="_blank">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M6.321 6.016c-.27 0-.44.202-.44.496 0 .287.17.499.44.499.271 0 .444-.212.444-.499 0-.294-.173-.496-.444-.496ZM6.325 9.017c-.526 0-.884.356-.884.886 0 .528.358.887.884.887.524 0 .881-.359.881-.887 0-.53-.357-.886-.881-.886Z"/><path d="M11.975 8.19c-.046-.807-.203-1.477-.469-2.003-.346-.685-.852-1.1-1.503-1.24-.356-.075-.732-.074-1.13.019-.372.089-.758.264-1.15.523-.323.216-.644.492-.96.823-.28.296-.554.628-.82.992-.226.311-.447.645-.664.996-.213.347-.42.711-.618 1.096-.2.388-.39.781-.564 1.177-.178.405-.34.812-.486 1.217-.147.408-.273.806-.378 1.194-.106.39-.193.756-.261 1.095-.069.345-.118.658-.147.938-.029.273-.043.498-.043.674 0 .174.011.33.03.468.016.117.038.21.063.282.025.07.051.114.076.13.013.009.027.014.043.014.014 0 .03-.004.045-.013.031-.018.066-.053.102-.102.036-.048.077-.114.12-.195.044-.081.094-.181.146-.301.053-.12.11-.257.169-.411.06-.153.121-.325.186-.513.064-.188.134-.389.209-.604.074-.214.154-.44.24-.677.086-.238.18-.486.277-.744.098-.258.204-.524.316-.799.112-.273.232-.551.357-.828.125-.277.262-.553.405-.823.14-.271.29-.533.443-.784.156-.252.322-.49.497-.707.175-.216.355-.412.538-.584.183-.171.368-.314.55-.428.179-.115.355-.2.52-.255.391-.127.746-.162 1.066-.106.353.061.677.229.915.527.226.285.393.707.472 1.192.05.304.07.644.056 1.007-.009.233-.031.479-.066.739-.015.108-.034.216-.056.324-.022.106-.045.213-.073.32-.026.107-.054.21-.085.31-.03.1-.063.198-.096.292-.067.188-.139.365-.216.528-.077.163-.16.309-.249.438-.088.128-.184.238-.285.33-.101.093-.21.162-.325.208-.336.134-.696.185-1.011.139-.316-.045-.572-.188-.756-.408-.176-.21-.271-.533-.271-.917 0-.35.086-.695.248-.99.163-.293.385-.52.64-.67.214-.128.434-.188.654-.188.114 0 .227.015.335.046.101.031.189.074.262.128.073.054.133.118.179.188.045.07.077.143.094.221l.011.054V9.77c-.027-.51-.19-.903-.482-1.157-.254-.22-.578-.311-.901-.26-.348.055-.655.243-.846.516-.186.266-.272.611-.25 1.003.002.024.005.048.008.071l.007.071c.03.327.125.592.282.78.157.188.357.293.581.31.164.015.356-.021.57-.108.074-.03.15-.067.226-.109.075-.042.153-.09.23-.144l.078-.054.077-.054c.052-.038.106-.08.161-.129.055-.05.107-.107.155-.17.073-.096.144-.213.2-.352.059-.14.104-.303.131-.488.031-.227.044-.442.044-.641 0-.61-.19-.958-.566-.958-.178 0-.359.101-.535.3-.18.204-.33.506-.441.891-.12.414-.177.822-.177 1.23 0 .414.056.827.17 1.23.114.404.283.757.502.1042.218.283.487.426.79.426.235 0 .446-.079.625-.236.16-.139.296-.341.39-.59.047-.127.085-.26.115-.4.03-.14.052-.284.066-.433.014-.145.021-.291.021-.438 0-.399-.047-.742-.14-.1014-.093-.271-.23-.496-.403-.66-.174-.166-.39-.272-.638-.317-.21-.038-.456-.03-.717.021-.26.052-.531.158-.79.31-.258.155-.495.366-.693.621-.198.255-.357.564-.467.914-.11.35-.162.739-.153 1.15.015.689.161 1.229.437 1.604.276.375.641.577 1.082.6.452.023.901-.15 1.329-.51.427-.358.834-.881 1.203-1.547.171-.31.327-.637.47-.981l.03-.071.025-.072c.118-.344.218-.696.299-1.053.08-.357.139-.72.175-1.084.036-.364.048-.73.036-1.092a4.696 4.696 0 0 0-.15-1.083c-.07-.291-.171-.563-.301-.812a2.39 2.39 0 0 0-.505-.685c-.2-.187-.442-.317-.717-.384-.275-.067-.584-.073-.913-.016-.36.062-.7.21-1.009.439-.309.23-.585.54-.817.92-.233.38-.42.83-.553 1.336-.133.507-.204 1.066-.208 1.664-.004.56.049 1.107.16 1.623.11.515.281.97.511 1.353.23.383.521.685.86.898.338.213.725.319 1.149.319.573 0 1.025-.189 1.343-.56.317-.37.525-.92.617-1.63L11.975 8.19Z"/></svg>
                <span>@sman1sumber.rembang</span>
            </a>
            <a href="<?= htmlspecialchars($setting['youtube_link']) ?>" class="social-item" style="display: flex; align-items: center; gap: 8px; color: white; text-decoration: none; font-size: 0.9rem;" target="_blank">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8.051 1.999h.089c.822.003 4.987.033 6.11.335a2.01 2.01 0 0 1 1.415 1.42c.101.38.172.883.22 1.402l.01.104.022.26.008.104c.065.914.073 1.77.074 1.957v.075c-.001.194-.01 1.043-.074 1.957l-.008.104-.022.26-.01.104c-.048.519-.119 1.023-.22 1.402a2.007 2.007 0 0 1-1.415 1.42c-1.16.312-5.569.334-6.18.335h-.142c-.309 0-1.587-.006-2.927-.052l-.17-.006-.087-.004-.171-.007-.171-.007c-1.11-.049-2.367-.102-2.99-.268a2.011 2.011 0 0 1-1.415-1.42c-.101-.38-.172-.883-.22-1.402l-.01-.104-.022-.26-.008-.104C.065 8.952.05 8.091.05 7.904v-.075c0-.188.015-1.049.08-1.963l.008-.104.022-.26.01-.104c.048-.519.119-1.023.22-1.402a2.007 2.007 0 0 1 1.415-1.42c.487-.13 1.544-.21 2.654-.26l.17-.007.172-.006.086-.003.171-.007A99.788 99.788 0 0 1 7.858 2h.193zM6.4 5.209v4.818l4.157-2.408L6.4 5.209z"/></svg>
                <span>@sman1sumber.rembang</span>
            </a>
        </div>
        <p><?= $setting['site_footer'] ?></p>
        <p style="margin-top: 10px; font-size: 0.8rem;">
            <a href="privacy.php" style="color: #94a3b8; text-decoration: none;">Privacy Policy</a>
        </p>
    </footer>
    <?php include 'includes/mobile_nav.php'; ?>
</body>
</html>
