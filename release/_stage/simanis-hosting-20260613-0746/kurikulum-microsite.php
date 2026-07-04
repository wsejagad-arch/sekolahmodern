<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['hak_akses'])) {
    header('location: index.php?haruslogin');
    exit;
}

include 'koneksi.php';
include 'functions.php';
require_once __DIR__ . '/kurikulum_menu_helper.php';

date_default_timezone_set('Asia/Jakarta');

$hakAkses = (int)($_SESSION['hak_akses'] ?? 0);
$noInduk = (string)($_SESSION['no_induk'] ?? ($_SESSION['username'] ?? ''));
$isAdmin = $hakAkses === 1;
$canManage = kurikulum_menu_can_manage($conn, $hakAkses, $noInduk);

if (!$isAdmin && $hakAkses !== 2) {
    http_response_code(403);
    echo '<h3 style="font-family:Arial,sans-serif;padding:20px;">403 - Akses ditolak</h3>';
    exit;
}

kurikulum_menu_ensure_table($conn);
kurikulum_menu_seed_defaults($conn, $noInduk !== '' ? $noInduk : 'system');

$flash = '';
$flashType = 'success';

$iconUploadDirRel = 'uploads/kurikulum-menu-icons';
$iconUploadDirAbs = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $iconUploadDirRel);
if (!is_dir($iconUploadDirAbs)) {
    @mkdir($iconUploadDirAbs, 0775, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canManage) {
        $flash = 'Anda tidak memiliki izin mengelola menu Kurikulum.';
        $flashType = 'danger';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $actorEsc = mysqli_real_escape_string($conn, $noInduk !== '' ? $noInduk : 'system');

        if ($action === 'save') {
            $idMenu = (int)($_POST['id_menu'] ?? 0);
            $menuTitle = trim((string)($_POST['menu_title'] ?? ''));
            $menuUrl = kurikulum_menu_normalize_url((string)($_POST['menu_url'] ?? ''));
            $menuIcon = trim((string)($_POST['menu_icon'] ?? 'bi-link-45deg'));
            $iconType = (string)($_POST['icon_type'] ?? 'bootstrap');
            $iconType = in_array($iconType, ['bootstrap', 'image'], true) ? $iconType : 'bootstrap';
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $openInNewTab = isset($_POST['open_in_new_tab']) ? 1 : 0;
            $uploadedIconPath = null;

            if ($menuTitle === '') {
                $flash = 'Nama menu wajib diisi.';
                $flashType = 'danger';
            } elseif (!kurikulum_menu_is_valid_url($menuUrl)) {
                $flash = 'Hyperlink tidak valid. Gunakan http://, https://, path lokal (.php), atau #.';
                $flashType = 'danger';
            } else {
                if (!empty($_FILES['icon_image']['name']) && (int)($_FILES['icon_image']['error'] ?? 1) !== UPLOAD_ERR_NO_FILE) {
                    $uploadErr = (int)($_FILES['icon_image']['error'] ?? 1);
                    if ($uploadErr !== UPLOAD_ERR_OK) {
                        $flash = 'Upload icon gagal. Kode error: ' . $uploadErr;
                        $flashType = 'danger';
                    } else {
                        $tmpName = (string)($_FILES['icon_image']['tmp_name'] ?? '');
                        $origName = (string)($_FILES['icon_image']['name'] ?? '');
                        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                        $allowed = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

                        if (!in_array($ext, $allowed, true)) {
                            $flash = 'Format icon harus PNG/JPG/JPEG/WEBP/GIF.';
                            $flashType = 'danger';
                        } else {
                            $size = (int)($_FILES['icon_image']['size'] ?? 0);
                            if ($size <= 0 || $size > 2 * 1024 * 1024) {
                                $flash = 'Ukuran icon maksimal 2MB.';
                                $flashType = 'danger';
                            } else {
                                $safeBase = kurikulum_menu_slugify($menuTitle);
                                $fileName = $safeBase . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
                                $targetAbs = $iconUploadDirAbs . DIRECTORY_SEPARATOR . $fileName;
                                if (!@move_uploaded_file($tmpName, $targetAbs)) {
                                    $flash = 'Gagal menyimpan file icon.';
                                    $flashType = 'danger';
                                } else {
                                    $uploadedIconPath = $iconUploadDirRel . '/' . $fileName;
                                    $iconType = 'image';
                                }
                            }
                        }
                    }
                }

                if ($flashType === 'danger' && $flash !== '') {
                    // validation/upload failed, skip SQL write
                } else {
                    if ($menuIcon === '') {
                        $menuIcon = 'bi-link-45deg';
                    }

                    $menuKeyBase = kurikulum_menu_slugify($menuTitle);
                    $menuKey = $menuKeyBase;
                    if ($idMenu <= 0) {
                        $seed = 1;
                        while (true) {
                            $menuKeyEsc = mysqli_real_escape_string($conn, $menuKey);
                            $cek = mysqli_query($conn, "SELECT id_menu FROM tbl_kurikulum_menu WHERE menu_key='{$menuKeyEsc}' LIMIT 1");
                            if ($cek && mysqli_num_rows($cek) === 0) {
                                break;
                            }
                            $seed++;
                            $menuKey = $menuKeyBase . '-' . $seed;
                        }
                    } else {
                        $menuKeyEsc = mysqli_real_escape_string($conn, $menuKey);
                        $cek = mysqli_query($conn, "SELECT id_menu FROM tbl_kurikulum_menu WHERE menu_key='{$menuKeyEsc}' AND id_menu <> {$idMenu} LIMIT 1");
                        if ($cek && mysqli_num_rows($cek) > 0) {
                            $menuKey = $menuKeyBase . '-' . $idMenu;
                        }
                    }

                    $menuKeyEsc = mysqli_real_escape_string($conn, $menuKey);
                    $menuTitleEsc = mysqli_real_escape_string($conn, $menuTitle);
                    $menuUrlEsc = mysqli_real_escape_string($conn, $menuUrl);
                    $menuIconEsc = mysqli_real_escape_string($conn, $menuIcon);
                    $iconTypeEsc = mysqli_real_escape_string($conn, $iconType);

                    if ($idMenu > 0) {
                        $currentRes = mysqli_query($conn, "SELECT icon_image_path FROM tbl_kurikulum_menu WHERE id_menu={$idMenu} LIMIT 1");
                        $current = $currentRes ? mysqli_fetch_assoc($currentRes) : null;
                        $currentIconPath = (string)($current['icon_image_path'] ?? '');

                        $iconPathSql = 'NULL';
                        if ($iconType === 'image') {
                            $finalPath = $uploadedIconPath ?: $currentIconPath;
                            if ($finalPath !== '') {
                                $iconPathSql = "'" . mysqli_real_escape_string($conn, $finalPath) . "'";
                            }
                        }

                        $ok = mysqli_query(
                            $conn,
                            "UPDATE tbl_kurikulum_menu
                             SET menu_key='{$menuKeyEsc}', menu_title='{$menuTitleEsc}', menu_url='{$menuUrlEsc}',
                                 menu_icon='{$menuIconEsc}', icon_type='{$iconTypeEsc}', icon_image_path={$iconPathSql},
                                 sort_order={$sortOrder}, is_active={$isActive}, open_in_new_tab={$openInNewTab}, updated_by='{$actorEsc}'
                             WHERE id_menu={$idMenu}"
                        );
                        if ($ok) {
                            $flash = 'Menu berhasil diperbarui.';
                            $flashType = 'success';
                        } else {
                            $flash = 'Gagal memperbarui menu: ' . mysqli_error($conn);
                            $flashType = 'danger';
                        }
                    } else {
                        $iconPathSql = 'NULL';
                        if ($iconType === 'image' && $uploadedIconPath) {
                            $iconPathSql = "'" . mysqli_real_escape_string($conn, $uploadedIconPath) . "'";
                        }

                        $ok = mysqli_query(
                            $conn,
                            "INSERT INTO tbl_kurikulum_menu (menu_key, menu_title, menu_url, menu_icon, icon_type, icon_image_path, sort_order, is_active, open_in_new_tab, created_by, updated_by)
                             VALUES ('{$menuKeyEsc}', '{$menuTitleEsc}', '{$menuUrlEsc}', '{$menuIconEsc}', '{$iconTypeEsc}', {$iconPathSql}, {$sortOrder}, {$isActive}, {$openInNewTab}, '{$actorEsc}', '{$actorEsc}')"
                        );
                        if ($ok) {
                            $flash = 'Menu baru berhasil ditambahkan.';
                            $flashType = 'success';
                        } else {
                            $flash = 'Gagal menambah menu: ' . mysqli_error($conn);
                            $flashType = 'danger';
                        }
                    }
                }
            }
        } elseif ($action === 'delete') {
            $idMenu = (int)($_POST['id_menu'] ?? 0);
            if ($idMenu > 0) {
                $qCur = mysqli_query($conn, "SELECT icon_image_path FROM tbl_kurikulum_menu WHERE id_menu={$idMenu} LIMIT 1");
                $cur = $qCur ? mysqli_fetch_assoc($qCur) : null;
                $ok = mysqli_query($conn, "DELETE FROM tbl_kurikulum_menu WHERE id_menu={$idMenu}");
                if ($ok) {
                    $iconRel = (string)($cur['icon_image_path'] ?? '');
                    if ($iconRel !== '') {
                        $iconAbs = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $iconRel);
                        if (is_file($iconAbs)) {
                            @unlink($iconAbs);
                        }
                    }
                    $flash = 'Menu berhasil dihapus.';
                } else {
                    $flash = 'Gagal menghapus menu: ' . mysqli_error($conn);
                    $flashType = 'danger';
                }
            }
        } elseif ($action === 'reset_defaults') {
            $allIconRes = mysqli_query($conn, "SELECT icon_image_path FROM tbl_kurikulum_menu WHERE icon_image_path IS NOT NULL AND icon_image_path <> ''");
            $iconPaths = [];
            if ($allIconRes) {
                while ($rowIcon = mysqli_fetch_assoc($allIconRes)) {
                    $iconPath = (string)($rowIcon['icon_image_path'] ?? '');
                    if ($iconPath !== '') {
                        $iconPaths[] = $iconPath;
                    }
                }
            }

            $deleteAll = mysqli_query($conn, "DELETE FROM tbl_kurikulum_menu");
            if (!$deleteAll) {
                $flash = 'Gagal reset menu: ' . mysqli_error($conn);
                $flashType = 'danger';
            } else {
                foreach ($iconPaths as $iconRel) {
                    $iconAbs = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $iconRel);
                    if (is_file($iconAbs)) {
                        @unlink($iconAbs);
                    }
                }

                kurikulum_menu_seed_defaults($conn, $noInduk !== '' ? $noInduk : 'system');
                $flash = 'Menu Kurikulum berhasil direset ke default.';
                $flashType = 'success';
            }
        }
    }
}

