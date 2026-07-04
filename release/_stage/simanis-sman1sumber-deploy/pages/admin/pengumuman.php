<?php
// Halaman Pengumuman
// File: pages/admin/pengumuman.php

if (!isset($conn)) {
    require_once __DIR__ . '/../../config.php';
}

$pesan = '';
$tipe_pesan = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'tambah' || $action == 'edit') {
        $judul = $_POST['judul'] ?? '';
        $isi = $_POST['isi'] ?? '';
        $status = $_POST['status'] ?? 'aktif';
        $user_id = $_SESSION['user_id'] ?? 1;
        
        if (empty($judul) || empty($isi)) {
            $pesan = "⚠️ Judul dan isi tidak boleh kosong!";
            $tipe_pesan = "warning";
        } else {
            // Escape strings untuk MySQLi
            $judul = mysqli_real_escape_string($conn, $judul);
            $isi = mysqli_real_escape_string($conn, $isi);
            
            if ($action == 'tambah') {
                $sql = "INSERT INTO tbl_pengumuman (judul, isi, status, created_by) VALUES ('$judul', '$isi', '$status', $user_id)";
                if (mysqli_query($conn, $sql)) {
                    $pesan = "✓ Pengumuman berhasil ditambahkan!";
                    $tipe_pesan = "success";
                } else {
                    $pesan = "❌ Error: " . mysqli_error($conn);
                    $tipe_pesan = "danger";
                }
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
                $sql = "UPDATE tbl_pengumuman SET judul = '$judul', isi = '$isi', status = '$status' WHERE id = $id AND id_sekolah = $idSekolah";
                if (mysqli_query($conn, $sql)) {
                    $pesan = "✓ Pengumuman berhasil diupdate!";
                    $tipe_pesan = "success";
                } else {
                    $pesan = "❌ Error: " . mysqli_error($conn);
                    $tipe_pesan = "danger";
                }
            }
        }
    }
}

// Handle delete
if (isset($_GET['hapus'])) {
    $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
    $sql = "DELETE FROM tbl_pengumuman WHERE id = $id AND id_sekolah = $idSekolah";
    if (mysqli_query($conn, $sql)) {
        $pesan = "✓ Pengumuman berhasil dihapus!";
        $tipe_pesan = "success";
    } else {
        $pesan = "❌ Error: " . mysqli_error($conn);
        $tipe_pesan = "danger";
    }
}

// Get data for edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
    $result = mysqli_query($conn, "SELECT * FROM tbl_pengumuman WHERE id = $id AND id_sekolah = $idSekolah");
    if ($result && mysqli_num_rows($result) > 0) {
        $edit_data = mysqli_fetch_assoc($result);
    }
}

// Ambil semua pengumuman
$pengumuman = [];
$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$result = @mysqli_query($conn, "SELECT * FROM tbl_pengumuman WHERE id_sekolah = $idSekolah ORDER BY created_at DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $pengumuman[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">📢 Kelola Pengumuman</h1>
    </div>

    <?php if (!empty($pesan)): ?>
        <div class="alert alert-<?php echo $tipe_pesan; ?> alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $pesan; ?>
        </div>
    <?php endif; ?>

    <!-- Form Tambah/Edit Pengumuman -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php echo $edit_data ? '✏️ Edit Pengumuman' : '➕ Tambah Pengumuman'; ?>
            </h6>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_data ? 'edit' : 'tambah'; ?>">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="font-weight-bold">Judul</label>
                    <input type="text" name="judul" class="form-control" required 
                           value="<?php echo $edit_data ? htmlspecialchars($edit_data['judul']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Isi Pengumuman</label>
                    <textarea name="isi" class="form-control" rows="5" required><?php echo $edit_data ? htmlspecialchars($edit_data['isi']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Status</label>
                    <select name="status" class="form-control">
                        <option value="aktif" <?php echo ($edit_data && $edit_data['status'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                        <option value="nonaktif" <?php echo ($edit_data && $edit_data['status'] == 'nonaktif') ? 'selected' : ''; ?>>Tidak Aktif</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $edit_data ? 'Update' : 'Simpan'; ?>
                </button>
                <?php if ($edit_data): ?>
                    <a href="?page=pengumuman" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Daftar Pengumuman -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">📋 Daftar Pengumuman</h6>
        </div>
        <div class="card-body">
            <?php if (count($pengumuman) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>Judul</th>
                                <th width="150">Status</th>
                                <th width="180">Tanggal Dibuat</th>
                                <th width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($pengumuman as $item): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['judul']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars(substr($item['isi'], 0, 100)); ?>...
                                    </small>
                                </td>
                                <td>
                                    <?php if ($item['status'] == 'aktif'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d-m-Y H:i', strtotime($item['created_at'])); ?></td>
                                <td>
                                    <a href="?page=pengumuman&edit=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?page=pengumuman&hapus=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Yakin akan menghapus pengumuman ini?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle"></i> Belum ada pengumuman.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .border-left-primary {
        border-left: .25rem solid #4e73df !important;
    }
    
    .text-gray-700 {
        color: #717171;
        line-height: 1.6;
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e3e6f0;
    }
    
    .badge-success {
        background-color: #1cc88a !important;
    }
</style>
