<?php
include "koneksi.php";

$nama = isset($_GET['nama']) ? $_GET['nama'] : '';
$kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';

if ($nama === '' || $kelas === '') {
    echo "Param nama dan kelas wajib diisi.";
    exit;
}

$namaEsc = mysqli_real_escape_string($conn, $nama);
$kelasEsc = mysqli_real_escape_string($conn, $kelas);

$sql = "SELECT no_induk, nama_siswa, kelas FROM tbl_siswa WHERE nama_siswa LIKE '%$namaEsc%' AND kelas = '$kelasEsc' LIMIT 10";
$res = mysqli_query($conn, $sql);

if (!$res) {
    echo "Query error: " . mysqli_error($conn);
    exit;
}

if (mysqli_num_rows($res) === 0) {
    echo "Data siswa tidak ditemukan.";
    exit;
}

while ($row = mysqli_fetch_assoc($res)) {
    echo "Nama: " . $row['nama_siswa'] . "\n";
    echo "Kelas: " . $row['kelas'] . "\n";
    echo "NIS: " . $row['no_induk'] . "\n";
    echo "---\n";
}
