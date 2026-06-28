# E-Jurnal Deployment Guide

## Instalasi di Web Hosting

### 1. Upload Files
- Upload semua file ke direktori public_html atau folder utama hosting
- Pastikan struktur folder tetap sama

### 2. Database Setup
- Buat database dengan nama: `smasumb1_sijurnal`
- Import file SQL dari folder `include/db_appsiswa.sql`
- Database credentials sudah dikonfigurasi di `koneksi.php`:
  - Host: localhost
  - Username: smasumb1_sijurnal1
  - Password: JU-gxs^([=UN
  - Database: smasumb1_sijurnal

### 3. Folder Permissions
Pastikan folder berikut memiliki permission write (755 atau 777):
- `/foto/` - untuk upload foto profil
- `/materi/` - untuk upload file materi
- `/file_materi/` - untuk file pembelajaran
- `/temp/` - untuk file temporary

### 4. File Configuration
- File `koneksi.php` sudah dikonfigurasi untuk hosting
- Untuk development lokal, copy `config.local.php.example` menjadi `config.local.php` dan sesuaikan

### 5. Features Included
✅ Login guru dengan NIP saja
✅ Penilaian dinamis per pertemuan  
✅ Input nilai dengan UI modern
✅ Grafik prosentase pengisian jurnal
✅ Presensi siswa dengan opsi H/S/I/A/D
✅ Tabel presensi tanpa weekend (Sabtu/Minggu)
✅ Export presensi ke PDF/CSV/Excel
✅ Mode rekap bulanan
✅ Cache management
✅ Dashboard guru modern

### 6. Default Login
- Admin: username/password sesuai database
- Guru: gunakan NIP yang terdaftar di tbl_guru

### 7. Troubleshooting
- Jika ada error koneksi database, cek credentials di `koneksi.php`
- Jika file upload error, cek permission folder
- Untuk development, gunakan `config.local.php`

### 8. Support
Sistem ini sudah siap deploy dan terintegrasi dengan semua fitur yang diminta.