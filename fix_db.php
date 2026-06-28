<?php
require 'koneksi.php';
mysqli_query($conn, "UPDATE tbl_izin_siswa SET status_izin = 'Disetujui Penuh' WHERE validasi_wali_kelas = 'Disetujui' AND validasi_guru_bk = 'Disetujui' AND status_izin = 'Menunggu Validasi' AND kategori_pengajuan != 'Keluar Sekolah'");
$aff1 = mysqli_affected_rows($conn);
mysqli_query($conn, "UPDATE tbl_izin_siswa SET status_izin = 'Menunggu Satpam' WHERE validasi_wali_kelas = 'Disetujui' AND validasi_guru_bk = 'Disetujui' AND status_izin = 'Menunggu Validasi' AND kategori_pengajuan = 'Keluar Sekolah'");
$aff2 = mysqli_affected_rows($conn);
echo "Fixed existing rows. Affected: " . ($aff1 + $aff2);
