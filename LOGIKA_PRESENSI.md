# DOKUMENTASI LOGIKA PRESENSI SISWA & JURNAL GURU (SSIMANIS)

Dokumen ini mencatat seluruh aturan dan logika bisnis sistem presensi siswa, jadwal sholat, penguncian jam pulang, serta integrasi dengan jurnal guru.

---

## 1. JADWAL SHOLAT DZUHUR & JUMAT
1. **Tampilan UI**: Widget presensi sholat selalu ditampilkan di **posisi tengah** dengan ukuran tombol yang **ringkas/kecil** dan menampilkan rentang **waktu sholat** (contoh: `11:45 - 13:30 WIB`).
2. **Verifikasi GPS Mushola**: Presensi sholat **wajib dan hanya dapat dilakukan** di titik koordinat lokasi Mushola/Masjid yang telah ditentukan oleh Admin (`7kih_mushola_locations`).

---

## 2. PRESENSI SISWA: MASUK (PERGI) & PULANG
Sistem presensi harian siswa hanya berfokus pada 2 alur utama:

### A. Presensi Masuk (Pagi)
- **Tepat Waktu**: Jika siswa presensi sebelum/tepat jam masuk sekolah (contoh: <= 07:00 WIB), status dicatat sebagai **Hadir (H)**.
- **Terlambat**: Jika siswa presensi setelah jam masuk sekolah (> 07:00 WIB), status otomatis dicatat sebagai **Telat (T)**.
- **Tanpa Presensi Masuk**: Jika siswa tidak melakukan presensi masuk pagi, status di rekapan adalah **A/TAM** (*Alpa / Tidak Absen Masuk*).

### B. Presensi Pulang (Sore)
- **Waktu Buka & Kunci**: Absen pulang **hanya dapat dilakukan pada pukul 15:30 – 17:00 WIB** (default, dapat diatur oleh Admin melalui menu Setting Presensi).
  - Jika dicoba sebelum jam **15:30 WIB**, sistem akan **menolak** dengan pesan: *"Waktu absen pulang belum dibuka (dibuka pukul 15:30 WIB)"*.
  - Jika dicoba setelah jam **17:00 WIB**, sistem akan **menolak** dan terkunci.
- **Syarat Wajib**: Siswa **HARUS sudah melakukan Presensi Masuk Pagi** terlebih dahulu. Siswa yang berstatus **A/TAM** (tidak absen masuk) **TIDAK BISA** melakukan absen pulang.

---

## 3. STATUS REKAPAN KEHADIRAN SISWA
Status rekapan harian siswa ditentukan oleh kombinasi Presensi Masuk dan Presensi Pulang:

| Presensi Masuk (Pagi) | Presensi Pulang (Sore) | Status Akhir Rekapan | Keterangan Hitungan |
| :--- | :--- | :--- | :--- |
| Hadir (H) | Absen Pulang (✓) | **Hadir (H)** | Terhitung Hadir |
| Telat (T) | Absen Pulang (✓) | **H/T** (Hadir Telat) | Terhitung Telat |
| Hadir / Telat | Tidak Absen Pulang (✗) | **TAPT** (Tidak Absen Pulang & Telat) | Terhitung Telat / H/T |
| Tidak Absen (A/TAM) | Tidak Bisa Absen (✗) | **TA / A/TAM** (Tidak Absen / Alpa) | Terhitung Alpa / TA |

---

## 4. INDEPENDENSI PRESENSI SISWA & JURNAL GURU
1. **Presensi Siswa Mandiri**: Presensi di dasbor siswa berdiri sendiri. Penginputan jurnal oleh guru mapel **TIDAK akan mengubah** status presensi di dasbor akun siswa.
2. **Presensi Siswa Mengisi Jurnal Guru**:
   - Presensi masuk pagi siswa otomatis mengisi data awal di kolom kehadiran jurnal guru saat guru membuka lembar jurnal mapel hari itu.
   - **Kunci Status Telat**: Jika siswa terdeteksi **Telat (T)** dari presensi pagi, statusnya di jurnal guru otomatis terkunci sebagai **Telat (T)** dan **tidak dapat diubah oleh guru** menjadi Hadir, sebagai bukti validasi sistem.
3. **Rekapan Kehadiran per-Mapel vs Rekapan Harian Dasbor Guru**:
   - **Rekapan per-Mapel**: Sesuai dengan isian *real-time* masing-masing guru mapel.
   - **Rekapan Harian Dasbor Guru / Wali Kelas**: Menggunakan metode **MODUS (Rata-rata Terbanyak)** dari isian seluruh guru mapel pada hari tersebut.
     - Contoh: Jika siswa pada 5 mapel diisi `Hadir` oleh mayoritas guru, maka kesimpulan harian di dasbor guru adalah `Hadir`.
