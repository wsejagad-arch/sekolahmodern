# SIMANIS Header & Footer Styling Guide

## Panduan Menerapkan Header & Footer Konsisten di Semua Halaman

### Status Terkini

✅ **guru.php** - Header dan Footer sudah menggunakan green gradient konsisten
✅ **Footer** - Fixed position with green gradient (`#10b981` to `#047857`)
✅ **Header** - Fixed position with matching green gradient
✅ **Profile Card** - Rapat dengan header tanpa spasi

### Warna yang Digunakan

#### Green Gradient (Header & Footer)

```css
background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
```

#### Accent Border (Cyan)

```css
border: 3px solid #06b6d4;
```

### Struktur CSS untuk Header & Footer

```css
/* HEADER */
.app-header {
  background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  border-bottom: 3px solid #06b6d4;
  box-shadow:
    0 4px 20px rgba(16, 185, 129, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

/* PROFILE CARD (Rapat dengan Header) */
.profile-card {
  background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
  margin-top: 70px;
  border-radius: 0;
  border-bottom: 2px solid #06b6d4;
}

/* FOOTER */
.bottom-nav {
  background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
  border-top: 3px solid #06b6d4;
  box-shadow:
    0 -4px 20px rgba(16, 185, 129, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

/* Body Padding untuk konten */
body {
  padding-top: 70px; /* Ruang untuk fixed header */
  padding-bottom: 80px; /* Ruang untuk fixed footer */
}
```

### Cara Menerapkan ke Halaman Lain

#### Metode 1: Copy CSS langsung

Salin seluruh styling dari guru.php ke halaman lain

#### Metode 2: Gunakan Component File (Rekomendasi)

```php
<?php
include '../../components/header_footer.php';
render_header_footer_styles();
?>
```

#### Metode 3: Buat File CSS Terpisah

```html
<link rel="stylesheet" href="../../css/simanis-theme.css" />
```

### Positioning & Z-Index

| Element      | Z-Index | Position     | Notes                         |
| ------------ | ------- | ------------ | ----------------------------- |
| Header       | 1000    | fixed top    | Paling atas, selalu terlihat  |
| Profile Card | inherit | relative     | Langsung dibawah header       |
| Content      | auto    | relative     | Di tengah                     |
| Footer       | 100     | fixed bottom | Paling bawah, selalu terlihat |

### Responsive Breakpoints

- **Mobile (< 576px)**: Footer visible, padding profile card terkurangi
- **Tablet (576px - 768px)**: Footer visible
- **Desktop (≥ 768px)**: Footer hidden, sidebar sidebar mode

### Testing Checklist

- [ ] Header muncul di atas halaman
- [ ] Profile card langsung dibawah header tanpa spasi
- [ ] Footer muncul di bawah halaman
- [ ] Warna konsisten (hijau gradient)
- [ ] Border cyan (#06b6d4) terlihat
- [ ] Responsive di mobile
- [ ] Z-index tidak overlap dengan modal

### File-File yang Sudah Updated

✅ `/pages/guru/guru.php` - Sudah menggunakan styling baru

### File-File yang Perlu Updated

Untuk menerapkan konsistensi ke halaman lain:

- [ ] `/pages/guru/guru_jurnal.php`
- [ ] `/pages/guru/nilai.php`
- [ ] `/pages/guru/presensi.php`
- [ ] `/pages/guru/inputtugas.php`
- [ ] `/pages/guru/kalender.php`
- [ ] `/pages/guru/validasi-izin.php`
- [ ] Halaman-halaman siswa di `/pages/siswa/`
- [ ] Halaman-halaman admin di `/pages/admin/`

---

**Created**: 2026-04-14
**Function**: Styling Guide untuk SIMANIS
**Maintained by**: Development Team
