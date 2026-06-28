# Troubleshooting Halaman Pengumuman Admin

## Masalah: Halaman https://sijurnal.sma1sumber.sch.id/home.php?page=pengumuman tidak berfungsi

### Kemungkinan Penyebab:

1. **Koneksi Database Gagal**
   - Kredensial database di `config.php` tidak sesuai dengan hosting
   - Tabel `tbl_pengumuman` belum dibuat

2. **Session Admin Tidak Valid**
   - User belum login sebagai admin
   - Session `hak_akses` tidak di-set ke 1

3. **File atau Path Error**
   - File `pages/admin/pengumuman.php` tidak ada
   - Path koneksi database salah

### Solusi Step-by-Step:

#### 1. Periksa Login Admin
- Pastikan Anda login sebagai administrator
- Akses: `index.php` → Login dengan akun admin

#### 2. Setup Tabel Database
- Jalankan file `setup_pengumuman.php` di browser hosting
- URL: `https://sijurnal.sma1sumber.sch.id/setup_pengumuman.php`
- File ini akan membuat tabel `tbl_pengumuman` secara otomatis

#### 3. Periksa Koneksi Database
- Pastikan file `config.php` memiliki kredensial yang benar untuk hosting
- Untuk hosting cPanel, biasanya:
  ```php
  $host = "localhost";
  $user = "username_database";
  $password = "password_database";
  $database = "nama_database";
  ```

#### 4. Periksa File Structure
- Pastikan file `pages/admin/pengumuman.php` ada
- Pastikan folder `materi/` ada dan writable untuk upload lampiran

#### 5. Debug Mode
- Jika masih error, tambahkan debug di `pages/admin/pengumuman.php`:
  ```php
  echo "<!-- DEBUG: Session = " . print_r($_SESSION, true) . " -->";
  echo "<!-- DEBUG: Connection = " . ($conn ? 'OK' : 'FAILED') . " -->";
  ```

### File yang Terlibat:
- `home.php` - Routing ke halaman pengumuman
- `pages/admin/pengumuman.php` - Halaman utama pengumuman
- `koneksi.php` - Koneksi database
- `setup_pengumuman.php` - Setup tabel database

### Testing:
1. Akses `setup_pengumuman.php` untuk memastikan database OK
2. Login sebagai admin
3. Akses `home.php?page=pengumuman`
4. Jika masih error, periksa error log hosting

### Kontak Support:
Jika masalah berlanjut, berikan informasi:
- Error message yang muncul
- Status login (admin/guru/siswa)
- Hasil dari `setup_pengumuman.php`