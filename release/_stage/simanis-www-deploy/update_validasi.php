<?php
$file = 'c:\xampp\htdocs\jurnal\pages\guru\validasi-izin.php';
$content = file_get_contents($file);

// Show the classes they manage in the UI so we can debug
$content = str_replace('<h2 class="fw-bold mb-4">Validasi Izin Siswa</h2>', '<h2 class="fw-bold mb-4">Validasi Izin Siswa</h2>
    <p class="text-muted">Mewakili Kelas: <?= htmlspecialchars(empty($kelas_wali) ? "Tidak ada" : implode(", ", $kelas_wali)) ?></p>', $content);

// In case the query was failing because of $kelas_in
// $kelas_in was constructed like this:
// $kelas_in = "'" . implode("','", array_map(function($k) use ($conn) { return mysqli_real_escape_string($conn, $k); }, $kelas_wali)) . "'";
// This is correct.

file_put_contents($file, $content);
echo "Updated validasi-izin.php";
?>
