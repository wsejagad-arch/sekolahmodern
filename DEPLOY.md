# Panduan Deployment WhatsApp Notification Gateway di aaPanel

Dokumen ini menjelaskan langkah-langkah cepat untuk memaketkan (build) aplikasi dari komputer lokal Anda (Windows) dan mendeploy-nya ke server Linux yang menggunakan panel **aaPanel**.

---

## Langkah 1: Pembuatan Berkas ZIP Bersih di Windows

Untuk menghindari ukuran file yang besar dan konflik modul OS (seperti folder `venv` dan `node_modules` Windows yang tidak kompatibel di Linux), Anda dapat menggunakan script build yang disediakan:

1. Double-click file **`build_zip.bat`** di direktori proyek ini.
2. Script akan berjalan secara otomatis di PowerShell, membersihkan cache, dan membuat file baru bernama **`wa_sender_aapanel.zip`**.
3. File **`wa_sender_aapanel.zip`** ini adalah paket bersih yang siap diunggah ke server Anda.

---

## Langkah 2: Unggah dan Ekstrak di aaPanel

1. Masuk ke dashboard **aaPanel** Anda.
2. Buka menu **Files** di sebelah kiri.
3. Masuk ke direktori webroot Anda, misalnya `/www/wwwroot/`.
4. Buat folder baru bernama `wa-sender` (sehingga jalurnya menjadi `/www/wwwroot/wa-sender`).
5. Unggah berkas **`wa_sender_aapanel.zip`** ke dalam folder tersebut.
6. Klik kanan berkas zip lalu pilih **Unzip** (Ekstrak).

---

## Langkah 3: Jalankan Script Inisialisasi Otomatis (`setup_aapanel.sh`)

Script inisialisasi ini akan secara otomatis membuat Python virtual environment (`venv`), mengunduh package dependensi Python & Node.js, serta memverifikasi port server.

1. Hubungi server Anda menggunakan SSH atau buka menu **Terminal** di aaPanel.
2. Masuk ke folder proyek Anda:
   ```bash
   cd /www/wwwroot/wa-sender
   ```
3. Berikan izin eksekusi pada script setup:
   ```bash
   chmod +x setup_aapanel.sh
   ```
4. Jalankan script setup:
   ```bash
   ./setup_aapanel.sh
   ```
5. Tunggu hingga proses instalasi dependensi selesai.

---

## Langkah 4: Jalankan Service di aaPanel

Setelah script setup selesai dijalankan, Anda perlu mengaktifkan service di latar belakang agar aplikasi tetap berjalan 24/7.

### A. WhatsApp Engine (Node.js Worker)
1. Buka **App Store** di aaPanel, instal **PM2 Manager** jika belum terpasang.
2. Buka **PM2 Manager**:
   - Pilih tab **Project List** atau **Node List** -> klik **Add Project**.
   - **Startup File**: Pilih `/www/wwwroot/wa-sender/worker/index.js`
   - **Run Directory**: `/www/wwwroot/wa-sender/worker`
   - **Project Name**: `wa-worker`
   - **Port**: `9000`
   - Klik **Submit**.

### B. Web Dashboard & API (Python FastAPI)
1. Buka **App Store** di aaPanel, instal **Python Manager** jika belum terpasang.
2. Buka **Python Manager**:
   - Klik **Add Project**.
   - **Project Name**: `wa-sender-api`
   - **Project Path**: `/www/wwwroot/wa-sender`
   - **Framework**: `FastAPI` (atau `Uvicorn`/`Custom`)
   - **Startup File**: `/www/wwwroot/wa-sender/main.py`
   - **Python Version**: Pilih Python 3.9 ke atas.
   - **Requirements.txt**: `/www/wwwroot/wa-sender/requirements.txt`
   - **Port**: `8000`
   - Centang opsi **Auto Start** (jika ada).
   - Klik **Submit**.

---

## Langkah 5: Hubungkan ke Domain dengan SSL (Reverse Proxy)

Agar dashboard dapat diakses menggunakan domain (misalnya `wa.namadomain.com`) dan menggunakan HTTPS:

1. Pergi ke menu **Website** -> klik **Add site**.
2. Masukkan domain Anda, pilih opsi **Pure-静态** (Static/Pure HTML), lalu klik **Submit**.
3. Buka konfigurasi website tersebut (klik nama domain di list) -> pergi ke menu **SSL** -> pasang Let's Encrypt SSL gratis.
4. Pergi ke menu **Reverse Proxy** (di dalam pengaturan website yang sama):
   - Klik **Add Reverse Proxy**.
   - **Proxy Name**: `wa-proxy`
   - **Target URL**: `http://127.0.0.1:8000`
   - **Sent Domain**: `$host`
   - Klik **Submit**.
5. Selesai! Buka domain Anda di browser untuk mengakses Dashboard WA Sender.
