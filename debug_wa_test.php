<?php
session_start();
include 'koneksi.php';

$msg = '';
$guruList = [];

// Test: langsung update no_wa via POST
if (isset($_POST['update_wa'])) {
    $id = (int)$_POST['id_guru'];
    $wa = mysqli_real_escape_string($conn, trim($_POST['no_wa']));
    $sql = "UPDATE tbl_guru SET no_wa='$wa' WHERE id_guru=$id";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $affected = mysqli_affected_rows($conn);
        $msg = "<div style='color:green'><b>✅ UPDATE BERHASIL!</b> Affected rows: $affected | SQL: <code>$sql</code></div>";
    } else {
        $msg = "<div style='color:red'><b>❌ UPDATE GAGAL:</b> " . mysqli_error($conn) . "</div>";
    }
}

// Ambil semua data guru
$r = mysqli_query($conn, "SELECT id_guru, nama_guru, no_wa FROM tbl_guru ORDER BY nama_guru LIMIT 30");
while ($row = mysqli_fetch_assoc($r)) {
    $guruList[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug No WA Guru</title>
    <style>
        body { font-family: monospace; padding: 20px; font-size: 13px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px 10px; text-align: left; }
        th { background: #f0f0f0; }
        tr:hover { background: #fffbe6; }
        .wa-ok { color: green; font-weight: bold; }
        .wa-empty { color: #aaa; }
        input[type=text] { padding: 4px; width: 180px; }
        button { padding: 5px 14px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
<h2>🔍 Debug Nomor WA Guru</h2>
<?= $msg ?>
<hr>
<h3>Data Guru Saat Ini (dari database langsung)</h3>
<table>
    <tr><th>#</th><th>ID</th><th>Nama Guru</th><th>No WA Tersimpan</th><th>Update Langsung</th></tr>
    <?php foreach ($guruList as $i => $g): ?>
    <tr>
        <td><?= $i+1 ?></td>
        <td><?= $g['id_guru'] ?></td>
        <td><?= htmlspecialchars($g['nama_guru']) ?></td>
        <td class="<?= !empty($g['no_wa']) ? 'wa-ok' : 'wa-empty' ?>">
            <?= !empty($g['no_wa']) ? htmlspecialchars($g['no_wa']) : '— (kosong/null)' ?>
        </td>
        <td>
            <form method="POST" style="display:inline">
                <input type="hidden" name="id_guru" value="<?= $g['id_guru'] ?>">
                <input type="text" name="no_wa" value="<?= htmlspecialchars($g['no_wa'] ?? '') ?>" placeholder="08xxx">
                <button type="submit" name="update_wa">Simpan</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<hr>
<h3>📋 Info POST terakhir</h3>
<pre><?= htmlspecialchars(print_r($_POST, true)) ?></pre>

<h3>📋 Info GET terakhir</h3>
<pre><?= htmlspecialchars(print_r($_GET, true)) ?></pre>
</body>
</html>
