<?php
session_start();
require_once '../config/database.php';

// Cek login
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin-rahasia');
    exit;
}

$message = '';

// Ambil statistik
$countPosts = $conn->query("SELECT COUNT(*) as total FROM posts")->fetch_assoc()['total'];
$countTeachers = $conn->query("SELECT COUNT(*) as total FROM teachers")->fetch_assoc()['total'];

// Handle Hapus
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Ambil gambar untuk dihapus filenya
    $res = $conn->query("SELECT image FROM posts WHERE id = $id");
    if($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if(!empty($row['image']) && file_exists("../uploads/posts/".$row['image'])) {
            unlink("../uploads/posts/".$row['image']);
        }
    }

    if($conn->query("DELETE FROM posts WHERE id = $id")) {
        $message = '<div class="alert alert-success">Postingan berhasil dihapus.</div>';
    }
}

// Handle Tambah Postingan
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $image_name = '';
    
    // Handle Jadwal Tayang
    $created_at = !empty($_POST['created_at']) ? date('Y-m-d H:i:s', strtotime($_POST['created_at'])) : date('Y-m-d H:i:s');

    // Handle Upload Gambar
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $image_name = time() . '_' . $filename;
            if(!file_exists("../uploads/posts/")) mkdir("../uploads/posts/", 0777, true);
            move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/posts/" . $image_name);
        } else {
            $message = '<div class="alert alert-error">Format gambar tidak didukung! (Hanya JPG, PNG, WEBP)</div>';
        }
    }

    if(empty($message)) {
        $stmt = $conn->prepare("INSERT INTO posts (title, content, image, created_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $content, $image_name, $created_at);
        
        if($stmt->execute()) {
            $message = '<div class="alert alert-success">Postingan berhasil ditambahkan!</div>';
        } else {
            $message = '<div class="alert alert-error">Gagal menambah postingan.</div>';
        }
    }
}

// Ambil semua postingan
$posts = $conn->query("SELECT * FROM posts ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?= htmlspecialchars($setting['site_name']) ?></title>
    <link rel="icon" type="image/png" href="../uploads/favicon.png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>

        <div class="admin-main">
            <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1>Dashboard Admin</h1>
                    <p>Selamat datang kembali! Berikut adalah ringkasan sistem Anda hari ini.</p>
                </div>
                <a href="logout.php" class="btn" style="background: #ef4444; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </a>
            </div>

            <div class="stat-grid">
                <div class="stat-card">
                    <h3>Total Postingan</h3>
                    <p><?= $countPosts ?></p>
                </div>
                <?php if($_SESSION['admin_role'] === 'superadmin'): ?>
                <div class="stat-card">
                    <h3>Total Guru</h3>
                    <p><?= $countTeachers ?></p>
                </div>
                <?php endif; ?>
            </div>

            <?= $message ?>

            <div class="admin-section">
                <h2>Buat Postingan Baru</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Judul Postingan</label>
                        <input type="text" name="title" class="form-control" required placeholder="Masukkan judul postingan...">
                    </div>
                    <div class="form-group">
                        <label>Konten</label>
                        <textarea name="content" id="editor" class="form-control" rows="10" required placeholder="Tulis isi postingan di sini..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Gambar Utama (Opsional)</label>
                        <input type="file" name="image" class="form-control" accept="image/*" style="background: white; border: 1px dashed #cbd5e1; padding: 1.5rem;">
                    </div>
                    <div class="form-group">
                        <label>Jadwal Tayang (Opsional)</label>
                        <input type="datetime-local" name="created_at" class="form-control" title="Kosongkan jika ingin langsung diterbitkan saat ini juga">
                        <small style="color: #64748b; margin-top: 0.5rem; display: block;">* Jika diisi dengan waktu di masa depan, postingan tidak akan muncul di beranda sampai waktu tersebut tiba (Otomatis Terjadwal).</small>
                    </div>
                    <button type="submit" class="btn" style="padding: 0.8rem 2rem; font-size: 1rem;">Publish Postingan</button>
                </form>
            </div>

            <div class="admin-section">
                <h2>Daftar Postingan Terbaru</h2>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Judul</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($posts && $posts->num_rows > 0): ?>
                                <?php while($row = $posts->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span style="font-weight: 600; color: #475569; display: block;"><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></span>
                                            <?php if(strtotime($row['created_at']) > time()): ?>
                                                <span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; display: inline-block; margin-top: 0.3rem;">Terjadwal</span>
                                            <?php else: ?>
                                                <span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; display: inline-block; margin-top: 0.3rem;">Terbit</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-weight: 500;"><?= htmlspecialchars($row['title']) ?></span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="../post.php?id=<?= $row['id'] ?>" target="_blank" class="btn" style="background: #f1f5f9; color: #475569; padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px;">Lihat</a>
                                            <a href="posts.php?edit=<?= $row['id'] ?>" class="btn" style="background: #eff6ff; color: #2563eb; padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px;">Edit</a>
                                            <a href="?delete=<?= $row['id'] ?>" class="btn" style="background-color: #fee2e2; color: #dc2626; padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px;" onclick="return confirm('Yakin ingin menghapus postingan ini?')">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 3rem; color: #94a3b8;">Belum ada postingan</td>
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
        CKEDITOR.replace('editor', {
            height: 350,
            removeButtons: 'Save,NewPage,ExportPdf,Preview,Print,Templates,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField',
            uiColor: '#f8fafc',
            versionCheck: false,
            filebrowserUploadUrl: 'upload_image.php'
        });
    </script>
</body>
</html>
