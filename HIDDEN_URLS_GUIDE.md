# 🔐 Hidden URL System - Documentation

## Overview

Semua URLs pages sudah disembunyikan di balik sistem routing yang aman. User tidak akan lihat struktur file asli di URL bar.

## Bagaimana Cara Kerjanya

### Sebelumnya (Exposed)

```
URL bar: http://localhost/jurnal/pages/guru/data-siswa.php
User bisa: Langsung akses file, lihat struktur folder
```

### Sekarang (Hidden)

```
URL bar: http://localhost/jurnal/pages/guru/data-siswa
Internally: Direwrite ke router.php?type=guru&page=data-siswa
Actual file: pages/guru/data-siswa.php
User tidak bisa: Akses file langsung, lihat struktur folder
```

## Implementasi

### 1. Helper Functions (di bootstrap.php)

```php
// Untuk guru pages
guru_page('data-siswa')                    // → /pages/guru/data-siswa
guru_page('data-siswa', ['id' => 123])     // → /pages/guru/data-siswa?id=123

// Untuk siswa pages
siswa_page('presensi')                     // → /pages/siswa/presensi
siswa_page('profil', ['no' => 123])        // → /pages/siswa/profil?no=123

// Untuk admin pages
admin_page('pengumuman')                   // → /pages/admin/pengumuman
admin_page('pengumuman', ['id' => 1])      // → /pages/admin/pengumuman?id=1
```

### 2. Penggunaan di HTML Links

**❌ Jangan (Old Way - Exposed)**

```html
<a href="pages/guru/data-siswa.php">Data Siswa</a>
<a href="pages/siswa/presensi.php?no=123">Presensi</a>
```

**✅ Harus (New Way - Hidden)**

```html
<a href="<?= guru_page('data-siswa') ?>">Data Siswa</a>
<a href="<?= siswa_page('presensi', ['no' => 123]) ?>">Presensi</a>
```

### 3. Penggunaan di JavaScript

**❌ Jangan (Old Way)**

```javascript
window.location = "pages/guru/data-siswa.php";
```

**✅ Harus (New Way)**

```javascript
window.location = '<?= guru_page('data-siswa') ?>';
```

### 4. Penggunaan di JavaScript AJAX

**Contoh:**

```javascript
fetch('<?= guru_page('api-data-siswa', ['class' => 'X']) ?>')
    .then(r => r.json())
    .then(data => console.log(data));
```

## File yang Sudah Setup

| File              | Fungsi                                                    |
| ----------------- | --------------------------------------------------------- |
| `router.php`      | Central router untuk semua page requests                  |
| `pages/.htaccess` | Rewrite rules untuk pages directory                       |
| `bootstrap.php`   | Helper functions: guru_page(), siswa_page(), admin_page() |

## Testing

Silakan test di: **http://localhost/jurnal/router_test.php**

Script ini akan:

- ✓ Menampilkan generated URLs
- ✓ Cek apakah file ada
- ✓ Provide test links

## Security Features

1. **Path Traversal Protection**
   - Hanya file di directory yang tepat yang bisa diakses
   - File lookup dibatasi dengan realpath()

2. **Access Control**
   - Guru page hanya bisa diakses role guru
   - Siswa page hanya bisa diakses role siswa
   - Admin page hanya bisa diakses role admin

3. **Direct File Access Blocked**
   - Akses langsung ke pages/guru/file.php akan ditolak
   - Harus melalui router.php

4. **Input Sanitization**
   - Page parameter di-sanitize dengan regex
   - Mencegah path traversal: `../../config.php`

## Migrasi File Lama

### Step-by-Step Untuk Update Existing Files

1. Buka file yang perlu update
2. Cari semua links ke `pages/guru/`, `pages/siswa/`, `pages/admin/`
3. Ganti dengan helper functions

**Example:**

File: `pages/guru/data-siswa.php`

```html
<!-- SEBELUM -->
<a href="pages/guru/nilai.php">Nilai</a>
<a href="pages/guru/presensi.php?id=<?= $id ?>">Presensi</a>

<!-- SESUDAH -->
<a href="<?= guru_page('nilai') ?>">Nilai</a>
<a href="<?= guru_page('presensi', ['id' => $id]) ?>">Presensi</a>
```

## Common Issues

### Issue: "Access denied: Guru only"

**Solusi:** User tidak login sebagai guru. Login sebagai guru dulu.

### Issue: "Page not found"

**Solusi:** File pages/guru/namafile.php tidak ada. Cek nama file dengan benar.

### Issue: URL masih menunjukkan router.php?type=guru

**Solusi:** .htaccess di pages/ directory tidak bekerja. Check mod_rewrite enabled di Apache.

## Next Steps

1. ✅ Setup router.php
2. ✅ Setup pages/.htaccess
3. ✅ Add helper functions
4. ⏳ Update existing links in files
5. ⏳ Test all pages

Untuk update existing links, bisa manual atau gunakan script find-replace.

---

**Dokumentasi dibuat:** 2026-05-12  
**Status:** Ready to use ✅
