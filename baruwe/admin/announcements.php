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
    if($conn->query("DELETE FROM announcements WHERE id = $id")) {
        $message = '<div class="alert alert-success">Pengumuman berhasil dihapus.</div>';
    }
}

// Handle Add/Edit
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $type = $conn->real_escape_string($_POST['type']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if($id > 0) {
        $stmt = $conn->prepare("UPDATE announcements SET title=?, content=?, type=? WHERE id=?");
        $stmt->bind_param("sssi", $title, $content, $type, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO announcements (title, content, type) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $content, $type);
    }

    if($stmt->execute()) {
        $message = '<div class="alert alert-success">Pengumuman berhasil disimpan!</div>';
    } else {
        $message = '<div class="alert alert-error">Gagal menyimpan pengumuman.</div>';
    }
}

// Ambil data untuk edit jika ada
$editData = null;
if(isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editRes = $conn->query("SELECT * FROM announcements WHERE id = $editId");
    $editData = $editRes->fetch_assoc();
}

// Ambil semua pengumuman
$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Pengumuman</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>

        <div class="admin-main">
            <div class="dashboard-header">
                <h1>Kelola Pengumuman</h1>
                <p>Gunakan halaman ini untuk mempublikasikan pengumuman penting bagi seluruh komunitas sekolah.</p>
            </div>

            <?= $message ?>

            <div class="admin-section">
                <h2><?= $editData ? 'Edit' : 'Buat' ?> Pengumuman Baru</h2>
                <form method="POST" action="announcements.php">
                    <?php if($editData): ?>
                        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Judul Pengumuman</label>
                        <input type="text" name="title" class="form-control" value="<?= $editData ? htmlspecialchars($editData['title']) : '' ?>" required placeholder="Masukkan judul pengumuman...">
                    </div>
                    
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="type" class="form-control">
                            <option value="Biasa" <?= $editData && $editData['type'] == 'Biasa' ? 'selected' : '' ?>>Biasa (Warna Abu-abu)</option>
                            <option value="Penting" <?= $editData && $editData['type'] == 'Penting' ? 'selected' : '' ?>>Penting (Warna Merah)</option>
                            <option value="Kegiatan" <?= $editData && $editData['type'] == 'Kegiatan' ? 'selected' : '' ?>>Kegiatan (Warna Hijau)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Isi Pengumuman</label>
                        <textarea name="content" id="editor" class="form-control" rows="5" required><?= $editData ? htmlspecialchars($editData['content']) : '' ?></textarea>
                    </div>

                    <button type="submit" class="btn" style="padding: 0.8rem 2rem;"><?= $editData ? 'Update' : 'Publish' ?> Pengumuman</button>
                    <?php if($editData): ?>
                        <a href="announcements.php" class="btn" style="background: #64748b; padding: 0.8rem 2rem;">Batal</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="admin-section">
                <h2>Daftar Pengumuman</h2>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($announcements && $announcements->num_rows > 0): ?>
                                <?php while($row = $announcements->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span style="font-weight: 600; color: #475569;"><?= date('d M Y', strtotime($row['created_at'])) ?></span>
                                        </td>
                                        <td>
                                            <span style="font-weight: 500;"><?= htmlspecialchars($row['title']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $row['type'] ?>"><?= $row['type'] ?></span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="?edit=<?= $row['id'] ?>" class="btn" style="background: #eff6ff; color: #2563eb; padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px;">Edit</a>
                                            <a href="?delete=<?= $row['id'] ?>" class="btn" style="background: #fee2e2; color: #dc2626; padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px;" onclick="return confirm('Hapus pengumuman ini?')">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">Belum ada pengumuman.</td>
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
            height: 250,
            removeButtons: 'Save,NewPage,ExportPdf,Preview,Print,Templates,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField',
            uiColor: '#f8fafc',
            versionCheck: false
        });
    </script>
</body>
</html>
