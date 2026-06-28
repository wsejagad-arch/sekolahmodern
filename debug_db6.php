<?php require "koneksi.php";
$nip_wali = "198108152014111002"; // Let's use the Dwi Wahyu NIP or similar.
// Wait, I will just search for WIWID's wali!
$q = mysqli_query($conn, "SELECT nip_wali FROM tbl_kelas WHERE kelas='XI F 4'");
$r = mysqli_fetch_assoc($q);
$nip_wali = $r['nip_wali'];
echo "NIP: $nip_wali\n";
$kelas_wali = []; 
$qWali = mysqli_query($conn, "SELECT DISTINCT k.kelas FROM tbl_wali_kelas wk JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas WHERE wk.nip_wali = '$nip_wali' AND k.kelas <> ''"); 
if($qWali) while($row = mysqli_fetch_assoc($qWali)) $kelas_wali[] = $row['kelas']; 

$qWali2 = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas WHERE nip_wali='$nip_wali' AND kelas <> ''");
if($qWali2) while($row = mysqli_fetch_assoc($qWali2)) if (!in_array($row['kelas'], $kelas_wali)) $kelas_wali[] = $row['kelas'];

$kelas_in = "'" . implode("','", array_map(function($k) use ($conn) { return mysqli_real_escape_string($conn, str_replace(' ', '', $k)); }, $kelas_wali)) . "'"; 
$qWaliIzin = mysqli_query($conn, "SELECT i.*, s.nama_siswa FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk WHERE REPLACE(s.kelas, ' ', '') IN ($kelas_in) AND i.validasi_wali_kelas = 'Menunggu' ORDER BY i.waktu_pengajuan ASC"); 
echo "NUM ROWS PENDING: " . mysqli_num_rows($qWaliIzin) . "\n"; 
while($row = mysqli_fetch_assoc($qWaliIzin)) { echo " -> " . $row['nama_siswa'] . "\n"; }
