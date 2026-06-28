<?php
include "koneksi.php";

$q1 = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru WHERE nama_guru LIKE '%Dwi Wahyu%'");
$guru = mysqli_fetch_assoc($q1);
$nip = $guru['no_induk'];
echo "NIP Dwi: $nip\n";

$q2 = mysqli_query($conn, "SELECT id_kelas, kelas FROM tbl_kelas WHERE nip_wali = '$nip'");
while($r = mysqli_fetch_assoc($q2)) {
    echo "Kelas Dwi: " . $r['kelas'] . "\n";
}

$q3 = mysqli_query($conn, "SELECT * FROM tbl_izin_siswa ORDER BY id_izin DESC LIMIT 5");
while($r = mysqli_fetch_assoc($q3)) {
    echo "Izin: ID=" . $r['id_izin'] . ", Kelas=" . $r['kelas_siswa'] . ", Kategori=" . $r['kategori_pengajuan'] . ", Validasi Wali=" . $r['validasi_wali_kelas'] . ", Status=" . $r['status_izin'] . "\n";
}
?>
