<?php
session_start();
require_once '../config/database.php';

// Cek login
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Handle Delete
if($action == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Get image path to delete file
    $res = $conn->query("SELECT image FROM posts WHERE id = $id");
    $data = $res->fetch_assoc();
    if($data && !empty($data['image'])) {
        $uploadPath = dirname(__DIR__) . "/uploads/posts/";
        if(file_exists($uploadPath . $data['image'])) {
            @unlink($uploadPath . $data['image']);
        }
    }
    
    if($conn->query("DELETE FROM posts WHERE id = $id")) {
        $message = '<div class="alert alert-success">Postingan berhasil dihapus.</div>';
    }
    $action = 'list';
}

// Handle Add/Edit Form Submit
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_post'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $_POST['content']; // HTML dari TinyMCE
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    $image_name = '';
    
    // Handle Image Upload
    if(isset($_FILES['image']) && $_FILES['image']['error'] != 4) {
        if($_FILES['image']['error'] == 0) {
            $uploadPath = dirname(__DIR__) . "/uploads/posts/";
            if (!is_dir($uploadPath)) {
                @mkdir($uploadPath, 0755, true);
            }
            
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $image_name = time() . "_" . uniqid() . "." . $ext;
                
                if(move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath . $image_name)) {
                    // Hapus gambar lama jika edit
                    if($id > 0) {
                        $oldRes = $conn->query("SELECT image FROM posts WHERE id = $id");
                        $oldData = $oldRes->fetch_assoc();
                        if($oldData && !empty($oldData['image']) && file_exists($uploadPath . $oldData['image'])) {
                            @unlink($uploadPath . $oldData['image']);
                        }
                    }
                } else {
                    $err = error_get_last();
                    $errMsg = $err ? $err['message'] : 'Permission denied';
                    $message = '<div class="alert alert-error">Gagal mengunggah thumbnail. Error: ' . $errMsg . '</div>';
                    $image_name = '';
                }
            } else {
                $message = '<div class="alert alert-error">Format gambar thumbnail tidak didukung! (Hanya JPG, PNG, WEBP)</div>';
            }
        } elseif($_FILES['image']['error'] == 1 || $_FILES['image']['error'] == 2) {
            $message = '<div class="alert alert-error">Gagal upload thumbnail: Ukuran file terlalu besar!</div>';
        }
    }

    if(empty($message)) {
        if($id > 0) {
            if(!empty($image_name)) {
                $stmt = $conn->prepare("UPDATE posts SET title=?, content=?, image=? WHERE id=?");
                $stmt->bind_param("sssi", $title, $content, $image_name, $id);
            } else {
                $stmt = $conn->prepare("UPDATE posts SET title=?, content=? WHERE id=?");
                $stmt->bind_param("ssi", $title, $content, $id);
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO posts (title, content, image) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $content, $image_name);
        }

        if($stmt->execute()) {
            $message = '<div class="alert alert-success">Postingan berhasil disimpan!</div>';
            $action = 'list';
        } else {
            $message = '<div class="alert alert-error">Gagal menyimpan postingan. ' . $conn->error . '</div>';
            $action = $id > 0 ? 'edit' : 'add';
            $_GET['id'] = $id;
        }
    } else {
        $action = $id > 0 ? 'edit' : 'add';
        $_GET['id'] = $id;
    }
}

