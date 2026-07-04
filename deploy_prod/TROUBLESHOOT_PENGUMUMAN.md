# TROUBLESHOOTING: Halaman Pengumuman Kosong

## Kemungkinan Penyebab:

### 1. Session Tidak Set
**Gejala:** Halaman kosong total
**Solusi:** Buka test_pengumuman.php untuk cek session

### 2. Database Connection Error  
**Gejala:** Halaman kosong atau error
**Cek:** test_pengumuman.php akan menunjukkan status koneksi

### 3. File Path Salah
**Cek:** 
- File harus di: `pages/admin/pengumuman.php`
- Include di home.php: `include "pages/admin/pengumuman.php";`

### 4. PHP Error
**Cek:** 
1. Buka http://localhost/jurnal/deploy_prod/test_pengumuman.php
2. Lihat debug output
3. Screenshot dan kirim jika ada error

## Quick Fix Steps:

### Step 1: Test dengan file debug
```
http://localhost/jurnal/deploy_prod/test_pengumuman.php
```

Lihat output:
- Session Info - harus ada hak_akses = 1
- Database Connection - harus SUCCESS  
- Include Test - harus ada output

### Step 2: Jika session kosong
Login dulu sebagai admin di:
```
http://localhost/jurnal/deploy_prod/index.php
```

Lalu buka lagi:
```
http://localhost/jurnal/deploy_prod/home.php?page=pengumuman
```

### Step 3: Jika database error
Cek di test_pengumuman.php:
- Apakah tabel tbl_pengumuman ada?
- Apakah koneksi berhasil?

Jika tabel tidak ada, jalankan di phpMyAdmin:
```sql
CREATE TABLE IF NOT EXISTS tbl_pengumuman (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  isi TEXT NOT NULL,
  penting TINYINT(1) DEFAULT 0,
  mulai DATE NOT NULL,
  selesai DATE NOT NULL,
  target_scope ENUM('SEMUA','KELAS','TINGKAT','GURU') DEFAULT 'SEMUA',
  target_value VARCHAR(100) DEFAULT NULL,
  lampiran VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARSET=utf8mb4;
```

### Step 4: View Source
Buka halaman pengumuman, lalu:
1. Klik kanan > View Page Source (Ctrl+U)
2. Cek apakah ada error message di HTML
3. Screenshot dan kirim

## Files to Check:

1. ✅ `deploy_prod/home.php` - Line 153: `case 'pengumuman':`
2. ✅ `deploy_prod/pages/admin/pengumuman.php` - File exists?
3. ✅ `deploy_prod/koneksi.php` - Database config
4. ✅ Session - Login sudah berhasil?

## Expected Output di test_pengumuman.php:

```
1. Session Info:
   - Session Started: YES
   - Username: admin (atau username Anda)
   - Hak Akses: 1
   - Nama: Administrator

2. File Paths:
   - Current Dir: C:\xampp\htdocs\jurnal\deploy_prod
   - koneksi.php exists: YES
   - pages/admin/pengumuman.php exists: YES

3. Database Connection:
   - Connection: SUCCESS
   - Database: localhost via TCP/IP
   - Table tbl_pengumuman: EXISTS
   - Total records: 0

4. Include Test:
   - Including pengumuman.php...
   - Output Length: 12345 bytes (angka bervariasi)
```

Jika ada yang berbeda, screenshot dan kirim!
