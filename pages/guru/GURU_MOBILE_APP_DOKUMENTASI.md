# 📱 Guru Dashboard Mobile App - Dokumentasi

## 📋 Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Fitur Utama](#fitur-utama)
3. [Cara Mengakses](#cara-mengakses)
4. [Penjelasan Interface](#penjelasan-interface)
5. [Panduan Penggunaan](#panduan-penggunaan)

---

## 🎯 Pengenalan

**Guru Dashboard Mobile App** adalah versi mobile-optimized dari dashboard guru yang dirancang khusus untuk memberikan pengalaman seperti aplikasi mobile native. Interface ini dioptimalkan untuk:

- ✅ **Tampilan responsif** di semua ukuran layar (mobile, tablet, desktop)
- ✅ **User experience app-like** dengan navigasi intuitif
- ✅ **Loading cepat** dengan desain yang ringan
- ✅ **Aksesibilitas tinggi** dengan touch-friendly buttons
- ✅ **Desain modern** dengan gradient colors dan smooth animations

---

## ⚡ Fitur Utama

### 1. **Header Modern**

- Logo sekolah dan nama institusi
- Avatar profil guru
- Sticky position untuk akses mudah

### 2. **Greeting Card**

- Salam personal untuk guru
- Motivasi/pesan setiap hari

### 3. **Today Info**

- Menampilkan hari dan tanggal saat ini
- Identifikasi cepat jadwal hari ini

### 4. **Quick Actions Grid** (6 Tombol Cepat)

```
┌─────────────────────────┐
│ 📝 Input Jurnal      │
├──────────────────────────┤
│ 🖨️  Cetak Jurnal     │
├──────────────────────────┤
│ ✏️  Input Nilai       │
├──────────────────────────┤
│ 📊 Daftar Nilai       │
├──────────────────────────┤
│ 👥 Daftar Presensi    │
├──────────────────────────┤
│ 📄 Laporan Kelas      │
└─────────────────────────┘
```

### 5. **Schedule Section** (Jadwal Hari Ini)

- Daftar semua jadwal untuk hari ini
- Tampil otomatis setelah waktu dimulai
- Info: Kelas, Mapel, Jam
- Tombol aksi: Isi Jurnal, Input Nilai
- Informasi file materi (jika ada)

### 6. **Bottom Navigation**

- Navigasi fixed di bawah layar
- 4 menu utama: Beranda, Jadwal, Atas, Logout
- Mobile-first design

### 7. **Modals/Dialog**

- Input Jurnal dengan form interaktif
- Input Nilai dengan tabel responsif
- Cetak Jurnal dengan iframe preview
- Pilih Jadwal untuk aksi cepat

---

## 🚀 Cara Mengakses

### Opsi 1: Buat Link di Dashboard

Tambahkan link ke halaman dashboard guru:

```html
<!-- Di guru.php atau guru_legacy.php -->
<a href="guru_mobile_app.php" class="btn btn-primary">Versi Mobile</a>
```

### Opsi 2: Setup Redirect Otomatis (Mobile-First)

Edit file `guru.php`:

```php
<?php
// Deteksi jika device mobile
$is_mobile = preg_match('/Mobile|Android|iPhone|iPad|iPod/', $_SERVER['HTTP_USER_AGENT']);

if ($is_mobile) {
    header('Location: guru_mobile_app.php');
    exit;
} else {
    header('Location: guru_legacy.php');
    exit;
}
?>
```

### Opsi 3: Menu Selection

Tambahkan di layout header untuk memilih versi:

```html
<div class="version-switcher">
  <a href="guru_legacy.php">Versi Desktop</a> |
  <a href="guru_mobile_app.php">Versi Mobile</a>
</div>
```

---

## 🎨 Penjelasan Interface

### Header Section

```
┌────────────────────────────────────────┐
│ 🏫 SMA Negeri 1 Jakarta   │ [👤]      │
│    Jl. Merdeka No. 1      │           │
└────────────────────────────────────────┘
```

**Elemen:**

- **Logo:** Clickable untuk back to top
- **School Info:** Nama sekolah + alamat (text muted untuk address)
- **Avatar:** Profile picture guru (rounded, dari folder foto/)

### Content Area

```
┌─────────────────────────────────────┐
│ Hai, Budi Santoso 👋               │
│ Kelola pembelajaran dengan mudah     │
│                                     │
│ 📅 Senin, 13 Mei 2026             │
│                                     │
│ AKSI CEPAT:                         │
│ [📝] [🖨️] [✏️] [📊] [👥] [📄]     │
│                                     │
│ JADWAL HARI INI:                    │
│ ┌─────────────────────────────┐    │
│ │ Kelas X A - Bahasa Indonesia│    │
│ │ 08:00 - 09:00 WIB          │    │
│ │ [Isi Jurnal] [Input Nilai]  │    │
│ └─────────────────────────────┘    │
│                                     │
│ ┌─────────────────────────────┐    │
│ │ Kelas X B - Matematika      │    │
│ │ 09:30 - 10:30 WIB          │    │
│ │ [Isi Jurnal] [Input Nilai]  │    │
│ └─────────────────────────────┘    │
└─────────────────────────────────────┘
```

### Bottom Navigation

```
┌──────────────────────────────────────┐
│ 🏠       📅        ⬆️        🚪      │
│ Beranda  Jadwal    Atas    Logout   │
└──────────────────────────────────────┘
```

---

## 📖 Panduan Penggunaan

### 1. **Input Jurnal**

```
Langkah:
1. Klik tombol "Input Jurnal" di aksi cepat
2. Pilih jadwal (jika ada lebih dari 1)
3. Isi form jurnal yang muncul di modal
4. Klik Submit untuk menyimpan
5. Muncul notifikasi sukses
```

### 2. **Cetak Jurnal**

```
Langkah:
1. Klik tombol "Cetak Jurnal"
2. Tunggu hingga PDF terload di preview
3. Gunakan tombol cetak browser atau download
4. Tutup modal dengan X atau klik luar modal
```

### 3. **Input Nilai**

```
Langkah:
1. Klik tombol "Input Nilai"
2. Pilih kelas dan pertemuan
3. Input nilai untuk setiap siswa
4. Klik Simpan
5. Nilai tersimpan di database
```

### 4. **Lihat Daftar Nilai**

```
Langkah:
1. Klik tombol "Daftar Nilai"
2. Pilih mata pelajaran
3. Pilih kelas
4. Lihat daftar nilai siswa
5. Bisa edit individual siswa
```

### 5. **Lihat Daftar Presensi**

```
Langkah:
1. Klik tombol "Daftar Presensi"
2. Pilih kelas
3. Lihat rekap absensi
4. Filter berdasarkan bulan/periode
```

### 6. **Lihat Detail Jadwal**

```
Langkah:
1. Klik menu "Jadwal" di bottom nav
2. Lihat seluruh jadwal mengajar
3. Daftar lengkap untuk semua hari
4. Info per kelas
```

---

## 🎯 Color Scheme

| Element   | Color     | Gradient       |
| --------- | --------- | -------------- |
| Primary   | `#0d6efd` | Blue - Navy    |
| Success   | `#20c997` | Green - Teal   |
| Warning   | `#f59e0b` | Amber - Orange |
| Info      | `#06b6d4` | Cyan - Blue    |
| Secondary | `#6c757d` | Gray           |
| Danger    | `#ef4444` | Red            |

---

## ⚙️ Customization

### Ubah Warna Primary

Di file `guru_mobile_app.php`, cari:

```css
.app-header {
  background: linear-gradient(135deg, #0d6efd 0%, #0856ca 100%);
  /* Ubah #0d6efd dan #0856ca dengan warna pilihan Anda */
}
```

### Ubah Greeting Message

Edit bagian:

```php
<p class="greeting-text">Hai, <?= htmlspecialchars(substr($dataguru['nama_guru'] ?? ($_SESSION["nama_guru"] ?? 'Guru'), 0, 20)); ?> 👋</p>
```

### Tambah Quick Action Baru

Tambahkan button di quick-actions-grid:

```html
<button class="quick-action-btn qa-primary" id="qaNewAction">
  <i class="bi bi-icon-name"></i>
  Nama Aksi
</button>
```

Tambahkan handler di JavaScript:

```javascript
$("#qaNewAction").on("click", function () {
  window.location = "link-ke-halaman";
});
```

---

## 📱 Responsive Breakpoints

| Size    | Width         | Layout                    |
| ------- | ------------- | ------------------------- |
| Mobile  | < 576px       | Single column, full width |
| Tablet  | 576px - 767px | Single column, full width |
| Desktop | ≥ 768px       | Max-width 600px, centered |

---

## 🔒 Security Notes

✅ Input sudah di-escape dengan `htmlspecialchars()`  
✅ Session checking di awal file  
✅ Role validation (hak_akses == 2)  
✅ CSRF protection dari config.php  
✅ Database query dengan parameterized input (via koneksi.php)

---

## 🐛 Troubleshooting

### Modal tidak muncul

- Pastikan Bootstrap 5 CDN sudah loaded
- Cek console browser untuk error
- Clear browser cache (Ctrl+Shift+Del)

### Data tidak muncul

- Cek koneksi database
- Verify jadwal ada di database
- Check file `koneksi.php` sudah benar

### Styling berantakan

- Clear CSS cache
- Reload halaman (Ctrl+F5)
- Cek viewport meta tag di head

---

## 📞 Support

Untuk pertanyaan atau issue, hubungi:

- IT Department
- Dashboard developer
- Check error_log di root folder

---

**Last Updated:** May 13, 2026  
**Version:** 1.0  
**Author:** System