// Ambil setting untuk sidebar
$setRes = $conn->query("SELECT * FROM settings WHERE id=1");
$setting = $setRes->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Postingan</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .preview-img {
            max-width: 300px;
            border-radius: 8px;
            margin-top: 10px;
            display: block;
            border: 1px solid #e2e8f0;
        }
        .tox-tinymce {
            border-radius: 8px !important;
            border: 1px solid #cbd5e1 !important;
            margin-bottom: 1.5rem;
        }
        /* Menghilangkan watermark tinyMCE jika muncul */
        .tox-statusbar__branding {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>

        <div class="admin-main">
            <?= $message ?>

            <?php if($action == 'list'): ?>
            <!-- ===================== DAFTAR POSTINGAN ===================== -->
            <div class="header-actions">
                <div>
                    <h1>Kelola Postingan</h1>
                    <p style="color: #64748b;">Manajemen artikel, berita, dan halaman informasi sekolah.</p>
                </div>
                <a href="?action=add" class="btn" style="background: #2563eb; color: white;">+ Tulis Postingan Baru</a>
            </div>

            <div class="admin-section">
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Thumbnail</th>
                                <th>Judul</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $posts = $conn->query("SELECT * FROM posts ORDER BY created_at DESC");
                            if($posts && $posts->num_rows > 0): 
                                while($row = $posts->fetch_assoc()): 
                            ?>
                                    <tr>
                                        <td>
                                            <span style="font-weight: 600; color: #475569;"><?= date('d M Y', strtotime($row['created_at'])) ?></span>
                                        </td>
                                        <td>
                                            <?php if(!empty($row['image'])): ?>
                                                <img src="../uploads/posts/<?= htmlspecialchars($row['image']) ?>" style="height: 50px; width: 75px; object-fit: cover; border-radius: 4px;">
                                            <?php else: ?>
                                                <span style="color: #cbd5e1; font-size: 0.8rem;">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-weight: 500; font-size: 1rem; color: #1e293b;"><?= htmlspecialchars($row['title']) ?></span>
                                        </td>
                                        <td style="text-align: right; min-width: 200px;">
                                            <a href="../post.php?id=<?= $row['id'] ?>" target="_blank" class="btn" style="background: #f1f5f9; color: #475569; padding: 0.4rem 0.8rem; font-size: 0.85rem; border-radius: 6px;">Lihat</a>
                                            <a href="?action=edit&id=<?= $row['id'] ?>" class="btn" style="background: #eff6ff; color: #2563eb; padding: 0.4rem 0.8rem; font-size: 0.85rem; border-radius: 6px;">Edit</a>
                                            <a href="?action=delete&id=<?= $row['id'] ?>" class="btn" style="background: #fee2e2; color: #dc2626; padding: 0.4rem 0.8rem; font-size: 0.85rem; border-radius: 6px;" onclick="return confirm('Apakah Anda yakin ingin menghapus postingan ini secara permanen?')">Hapus</a>
                                        </td>
                                    </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">Belum ada postingan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php else: ?>
            <!-- ===================== FORM TAMBAH/EDIT POSTINGAN ===================== -->
            <?php
            $editData = null;
            if($action == 'edit' && isset($_GET['id'])) {
                $editId = (int)$_GET['id'];
                $editRes = $conn->query("SELECT * FROM posts WHERE id = $editId");
                if($editRes && $editRes->num_rows > 0) {
                    $editData = $editRes->fetch_assoc();
                } else {
                    echo "<div class='alert alert-error'>Postingan tidak ditemukan!</div>";
                    $action = 'list';
                }
            }
            ?>
            <div class="header-actions">
                <div>
                    <h1><?= $editData ? 'Edit Postingan' : 'Tulis Postingan Baru' ?></h1>
                    <p style="color: #64748b;">Gunakan editor di bawah untuk menyusun konten Anda.</p>
                </div>
                <a href="posts.php" class="btn" style="background: #f1f5f9; color: #475569;">&larr; Kembali</a>
            </div>

            <div class="admin-section">
                <form method="POST" action="posts.php" enctype="multipart/form-data">
                    <?php if($editData): ?>
                        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #1e293b; font-size: 1.05rem;">Judul Postingan</label>
                        <input type="text" name="title" class="form-control" style="font-size: 1.1rem; padding: 0.8rem;" value="<?= $editData ? htmlspecialchars($editData['title']) : (isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '') ?>" required placeholder="Masukkan judul postingan di sini...">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600; color: #1e293b;">Isi Konten</label>
                        <!-- ID editor untuk TinyMCE -->
                        <textarea name="content" id="modern-editor" class="form-control"><?= $editData ? htmlspecialchars($editData['content']) : (isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '') ?></textarea>
                    </div>

                    <div class="form-group" style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px dashed #cbd5e1;">
                        <label style="font-weight: 600; color: #1e293b;">Gambar Thumbnail Utama (Opsional)</label>
                        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 10px;">Gambar ini akan muncul di daftar berita dan halaman depan (Maksimal 2MB).</p>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" style="background: white;">
                        <?php if($editData && !empty($editData['image'])): ?>
                            <div style="margin-top: 15px;">
                                <span style="font-size: 0.85rem; color: #64748b; display: block; margin-bottom: 5px;">Thumbnail Saat Ini:</span>
                                <img src="../uploads/posts/<?= htmlspecialchars($editData['image']) ?>" class="preview-img">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 2rem;">
                        <button type="submit" name="save_post" class="btn" style="background: #2563eb; color: white; padding: 0.8rem 2rem; font-size: 1.05rem;">
                            <?= $editData ? 'Simpan Perubahan' : 'Publish Postingan' ?>
                        </button>
                        <a href="posts.php" class="btn" style="background: #f1f5f9; color: #475569; padding: 0.8rem 2rem; font-size: 1.05rem;">Batal</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if($action != 'list'): ?>
    <!-- TinyMCE 7 CDN (Open Source Version) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#modern-editor',
            height: 500,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
            'bold italic underline forecolor | fontfamily fontsize | ' +
            'alignleft aligncenter alignright alignjustify | ' +
            'bullist numlist outdent indent | ' +
            'link image media table | ' +
            'removeformat | help',
            font_family_formats: 'Andale Mono=andale mono,times; Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Book Antiqua=book antiqua,palatino; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Symbol=symbol; Tahoma=tahoma,arial,helvetica,sans-serif; Terminal=terminal,monaco; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva; Webdings=webdings; Wingdings=wingdings,zapf dingbats',
            font_size_formats: '8pt 10pt 12pt 14pt 16pt 18pt 24pt 36pt 48pt',
            content_style: 'body { font-family: "Inter", Helvetica, Arial, sans-serif; font-size: 16px; color: #334155; line-height: 1.6; }',
            
            // Konfigurasi Upload Gambar
            images_upload_url: 'upload_image.php',
            images_upload_credentials: true,
            automatic_uploads: true,
            
            // Pengaturan Image/Media
            image_title: true,
            file_picker_types: 'image'
        });
    </script>
    <?php endif; ?>
</body>
</html>
