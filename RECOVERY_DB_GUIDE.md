# Panduan Pemulihan Koneksi Database Lokal (XAMPP)

Jika aplikasi masih tidak bisa terhubung ke database (Access denied / connection failed), ikuti langkah-langkah berikut.

---
## 1. Pastikan MySQL Berjalan
- Buka XAMPP Control Panel
- Pastikan module MySQL berwarna hijau (status: Running)
- Jika belum berjalan, klik Start

## 2. Cek Port MySQL
- Klik tombol Config di baris MySQL → pilih my.ini
- Cari baris: `port=3306` (atau angka lain)
- Jika bukan 3306, catat nilainya dan buat file `config.local.php`:
```php
<?php
$cfg['port'] = 3307; // contoh kalau port 3307
?>
```

## 3. Cek Password root
Masuk ke phpMyAdmin:
- Buka http://localhost/phpmyadmin
- Jika langsung masuk tanpa diminta password → root password kosong
- Jika diminta login dan Anda tahu password → gunakan password itu di `config.local.php`:
```php
<?php
$cfg['user'] = 'root';
$cfg['password'] = 'PASSWORDANDA';
?>
```

## 4. Buat Database Jika Belum Ada
- Di phpMyAdmin klik Tab Databases
- Input nama: `jurnal` → Create
- Import struktur & data jika tersedia (contoh file: `include/db_appsiswa.sql`)

Langkah import:
1. Klik database `jurnal`
2. Klik tab Import
3. Pilih file SQL → Execute

## 5. Verifikasi Tabel Utama
Pastikan tabel penting ada, misal:
- tbl_guru
- tbl_siswa
- tbl_materi
- tbl_absen

Jika tidak ada → pastikan file SQL benar di-import.

## 6. Buat File config.local.php (Jika Perlu)
Buat file baru di root proyek: `config.local.php`
```php
<?php
$cfg['host'] = 'localhost';
$cfg['user'] = 'root';
$cfg['password'] = ''; // kosongkan jika tanpa password
$cfg['db'] = 'jurnal';
$cfg['port'] = 3306;  // atau sesuai my.ini
?>
```

## 7. Jalankan Tes
Buka:
- http://localhost:8000/diagnostic.php
- http://localhost:8000/test_koneksi_local.php

Jika masih gagal, lihat file log: `koneksi_error.log` di root proyek.

## 8. Reset Password root (Jika Lupa)
1. Stop MySQL di XAMPP
2. Jalankan MySQL dengan mode aman (skip grant tables) – Panduan resmi MySQL / banyak tutorial tersedia
3. Ubah password root
4. Jalankan ulang MySQL normal

## 9. Alternatif: Buat User Baru
Di phpMyAdmin:
1. Tab User Accounts → Add user
2. Username: `jurnaluser`
3. Host: `localhost`
4. Password: (isi sendiri)
5. Centang: Grant all privileges
6. Buat `config.local.php`:
```php
<?php
$cfg['user'] = 'jurnaluser';
$cfg['password'] = 'PASSWORD_BARU';
$cfg['db'] = 'jurnal';
?>
```

## 10. Jika Semua Gagal
Kirimkan informasi berikut:
- Output `diagnostic.php`
- Isi file `koneksi_error.log`
- Port MySQL
- Apakah database `jurnal` ada?

---
Semoga membantu! 👍
