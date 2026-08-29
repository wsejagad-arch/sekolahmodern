<?php
session_start();
require_once '../config/database.php';

// Cek login
if(!isset($_SESSION['admin_logged_in'])) {
        header('Location: /logsman1s');

if($_SESSION['admin_role'] !== 'superadmin') {
    header('Location: index.php');
    exit;
}

// Ambil data setting awal
$setRes = $conn->query("SELECT * FROM settings WHERE id=1");
$setting = $setRes->fetch_assoc();

$message = '';

// Handle Update
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $conn->real_escape_string($_POST['phone']);
    $address_name = $conn->real_escape_string($_POST['address_name']);
    $address_text = $conn->real_escape_string($_POST['address_text']);
    $ads_code = $conn->real_escape_string($_POST['ads_code']);
    $ppdb_title = $conn->real_escape_string($_POST['ppdb_title']);
    $ppdb_subtitle = $conn->real_escape_string($_POST['ppdb_subtitle']);
    $ppdb_btn_text = $conn->real_escape_string($_POST['ppdb_btn_text']);
    $fb_link = $conn->real_escape_string($_POST['fb_link']);
    $tiktok_link = $conn->real_escape_string($_POST['tiktok_link']);
    $threads_link = $conn->real_escape_string($_POST['threads_link']);
    $youtube_link = $conn->real_escape_string($_POST['youtube_link']);
    $principal_name = $conn->real_escape_string($_POST['principal_name']);
    $principal_welcome = $conn->real_escape_string($_POST['principal_welcome']);
    $site_name = $conn->real_escape_string($_POST['site_name']);
    $site_subtitle = $conn->real_escape_string($_POST['site_subtitle']);
    $hero_title = $conn->real_escape_string($_POST['hero_title']);
    $hero_subtitle = $conn->real_escape_string($_POST['hero_subtitle']);
    $site_footer = $conn->real_escape_string($_POST['site_footer']);
    $seo_keywords = $conn->real_escape_string($_POST['seo_keywords'] ?? '');
    $seo_description = $conn->real_escape_string($_POST['seo_description'] ?? '');
    $privacy_policy = $_POST['privacy_policy'] ?? '';
    
    $logo_name = $setting['logo'];
    $hero_bg = $setting['hero_bg'];
    $principal_photo = $setting['principal_photo'];

    // Auto-fix folder uploads permissions
    $uploadPathGlobal = dirname(__DIR__) . "/uploads/";
    if (!is_dir($uploadPathGlobal)) {
        @mkdir($uploadPathGlobal, 0755, true);
    }
    if (!is_writable($uploadPathGlobal)) {
        @chmod($uploadPathGlobal, 0755);
        if (!is_writable($uploadPathGlobal)) {
            @chmod($uploadPathGlobal, 0777);
        }
    }

    // Fungsi bantuan untuk upload
    function handleUpload($fileArray, $oldFile, $prefix, &$message) {
        if(isset($fileArray) && $fileArray['error'] != 4) { // 4 = UPLOAD_ERR_NO_FILE
            if($fileArray['error'] == 1 || $fileArray['error'] == 2) {
                $message .= '<div class="alert alert-error">Gagal upload gambar: Ukuran file terlalu besar! (Maksimal 2MB atau sesuai limit server)</div>';
                return $oldFile;
            } elseif($fileArray['error'] != 0) {
                $message .= '<div class="alert alert-error">Gagal upload gambar: Terjadi kesalahan (Error Code: ' . $fileArray['error'] . ')</div>';
                return $oldFile;
            }

            $ext = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $uploadPath = dirname(__DIR__) . "/uploads/";
                if(!empty($oldFile) && file_exists($uploadPath . $oldFile) && !in_array($oldFile, ['school_banner.png', 'kepala_sekolah.png'])) {
                    @unlink($uploadPath . $oldFile);
                }
                $newName = $prefix . '_' . time() . '.' . $ext;
                if(move_uploaded_file($fileArray['tmp_name'], $uploadPath . $newName)) {
                    return $newName;
                } else {
                    $err = error_get_last();
                    $errMsg = $err ? $err['message'] : 'Unknown error';
                    $isWritable = is_writable($uploadPath) ? 'Ya' : 'Tidak';
                    $message .= '<div class="alert alert-error">Gagal menyimpan file ' . $prefix . '. Writable: ' . $isWritable . '. Path: ' . $uploadPath . '. Error: ' . $errMsg . '</div>';
                    return $oldFile;
                }
            } else {
                $message .= '<div class="alert alert-error">Format file gambar tidak didukung! (Hanya JPG, PNG, WEBP)</div>';
                return $oldFile;
            }
        }
        return $oldFile;
    }

    $hero_bg = handleUpload($_FILES['hero_bg'] ?? null, $setting['hero_bg'], 'hero', $message);
    $principal_photo = handleUpload($_FILES['principal_photo'] ?? null, $setting['principal_photo'], 'principal', $message);

    // Untuk logo ada favicon generation
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] != 4) {
        if($_FILES['logo']['error'] == 1 || $_FILES['logo']['error'] == 2) {
            $message .= '<div class="alert alert-error">Gagal upload logo: Ukuran file terlalu besar! (Maksimal 2MB)</div>';
        } elseif($_FILES['logo']['error'] != 0) {
            $message .= '<div class="alert alert-error">Gagal upload logo: Terjadi kesalahan (Error Code: ' . $_FILES['logo']['error'] . ')</div>';
        } else {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $uploadPath = dirname(__DIR__) . "/uploads/";
                if(!empty($setting['logo']) && file_exists($uploadPath . $setting['logo'])) {
                    @unlink($uploadPath . $setting['logo']);
                }
                $logo_name = 'logo_' . time() . '.' . $ext;
                $target_path = $uploadPath . $logo_name;
                if(move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                    // Generate Favicon Otomatis (64x64)
                    try {
                        if($ext == 'png') $src_img = @imagecreatefrompng($target_path);
                        elseif($ext == 'webp') $src_img = @imagecreatefromwebp($target_path);
                        else $src_img = @imagecreatefromjpeg($target_path);

                        if($src_img) {
                            $old_x = imagesx($src_img);
                            $old_y = imagesy($src_img);
                            $favicon_size = 64;
                            $favicon = imagecreatetruecolor($favicon_size, $favicon_size);
                            
                            imagealphablending($favicon, false);
                            imagesavealpha($favicon, true);
                            $transparent = imagecolorallocatealpha($favicon, 255, 255, 255, 127);
                            imagefilledrectangle($favicon, 0, 0, $favicon_size, $favicon_size, $transparent);

                            imagecopyresampled($favicon, $src_img, 0, 0, 0, 0, $favicon_size, $favicon_size, $old_x, $old_y);
                            imagepng($favicon, $uploadPath . "favicon.png");
                            imagedestroy($favicon);
                            imagedestroy($src_img);
                        }
                    } catch (Exception $e) {
                        // Silently fail favicon generation
                    }
                } else {
                    $err = error_get_last();
                    $errMsg = $err ? $err['message'] : 'Unknown error';
                    $isWritable = is_writable($uploadPath) ? 'Ya' : 'Tidak';
                    $message .= '<div class="alert alert-error">Gagal menyimpan file logo. Writable: ' . $isWritable . '. Path: ' . $uploadPath . '. Error: ' . $errMsg . '</div>';
                    $logo_name = $setting['logo']; // revert
                }
            } else {
                $message .= '<div class="alert alert-error">Format logo tidak didukung! (Hanya JPG, PNG, WEBP)</div>';
            }
        }
    }

    if(empty($message)) {
        $stmt = $conn->prepare("UPDATE settings SET phone=?, address_name=?, address_text=?, logo=?, ads_code=?, ppdb_title=?, ppdb_subtitle=?, ppdb_btn_text=?, hero_bg=?, principal_photo=?, fb_link=?, tiktok_link=?, threads_link=?, youtube_link=?, principal_name=?, principal_welcome=?, site_name=?, hero_title=?, hero_subtitle=?, site_subtitle=?, site_footer=?, seo_keywords=?, seo_description=?, privacy_policy=? WHERE id=1");
        $stmt->bind_param("ssssssssssssssssssssssss", $phone, $address_name, $address_text, $logo_name, $ads_code, $ppdb_title, $ppdb_subtitle, $ppdb_btn_text, $hero_bg, $principal_photo, $fb_link, $tiktok_link, $threads_link, $youtube_link, $principal_name, $principal_welcome, $site_name, $hero_title, $hero_subtitle, $site_subtitle, $site_footer, $seo_keywords, $seo_description, $privacy_policy);
        
        if($stmt->execute()) {
            $message = '<div class="alert alert-success">Pengaturan berhasil diperbarui!</div>';
            // Refresh data setting setelah update
            $setRes = $conn->query("SELECT * FROM settings WHERE id=1");
            $setting = $setRes->fetch_assoc();
        } else {
            $message = '<div class="alert alert-error">Gagal memperbarui pengaturan.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Web - <?= htmlspecialchars($setting['site_name']) ?></title>
    <link rel="icon" type="image/png" href="../uploads/favicon.png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>

        <div class="admin-main">
            <div class="dashboard-header">
                <h1>Pengaturan Web</h1>
                <p>Kelola informasi dasar sekolah, kontak, dan logo website Anda.</p>
            </div>

            <?= $message ?>

            <div class="admin-section">
                <h2>Informasi Sekolah & Kontak</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nama Website / Sekolah (Muncul di Header)</label>
                        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($setting['site_name']) ?>" required placeholder="Contoh: SekolahKu.">
                    </div>
                    <div class="form-group">
                        <label>Sub-judul Website (Muncul di bawah Nama Sekolah)</label>
                        <input type="text" name="site_subtitle" class="form-control" value="<?= htmlspecialchars($setting['site_subtitle']) ?>" placeholder="Contoh: Layanan Informasi akademik SMAN 1 Sumber rembang">
                    </div>
                    <div class="form-group">
                        <label>Nomor WhatsApp (Gunakan kode negara, misal: 0812...)</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($setting['phone']) ?>" required placeholder="Contoh: 081234567890">
                    </div>
                    <div class="form-group">
                        <label>Teks Footer (Copyright)</label>
                        <input type="text" name="site_footer" class="form-control" value="<?= htmlspecialchars($setting['site_footer']) ?>" placeholder="Contoh: &copy; 2026 SekolahKu. Dibuat dengan desain Modern & Minimalis.">
                    </div>
                    <div class="form-group">
                        <label>Nama Alamat / Gedung</label>
                        <input type="text" name="address_name" class="form-control" value="<?= htmlspecialchars($setting['address_name']) ?>" required placeholder="Contoh: Gedung Utama SMAN 1">
                    </div>
                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <textarea name="address_text" class="form-control" rows="3" required placeholder="Tuliskan alamat lengkap sekolah di sini..."><?= htmlspecialchars($setting['address_text']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Logo Sekolah</label>
                        <div style="display: flex; align-items: center; gap: 2rem; margin-top: 0.5rem; background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px dashed var(--admin-border);">
                            <?php if(!empty($setting['logo']) && file_exists("../uploads/" . $setting['logo'])): ?>
                                <div style="text-align: center;">
                                    <p style="font-size: 0.75rem; color: var(--admin-text-muted); margin-bottom: 0.5rem;">Logo Saat Ini</p>
                                    <img src="../uploads/<?= htmlspecialchars($setting['logo']) ?>" alt="Logo" style="height: 60px; width: auto; object-fit: contain;">
                                </div>
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; background: #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 0.75rem; text-align: center;">Belum Ada Logo</div>
                            <?php endif; ?>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 2rem;">
                        <h2 style="margin-bottom: 1rem;">Foto Header & Kepala Sekolah</h2>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                            <div>
                                <label>Foto Header / Banner (Hero)</label>
                                <div style="margin-bottom: 1rem;">
                                    <img src="../uploads/<?= htmlspecialchars($setting['hero_bg']) ?>" style="width: 100%; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
                                </div>
                                <input type="file" name="hero_bg" class="form-control" accept="image/*">
                            </div>
                            <div>
                                <label>Foto Kepala Sekolah</label>
                                <div style="margin-bottom: 1rem;">
                                    <img src="../uploads/<?= htmlspecialchars($setting['principal_photo']) ?>" style="height: 100px; width: auto; object-fit: contain; border-radius: 8px; border: 1px solid #cbd5e1; background: white;">
                                </div>
                                <input type="file" name="principal_photo" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 1.5rem;">
                            <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #1e293b;">Teks Banner Utama (Hero Section)</h3>
                            <div class="form-group">
                                <label>Judul Utama</label>
                                <input type="text" name="hero_title" class="form-control" value="<?= htmlspecialchars($setting['hero_title']) ?>" placeholder="Misal: Membangun Generasi Cemerlang">
                            </div>
                            <div class="form-group">
                                <label>Sub-judul / Deskripsi Pendek</label>
                                <textarea name="hero_subtitle" class="form-control" rows="3"><?= htmlspecialchars($setting['hero_subtitle']) ?></textarea>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1.5rem;">
                            <div class="form-group">
                                <label>Nama Kepala Sekolah</label>
                                <input type="text" name="principal_name" class="form-control" value="<?= htmlspecialchars($setting['principal_name']) ?>" placeholder="Misal: Bpk. Kepala Sekolah">
                            </div>
                            <div class="form-group">
                                <label>Teks Sambutan Singkat</label>
                                <input type="text" name="principal_welcome" class="form-control" value="<?= htmlspecialchars($setting['principal_welcome']) ?>" placeholder="Misal: Selamat Datang">
                            </div>
                        </div>
                    </div>


                    <div class="admin-section" style="margin-top: 2rem; border-top: 1px solid #e2e8f0; padding-top: 2rem;">
                        <h2 style="margin-bottom: 1rem;">Pengaturan SEO (Mesin Pencari)</h2>
                        <div class="form-group">
                            <label>Kata Kunci (Keywords) - Pisahkan dengan koma</label>
                            <input type="text" name="seo_keywords" class="form-control" value="<?= htmlspecialchars($setting['seo_keywords'] ?? '') ?>" placeholder="Misal: sekolah modern, sman 1 sumber, sekolah favorit">
                        </div>
                        <div class="form-group">
                            <label>Deskripsi Website (Meta Description)</label>
                            <textarea name="seo_description" class="form-control" rows="3" placeholder="Deskripsi singkat tentang sekolah Anda untuk Google..."><?= htmlspecialchars($setting['seo_description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="admin-section" style="margin-top: 2rem; border-top: 1px solid #e2e8f0; padding-top: 2rem;">
                        <h2 style="margin-bottom: 1rem;">Pengaturan Iklan & Banner PPDB</h2>
                        
                        <div style="background: #f1f5f9; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
                            <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #1e293b;">Banner Penerimaan Siswa Baru (PPDB)</h3>
                            <div class="form-group">
                                <label>Judul Banner</label>
                                <input type="text" name="ppdb_title" class="form-control" value="<?= htmlspecialchars($setting['ppdb_title']) ?>">
                            </div>
                            <div class="form-group">
                                <label>Sub-judul / Deskripsi</label>
                                <textarea name="ppdb_subtitle" class="form-control" rows="2"><?= htmlspecialchars($setting['ppdb_subtitle']) ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Teks Tombol</label>
                                <input type="text" name="ppdb_btn_text" class="form-control" value="<?= htmlspecialchars($setting['ppdb_btn_text']) ?>">
                            </div>
                        </div>

                        <label>Script Google Ads / AdSense / Ads Teks (300x600 dll)</label>
                        <textarea name="ads_code" class="form-control" rows="5" placeholder="Paste script iklan di sini..."><?= htmlspecialchars($setting['ads_code']) ?></textarea>
                        <small style="color: #64748b; margin-top: 0.5rem; display: block;">* Jika script iklan diisi, banner PPDB di atas akan digantikan oleh iklan di sidebar.</small>
                    </div>

                    <div class="admin-section" style="margin-top: 2rem; border-top: 1px solid #e2e8f0; padding-top: 2rem;">
                        <h2 style="margin-bottom: 1rem;">Halaman Kebijakan Privasi (Privacy Policy)</h2>
                        <div class="form-group">
                            <label>Konten Kebijakan Privasi</label>
                            <textarea name="privacy_policy" id="editor" class="form-control" rows="10"><?= htmlspecialchars($setting['privacy_policy'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="admin-section" style="margin-top: 2rem; border-top: 1px solid #e2e8f0; padding-top: 2rem;">
                        <h2 style="margin-bottom: 1rem;">Link Media Sosial</h2>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label>Link Facebook</label>
                                <input type="text" name="fb_link" class="form-control" value="<?= htmlspecialchars($setting['fb_link']) ?>" placeholder="https://facebook.com/username">
                            </div>
                            <div class="form-group">
                                <label>Link TikTok</label>
                                <input type="text" name="tiktok_link" class="form-control" value="<?= htmlspecialchars($setting['tiktok_link']) ?>" placeholder="https://tiktok.com/@username">
                            </div>
                            <div class="form-group">
                                <label>Link Threads</label>
                                <input type="text" name="threads_link" class="form-control" value="<?= htmlspecialchars($setting['threads_link']) ?>" placeholder="https://threads.net/@username">
                            </div>
                            <div class="form-group">
                                <label>Link YouTube</label>
                                <input type="text" name="youtube_link" class="form-control" value="<?= htmlspecialchars($setting['youtube_link']) ?>" placeholder="https://youtube.com/@channel">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn">Simpan Pengaturan</button>
                </form>
            </div>
        </div>
    </div>
</body>
    <script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
    <script>
        CKEDITOR.replace('editor', {
            height: 300,
            versionCheck: false
        });
    </script>
</html>
