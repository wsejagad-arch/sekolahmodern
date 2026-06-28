<?php
include "c:\xampp\htdocs\jurnal\koneksi.php";

$q = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru WHERE nama_guru LIKE '%Dwi Wahyu%'");
$guru = mysqli_fetch_assoc($q);
print_r($guru);

$nip = $guru['no_induk'];

$q2 = mysqli_query($conn, "SELECT id_kelas, kelas FROM tbl_kelas WHERE nip_wali = '$nip'");
while($r = mysqli_fetch_assoc($q2)) {
    print_r($r);
    $kelas = $r['kelas'];
    
    echo "Izin for class $kelas:\n";
    // Check if there are any izin in tbl_izin_siswa
    $qIzin = mysqli_query($conn, "SELECT id_izin, no_induk_siswa, kategori_pengajuan, validasi_wali_kelas, kelas_siswa FROM tbl_izin_siswa WHERE kelas_siswa = '$kelas'");
    while($rIzin = mysqli_fetch_assoc($qIzin)) {
        print_r($rIzin);
    }
    
    // Check with JOIN tbl_siswa
    $qIzin2 = mysqli_query($conn, "SELECT i.id_izin, i.validasi_wali_kelas, s.kelas FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk WHERE s.kelas = '$kelas'");
    while($rIzin2 = mysqli_fetch_assoc($qIzin2)) {
        print_r($rIzin2);
    }
}
?>
