# ✅ DAFTAR FILE YANG HARUS DI-UPLOAD KE HOSTING

## 🔴 FILE PENTING (WAJIB UPLOAD - INI YANG MENYEBABKAN SIDEBAR TIDAK BERFUNGSI)

### 1. **sidebar.php** [CRITICAL - PALING PENTING]
   - **Status:** ✅ SUDAH DIPERBAIKI
   - **Masalah:** Menu Pengumuman & Quotes tidak muncul di sidebar
   - **Perbaikan:** Struktur HTML fixed, menu sekarang terpisah dengan benar
   - **Ukuran file:** ~8 KB
   - **Path di hosting:** `/sidebar.php`

### 2. **pages/admin/pengumuman.php** [CRITICAL]
   - **Status:** ✅ SUDAH DIKONVERSI KE MySQLi
   - **Masalah:** Menggunakan PDO (hosting pakai MySQLi)
   - **Perbaikan:** Semua query dikonversi ke MySQLi syntax
   - **Ukuran file:** ~7 KB
   - **Path di hosting:** `/pages/admin/pengumuman.php`

### 3. **pages/admin/kelola-quotes.php** [CRITICAL]
   - **Status:** ✅ SUDAH DIPERBAIKI (perbaikan sebelumnya)
   - **Masalah:** Duplicate HTML sudah dihapus
   - **Ukuran file:** ~8 KB
   - **Path di hosting:** `/pages/admin/kelola-quotes.php`

---

## 🟡 FILE PENTING (JIKA BELUM ADA DI HOSTING)

### 4. **header.php**
   - **Fungsi:** HTML head dengan CSS/JS includes + polyfill exports
   - **Path di hosting:** `/header.php`
   - **Ukuran:** ~2 KB

### 5. **home.php**
   - **Fungsi:** Router utama yang handle semua page routing
   - **Path di hosting:** `/home.php`
   - **Ukuran:** ~25 KB

---

## 🟢 FILE SETUP (JALANKAN JIKA TABEL BELUM ADA)

### 6. **setup_pengumuman.php**
   - **Fungsi:** Buat table pengumuman & seed data contoh
   - **Cara pakai:** Buka di browser: `https://domain.com/setup_pengumuman.php`
   - **Jalankan:** Sekali saja (pertama kali)
   - **Path di hosting:** `/setup_pengumuman.php`

### 7. **setup_quotes.php**
   - **Fungsi:** Buat table quotes & seed data contoh
   - **Cara pakai:** Buka di browser: `https://domain.com/setup_quotes.php`
   - **Jalankan:** Sekali saja (pertama kali)
   - **Path di hosting:** `/setup_quotes.php`

---

## 📋 FILE DIAGNOSTIC (UNTUK TROUBLESHOOTING)

### 8. **diagnostic_sidebar.php** [OPTIONAL]
   - **Fungsi:** Cek status database connection, table existence, session
   - **Cara pakai:** Buka di browser: `https://domain.com/diagnostic_sidebar.php`
   - **Guna:** Debugging jika masih ada masalah
   - **Path di hosting:** `/diagnostic_sidebar.php`

### 9. **PERBAIKAN_SIDEBAR_MENU.md** [DOKUMENTASI]
   - **Fungsi:** Dokumentasi lengkap perbaikan yang dilakukan
   - **Path di hosting:** `/PERBAIKAN_SIDEBAR_MENU.md` (tidak wajib)

---

## 📦 URUTAN UPLOAD DAN EKSEKUSI

### STEP 1: Upload File Kritis (5 menit)
Gunakan FTP Client atau File Manager hosting:
1. Upload `sidebar.php` → `/sidebar.php`
2. Upload `pages/admin/pengumuman.php` → `/pages/admin/pengumuman.php`
3. Upload `pages/admin/kelola-quotes.php` → `/pages/admin/kelola-quotes.php`
4. Upload `header.php` → `/header.php` (jika belum ada)
5. Upload `home.php` → `/home.php` (jika belum ada)

### STEP 2: Jalankan Setup Scripts (1 menit)
1. Buka: `https://sijurnal.sma1sumber.sch.id/setup_pengumuman.php`
   - Tunggu sampai selesai, akan ada pesan sukses
2. Buka: `https://sijurnal.sma1sumber.sch.id/setup_quotes.php`
   - Tunggu sampai selesai, akan ada pesan sukses

### STEP 3: Test (2 menit)
1. Buka: `https://sijurnal.sma1sumber.sch.id/home.php`
2. Login sebagai admin
3. Lihat sidebar kanan - Pengumuman & Quotes harus ada
4. Klik menu untuk test collapse/expand

---

## 🧪 TESTING CHECKLIST

Setelah upload selesai, verifikasi ini:

- [ ] Sidebar menu muncul (sidebar.php loaded)
- [ ] Menu "Pengumuman" terlihat di sidebar (admin only)
- [ ] Menu "Quotes" terlihat di sidebar (admin only)
- [ ] Klik "Pengumuman" → collapse/expand works
- [ ] Klik "Quotes" → collapse/expand works
- [ ] Klik "Kelola Pengumuman" → page loads (pengumuman.php)
- [ ] Klik "Daftar Quotes" → page loads (kelola-quotes.php)
- [ ] Form "Tambah Pengumuman" → bisa submit
- [ ] Form "Tambah Quotes" → bisa submit
- [ ] Badge counter pengumuman tampil (jika ada data aktif)
- [ ] Edit button bekerja
- [ ] Delete button bekerja
- [ ] Browser console tidak ada error (F12 → Console tab)

---

## 🆘 JIKA MASIH ADA MASALAH

### Langkah Troubleshooting:

1. **Clear Browser Cache**
   - Tekan: `Ctrl + Shift + Delete`
   - Pilih "All time" dan hapus

2. **Jalankan Diagnostic**
   - Buka: `https://sijurnal.sma1sumber.sch.id/diagnostic_sidebar.php`
   - Lihat status semua komponen

3. **Periksa di FTP**
   - Pastikan semua file sudah terupload dengan benar
   - Pastikan file size tidak 0 bytes

4. **Lihat Error di Console**
   - Tekan F12 → Console tab
   - Ada error? Screenshotkan dan tanyakan

5. **Jika Tabel Tidak Ada**
   - Buka setup scripts lagi
   - Lihat pesan error yang muncul

---

## 📝 RINGKAS: FILE YANG PALING PENTING

**UPLOAD INI TERLEBIH DAHULU** (Ini yang rusak):
1. ✅ `sidebar.php`
2. ✅ `pages/admin/pengumuman.php`
3. ✅ `pages/admin/kelola-quotes.php`

**KEMUDIAN JALANKAN** (Setup tabel):
4. 🔗 `https://domain.com/setup_pengumuman.php`
5. 🔗 `https://domain.com/setup_quotes.php`

**HASILNYA:**
✨ Sidebar menu Pengumuman & Quotes akan muncul dan berfungsi!

---

**Questions?** Gunakan `diagnostic_sidebar.php` untuk cek status lengkap.
**Last Updated:** 18 Januari 2026
