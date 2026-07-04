<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnostik Lingkungan & Koneksi Database</h1>";

// 1. Cek ekstensi mysqli
echo '<h2>1. Ekstensi mysqli</h2>';
echo extension_loaded('mysqli') ? "<p style='color:green'>✅ Ekstensi mysqli aktif</p>" : "<p style='color:red'>❌ Ekstensi mysqli TIDAK aktif</p>";

echo '<h2>2. Informasi PHP</h2>';
echo '<ul>';
echo '<li>PHP Version: '.phpversion().'</li>';
echo '<li>OS: '.PHP_OS.'</li>';
echo '<li>UNAME: '.php_uname().'</li>';
echo '<li>SERVER_NAME: '.($_SERVER['SERVER_NAME'] ?? '(CLI)').'</li>';
echo '</ul>';

// 3. Cek port MySQL umum
$ports = [3306,3307,3308];
echo '<h2>3. Scan Port MySQL (TCP) (perkiraan)</h2>';
echo '<ul>';
foreach ($ports as $p) {
    $conn = @fsockopen('127.0.0.1', $p, $errno, $errstr, 0.25);
    if ($conn) {
        echo '<li style="color:green">Port '.$p.' TERBUKA</li>';
        fclose($conn);
    } else {
        echo '<li style="color:#999">Port '.$p.' tertutup / tidak respons</li>';
    }
}
echo '</ul>';

// 4. Coba koneksi manual variasi
$users = ['root'];
$passwords = ['', 'root', 'admin'];
$dbNames = ['jurnal','test','mysql'];
$attemptData = [];

echo '<h2>4. Percobaan Koneksi Manual</h2>';
foreach ($ports as $p) {
    foreach ($users as $u) {
        foreach ($passwords as $pw) {
            foreach ($dbNames as $db) {
                $start = microtime(true);
                $link = @mysqli_connect('localhost', $u, $pw, $db, $p);
                $attemptData[] = [
                    'port'=>$p,
                    'user'=>$u,
                    'pw'=>$pw === '' ? '(empty)' : $pw,
                    'db'=>$db,
                    'ok'=>(bool)$link,
                    'time'=>round((microtime(true)-$start)*1000,1),
                    'error'=>$link? null : mysqli_connect_error(),
                ];
                if ($link) { mysqli_close($link);}            }
        }
    }
}

echo '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse;font-size:12px">';
echo '<tr style="background:#eee"><th>Port</th><th>User</th><th>Password</th><th>DB</th><th>Status</th><th>Waktu (ms)</th><th>Error</th></tr>';
foreach ($attemptData as $a) {
    $color = $a['ok'] ? 'green' : '#b00';
    $status = $a['ok'] ? 'OK' : 'FAIL';
    echo '<tr>'
        .'<td>'.$a['port'].'</td>'
        .'<td>'.$a['user'].'</td>'
        .'<td>'.$a['pw'].'</td>'
        .'<td>'.$a['db'].'</td>'
        .'<td style="color:'.$color.'">'.$status.'</td>'
        .'<td>'.$a['time'].'</td>'
        .'<td>'.htmlspecialchars($a['error'] ?? '-').'</td>'
        .'</tr>';
}
echo '</table>';

// 5. Saran otomatis
$anyOk = array_filter($attemptData, function($r) { return $r['ok']; });
echo '<h2>5. Analisis & Saran</h2>';
if ($anyOk) {
    echo '<p style="color:green">✅ Ada kombinasi yang BERHASIL. Gunakan salah satu baris yang statusnya OK untuk config.local.php</p>';
} else {
    echo '<p style="color:red">❌ Tidak ada koneksi yang berhasil.</p>';
    echo '<ol>';
    echo '<li>Buka XAMPP Control Panel → pastikan MySQL RUNNING.</li>';
    echo '<li>Klik Admin pada MySQL → phpMyAdmin harus bisa terbuka.</li>';
    echo '<li>Cek apakah database bernama "jurnal" sudah dibuat. Jika belum, buat.</li>';
    echo '<li>Jika root punya password: catat password itu, lalu buat file <code>config.local.php</code>.</li>';
    echo '<li>Jika pakai port bukan 3306: buka my.ini di XAMPP dan cari baris <code>port=XXXX</code>.</li>';
    echo '<li>Restart Apache & MySQL setelah perubahan.</li>';
    echo '</ol>';
}

echo '<h2>6. Contoh config.local.php</h2>';
echo '<pre>&lt;?php\n$cfg[\'host\']=\'localhost\';\n$cfg[\'user\']=\'root\';\n$cfg[\'password\']=\'JIKA_ADA\';\n$cfg[\'db\']=\'jurnal\';\n$cfg[\'port\']=3306;\n?&gt;</pre>';

?>