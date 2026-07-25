<?php
session_start();
require_once '../config/database.php';

// Cek login
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// Handle Hapus Semua
if(isset($_GET['delete_all'])) {
    $res = $conn->query("SELECT foto FROM tbl_guru");
    while($row = $res->fetch_assoc()) {
        if(!empty($row['foto']) && file_exists("../uploads/".$row['foto'])) {
            unlink("../uploads/".$row['foto']);
        }
    }
    if($conn->query("TRUNCATE TABLE tbl_guru")) {
        $message = '<div class="alert alert-success">Semua data guru berhasil dihapus beserta fotonya.</div>';
    }
}

// Handle Hapus Terpilih
if(isset($_POST['delete_selected']) && !empty($_POST['selected_ids'])) {
    $ids = $_POST['selected_ids'];
    $ids = array_map('intval', $ids);
    $id_list = implode(',', $ids);
    
    $res = $conn->query("SELECT foto FROM tbl_guru WHERE id_guru IN ($id_list)");
    while($row = $res->fetch_assoc()) {
        if(!empty($row['foto']) && file_exists("../uploads/".$row['foto'])) {
            unlink("../uploads/".$row['foto']);
        }
    }
    
    if($conn->query("DELETE FROM tbl_guru WHERE id_guru IN ($id_list)")) {
        $message = '<div class="alert alert-success">'.count($ids).' data guru terpilih berhasil dihapus.</div>';
    }
}

// Handle Hapus Satu Data
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Ambil gambar untuk dihapus filenya
    $res = $conn->query("SELECT foto FROM tbl_guru WHERE id_guru = $id");
    if($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if(!empty($row['foto']) && file_exists("../uploads/".$row['foto'])) {
            unlink("../uploads/".$row['foto']);
        }
    }

    if($conn->query("DELETE FROM tbl_guru WHERE id_guru = $id")) {
        $message = '<div class="alert alert-success">Data guru berhasil dihapus.</div>';
    }
}


// Handle Tambah/Edit Guru
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nama_guru'])) {
    $id_guru = isset($_POST['id_guru']) ? (int)$_POST['id_guru'] : 0;
    $nama_guru = $conn->real_escape_string($_POST['nama_guru']);
    $id_mapel = (int)$_POST['id_mapel'];
    $no_induk = $conn->real_escape_string($_POST['no_induk']);
    $nip_guru = $conn->real_escape_string($_POST['nip_guru']);
    $no_wa = $conn->real_escape_string($_POST['no_wa']);
    $status_kepegawaian = $conn->real_escape_string($_POST['status_kepegawaian']);
    $status = $conn->real_escape_string($_POST['status']);
    $is_guru_bk = isset($_POST['is_guru_bk']) ? 1 : 0;
    $walas = $conn->real_escape_string($_POST['walas']);
    $jabatan = $conn->real_escape_string($_POST['jabatan']);
    
    $foto_name = '';
    if($id_guru > 0) {
        $oldRes = $conn->query("SELECT foto FROM tbl_guru WHERE id_guru = $id_guru");
        $oldData = $oldRes->fetch_assoc();
        $foto_name = $oldData['foto'];
    }

    // Handle Upload Gambar
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            // Hapus foto lama jika edit
            if($id_guru > 0 && !empty($foto_name) && file_exists("../uploads/".$foto_name)) {
                unlink("../uploads/".$foto_name);
            }
            $foto_name = time() . '_guru_' . $filename;
            move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $foto_name);
        } else {
            $message = '<div class="alert alert-error">Format gambar tidak didukung! (Hanya JPG, PNG, WEBP)</div>';
        }
    }

    if(empty($message)) {
        if($id_guru > 0) {
            $stmt = $conn->prepare("UPDATE tbl_guru SET no_induk=?, nama_guru=?, jabatan=?, id_mapel=?, no_wa=?, status_kepegawaian=?, walas=?, nip_guru=?, foto=?, status=?, is_guru_bk=? WHERE id_guru=?");
            $stmt->bind_param("sssissssssii", $no_induk, $nama_guru, $jabatan, $id_mapel, $no_wa, $status_kepegawaian, $walas, $nip_guru, $foto_name, $status, $is_guru_bk, $id_guru);
        } else {
            $stmt = $conn->prepare("INSERT INTO tbl_guru (no_induk, nama_guru, jabatan, id_mapel, no_wa, status_kepegawaian, walas, nip_guru, foto, status, is_guru_bk) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssissssssi", $no_induk, $nama_guru, $jabatan, $id_mapel, $no_wa, $status_kepegawaian, $walas, $nip_guru, $foto_name, $status, $is_guru_bk);
        }
        
        if($stmt->execute()) {
            $message = '<div class="alert alert-success">Data guru berhasil ' . ($id_guru > 0 ? 'diperbarui' : 'ditambahkan') . '!</div>';
        } else {
            $message = '<div class="alert alert-error">Gagal menyimpan data guru: ' . $conn->error . '</div>';
        }
    }
}

