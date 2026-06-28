# Panduan Troubleshooting "Gagal Memuat Form Jurnal" di Web Hosting

## Masalah
Error "Gagal memuat form jurnal" muncul saat mencoba input jurnal di web hosting.

## Penyebab Umum & Solusi

### 1. **Path Include Yang Salah**
**Masalah:** File PHP di folder `pages/guru/` tidak bisa mengakses `koneksi.php`

**Solusi:**
- Pastikan semua file di `pages/guru/` menggunakan path: `../../koneksi.php`
- Jangan gunakan path relatif seperti `koneksi.php` atau `../koneksi.php`

**File yang sudah diperbaiki:**
- ✅ `pages/guru/detailmateri.php` - menggunakan `../../koneksi.php`
- ✅ `pages/guru/simpanmateri.php` - menggunakan `../../koneksi.php`
- ✅ `pages/guru/guru_jurnal.php` - diperbaiki dari `koneksi.php` ke `../../koneksi.php`

### 2. **Kredensial Database Hosting**
**Masalah:** Database credentials tidak sesuai dengan hosting

**Solusi di `koneksi.php`:**
```php
<?php
$host = "localhost";
$port = "3306";
$user = "smasumb1_sijurnal1";
$password = "JU-gxs^([=UN";
$database = "smasumb1_sijurnal";

$conn = new mysqli($host, $user, $password, $database, $port);
mysqli_set_charset($conn, "utf8");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
```

### 3. **Error Reporting untuk Debugging**
**Tambahkan di awal file PHP:**
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Langkah Troubleshooting di Hosting

### Step 1: Test Koneksi Database
1. Upload file `test_hosting.php` ke root folder hosting
2. Akses: `https://domain-anda.com/test_hosting.php`
3. Periksa semua test menunjukkan ✅

### Step 2: Test AJAX Form
1. Upload file `test_ajax.php` ke folder `pages/guru/`
2. Akses: `https://domain-anda.com/pages/guru/test_ajax.php`
3. Periksa semua test menunjukkan ✅

### Step 3: Periksa File yang Bermasalah
**Jika error masih muncul, periksa file berikut:**

1. **`koneksi.php`** - harus di root folder
2. **`functions.php`** - harus di root folder  
3. **`pages/guru/detailmateri.php`** - pastikan path include benar
4. **`pages/guru/simpanmateri.php`** - pastikan path include benar

### Step 4: Periksa Permissions File
**Di hosting, pastikan permissions:**
- File PHP: 644
- Folder: 755

### Step 5: Periksa Error Log Hosting
**Lokasi biasanya di:**
- cPanel → Error Logs
- Atau file `error_log` di folder public_html

## Checklist Verifikasi

### ✅ Struktur File yang Benar:
```
root/
├── koneksi.php (credentials hosting)
├── functions.php
├── index.php
└── pages/
    └── guru/
        ├── detailmateri.php (include "../../koneksi.php")
        ├── simpanmateri.php (include "../../koneksi.php")
        └── guru_jurnal.php (include "../../koneksi.php")
```

### ✅ Database Credentials:
- Host: `localhost`
- Port: `3306`
- User: `smasumb1_sijurnal1`
- Password: `JU-gxs^([=UN`
- Database: `smasumb1_sijurnal`

### ✅ File Permissions:
- Semua file .php: 644
- Semua folder: 755

## Jika Masih Error

1. **Aktifkan error reporting** di semua file PHP
2. **Periksa error log** hosting untuk detail error
3. **Test koneksi database** langsung dengan file test
4. **Pastikan semua path include** menggunakan relative path yang benar
5. **Hapus file config.local.php** jika ada (hanya untuk development)

## File Test yang Disediakan

1. **`test_hosting.php`** - Test koneksi database dan path
2. **`pages/guru/test_ajax.php`** - Test AJAX request form jurnal
3. **`debug_jurnal.php`** - Debugging lengkap journal form

Jalankan ketiga file test ini di hosting untuk memastikan semua konfigurasi benar.