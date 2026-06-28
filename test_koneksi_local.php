<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/koneksi.php';

echo "<h2>Test Koneksi Database</h2>";
$info = db_info();
echo '<pre>' . htmlspecialchars(print_r($info, true)) . '</pre>';

if (empty($info['attempts'])) {
    echo "<p><em>Tidak ada data percobaan koneksi (mungkin koneksi langsung berhasil pada percobaan pertama).</em></p>";
} else {
    echo "<h3>Log Percobaan Koneksi</h3>";
    echo "<table border='1' cellpadding='6' style='border-collapse:collapse;font-family:monospace;font-size:13px'>";
    echo "<tr style='background:#eee'><th>Host</th><th>Port</th><th>User</th><th>Password?</th><th>DB</th><th>Status</th><th>Error</th></tr>";
    foreach ($info['attempts'] as $a) {
        $status = $a['ok'] ? "<span style='color:green'>OK</span>" : "<span style='color:red'>FAIL</span>";
        echo '<tr>'
            .'<td>'.htmlspecialchars($a['host']).'</td>'
            .'<td>'.htmlspecialchars($a['port']).'</td>'
            .'<td>'.htmlspecialchars($a['user']).'</td>'
            .'<td>'.htmlspecialchars($a['password_set']).'</td>'
            .'<td>'.htmlspecialchars($a['db']).'</td>'
            .'<td>'.$status.'</td>'
            .'<td>'.htmlspecialchars($a['error'] ?? '-').'</td>'
            .'</tr>';
    }
    echo '</table>';
}

$result = mysqli_query($conn, "SHOW DATABASES LIKE '" . mysqli_real_escape_string($conn, $info['db']) . "'");
if ($result && mysqli_num_rows($result) > 0) {
    echo "<p style='color:green'>✅ Database ditemukan.</p>";
} else {
    echo "<p style='color:red'>⚠️ Database tidak ditemukan atau tidak bisa diakses.</p>";
    echo "<p><strong>Langkah perbaikan:</strong><br>1. Buka XAMPP Control Panel -> klik Admin MySQL atau phpMyAdmin.<br>2. Pastikan database dengan nama <code>".htmlspecialchars($info['db'])."</code> ada.<br>3. Jika port bukan 3306, cek file my.ini (lihat baris port).<br>4. Jika root memiliki password, buat file <code>config.local.php</code> dengan isi contoh dari <code>config.local.php.example</code>.</p>";
}

$tables = mysqli_query($conn, "SHOW TABLES");
if ($tables) {
    echo "<p>Daftar tabel (maks 10 pertama):</p><ul>";
    $i = 0;
    while ($row = mysqli_fetch_row($tables)) {
        echo '<li>' . htmlspecialchars($row[0]) . '</li>';
        if (++$i >= 10) break;
    }
    echo '</ul>';
} else {
    echo "<p style='color:red'>Tidak bisa mengambil daftar tabel: " . mysqli_error($conn) . "</p>";
}

?>