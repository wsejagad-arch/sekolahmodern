# TEST PENGUMUMAN - CHECKLIST

## Files Yang Sudah Diupdate:
- ✅ home.php - Chart script hanya jalan di dashboard
- ✅ pages/admin/pengumuman.php - Form pengumuman dengan null checks
- ✅ Path include di home.php: `include "pages/admin/pengumuman.php";`

## Testing Steps:

### 1. Clear Browser Cache
- Tekan `Ctrl + Shift + Delete`
- Centang "Cached images and files"
- Klik Clear data
- ATAU buka Incognito/Private window

### 2. Login sebagai Admin
- http://localhost/jurnal/ (atau deploy_prod)
- Username & password admin

### 3. Test Menu Pengumuman
- Klik menu "Pengumuman" di sidebar
- Halaman harus muncul tanpa error
- Buka Console (F12) - tidak boleh ada error

### 4. Test Form Pengumuman
- Isi form:
  - Judul: "Test Pengumuman"
  - Tanggal Mulai: pilih tanggal hari ini
  - Tanggal Selesai: pilih tanggal besok
  - Isi: "Ini adalah test pengumuman"
  - Target: pilih "Semua"
  - (Optional) Centang "Tandai Penting"
- Klik "Simpan Pengumuman"
- Harus muncul alert hijau "Pengumuman berhasil disimpan"
- Data harus muncul di tabel bawah

### 5. Cek Console Errors
Buka Console (F12), seharusnya TIDAK ada error:
- ❌ TIDAK BOLEH ADA: "exports is not defined"
- ❌ TIDAK BOLEH ADA: "Cannot read properties of null"
- ❌ TIDAK BOLEH ADA: "getContext is null"
- ✅ BOLEH ADA: "Pengumuman form elements not found" (normal warning)
- ✅ HARUS ADA: "✅ Sidebar initialized"

## Expected Console Output (Normal):
```
DataTables demo: jQuery ready
DataTables demo: jQuery version: 3.6.0
✅ jQuery version: 3.6.0
✅ Select2 loaded? function
Initializing sidebar collapse...
Found 11 collapse links
✅ Sidebar collapse initialized successfully
✅ Sidebar initialized on attempt 1
Pengumuman form elements not found (ini NORMAL karena chart elements tidak ada di halaman pengumuman)
```

## Troubleshooting:

### Jika masih ada error "exports is not defined":
1. Cek file footer.php - pastikan tidak ada library corrupt
2. Pastikan semua library dari CDN (bukan vendor/)
3. Clear browser cache total (Ctrl + Shift + Delete)

### Jika form tidak menyimpan:
1. Cek koneksi database di koneksi.php
2. Pastikan tabel tbl_pengumuman ada di database
3. Cek error di Console (F12)

### Jika halaman blank:
1. Pastikan path include benar: `pages/admin/pengumuman.php`
2. Cek error PHP di browser atau error_log
3. Pastikan folder pages/admin/ ada

## Database Check:
Jalankan query ini di phpMyAdmin untuk memastikan tabel ada:
```sql
SHOW TABLES LIKE 'tbl_pengumuman';
DESC tbl_pengumuman;
```

Harus ada kolom:
- id, judul, isi, penting, mulai, selesai, target_scope, target_value, lampiran, created_at, updated_at

## Upload ke Hosting:
Setelah test berhasil di localhost, upload files ini via cPanel:
1. home.php
2. pages/admin/pengumuman.php
3. header.php (jika ada update)
4. footer.php (jika ada update)
5. sidebar.php (jika ada update)

Jangan lupa clear browser cache di hosting juga!
