<?php
$file = 'c:\Users\sman1\.gemini\antigravity\brain\10f8b2b4-aed8-4bfd-ab88-9f4f8aad0919\walkthrough_izin_keluar.md';
$content = <<<MD
# Penyelesaian Fitur Izin Keluar Sekolah

Seluruh alur fitur izin keluar sekolah telah diimplementasikan:

## 1. Halaman Pengajuan Siswa (\`ajukan-izin.php\`)
* Siswa kini dapat memilih antara "Izin Tidak Masuk", "Keluar Sekolah", dan "Dispen".
* Khusus untuk "Keluar Sekolah", siswa wajib mengisi opsi kembali ("Kembali ke Sekolah" atau "Tidak Kembali").
* Penyimpanan menggunakan tabel \`tbl_izin_siswa\` dan status awal akan diset ke "Menunggu Validasi".

## 2. Halaman Persetujuan Wali Kelas (\`validasi-izin.php\`)
* Wali Kelas dapat masuk ke menu **Validasi Izin**.
* Sistem akan mendeteksi kelas-kelas yang menjadi tanggung jawab Wali Kelas (via \`nip_wali\`).
* Wali Kelas dapat melihat pengajuan Izin Keluar dan menekan tombol **Setujui** atau **Tolak**.

## 3. Halaman Dashboard Satpam (\`satpam.php\`)
* Satpam dapat melihat seluruh Riwayat Izin.
* Untuk Izin Keluar Sekolah, terdapat penguncian tombol validasi:
    * Jika Wali Kelas belum menyetujui, tombol \`Validasi Keluar\` dikunci (Disabled).
    * Jika Wali Kelas sudah menyetujui, tombol \`Validasi Keluar\` terbuka.
* Tombol **Masuk Lagi** akan muncul jika siswa memilih opsi "Kembali ke Sekolah" namun belum mencatat waktu kembali.
* Saat siswa klik "Masuk Lagi", sistem otomatis akan mencatat riwayat izin tersebut ke dalam \`tbl_jurnal\` (Jurnal Guru).

Seluruh sistem telah terintegrasi dengan struktur tabel yang dimodifikasi (\`tbl_izin_siswa\`).
MD;
file_put_contents($file, $content);
?>
