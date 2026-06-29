# WhatsApp Notification Sender (Anti-Spam & aaPanel Ready)

Project gateway pengiriman notifikasi WhatsApp otomatis berbasis **Python (FastAPI)** dan **Node.js (@whiskeysockets/baileys)**. Didesain khusus untuk dijalankan di **aaPanel** dengan performa tinggi, UI premium glassmorphism, dan sistem pengiriman **Anti-Spam/Anti-Ban Queue** (pesan diantrekan dan dikirim dengan jeda acak manusiawi disertai status "sedang mengetik").

## Fitur Utama

- **Login Multi-Device**:
  - Scan **QR Code** langsung dari halaman dashboard.
  - Link dengan nomor telepon (**Pairing Code**) jika kamera ponsel Anda bermasalah.
- **Anti-Spam Queue**: Notifikasi masuk antrean database SQLite secara instan, lalu dikirim berurutan dengan jeda acak 3-7 detik dan simulasi status "typing..." selama 2 detik untuk meniru pola manusia.
- **REST API Sederhana**: Integrasikan sistem notifikasi ini ke website, aplikasi billing, WHMCS, atau sistem Anda lainnya.
- **Dashboard Premium**: Tampilan Dark Mode modern, responsif, grafik status pengiriman notifikasi (Pending, Sent, Failed), dan log history real-time.

---

## Panduan Instalasi di aaPanel

Ikuti langkah-langkah di bawah ini untuk memasang aplikasi di aaPanel Anda:

### Langkah 1: Unggah Berkas
1. Kompres semua berkas proyek ini menjadi format `.zip`.
2. Buka aaPanel -> **Files**, lalu buat folder baru di `/www/wwwroot/wa-sender`.
3. Unggah berkas `.zip` tersebut ke folder tersebut dan ekstrak.

---

### Langkah 2: Jalankan Engine WhatsApp (Node.js Worker)
Engine WhatsApp berjalan di latar belakang menggunakan Node.js dan PM2 Manager untuk menjaga sesi login tetap aktif.

1. Masuk ke aaPanel -> **App Store**, lalu instal **PM2 Manager** (jika belum terpasapng).
2. Buka terminal server Anda melalui menu **Terminal** di aaPanel atau SSH, lalu jalankan perintah berikut untuk menginstal dependensi worker:
   ```bash
   cd /www/wwwroot/wa-sender/worker
   npm install
   ```
3. Kembali ke **PM2 Manager** di aaPanel:
   - Pilih tab **Node List** atau **Project List** -> klik **Add Project**.
   - **Startup File**: Pilih `/www/wwwroot/wa-sender/worker/index.js`.
   - **Run Directory**: `/www/wwwroot/wa-sender/worker`.
   - **Project Name**: `wa-worker`.
   - **Port**: `9000`.
   - Klik **Submit** untuk menjalankan.

---

### Langkah 3: Jalankan Web Server Dashboard & API (Python FastAPI)
FastAPI bertindak sebagai antarmuka dashboard, API gateway, dan pengelola antrean (queue).

1. Buka aaPanel -> **App Store**, lalu instal **Python Manager** (jika belum terpasang).
2. Buka **Python Manager**:
   - Klik **Add Project**.
   - **Project Name**: `wa-sender-api`.
   - **Project Path**: `/www/wwwroot/wa-sender`.
   - **Framework**: `FastAPI` (atau Custom/Uvicorn).
   - **Startup File**: `/www/wwwroot/wa-sender/main.py`.
   - **Python Version**: Pilih Python 3.9 ke atas.
   - **Requirements.txt**: Pilih `/www/wwwroot/wa-sender/requirements.txt` agar otomatis terinstal.
   - **Port**: `8000`.
   - Centang **Auto Start** agar otomatis berjalan kembali jika server restart.
   - Klik **Submit**.

---

### Langkah 4: Hubungkan ke Domain dengan Reverse Proxy Nginx (Opsional but Recommended)
Agar dashboard dapat diakses via nama domain (misalnya `wa.domainanda.com`) dan menggunakan SSL/HTTPS:

1. Pergi ke menu **Website** -> klik **Add site**.
2. Masukkan domain Anda (misal: `wa.domainanda.com`), pilih **Pure-静态** (Static/Pure HTML) karena backend kita ditangani Python, klik **Submit**.
3. Buka konfigurasi website tersebut (klik nama domain di list) -> pergi ke menu **SSL** -> pasang Let's Encrypt SSL gratis.
4. Pergi ke menu **Reverse Proxy** (di dalam pengaturan website yang sama):
   - Klik **Add Reverse Proxy**.
   - **Proxy Name**: `wa-proxy`.
   - **Target URL**: `http://127.0.0.1:8000`.
   - **Sent Domain**: `$host`.
   - Klik **Submit**.
5. Selesai! Sekarang buka domain Anda di browser untuk mengakses Dashboard WA Sender.

---

## Cara Penggunaan API (Integrasi Website/Aplikasi Lain)

Kirim permintaan HTTP POST dari sistem Anda untuk mengirim notifikasi secara aman:

- **Endpoint**: `http://IP_SERVER_ANDA:8000/api/send` (atau `https://domainanda.com/api/send`)
- **Method**: `POST`
- **Headers**:
  ```json
  {
    "Content-Type": "application/json"
  }
  ```
- **Body / Payload (JSON)**:
  ```json
  {
    "number": "62812345678",
    "message": "Halo! Tagihan invoice #10294 Anda telah lunas."
  }
  ```

---

## Konfigurasi Tambahan Anti-Spam (Jika Diperlukan)
Jeda waktu pengiriman bawaan adalah jeda acak **3 s.d. 7 detik** antar pesan. Anda dapat mengubah parameter ini di berkas [main.py](file:///c:/Users/sman1/wa/main.py) pada baris berikut:
```python
# Ubah angka 3.0 dan 7.0 sesuai dengan kebutuhan keamanan Anda
delay_sec = random.uniform(3.0, 7.0)
```
Dan waktu status "composing/typing" dapat diubah di berkas [worker/index.js](file:///c:/Users/sman1/wa/worker/index.js) pada baris berikut:
```javascript
// Simulasi mengetik selama 2 detik sebelum kirim
await delay(2000); 
```
