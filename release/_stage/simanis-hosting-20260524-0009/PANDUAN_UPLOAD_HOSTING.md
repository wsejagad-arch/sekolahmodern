# Panduan Upload SIMANIS ke Web Hosting

## Isi paket

- Aplikasi PHP siap upload ke `public_html` atau subfolder
- Library Composer (TCPDF, HTML2PDF) untuk cetak PDF
- Folder `uploads/` kosong (permission tulis diperlukan)
- Folder `_instalasi/sql/` berisi skrip migrasi opsional
- Dua varian `.htaccess` di folder `htaccess/`

## Persyaratan hosting

- PHP **7.4+** (disarankan 8.0+)
- Ekstensi: `mysqli`, `gd`, `mbstring`, `zip`, `json`
- MySQL / MariaDB
- `mod_rewrite` aktif (Apache) atau aturan rewrite setara (LiteSpeed)

## Langkah upload

### 1. Ekstrak ZIP

Ekstrak isi ZIP ke:

- **Subfolder:** `public_html/jurnal/` — gunakan `htaccess/.htaccess-subfolder` sebagai `.htaccess`
- **Root domain:** `public_html/` — gunakan `htaccess/.htaccess-root` sebagai `.htaccess`

Salin file htaccess yang sesuai ke `.htaccess` di folder aplikasi.

### 2. Database

1. Buat database dan user di cPanel / panel hosting
2. Import backup database produksi (phpMyAdmin)
3. Salin `config.hosting.example.php` → `config.hosting.php`
4. Isi host, user, password, dan nama database

### 3. Permission folder

Set permission **755** atau **775** (sesuai kebijakan hosting) untuk:

- `uploads/` dan semua subfoldernya
- `logs/`
- `img/` (jika upload logo/foto lewat aplikasi)

### 4. Konfigurasi Google OAuth (opsional)

Jika memakai login Google, atur redirect URI di Google Cloud Console sesuai domain hosting, misalnya:

`https://domainanda.com/jurnal/google-callback.php`

### 5. Uji aplikasi

Buka `https://domainanda.com/jurnal/` — harus mengarah ke halaman splash/login.

## Keamanan setelah upload

- Jangan biarkan file `config.hosting.php` bisa diakses publik (sudah diblokir `.htaccess`)
- Hapus file uji/debug jika masih ada
- Nonaktifkan `display_errors` di production (sudah diset di paket hosting)
- Ganti password admin default jika masih memakai password bawaan

## Pemecahan masalah

| Gejala | Solusi |
|--------|--------|
| 500 Internal Server Error | Cek error log hosting; pastikan PHP 7.4+ |
| Halaman tanpa CSS | Pastikan folder `vendor/`, `css/`, `js/` ter-upload |
| Koneksi database gagal | Periksa `config.hosting.php` dan hak akses user DB |
| URL cantik tidak jalan | Aktifkan mod_rewrite; sesuaikan `RewriteBase` di `.htaccess` |
| Upload gagal | Permission folder `uploads/` harus writable |

## Membuat ulang paket

Di komputer development (Windows):

```powershell
cd C:\xampp\htdocs\jurnal
powershell -ExecutionPolicy Bypass -File .\build-hosting.ps1
```

File ZIP akan ada di folder `release/`.
