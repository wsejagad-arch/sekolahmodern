<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$id = $_GET['id'] ?? null;
$pesan = '';
$tipe_pesan = '';

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM pengumuman WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        header('Location: index.php');
        exit;
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = $_POST['judul'];
    $isi = $_POST['isi'];
    $status = $_POST['status'];
    $updated_by = $_SESSION['user_id'];

    if (empty($judul) || empty($isi)) {
        $pesan = "⚠️ Judul dan isi tidak boleh kosong!";
        $tipe_pesan = "error";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE pengumuman SET judul = ?, isi = ?, status = ?, updated_by = ? WHERE id = ?");
            $stmt->execute([$judul, $isi, $status, $updated_by, $id]);
            $pesan = "✓ Pengumuman berhasil diupdate!";
            $tipe_pesan = "success";
            header('Refresh: 2; URL=index.php');
        } catch(PDOException $e) {
            $pesan = "❌ Error: " . $e->getMessage();
            $tipe_pesan = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Pengumuman</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input[type="text"], textarea, select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 3px;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
        }
        textarea { resize: vertical; }
        .btn { padding: 10px 20px; margin-top: 20px; margin-right: 10px; cursor: pointer; border: none; border-radius: 3px; }
        .btn-submit { background-color: #4CAF50; color: white; }
        .btn-submit:hover { background-color: #45a049; }
        .btn-back { background-color: #008CBA; color: white; text-decoration: none; display: inline-block; }
        .btn-back:hover { background-color: #007399; }
        h1 { color: #333; }
        .pesan { padding: 15px; margin: 15px 0; border-radius: 3px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✏️ Edit Pengumuman</h1>

        <?php if ($pesan): ?>
            <div class="pesan <?php echo $tipe_pesan; ?>">
                <?php echo $pesan; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Judul</label>
                <input type="text" name="judul" value="<?php echo htmlspecialchars($data['judul']); ?>" required>
            </div>

            <div class="form-group">
                <label>Isi Pengumuman</label>
                <textarea name="isi" rows="10" required><?php echo htmlspecialchars($data['isi']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="aktif" <?php echo $data['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="tidak aktif" <?php echo $data['status'] == 'tidak aktif' ? 'selected' : ''; ?>>Tidak Aktif</option>
                </select>
            </div>

            <button type="submit" class="btn btn-submit">💾 Update</button>
            <a href="index.php" class="btn btn-back">← Kembali</a>
        </form>
    </div>
</body>
</html>
