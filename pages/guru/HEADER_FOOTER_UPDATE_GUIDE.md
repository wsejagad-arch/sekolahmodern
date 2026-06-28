# Panduan Update Header & Footer untuk Semua Halaman Guru

## Status Update

- ✅ `guru.php` - SUDAH DIUPDATE
- ✅ `guru_jurnal.php` - SUDAH DIUPDATE
- 🔄 `nilai.php` - READY TO UPDATE
- 🔄 `presensi.php` - READY TO UPDATE
- 🔄 `history-tugas.php` - READY TO UPDATE
- 🔄 `twibbon.php` - READY TO UPDATE
- 🔄 `validasi-izin.php` - READY TO UPDATE
- 🔄 `kalender.php` - READY TO UPDATE

## File-File Komponen Baru Yang Tersedia

### 1. **guru_header.php**

Header dan sidebar yang dapat digunakan di semua halaman guru.

- Sudah include: Bootstrap CSS, Bootstrap Icons, Poppins Font
- Sudah include: HTML DOCTYPE, meta tags, CSS untuk header/sidebar/footer
- Output: `<!DOCTYPE>` ... sampe opening `<main class="app-container">`

### 2. **guru_footer.php**

Footer dan JavaScript yang dapat digunakan di semua halaman guru.

- Sudah include: Bootstrap JS, SweetAlert2 (optional)
- Output: Closing `</main>`, `</body>`, `</html>` + mobile footer nav dan scripts

### 3. **guru_notifikasi.php**

File logika notifikasi yang di-include di header.

- Gunakan sebelum include guru_header.php
- Pastikan variable berikut sudah di-set:
  - `$conn` (database connection)
  - `$nipguru` (guru NIP)
  - `$tglskr` (current date Y-m-d format)
  - `$hariini` (day in Indonesian)
  - `$jadwalHariIni` (optional, untuk jurnal notification)

### 4. **guru_page_template.php**

File reference untuk struktur yang benar. Buka file ini untuk contoh lengkap.

## Cara Update Halaman Lama

### Template Umum

```php
<?php
session_start();
if (!isset($_SESSION["no_induk"])) {
  header("location: ../../index.php?haruslogin");
  exit;
} else if ($_SESSION['hak_akses'] != 2) {
  echo "<script>window.location='404.html';</script>";
  exit;
}

// INCLUDE DATABASE DAN FUNCTIONS
include "../../koneksi.php";
include "../../functions.php";
date_default_timezone_set('Asia/Jakarta');

// SETUP VARIABLES
$nipguru = $_SESSION['no_induk'];
$tglskr = date("Y-m-d");
$hariini = ubah_nama_hari($tglskr);
$lembaga = data_lembaga();

// AMBIL DATA YANG DIPERLUKAN HALAMAN (contoh untuk nilai.php)
$tanggal = mysqli_real_escape_string($conn, $_GET['tanggal'] ?? '');
$kelas = mysqli_real_escape_string($conn, $_GET['kelas'] ?? '');
// ... query lainnya ...

// SETUP NOTIFIKASI (optional)
$notifikasiData = [];
include 'guru_notifikasi.php';

// SETUP PAGE TITLE
$pageTitle = 'Halaman Guru';

// INCLUDE HEADER - INI OUTPUT HINGGA <main class="app-container">
include 'guru_header.php';
?>

<!-- HEADER/STYLE CUSTOM UNTUK PAGE INI (if any) -->
<style>
  /* CSS spesifik untuk halaman ini */
  .my-custom-class {
    /* ... */
  }
</style>

<!-- PAGE CONTENT -->
<div class="page-container" style="padding: 1rem; max-width: 1200px; margin: 0 auto;">
  <h2>Halaman Guru</h2>

  <!-- Content dari halaman lama, tanpa <html>, <head>, <body> tags -->
  <!-- Cukup copy semua content dari <body> tag yang ada sekarang -->

</div>

<!-- TAMBAHAN SPACE UNTUK MOBILE FOOTER -->
<div style="margin-bottom: 2rem;"></div>

<!-- INCLUDE FOOTER - INI OUTPUT DARI </main> HINGGA </html> -->
<?php include 'guru_footer.php'; ?>
```

## Step-by-Step Untuk Update File

### Contoh: Update `nilai.php`

1. **Backup file lama**

   ```
   Copy nilai.php → nilai.backup.php
   ```

2. **Extract PHP logic (jangan HTML)**
   - Ambil semua code dari opening `<?php` hingga sebelum `<!DOCTYPE>`
   - Sesuaikan dengan template di atas
   - Pastikan variable seperti `$tanggal`, `$kelas` sudah di-setup

