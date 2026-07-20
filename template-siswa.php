<?php
// Nama file download
$filename = "template_siswa.csv";

// Set header supaya browser otomatis download
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

// Buka output ke browser
$output = fopen("php://output", "w");

// Header kolom sesuai tabel
fputcsv($output, array('nis','nama','kelas'), ';');

// Contoh data (opsional, bisa dihapus kalau mau kosong)
fputcsv($output, array('12345','BUDI SANTOSO','XI IPA 1'), ';');
fputcsv($output, array('67890','ANITA DEWI','XII IPS 2'), ';');

fclose($output);
exit;
?>

