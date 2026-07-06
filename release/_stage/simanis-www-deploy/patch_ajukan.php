<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\ajukan-izin.php';
$content = file_get_contents($file);

$old_wa = <<<PHP
                // NOTIF WA WALI KELAS
                \$waliQuery = mysqli_query(\$conn, "SELECT g.no_wa FROM tbl_wali_kelas wk JOIN tbl_guru g ON wk.nip_wali = g.no_induk JOIN tbl_kelas k ON wk.id_kelas = k.id_kelas WHERE k.kelas = '\$kelas' LIMIT 1");
                if (\$waliQuery && mysqli_num_rows(\$waliQuery) > 0) {
                    \$rowWali = mysqli_fetch_assoc(\$waliQuery);
                    \$no_wa_wali = \$rowWali['no_wa'];
                    if (!empty(\$no_wa_wali)) {
                        \$pesanWA = "Halo Bapak/Ibu Wali Kelas,\\n\\nSiswa Anda:\\nNama: *\$nama_siswa*\\nKelas: *\$kelas*\\n\\nTelah mengajukan izin *\$kategori_pengajuan* dengan keterangan: _{\$detail_izin}_.\\n\\nSilakan periksa sistem untuk validasi.";
                        notif_send_whatsapp(\$no_wa_wali, "Pengajuan Izin Keluar", \$pesanWA, \$conn);
                    }
                }
PHP;

$new_wa = <<<PHP
                // NOTIF WA WALI KELAS
                \$waliQuery = mysqli_query(\$conn, "SELECT g.no_wa FROM tbl_kelas k JOIN tbl_guru g ON k.nip_wali = g.no_induk WHERE REPLACE(k.kelas, ' ', '') = REPLACE('\$kelas', ' ', '') LIMIT 1");
                if (\$waliQuery && mysqli_num_rows(\$waliQuery) > 0) {
                    \$rowWali = mysqli_fetch_assoc(\$waliQuery);
                    \$no_wa_wali = \$rowWali['no_wa'];
                    if (!empty(\$no_wa_wali)) {
                        \$pesanWA = "Halo Bapak/Ibu Wali Kelas,\\n\\nSiswa Anda:\\nNama: *\$nama_siswa*\\nKelas: *\$kelas*\\n\\nTelah mengajukan izin *\$kategori_pengajuan* dengan keterangan: _{\$detail_izin}_.\\n\\nSilakan periksa sistem untuk validasi.";
                        notif_send_whatsapp(\$no_wa_wali, "Pengajuan Izin Siswa", \$pesanWA, \$conn);
                    }
                }
                
                // NOTIF WA GURU BK
                \$bkQuery = mysqli_query(\$conn, "SELECT no_wa FROM tbl_guru WHERE (jabatan LIKE '%BK%' OR tugas_tambahan LIKE '%BK%') AND no_wa != ''");
                if (\$bkQuery && mysqli_num_rows(\$bkQuery) > 0) {
                    while (\$rowBk = mysqli_fetch_assoc(\$bkQuery)) {
                        \$no_wa_bk = \$rowBk['no_wa'];
                        if (!empty(\$no_wa_bk)) {
                            \$pesanWABK = "Halo Bapak/Ibu Guru BK,\\n\\nSiswa:\\nNama: *\$nama_siswa*\\nKelas: *\$kelas*\\n\\nTelah mengajukan izin *\$kategori_pengajuan* dengan keterangan: _{\$detail_izin}_.\\n\\nSilakan periksa sistem untuk memantau.";
                            notif_send_whatsapp(\$no_wa_bk, "Notifikasi Izin Siswa", \$pesanWABK, \$conn);
                        }
                    }
                }
PHP;

$content = str_replace($old_wa, $new_wa, $content);

// Also add id_sekolah if possible to the insert query
$old_insert = "INSERT INTO tbl_izin_siswa (no_induk_siswa, kelas_siswa, jenis_izin, detail_izin, lokasi_izin, foto_selfie, tanggal_izin, waktu_pengajuan, kategori_pengajuan, opsi_kembali, validasi_wali_kelas, validasi_satpam, status_izin) 
                        VALUES ('\$nis', '\$kelas', '\$jenis_izin', '\$detail_izin', '\$lokasi_izin', '\$foto_name', '\$tanggal_izin', '\$waktu_pengajuan', '\$kategori_pengajuan', \" . (\$opsi_kembali ? \"'\$opsi_kembali'\" : \"NULL\") . \", 'Menunggu', 'Menunggu', 'Menunggu Validasi')";

$school_id = "mt_current_school_id()";

$new_insert = "INSERT INTO tbl_izin_siswa (no_induk_siswa, kelas_siswa, jenis_izin, detail_izin, lokasi_izin, foto_selfie, tanggal_izin, waktu_pengajuan, kategori_pengajuan, opsi_kembali, validasi_wali_kelas, validasi_satpam, status_izin, id_sekolah) 
                        VALUES ('\$nis', '\$kelas', '\$jenis_izin', '\$detail_izin', '\$lokasi_izin', '\$foto_name', '\$tanggal_izin', '\$waktu_pengajuan', '\$kategori_pengajuan', \" . (\$opsi_kembali ? \"'\$opsi_kembali'\" : \"NULL\") . \", 'Menunggu', 'Menunggu', 'Menunggu Validasi', '\".mt_current_school_id().\"')";

$content = str_replace($old_insert, $new_insert, $content);

file_put_contents($file, $content);
echo "Updated ajukan-izin.php\n";
?>
