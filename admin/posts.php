<?php
session_start();
require_once '../config/database.php';

// Cek login
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// Handle Delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get image path to delete file
    $res = $conn->query("SELECT image FROM posts WHERE id = $id");
    $data = $res->fetch_assoc();
    if($data && !empty($data['image']) && file_exists("../uploads/posts/" . $data['image'])) {
        unlink("../uploads/posts/" . $data['image']);
    }
    
    if($conn->query("DELETE FROM posts WHERE id = $id")) {
        $message = '<div class="alert alert-success">Postingan berhasil dihapus.</div>';
    }
}

// Handle Add/Edit
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $_POST['content']; // HTML from CKEditor
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    $image_name = '';
    
    // Handle Image Upload
    if(!empty($_FILES['image']['name'])) {
        $target_dir = "../uploads/posts/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = time() . "_" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image_name);
        
        // Delete old image if editing
        if($id > 0) {
            $oldRes = $conn->query("SELECT image FROM posts WHERE id = $id");
            $oldData = $oldRes->fetch_assoc();
            if($oldData && !empty($oldData['image']) && file_exists($target_dir . $oldData['image'])) {
                unlink($target_dir . $oldData['image']);
            }
        }
    }

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
    } else {
        $message = '<div class="alert alert-error">Gagal menyimpan postingan. ' . $conn->error . '</div>';
    }
}

// Ambil data untuk edit jika ada
$editData = null;
if(isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editRes = $conn->query("SELECT * FROM posts WHERE id = $editId");
    $editData = $editRes->fetch_assoc();
}

// Ambil semua postingan
$posts = $conn->query("SELECT * FROM posts ORDER BY created_at DESC");
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
        .ai-btn {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-bottom: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .ai-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        .ai-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .preview-img {
            max-width: 200px;
            border-radius: 8px;
            margin-top: 10px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>

        <div class="admin-main">
            <div class="dashboard-header">
                <h1>Kelola Postingan</h1>
                <p>Manajemen artikel, berita, dan halaman informasi sekolah.</p>
            </div>

            <?= $message ?>

            <div class="admin-section">
                <div style="margin-bottom: 1rem;">
                    <h2><?= $editData ? 'Edit' : 'Tulis' ?> Postingan</h2>
                </div>
                
                <form method="POST" action="posts.php" enctype="multipart/form-data">
                    <?php if($editData): ?>
                        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Judul Postingan</label>
                        <input type="text" name="title" id="postTitle" class="form-control" value="<?= $editData ? htmlspecialchars($editData['title']) : '' ?>" required placeholder="Contoh: Kegiatan Upacara Bendera HUT RI">
                    </div>

                    <div class="form-group">
                        <label>Gambar Utama (Opsional)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <?php if($editData && !empty($editData['image'])): ?>
                            <img src="../uploads/posts/<?= htmlspecialchars($editData['image']) ?>" class="preview-img">
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Isi Konten</label>
                        <textarea name="content" id="editor" class="form-control" rows="10"><?= $editData ? htmlspecialchars($editData['content']) : '' ?></textarea>
                    </div>

                    <button type="submit" class="btn" style="padding: 0.8rem 2rem;"><?= $editData ? 'Update' : 'Publish' ?> Postingan</button>
                    <?php if($editData): ?>
                        <a href="posts.php" class="btn" style="background: #64748b; padding: 0.8rem 2rem;">Batal</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="admin-section">
                <h2>Daftar Postingan</h2>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Gambar</th>
                                <th>Judul</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($posts && $posts->num_rows > 0): ?>
                                <?php while($row = $posts->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span style="font-weight: 600; color: #475569;"><?= date('d M Y', strtotime($row['created_at'])) ?></span>
                                        </td>
                                        <td>
                                            <?php if(!empty($row['image'])): ?>
                                                <img src="../uploads/posts/<?= htmlspecialchars($row['image']) ?>" style="height: 40px; width: 60px; object-fit: cover; border-radius: 4px;">
                                            <?php else: ?>
                                                <span style="color: #cbd5e1; font-size: 0.8rem;">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-weight: 500;"><?= htmlspecialchars($row['title']) ?></span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="../post.php?id=<?= $row['id'] ?>" target="_blank" class="btn" style="background: #f1f5f9; color: #475569; padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px;">Lihat</a>
                                            <a href="?edit=<?= $row['id'] ?>" class="btn" style="background: #eff6ff; color: #2563eb; padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px;">Edit</a>
                                            <a href="?delete=<?= $row['id'] ?>" class="btn" style="background: #fee2e2; color: #dc2626; padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px;" onclick="return confirm('Hapus postingan ini?')">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">Belum ada postingan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
    <script>
        var editor = CKEDITOR.replace('editor', {
            height: 400,
            versionCheck: false,
            filebrowserUploadUrl: 'upload_image.php?type=Files'
        });
    </script>
</body>
</html>