// Get Data for Edit
$editData = null;
if(isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM tbl_guru WHERE id_guru = $id");
    $editData = $res->fetch_assoc();
}

// Ambil semua data mapel untuk dropdown
$allMapel = $conn->query("SELECT * FROM tbl_mapel ORDER BY nama_mapel ASC");

// Ambil semua data guru dengan JOIN ke mapel
$teachers = $conn->query("SELECT g.*, m.nama_mapel 
                         FROM tbl_guru g 
                         LEFT JOIN tbl_mapel m ON g.id_mapel = m.id_mapel 
                         ORDER BY 
                            FIELD(g.jabatan, 'Kepala Sekolah', 'Guru', 'Tenaga Kependidikan'),
                            g.nama_guru ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Guru - <?= htmlspecialchars($setting['site_name']) ?></title>
    <link rel="icon" type="image/png" href="../uploads/favicon.png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>

        <div class="admin-main">
            <div class="dashboard-header">
                <h1>Kelola Data Guru</h1>
                <p>Tambah, edit, atau hapus informasi tenaga pendidik sekolah.</p>
            </div>

            <?= $message ?>

            <div class="admin-section">
                <h2><?= $editData ? 'Edit' : 'Tambah' ?> Profil Guru</h2>
                <form method="POST" action="teachers.php" enctype="multipart/form-data">
                    <?php if($editData): ?>
                        <input type="hidden" name="id_guru" value="<?= $editData['id_guru'] ?>">
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label>Nama Lengkap & Gelar</label>
                            <input type="text" name="nama_guru" class="form-control" value="<?= $editData ? htmlspecialchars($editData['nama_guru']) : '' ?>" placeholder="Misal: Amat Soleh, S. Pd." required>
                        </div>
                        <div class="form-group">
                            <label>Mata Pelajaran yang Diampu</label>
                            <select name="id_mapel" class="form-control" required>
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                <?php if($allMapel && $allMapel->num_rows > 0): ?>
                                    <?php $allMapel->data_seek(0); while($m = $allMapel->fetch_assoc()): ?>
                                        <option value="<?= $m['id_mapel'] ?>" <?= ($editData && $editData['id_mapel'] == $m['id_mapel']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['nama_mapel']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>No. Induk / NIP</label>
                            <input type="text" name="no_induk" class="form-control" value="<?= $editData ? htmlspecialchars($editData['no_induk']) : '' ?>" placeholder="Masukkan NIP atau No Induk">
                        </div>
                        <div class="form-group">
                            <label>NIP Guru (Khusus)</label>
                            <input type="text" name="nip_guru" class="form-control" value="<?= $editData ? htmlspecialchars($editData['nip_guru']) : '' ?>" placeholder="Samakan dengan No Induk jika tidak ada">
                        </div>
                        <div class="form-group">
                            <label>Nomor WhatsApp</label>
                            <input type="text" name="no_wa" class="form-control" value="<?= $editData ? htmlspecialchars($editData['no_wa']) : '' ?>" placeholder="Contoh: 628123456789">
                        </div>
                        <div class="form-group">
                            <label>Status Kepegawaian</label>
                            <select name="status_kepegawaian" class="form-control">
                                <option value="ASN" <?= ($editData && $editData['status_kepegawaian'] == 'ASN') ? 'selected' : '' ?>>ASN</option>
                                <option value="Non-ASN" <?= ($editData && $editData['status_kepegawaian'] == 'Non-ASN') ? 'selected' : '' ?>>Non-ASN</option>
                                <option value="PNS" <?= ($editData && $editData['status_kepegawaian'] == 'PNS') ? 'selected' : '' ?>>PNS</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status Aktif</label>
                            <select name="status" class="form-control">
                                <option value="Aktif" <?= ($editData && $editData['status'] == 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="Non-Aktif" <?= ($editData && $editData['status'] == 'Non-Aktif') ? 'selected' : '' ?>>Non-Aktif</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Wali Kelas (Opsional)</label>
                            <input type="text" name="walas" class="form-control" value="<?= $editData ? htmlspecialchars($editData['walas']) : '' ?>" placeholder="Misal: 10 IPA 1">
                        </div>
                        <div class="form-group">
                            <label>Jabatan / Posisi</label>
                            <select name="jabatan" class="form-control" required>
                                <option value="Guru" <?= ($editData && $editData['jabatan'] == 'Guru') ? 'selected' : '' ?>>Guru</option>
                                <option value="Kepala Sekolah" <?= ($editData && $editData['jabatan'] == 'Kepala Sekolah') ? 'selected' : '' ?>>Kepala Sekolah</option>
                                <option value="Tenaga Kependidikan" <?= ($editData && $editData['jabatan'] == 'Tenaga Kependidikan') ? 'selected' : '' ?>>Tenaga Kependidikan (TU)</option>
                            </select>
                        </div>
                        <div class="form-group" style="display: flex; align-items: center; gap: 10px; padding-top: 1.5rem;">
                            <input type="checkbox" name="is_guru_bk" id="is_guru_bk" <?= ($editData && $editData['is_guru_bk']) ? 'checked' : '' ?>>
                            <label for="is_guru_bk" style="margin: 0; cursor: pointer;">Guru BK</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Foto Guru (Original)</label>
                        <?php if($editData && $editData['foto']): ?>
                            <div style="margin-bottom: 0.5rem;">
                                <img src="../uploads/<?= htmlspecialchars($editData['foto']) ?>" style="height: 50px; border-radius: 4px;">
                                <small style="display: block; color: #64748b;">Kosongkan jika tidak ingin mengubah foto</small>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" id="inputFotoOriginal" class="form-control" accept="image/*" <?= $editData ? '' : 'required' ?> style="background: white; border: 1px dashed #cbd5e1; padding: 1rem;">
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn" style="padding: 0.8rem 2rem;"><?= $editData ? 'Perbarui' : 'Simpan' ?> Data Guru</button>
                        <?php if($editData): ?>
                            <a href="teachers.php" class="btn" style="background: #94a3b8; padding: 0.8rem 2rem;">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="admin-section">
                <form method="POST" action="">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h2 style="margin: 0; border: none; padding: 0;">Daftar Guru Terdaftar</h2>
                        <?php if($teachers && $teachers->num_rows > 0): ?>
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="submit" name="delete_selected" class="btn" style="background-color: #f59e0b; padding: 0.5rem 1.2rem; font-size: 0.875rem;" onclick="return confirm('Anda yakin ingin menghapus data guru yang dicentang?')">Hapus Terpilih</button>
                                <a href="?delete_all=1" class="btn" style="background-color: #ef4444; padding: 0.5rem 1.2rem; font-size: 0.875rem;" onclick="return confirm('PERINGATAN: Anda yakin ingin menghapus SEMUA data guru beserta fotonya?')">Hapus Semua</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="table-responsive">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAll"></th>
                                    <th>Foto</th>
                                    <th>Nama Guru</th>
                                    <th>Mapel Diampu</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($teachers && $teachers->num_rows > 0): ?>
                                    <?php while($row = $teachers->fetch_assoc()): ?>
                                        <tr>
                                            <td style="text-align: center;">
                                                <input type="checkbox" name="selected_ids[]" value="<?= $row['id_guru'] ?>" class="selectItem">
                                            </td>
                                            <td>
                                                <?php if(!empty($row['foto']) && file_exists("../uploads/" . $row['foto'])): ?>
                                                    <img src="../uploads/<?= htmlspecialchars($row['foto']) ?>" alt="Foto" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                                                <?php else: ?>
                                                    <div style="width: 50px; height: 50px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #94a3b8;">No Image</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($row['nama_guru']) ?></div>
                                                <div style="font-size: 0.75rem; color: #64748b;">
                                                    <span style="color: var(--primary); font-weight: 700;"><?= htmlspecialchars($row['jabatan']) ?></span> | 
                                                    <?= htmlspecialchars($row['status_kepegawaian']) ?> <?= $row['is_guru_bk'] ? ' | Guru BK' : '' ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="font-weight: 500; color: #475569;"><?= htmlspecialchars($row['nama_mapel'] ?: 'Belum diatur') ?></span>
                                            </td>
                                            <td>
                                                <span style="padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: <?= $row['status'] == 'Aktif' ? '#ecfdf5' : '#fef2f2' ?>; color: <?= $row['status'] == 'Aktif' ? '#059669' : '#dc2626' ?>;">
                                                    <?= htmlspecialchars($row['status']) ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <a href="?edit=<?= $row['id_guru'] ?>" class="btn" style="background: #eff6ff; color: #2563eb; padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px;">Edit</a>
                                                <a href="?delete=<?= $row['id_guru'] ?>" class="btn" style="background-color: #fee2e2; color: #dc2626; padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px;" onclick="return confirm('Yakin ingin menghapus profil guru ini?')">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 3rem; color: #94a3b8;">Belum ada data guru</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        var selectAll = document.getElementById('selectAll');
        if(selectAll) {
            selectAll.addEventListener('change', function() {
                var checkboxes = document.querySelectorAll('.selectItem');
                for(var checkbox of checkboxes) {
                    checkbox.checked = this.checked;
                }
            });
        }
    </script>
</body>
</html>
