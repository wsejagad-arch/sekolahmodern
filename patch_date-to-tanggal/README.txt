Patch: Ganti referensi kolom `date` -> `tanggal`

Isi paket:
- MODIFIED_FILES.txt  (daftar file yang diubah)
- trigger_sync_tanggal_date.sql  (opsional: buat/ sinkronisasi kolom `date` + trigger)
- INSTRUCTIONS.txt (langkah penerapan)

INSTRUCTIONS:
1) Review file di daftar `MODIFIED_FILES.txt`.
2) Commit dan deploy perubahan PHP ke server (overwrite file yang ada di hosting).
   - Jika tidak menggunakan git pada hosting, Anda bisa upload file yang diubah via FTP atau kontrol panel.
3) (Opsional tapi direkomendasikan) Jalankan SQL di `trigger_sync_tanggal_date.sql` di database hosting (phpMyAdmin / mysql client) untuk:
   - Menambahkan kolom `date` bila belum ada
   - Mengisi nilai `date` dari `tanggal` untuk baris yang ada
   - Menambahkan trigger BEFORE INSERT / BEFORE UPDATE supaya `date` selalu sinkron dengan `tanggal`

Cara menjalankan SQL via mysql client:

```sql
-- di terminal (jika tersedia):
mysql -u DBUSER -p DBNAME < trigger_sync_tanggal_date.sql
```

Atau paste isi file SQL di phpMyAdmin -> SQL -> Go.

Catatan keamanan:
- Pastikan backup database sebelum menjalankan ALTER TABLE / trigger.
- Trigger yang dibuat menindaklanjuti kolom `tanggal` sebagai sumber kebenaran.

Jika Anda mau saya buatkan file ZIP atau PR GitHub, beri akses repo atau instruksi target upload.
