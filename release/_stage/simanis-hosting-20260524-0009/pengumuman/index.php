<?php
require_once '../config.php';

// Check session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

try {
    $stmt = $conn->query("SELECT * FROM pengumuman ORDER BY tanggal_dibuat DESC");
    $pengumuman = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Pengumuman</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .btn { padding: 10px 15px; margin: 5px; cursor: pointer; text-decoration: none; border-radius: 3px; }
        .btn-add { background-color: #4CAF50; color: white; display: inline-block; }
        .btn-edit { background-color: #008CBA; color: white; }
        .btn-delete { background-color: #f44336; color: white; }
        .btn:hover { opacity: 0.8; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📢 Kelola Pengumuman</h1>
        <a href="tambah.php" class="btn btn-add">+ Tambah Pengumuman</a>

        <?php if (count($pengumuman) > 0): ?>
        <table id="tblPengumuman">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul</th>
                    <th>Status</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($pengumuman as $item): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($item['judul']); ?></td>
                    <td>
                        <span style="padding: 5px 10px; border-radius: 3px; <?php echo $item['status'] == 'aktif' ? 'background-color: #4CAF50; color: white;' : 'background-color: #f44336; color: white;'; ?>">
                            <?php echo ucfirst($item['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('d-m-Y H:i', strtotime($item['tanggal_dibuat'])); ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-edit">Edit</a>
                        <a href="hapus.php?id=<?php echo $item['id']; ?>" class="btn btn-delete" onclick="return confirm('Yakin akan menghapus pengumuman ini?')">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color: #999; margin-top: 20px;">Belum ada pengumuman. <a href="tambah.php">Buat pengumuman baru</a></p>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            if ($('#tblPengumuman').length) {
                $('#tblPengumuman').DataTable();
            }
        });
    </script>
</body>
</html>
