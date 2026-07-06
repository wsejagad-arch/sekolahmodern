<?php
$file = 'c:\xampp\htdocs\jurnal\data-siswa.php';
$content = file_get_contents($file);

// Add status filter for the distinct class query
$content = str_replace(
    "SELECT DISTINCT kelas FROM tbl_siswa s WHERE {\$tenantSiswa} AND kelas IS NOT NULL",
    "SELECT DISTINCT kelas FROM tbl_siswa s WHERE {\$tenantSiswa} AND s.status = 'Aktif' AND kelas IS NOT NULL",
    $content
);

// Add status filter for the main student list query
$content = str_replace(
    "\$conditions = [\$tenantSiswa];",
    "\$conditions = [\$tenantSiswa, \"s.status = 'Aktif'\"];",
    $content
);

file_put_contents($file, $content);
echo "data-siswa.php updated";
?>