3. **Setup Notifikasi**

   ```php
   $notifikasiData = [];
   include 'guru_notifikasi.php';
   $pageTitle = 'Input Nilai';
   ```

4. **Include Header**

   ```php
   include 'guru_header.php';
   ?>
   ```

5. **Move CSS Custom ke dalam <style> tag**
   - Ambil semua CSS dari `<head>` section (bukan dari guru_header.php)
   - Tulis dalam `<style>` tag setelah include header

6. **Wrap Content**

   ```html
   <div
     class="page-container"
     style="padding: 1rem; max-width: 1200px; margin: 0 auto;">
     <!-- Semua HTML content dari <body> halaman lama, tanpa tags <body> <html> <head> -->
   </div>
   ```

7. **Include Footer**
   ```php
   <?php include 'guru_footer.php'; ?>
   ```

## CSS Classes Yang Shared (dari guru_header.php)

Jangan duplicate CSS untuk classes ini, sudah ada di guru_header.php:

- `.app-header` - Header styling
- `.sidebar` - Sidebar styling
- `.bottom-nav` - Mobile footer navigation
- `.app-container` - Main container di desktop
- `.nav-item` - Navigation items
- `.notif-*` - Notification classes

## Responsive Breakpoints Yang Tersedia

- Mobile: < 768px
  - Bottom navigation visible
  - Sidebar slide-out dari kiri
  - Single column layout

- Desktop: ≥ 768px
  - Sidebar visible di sisi kiri (280px width)
  - Bottom navigation hidden
  - Multi-column layout

## JavaScript Functions Yang Sudah Available

Di guru_footer.php sudah tersedia:

- `toggleSidebar()` - Toggle sidebar di mobile
- `toggleNotifDropdown()` - Toggle notification dropdown
- `setActiveNavItem()` - Mark active nav item

Jangan perlu re-define function ini di halaman lain.

## Testing Checklist Setelah Update

- [ ] Header tampil di mobile (burger menu ada)
- [ ] Header tampil di desktop (logo + notif bell + logout)
- [ ] Sidebar bisa dibuka/tutup di mobile
- [ ] Sidebar visible dan rapi di desktop
- [ ] Mobile footer nav tampil di bawah layar mobile
- [ ] Mobile footer nav hidden di desktop
- [ ] Semua link di sidebar berfungsi
- [ ] Logout button bekerja
- [ ] Responsive design bekerja (test di berbagai ukuran)
- [ ] Content page tidak terpotong
- [ ] Pagination/scroll bekerja dengan baik

## FAQs

### Q: Apakah perlu copy CSS dari guru_header.php ke halaman lain?

A: Tidak. Pastikan untuk include guru_header.php agar CSS sudah loaded.

### Q: Kalau halaman punya custom CSS yang conflict?

A: Redesign CSS atau gunakan CSS specificity yang lebih tinggi untuk override.

### Q: Bagaimana cara add custom button ke header?

A: Edit guru_header.php di bagian `.header-right` div.

### Q: Bisakah menambah menu item di sidebar?

A: Ya, edit guru_header.php di bagian `.sidebar-menu` list.

### Q: Apakah halaman lama bisa tetap pakai HTML tag sendiri?

A: Tidak. Harus ganti dengan include guru_header.php dan guru_footer.php untuk consistency.

## Support Files Location

```
/pages/guru/
  ├── guru.php (✅ UPDATED)
  ├── guru_jurnal.php (✅ UPDATED)
  ├── guru_header.php (INCLUDE FILE)
  ├── guru_footer.php (INCLUDE FILE)
  ├── guru_notifikasi.php (INCLUDE FILE)
  ├── guru_page_template.php (REFERENCE)
  ├── HEADER_FOOTER_UPDATE_GUIDE.md (THIS FILE)
  │
  ├── nilai.php (ready to update)
  ├── presensi.php (ready to update)
  ├── history-tugas.php (ready to update)
  ├── twibbon.php (ready to update)
  ├── validasi-izin.php (ready to update)
  └── kalender.php (ready to update)
```

## Automated Script (Optional)

Untuk mempercepat update across multiple files, bisa membuat script wrapper yang:

1. Load existing HTML halaman
2. Strip DOCTYPE/html/head tags
3. Inject guru_header.php di awal
4. Inject guru_footer.php di akhir

Namun approach ini tidak recommended karena:

- Risk of breaking page-specific CSS/JS
- Sulit di-maintain
- Better untuk do manually per file

---

**Last Updated**: April 14, 2026
**Next Steps**: Update remaining pages according to this guide
