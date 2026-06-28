<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\ajukan-izin.php';
$content = file_get_contents($file);

// Fix the INSERT INTO query
$old_query = "INSERT INTO tbl_izin_siswa (nis, nama_siswa, kelas, jenis_izin, detail_izin, lokasi_izin, foto_selfie, tanggal_izin, waktu_pengajuan, kategori_pengajuan, opsi_kembali, acc_wali, acc_satpam)";
$new_query = "INSERT INTO tbl_izin_siswa (no_induk_siswa, kelas_siswa, jenis_izin, detail_izin, lokasi_izin, foto_selfie, tanggal_izin, waktu_pengajuan, kategori_pengajuan, opsi_kembali, validasi_wali_kelas, validasi_satpam, status_izin)";

$content = str_replace($old_query, $new_query, $content);

$old_vals = "VALUES ('\$nis', '\$nama_siswa', '\$kelas', '\$jenis_izin', '\$detail_izin', '\$lokasi_izin', '\$foto_name', '\$tanggal_izin', '\$waktu_pengajuan', '\$kategori_pengajuan', \" . (\$opsi_kembali ? \"'\$opsi_kembali'\" : \"NULL\") . \", '\$acc_wali', \" . (\$acc_satpam ? \"'\$acc_satpam'\" : \"NULL\") . \")";
$new_vals = "VALUES ('\$nis', '\$kelas', '\$jenis_izin', '\$detail_izin', '\$lokasi_izin', '\$foto_name', '\$tanggal_izin', '\$waktu_pengajuan', '\$kategori_pengajuan', \" . (\$opsi_kembali ? \"'\$opsi_kembali'\" : \"NULL\") . \", 'Menunggu', 'Menunggu', 'Menunggu Validasi')";

$content = str_replace($old_vals, $new_vals, $content);

// Also remove the fallback query
$content = preg_replace('/else\s*\{\s*\$sql\s*=\s*"INSERT INTO tbl_izin_siswa.*?";\s*\}/s', '', $content);

file_put_contents($file, $content);
echo "Query updated!";
?>
