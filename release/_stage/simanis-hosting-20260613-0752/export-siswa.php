<?php
include "koneksi.php";

// Nama file download
$filename = "data_siswa_" . date('Ymd') . ".xls";

// Header untuk download file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil data siswa
$query = mysqli_query($conn, "SELECT * FROM tbl_siswa ORDER BY kelas ASC");

// Tulis header tabel
echo "<table border='1'>";
echo "<tr>
        <th>No.</th>
        <th>No. Induk Siswa</th>
        <th>Nama Siswa</th>
        <th>Kelas</th>
        <th>Status</th>
      </tr>";

$no = 1;
while ($row = mysqli_fetch_assoc($query)) {
    echo "<tr>
            <td>".$no++."</td>
            <td>".$row['no_induk']."</td>
            <td>".$row['nama_siswa']."</td>
            <td>".$row['kelas']."</td>
            <td>".$row['status']."</td>
          </tr>";
}
echo "</table>";
?>

