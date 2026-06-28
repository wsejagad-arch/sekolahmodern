<?php
include 'koneksi.php';
$result = mysqli_query($conn, 'SELECT no_induk, nama_guru FROM tbl_guru LIMIT 5');
echo "=== DATA GURU YANG TERSEDIA ===\n";
while($row = mysqli_fetch_assoc($result)) {
    echo 'NIP: ' . $row['no_induk'] . ' - Nama: ' . $row['nama_guru'] . "\n";
}
echo "\n=== DATA JADWAL MENGAJAR ===\n";
$result2 = mysqli_query($conn, 'SELECT no_induk, nama_guru, COUNT(*) as total_jadwal FROM tbl_guru g LEFT JOIN tbl_mapel_ampu m ON g.no_induk = m.no_induk GROUP BY g.no_induk LIMIT 5');
while($row = mysqli_fetch_assoc($result2)) {
    echo 'NIP: ' . $row['no_induk'] . ' - Nama: ' . $row['nama_guru'] . ' - Jadwal: ' . $row['total_jadwal'] . "\n";
}
?>