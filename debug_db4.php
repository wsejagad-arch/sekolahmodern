<?php require "koneksi.php";
$nipGuru = "198108152014111002"; // Let's test with any NIP or we can just run the query directly.
// Let's find the NIP of the Wali Kelas for XI F4!
$q = mysqli_query($conn, "SELECT nip_wali FROM tbl_kelas WHERE kelas='XI F4'");
$r = mysqli_fetch_assoc($q);
$nip_wali = $r['nip_wali'];
echo "NIP WALI KELAS XI F4: $nip_wali \n";

// Now run the query for this Wali Kelas!
$kelas_wali = [];
$qWali = mysqli_query($conn, "SELECT DISTINCT k.kelas FROM tbl_wali_kelas wk JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas WHERE wk.nip_wali = '$nip_wali' AND k.kelas <> ''");
if($qWali) while($row = mysqli_fetch_assoc($qWali)) $kelas_wali[] = $row['kelas'];

$qWali2 = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas WHERE nip_wali='$nip_wali' AND kelas <> ''");
if($qWali2) while($row = mysqli_fetch_assoc($qWali2)) if (!in_array($row['kelas'], $kelas_wali)) $kelas_wali[] = $row['kelas'];

echo "KELAS WALI: " . implode(", ", $kelas_wali) . "\n";

$kelas_in = "'" . implode("','", array_map(function($k) use ($conn) { return mysqli_real_escape_string($conn, $k); }, $kelas_wali)) . "'";
$qWaliIzin = mysqli_query($conn, "SELECT i.*, s.nama_siswa FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk WHERE s.kelas IN ($kelas_in) AND i.validasi_wali_kelas = 'Menunggu' ORDER BY i.waktu_pengajuan ASC");

echo "NUM ROWS PENDING: " . mysqli_num_rows($qWaliIzin) . "\n";
while($row = mysqli_fetch_assoc($qWaliIzin)) {
    echo " -> " . $row['nama_siswa'] . " (" . $row['kelas_siswa'] . ")\n";
}
