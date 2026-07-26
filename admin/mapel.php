<?php
session_start();
require_once '../config/database.php';

// Cek login
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin-rahasia');
    exit;
}

$message = '';

// Handle Delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if($conn->query("DELETE FROM tbl_mapel WHERE id_mapel = $id")) {
        $message = '<div class="alert alert-success">Mata pelajaran berhasil dihapus.</div>';
    }
}

// Handle Add/Edit
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_mapel = $conn->real_escape_string($_POST['nama_mapel']);
    $id_mapel = isset($_POST['id_mapel']) ? (int)$_POST['id_mapel'] : 0;

    if($id_mapel > 0) {
        // Edit
        $stmt = $conn->prepare("UPDATE tbl_mapel SET nama_mapel = ? WHERE id_mapel = ?");
        $stmt->bind_param("si", $nama_mapel, $id_mapel);
        if($stmt->execute()) {
            $message = '<div class="alert alert-success">Mata pelajaran berhasil diperbarui.</div>';
        }
    } else {
        // Add
        $stmt = $conn->prepare("INSERT INTO tbl_mapel (nama_mapel) VALUES (?)");
        $stmt->bind_param("s", $nama_mapel);
        if($stmt->execute()) {
            $message = '<div class="alert alert-success">Mata pelajaran berhasil ditambahkan.</div>';
        }
    }
}

// Get data for edit
$editData = null;
if(isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM tbl_mapel WHERE id_mapel = $id");
    $editData = $res->fetch_assoc();
}

// Get all subjects
$subjects = $conn->query("SELECT * FROM tbl_mapel ORDER BY nama_mapel ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mata Pelajaran - AdminPanel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>

        <div class="admin-main">
            <div class="dashboard-header">
                <h1>Manajemen Mata Pelajaran</h1>
                <p>Tambah, edit, atau hapus daftar mata pelajaran yang terdaftar di sistem.</p>
            </div>

            <?= $message ?>

            <div class="admin-section">
                <h2><?= $editData ? 'Edit' : 'Tambah' ?> Mata Pelajaran</h2>
                <form method="POST" action="mapel.php">
                    <?php if($editData): ?>
                        <input type="hidden" name="id_mapel" value="<?= $editData['id_mapel'] ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Nama Mata Pelajaran</label>
                        <input type="text" name="nama_mapel" class="form-control" required 
                               value="<?= $editData ? htmlspecialchars($editData['nama_mapel']) : '' ?>"
                               placeholder="Contoh: MATEMATIKA, FISIKA, dll.">
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn"><?= $editData ? 'Perbarui' : 'Simpan' ?> Mapel</button>
                        <?php if($editData): ?>
                            <a href="mapel.php" class="btn" style="background: #94a3b8;">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="admin-section">
                <h2>Daftar Mata Pelajaran</h2>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Mata Pelajaran</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($subjects && $subjects->num_rows > 0): ?>
                                <?php while($row = $subjects->fetch_assoc()): ?>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600;"><?= $row['id_mapel'] ?></td>
                                        <td><span style="font-weight: 500;"><?= htmlspecialchars($row['nama_mapel']) ?></span></td>
                                        <td style="text-align: right;">
                                            <a href="?edit=<?= $row['id_mapel'] ?>" class="btn" style="background: #eff6ff; color: #2563eb; padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px;">Edit</a>
                                            <a href="?delete=<?= $row['id_mapel'] ?>" class="btn" style="background-color: #fee2e2; color: #dc2626; padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px;" onclick="return confirm('Yakin ingin menghapus mapel ini?')">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 3rem; color: #94a3b8;">Belum ada data mata pelajaran</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
