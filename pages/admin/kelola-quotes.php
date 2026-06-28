<?php
// Halaman Kelola Quotes
// File: pages/admin/kelola-quotes.php

if (!isset($conn)) {
    require_once __DIR__ . '/../../config.php';
}

$pesan = '';
$tipe_pesan = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $quote = trim($_POST['quote'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $category = $_POST['category'] ?? 'motivasi';
        $status = $_POST['status'] ?? 'aktif';
        $user_id = (int)($_SESSION['user_id'] ?? 1);

        if ($quote === '' || $author === '') {
            $pesan = '⚠️ Kutipan dan penulis tidak boleh kosong!';
            $tipe_pesan = 'warning';
        } else {
            $quote = mysqli_real_escape_string($conn, $quote);
            $author = mysqli_real_escape_string($conn, $author);
            $category = mysqli_real_escape_string($conn, $category);
            $status = mysqli_real_escape_string($conn, $status);

            if ($action === 'tambah') {
                $sql = "INSERT INTO quotes (quote, author, category, status, created_by) VALUES ('$quote', '$author', '$category', '$status', $user_id)";
                if (mysqli_query($conn, $sql)) {
                    $pesan = '✓ Kutipan berhasil ditambahkan!';
                    $tipe_pesan = 'success';
                } else {
                    $pesan = '❌ Kesalahan: ' . mysqli_error($conn);
                    $tipe_pesan = 'danger';
                }
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $sql = "UPDATE quotes SET quote = '$quote', author = '$author', category = '$category', status = '$status' WHERE id = $id";
                if (mysqli_query($conn, $sql)) {
                    $pesan = '✓ Kutipan berhasil diperbarui!';
                    $tipe_pesan = 'success';
                } else {
                    $pesan = '❌ Kesalahan: ' . mysqli_error($conn);
                    $tipe_pesan = 'danger';
                }
            }
        }
    }
}

// Handle delete
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $sql = "DELETE FROM quotes WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        $pesan = '✓ Kutipan berhasil dihapus!';
        $tipe_pesan = 'success';
    } else {
        $pesan = '❌ Kesalahan: ' . mysqli_error($conn);
        $tipe_pesan = 'danger';
    }
}

// Data untuk edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM quotes WHERE id = $id");
    if ($result && mysqli_num_rows($result) > 0) {
        $edit_data = mysqli_fetch_assoc($result);
    }
}

// Ambil semua quotes
$quotes = [];
$result = @mysqli_query($conn, "SELECT * FROM quotes ORDER BY tanggal_dibuat DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $quotes[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"> Kelola Kutipan</h1>
    </div>

    <?php if (!empty($pesan)): ?>
        <div class="alert alert-<?php echo $tipe_pesan; ?> alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $pesan; ?>
        </div>
    <?php endif; ?>

    <!-- Form Tambah/Edit Quote -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php echo $edit_data ? '✏️ Edit Kutipan' : '➕ Tambah Kutipan'; ?>
            </h6>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_data ? 'edit' : 'tambah'; ?>">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$edit_data['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="font-weight-bold">Kutipan</label>
                    <textarea name="quote" class="form-control" rows="3" required><?php echo $edit_data ? htmlspecialchars($edit_data['quote']) : ''; ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Penulis</label>
                            <input type="text" name="author" class="form-control" required value="<?php echo $edit_data ? htmlspecialchars($edit_data['author']) : ''; ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Kategori</label>
                            <select name="category" class="form-control">
                                <option value="motivasi" <?php echo ($edit_data && $edit_data['category'] === 'motivasi') ? 'selected' : ''; ?>>Motivasi</option>
                                <option value="pendidikan" <?php echo ($edit_data && $edit_data['category'] === 'pendidikan') ? 'selected' : ''; ?>>Pendidikan</option>
                                <option value="inspirasi" <?php echo ($edit_data && $edit_data['category'] === 'inspirasi') ? 'selected' : ''; ?>>Inspirasi</option>
                                <option value="kehidupan" <?php echo ($edit_data && $edit_data['category'] === 'kehidupan') ? 'selected' : ''; ?>>Kehidupan</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Status</label>
                            <select name="status" class="form-control">
                                <option value="aktif" <?php echo ($edit_data && $edit_data['status'] === 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                                <option value="tidak aktif" <?php echo ($edit_data && $edit_data['status'] === 'tidak aktif') ? 'selected' : ''; ?>>Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $edit_data ? 'Perbarui' : 'Simpan'; ?>
                </button>
                <?php if ($edit_data): ?>
                    <a href="?page=kelola-quotes" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Daftar Quotes -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">📋 Daftar Kutipan</h6>
        </div>
        <div class="card-body">
            <?php if (count($quotes) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>Kutipan</th>
                                <th width="150">Author</th>
                                <th width="120">Kategori</th>
                                <th width="100">Status</th>
                                <th width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($quotes as $item): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <i class="fas fa-quote-left text-muted"></i>
                                    <em><?php echo htmlspecialchars($item['quote']); ?></em>
                                    <i class="fas fa-quote-right text-muted"></i>
                                </td>
                                <td><?php echo htmlspecialchars($item['author']); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo ucfirst($item['category']); ?></span>
                                </td>
                                <td>
                                    <?php if ($item['status'] === 'aktif'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?page=kelola-quotes&edit=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?page=kelola-quotes&hapus=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin akan menghapus kutipan ini?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle"></i> Belum ada quotes.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
