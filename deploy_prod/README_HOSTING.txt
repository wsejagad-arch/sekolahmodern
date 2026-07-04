E-JURNAL HOSTING DEPLOYMENT
==========================

Langkah Deploy:
1. Upload semua isi folder ini ke public_html hosting Anda.
2. Pastikan folder 'vendor', 'css', 'js', 'img', 'foto', 'phpqrcode', 'pages', 'materi' ikut terupload.
3. Import database: gunakan file SQL yang sudah ada di server hosting (DB: smasumb1_sijurnal).
4. Kredensial koneksi (di koneksi.php):
   Host: localhost
   User: smasumb1_sijurnal1
   Pass: JU-gxs^([=UN
   DB  : smasumb1_sijurnal
5. Jika blank page, aktifkan display_errors sementara atau cek error_log.
6. Pastikan versi PHP >= 7.4 dan ekstensi mysqli aktif.

Keamanan:
- Setelah verifikasi jalan, hapus file error_log bila ukurannya besar.
- Ganti password user DB jika sudah live dan update di koneksi.php.

Login Awal:
- Gunakan akun admin yang sudah dibuat di database.

— Generated on 2025-09-15 12:13:56
