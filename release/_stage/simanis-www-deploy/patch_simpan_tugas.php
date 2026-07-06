<?php
$file = 'c:\xampp\htdocs\jurnal\pages\guru\simpan_tugas.php';
$content = file_get_contents($file);

// Replace POST field
$content = str_replace("\$deadline = trim((string)(\$_POST['tanggal_pengumpulan'] ?? ''));", "\$deadline = trim((string)(\$_POST['batas_waktu'] ?? ''));", $content);

// Replace insert query columns
$content = str_replace(
    "(tanggal, id_mapel, kelas, mapel, no_induk_guru, judul_tugas, deskripsi, link_tugas, file_tugas, tanggal_pengumpulan, status)",
    "(tanggal, id_mapel, kelas, mapel, no_induk_guru, judul_tugas, deskripsi, link_tugas, file_tugas, batas_waktu, status)",
    $content
);

file_put_contents($file, $content);
echo "Updated simpan_tugas.php";
?>