$menus = kurikulum_menu_get_items($conn, false);
$menusActive = array_values(array_filter($menus, function ($item) {
    return (int)($item['is_active'] ?? 0) === 1;
}));

$quickHomeLink = $hakAkses === 1 ? 'home.php' : guru_page('guru_legacy');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Microsite Kurikulum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8fafc !important;
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(37, 99, 235, 0.04) 0%, transparent 50%),
                radial-gradient(circle at 100% 0%, rgba(99, 102, 241, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 50% 100%, rgba(240, 180, 41, 0.02) 0%, transparent 60%),
                radial-gradient(rgba(148, 163, 184, 0.08) 1.5px, transparent 1.5px) !important;
            background-size: auto, auto, auto, 24px 24px !important;
            background-attachment: fixed !important;
            font-family: 'Segoe UI', sans-serif;
        }

        .hero {
            border-radius: 18px;
            padding: 24px;
            color: #fff;
            background: linear-gradient(135deg, #1d4ed8, #1e3a8a 60%, #0f172a);
            box-shadow: 0 16px 30px rgba(30, 64, 175, 0.35);
        }

        .menu-card {
            border: 1px solid #dbe7ff;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.07);
            transition: transform .2s ease, box-shadow .2s ease;
            height: 100%;
        }

        .menu-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
        }

        .menu-card a {
            text-decoration: none;
            color: inherit;
            display: block;
            padding: 16px;
        }

        .menu-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            font-size: 19px;
            margin-bottom: 10px;
        }

        .section-card {
            border: 1px solid #dbe7ff;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.07);
        }

        .badge-role {
            font-size: 12px;
            border-radius: 999px;
            padding: 6px 10px;
            background: #dbeafe;
            color: #1d4ed8;
            font-weight: 700;
        }

        .hero-logout-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.22);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #fee2e2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all .2s ease;
            box-shadow: 0 6px 14px rgba(127, 29, 29, 0.25);
        }

        .hero-logout-btn:hover {
            color: #ffffff;
            background: rgba(239, 68, 68, 0.35);
            transform: translateY(-1px);
        }

        .hero-logout-btn i {
            font-size: 16px;
            line-height: 1;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <div class="container py-4 py-md-5">
        <div class="hero mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:12px;">
                <div>
                    <h3 class="mb-1">Microsite Kurikulum</h3>
                    <p class="mb-0" style="opacity:.9;">Menu Kurikulum dapat dikustom oleh WKS Kurikulum, WKS Kesiswaan, WKS Humas, WKS Sarpras, dan Admin.</p>
                </div>
                <div class="d-flex align-items-center" style="gap:10px;">
                    <span class="badge-role"><?php echo $isAdmin ? 'Admin' : 'Guru'; ?></span>
                    <a href="logout.php" class="hero-logout-btn" title="Log off" aria-label="Log off" onclick="return confirm('Yakin mau logout?');">
                        <i class="bi bi-power"></i>
                    </a>
                    <a href="<?php echo htmlspecialchars($quickHomeLink); ?>" class="btn btn-light btn-sm"><i class="bi bi-house-door-fill me-1"></i> Kembali</a>
                </div>
            </div>
        </div>

        <?php if ($flash !== ''): ?>
            <div class="alert alert-<?php echo $flashType; ?> py-2"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <div class="section-card p-3 p-md-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Menu Layanan Kurikulum</h5>
                <span class="text-muted" style="font-size:13px;"><?php echo count($menusActive); ?> menu aktif</span>
            </div>

            <?php if (empty($menusActive)): ?>
                <div class="alert alert-warning mb-0">Belum ada menu aktif.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($menusActive as $menu): ?>
                        <?php
                        $url = trim((string)($menu['menu_url'] ?? '#'));
                        if ($url === '') {
                            $url = '#';
                        }
                        $isImageIcon = ((string)($menu['icon_type'] ?? 'bootstrap')) === 'image' && !empty($menu['icon_image_path']);
                        $openInNewTab = (int)($menu['open_in_new_tab'] ?? 1) === 1;
                        ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="menu-card">
                                <a href="<?php echo htmlspecialchars($url); ?>" <?php echo ($url !== '#' && $openInNewTab) ? 'target="_blank" rel="noopener"' : ''; ?>>
                                    <?php if ($isImageIcon): ?>
                                        <span class="menu-icon" style="padding:0; overflow:hidden; background:#ffffff; border:1px solid #dbeafe;">
                                            <img src="<?php echo htmlspecialchars((string)$menu['icon_image_path']); ?>" alt="icon" style="width:100%;height:100%;object-fit:cover;">
                                        </span>
                                    <?php else: ?>
                                        <span class="menu-icon"><i class="bi <?php echo htmlspecialchars((string)($menu['menu_icon'] ?? 'bi-link-45deg')); ?>"></i></span>
                                    <?php endif; ?>
                                    <div style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars((string)$menu['menu_title']); ?></div>
                                    <?php if ($url === '#'): ?>
                                        <div style="font-size:12px; color:#64748b; margin-top:2px;">Tautan belum diisi</div>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($canManage): ?>
            <div class="section-card p-3 p-md-4 mb-4">
                <h5 class="mb-3">Tambah Menu Baru</h5>
                <form method="post" class="row g-3" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save">
                    <div class="col-md-4">
                        <label class="form-label">Nama Menu</label>
                        <input type="text" name="menu_title" class="form-control" required maxlength="150" placeholder="Contoh: Bank Soal Kurikulum">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Hyperlink</label>
                        <input type="text" name="menu_url" class="form-control" placeholder="https://... / halaman.php / #">
                        <small class="text-muted">Format valid: http(s), path lokal .php, atau #</small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Icon (Bootstrap)</label>
                        <input type="text" name="menu_icon" class="form-control" value="bi-link-45deg" maxlength="60">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="sort_order" class="form-control" value="100" min="0" max="9999">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mode Icon</label>
                        <select name="icon_type" class="form-select">
                            <option value="bootstrap">Bootstrap Icon</option>
                            <option value="image">Upload Gambar</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Upload Icon Gambar</label>
                        <input type="file" name="icon_image" class="form-control" accept=".png,.jpg,.jpeg,.webp,.gif">
                        <small class="text-muted">Opsional, maksimal 2MB.</small>
                    </div>
                    <div class="col-md-12 d-flex align-items-center" style="gap:12px;">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="activeNew" name="is_active" checked>
                            <label class="form-check-label" for="activeNew">Aktif</label>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="newTabNew" name="open_in_new_tab" checked>
                            <label class="form-check-label" for="newTabNew">Buka di tab baru</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i> Tambah Menu</button>
                    </div>
                </form>
            </div>

            <div class="section-card p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px;">
                    <h5 class="mb-0">Kelola Semua Menu</h5>
                    <form method="post" onsubmit="return confirm('Reset semua menu ke default? Perubahan custom akan hilang.');" class="mb-0">
                        <input type="hidden" name="action" value="reset_defaults">
                        <button type="submit" class="btn btn-outline-warning btn-sm"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset Default</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th style="min-width:220px;">Menu</th>
                                <th style="min-width:220px;">Hyperlink</th>
                                <th style="min-width:200px;">Icon Custom</th>
                                <th style="width:90px;">Urut</th>
                                <th style="width:100px;">Target</th>
                                <th style="width:90px;">Aktif</th>
                                <th style="width:130px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menus as $menu): ?>
                                <?php $formId = 'menuForm' . (int)$menu['id_menu']; ?>
                                <tr>
                                    <td>
                                        <input type="text" name="menu_title" form="<?php echo $formId; ?>" class="form-control form-control-sm mb-1" value="<?php echo htmlspecialchars((string)$menu['menu_title']); ?>" required>
                                        <input type="text" name="menu_icon" form="<?php echo $formId; ?>" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)$menu['menu_icon']); ?>" placeholder="bi-...">
                                        <select name="icon_type" form="<?php echo $formId; ?>" class="form-select form-select-sm mt-1">
                                            <option value="bootstrap" <?php echo ((string)($menu['icon_type'] ?? 'bootstrap') === 'bootstrap') ? 'selected' : ''; ?>>Bootstrap</option>
                                            <option value="image" <?php echo ((string)($menu['icon_type'] ?? 'bootstrap') === 'image') ? 'selected' : ''; ?>>Gambar</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="menu_url" form="<?php echo $formId; ?>" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)$menu['menu_url']); ?>" placeholder="https://... / halaman.php / #">
                                    </td>
                                    <td>
                                        <?php if (!empty($menu['icon_image_path'])): ?>
                                            <div class="mb-1" style="font-size:11px;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;"><?php echo htmlspecialchars((string)$menu['icon_image_path']); ?></div>
                                        <?php endif; ?>
                                        <input type="file" name="icon_image" form="<?php echo $formId; ?>" class="form-control form-control-sm" accept=".png,.jpg,.jpeg,.webp,.gif">
                                    </td>
                                    <td>
                                        <input type="number" name="sort_order" form="<?php echo $formId; ?>" class="form-control form-control-sm" value="<?php echo (int)$menu['sort_order']; ?>" min="0" max="9999">
                                    </td>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="open_in_new_tab" form="<?php echo $formId; ?>" <?php echo ((int)($menu['open_in_new_tab'] ?? 1) === 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" style="font-size:12px;">Tab baru</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_active" form="<?php echo $formId; ?>" <?php echo ((int)$menu['is_active'] === 1) ? 'checked' : ''; ?>>
                                        </div>
                                    </td>
                                    <td>
                                        <form id="<?php echo $formId; ?>" method="post" enctype="multipart/form-data" class="mb-1">
                                            <input type="hidden" name="action" value="save">
                                            <input type="hidden" name="id_menu" value="<?php echo (int)$menu['id_menu']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-save me-1"></i> Simpan</button>
                                        </form>
                                        <form method="post" onsubmit="return confirm('Hapus menu ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_menu" value="<?php echo (int)$menu['id_menu']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash me-1"></i> Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Anda dapat melihat menu Kurikulum, namun hanya WKS Kurikulum/Kesiswaan/Humas/Sarpras atau Admin yang bisa mengubah menu.</div>
        <?php endif; ?>

        <?php include_once __DIR__ . '/components/shared_footer.php'; ?>
    </div>
</body>

</html>