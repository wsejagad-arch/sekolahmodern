# 📚 Sistem Login e-Jurnal Baru

## 🎯 Fitur-Fitur Baru

### 1. **Splash Screen** (`splash.php`)

- Tampilan awal dengan logo sekolah yang animated
- Loading animation yang menarik
- Otomatis redirect ke halaman pilihan login setelah 3 detik
- Responsive design untuk semua ukuran layar

### 2. **Halaman Pilihan Login** (`login-pilihan.php`)

- Grid cards yang menarik untuk setiap tipe login
- 3 pilihan: Guru, Admin Siswa, dan Siswa
- Deskripsi lengkap untuk setiap tipe akses
- Hover effects yang smooth dan interaktif
- Fully responsive dari mobile hingga desktop

### 3. **Login Guru** (`login-guru.php`)

- Design modern dengan gradient color ungu
- Form khusus dengan NIP/NUPTK
- Password toggle untuk kemudahan
- Animasi smooth saat load
- Responsive layout untuk mobile

### 4. **Login Admin Siswa** (`login-admin-siswa.php`)

- Design dengan gradient color merah
- Form standard username & password
- Interface yang profesional
- Password visibility toggle
- Optimized untuk semua device

### 5. **Login Siswa** (`login-siswa.php`)

- Design dengan gradient color hijau
- Form username/NIS & password
- User-friendly interface
- Mobile-first responsive design
- Consistent styling dengan halaman lainnya

## 📱 Responsive Design

Semua halaman dioptimalkan untuk:

- **Mobile** (< 480px)
- **Tablet** (480px - 768px)
- **Desktop** (> 768px)

## 🎨 UI/UX Highlights

✅ **Gradient Colors** yang menarik dan modern
✅ **Smooth Animations** untuk transisi yang halus
✅ **Icons** dari Bootstrap Icons
✅ **Typography** menggunakan Poppins font
✅ **Hover Effects** yang interaktif
✅ **Error Messages** dengan styling yang jelas
✅ **Back Navigation** untuk kemudahan navigasi

## 🔗 Alur Navigasi

```
index.php (redirect)
    ↓
splash.php (3 detik)
    ↓
login-pilihan.php
    ├─→ login-guru.php
    ├─→ login-admin-siswa.php
    └─→ login-siswa.php
    ↓
login_action.php (proses login)
```

## ⚡ Fitur Interaktif

1. **Password Toggle** - Tampilkan/sembunyikan password dengan ikon mata
2. **Form Validation** - Validasi form real-time
3. **Loading States** - Animasi loading yang menarik
4. **Error Handling** - Pesan error yang jelas dan informatif
5. **Smooth Transitions** - Animasi yang tidak mengganggu

## 🛠️ Teknologi Yang Digunakan

- **HTML5** - Struktur semantic
- **CSS3** - Gradient, animations, flexbox, grid
- **Bootstrap 5** - Responsive grid system
- **Bootstrap Icons** - Ikon modern
- **Vanilla JavaScript** - Interaktivitas tanpa library berat
- **Google Fonts** - Poppins typography

## 📦 File-File Baru

```
├── splash.php                 # Halaman awal dengan loading animation
├── login-pilihan.php          # Pilihan tipe login
├── login-guru.php             # Login untuk guru (NIP only)
├── login-admin-siswa.php      # Login untuk admin siswa
├── login-siswa.php            # Login untuk siswa
└── README_LOGIN.md            # Dokumentasi ini
```

## 🚀 Cara Menggunakan

1. Buka `http://localhost/jurnal/index.php`
2. Akan otomatis redirect ke splash screen
3. Tunggu 3 detik atau skip dengan click
4. Pilih tipe login yang diinginkan
5. Masukkan credentials
6. Klik tombol "Masuk"

## 🔒 Keamanan

- Password field dilindungi
- Form validation di client-side
- CSRF protection (via login_action.php)
- Input sanitization
- Session management

## 📱 Testing pada Different Devices

✅ Tested on:

- Mobile (iPhone, Android)
- Tablet (iPad, Android Tablet)
- Desktop (Windows, Mac, Linux)

## 💡 Tips Pengembangan

1. Modifikasi warna gradient sesuai brand sekolah
2. Ganti logo di `/img/` folder
3. Update deskripsi/subtitle di setiap halaman
4. Customize pesan error di form submission
5. Tambahkan analytics jika diperlukan

## 📋 Checklist Implementasi

- [x] Splash screen dengan logo
- [x] Login pilihan page
- [x] Login guru terpisah
- [x] Login admin siswa terpisah
- [x] Login siswa page
- [x] Responsive mobile design
- [x] Smooth animations
- [x] Password toggle
- [x] Back navigation
- [x] Error messages styling

## 🔧 Maintenance

- Update Bootstrap CDN versi terbaru jika diperlukan
- Monitor form submission di login_action.php
- Test responsiveness di berbagai browser
- Update logo/colors sesuai kebutuhan

---

**Dibuat oleh:** TIM IT
**Versi:** 1.0
**Tanggal:** 2026
