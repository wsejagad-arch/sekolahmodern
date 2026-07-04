<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["no_induk"])) {
    header("location: ../../index.php?haruslogin");
    exit;
} else if ($_SESSION['hak_akses'] != 3) {
    echo "<script>window.location='../../404.html';</script>";
    exit;
}

include "../../koneksi.php";
include "../../functions.php";
date_default_timezone_set('Asia/Jakarta');

$nis = $_SESSION['no_induk'];
$kelas = $_SESSION['kelas'];
$nama_siswa = $_SESSION['nama_siswa'];

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_izin   = $_POST['jenis_izin']   ?? '';
    $detail_izin  = $_POST['detail_izin']  ?? '';
    $lokasi_izin  = $_POST['lokasi_izin']  ?? '';
    $foto_selfie_b64 = $_POST['foto_selfie_data'] ?? '';
    $tanggal_izin = date('Y-m-d');

    if (!empty($jenis_izin) && !empty($detail_izin) && !empty($foto_selfie_b64)) {

        // --- Simpan foto selfie ---
        $foto_selfie_path = null;
        $uploadDir = '../../uploads/izin/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

        // Decode base64 (format: data:image/jpeg;base64,XXXXXX)
        if (preg_match('/^data:image\/(\w+);base64,/', $foto_selfie_b64, $matches)) {
            $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $foto_selfie_b64));
            if ($imageData !== false) {
                $ext = 'jpg';
                $namaFile = 'selfie_' . $nis . '_' . time() . '.' . $ext;
                if (file_put_contents($uploadDir . $namaFile, $imageData) !== false) {
                    $foto_selfie_path = $namaFile;
                }
            }
        }

        if ($foto_selfie_path === null) {
            $pesan = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Gagal!</strong>
                        <span class="block sm:inline">Gagal menyimpan foto selfie. Coba ambil foto ulang.</span>
                      </div>';
        } else {
            $tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_izin_siswa'");
            if ($tblCheck && mysqli_num_rows($tblCheck) > 0) {
                // Cek kolom foto_selfie ada
                $cekKolom = mysqli_query($conn, "SHOW COLUMNS FROM tbl_izin_siswa LIKE 'foto_selfie'");
                $adaKolom = ($cekKolom && mysqli_num_rows($cekKolom) > 0);

                if ($adaKolom) {
                    $query = "INSERT INTO tbl_izin_siswa
                              (no_induk_siswa, kelas_siswa, tanggal_izin, jenis_izin, detail_izin, lokasi_izin, foto_selfie, waktu_pengajuan, status_izin)
                              VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Menunggu Wali Kelas')";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, 'sssssss', $nis, $kelas, $tanggal_izin, $jenis_izin, $detail_izin, $lokasi_izin, $foto_selfie_path);
                } else {
                    $query = "INSERT INTO tbl_izin_siswa
                              (no_induk_siswa, kelas_siswa, tanggal_izin, jenis_izin, detail_izin, lokasi_izin, waktu_pengajuan)
                              VALUES (?, ?, ?, ?, ?, ?, NOW())";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, 'ssssss', $nis, $kelas, $tanggal_izin, $jenis_izin, $detail_izin, $lokasi_izin);
                }

                if (mysqli_stmt_execute($stmt)) {
                    $berhasil = true;
                    $pesan = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                                <strong class="font-bold">Berhasil!</strong>
                                <span class="block sm:inline">Pengajuan izin terkirim. Menunggu validasi Wali Kelas &rarr; Guru BK.</span>
                              </div>';
                } else {
                    $pesan = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <strong class="font-bold">Gagal!</strong>
                                <span class="block sm:inline">Terjadi kesalahan database. Silakan coba lagi.</span>
                              </div>';
                }
                mysqli_stmt_close($stmt);
            } else {
                $pesan = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <strong class="font-bold">Gagal!</strong>
                            <span class="block sm:inline">Tabel izin belum tersedia. Hubungi administrator.</span>
                          </div>';
            }
        }
    } else {
        $missing = [];
        if (empty($jenis_izin))  $missing[] = 'jenis izin';
        if (empty($detail_izin)) $missing[] = 'detail izin';
        if (empty($foto_selfie_b64)) $missing[] = 'foto selfie';
        $pesan = '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Perhatian!</strong>
                    <span class="block sm:inline">Kolom berikut wajib diisi: ' . implode(', ', $missing) . '.</span>
                  </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengajuan Izin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        /* Hide scrollbar for stepper */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        /* Custom select icon spacing */
        select { appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 1rem center; background-size: 1em; }
    </style>
</head>
<body class="text-slate-800 antialiased">
    <div class="w-full max-w-2xl mx-auto p-4 sm:p-6 pb-16">
        
        <!-- Header -->
        <header class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <div class="relative bg-blue-500 rounded-[20px] w-[60px] h-[60px] flex items-center justify-center text-white shadow-lg shadow-blue-500/20 flex-shrink-0">
                    <i class="fas fa-file-invoice text-2xl"></i>
                    <div class="absolute -bottom-1 -right-1 bg-emerald-400 border-[3px] border-[#f8fafc] rounded-full w-7 h-7 flex items-center justify-center text-white text-xs font-black">
                        <i class="fas fa-plus"></i>
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Form Pengajuan Izin</h1>
                    <p class="text-xs text-slate-500 mt-1 font-medium">Alur: Siswa &rarr; Wali Kelas &rarr; Guru BK &rarr; Cetak Otomatis</p>
                </div>
            </div>
            <a href="siswa.php" class="text-blue-600 hover:text-blue-700 text-sm font-semibold flex items-center gap-1.5 transition-colors hidden sm:flex">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <!-- Mobile back button -->
            <a href="siswa.php" class="text-blue-600 hover:text-blue-700 flex items-center justify-center w-10 h-10 rounded-full bg-blue-50 sm:hidden">
                <i class="fas fa-arrow-left"></i>
            </a>
        </header>

        <!-- Stepper Alur -->
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-100 mb-6 flex items-center justify-between overflow-x-auto no-scrollbar gap-2 sm:gap-4">
            
            <!-- Step 1 -->
            <div class="flex flex-col items-center gap-2.5 min-w-[90px] relative border-b-[3px] border-blue-500 pb-2">
                <div class="w-12 h-12 rounded-[18px] bg-blue-50 text-blue-600 flex items-center justify-center text-[22px]">
                    <i class="fas fa-user"></i>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] font-bold text-blue-600 leading-tight text-center">
                    <span class="w-[18px] h-[18px] rounded-full bg-blue-600 text-white flex items-center justify-center text-[9px] flex-shrink-0">1</span> Siswa Ajukan
                </div>
            </div>
            <i class="fas fa-arrow-right text-slate-300 text-sm"></i>

            <!-- Step 2 -->
            <div class="flex flex-col items-center gap-2.5 min-w-[90px] pb-2 border-b-[3px] border-transparent">
                <div class="w-12 h-12 rounded-[18px] bg-orange-50 text-orange-500 flex items-center justify-center text-[22px]">
                    <i class="fas fa-users"></i>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] font-bold text-orange-500 leading-tight text-center">
                    <span class="w-[18px] h-[18px] rounded-full bg-orange-500 text-white flex items-center justify-center text-[9px] flex-shrink-0">2</span> Wali Kelas<br>Validasi
                </div>
            </div>
            <i class="fas fa-arrow-right text-slate-300 text-sm"></i>

            <!-- Step 3 -->
            <div class="flex flex-col items-center gap-2.5 min-w-[90px] pb-2 border-b-[3px] border-transparent">
                <div class="w-12 h-12 rounded-[18px] bg-purple-50 text-purple-600 flex items-center justify-center text-[22px]">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] font-bold text-purple-600 leading-tight text-center">
                    <span class="w-[18px] h-[18px] rounded-full bg-purple-600 text-white flex items-center justify-center text-[9px] flex-shrink-0">3</span> Guru BK<br>Validasi
                </div>
            </div>
            <i class="fas fa-arrow-right text-slate-300 text-sm"></i>

            <!-- Step 4 -->
            <div class="flex flex-col items-center gap-2.5 min-w-[90px] pb-2 border-b-[3px] border-transparent">
                <div class="w-12 h-12 rounded-[18px] bg-emerald-50 text-emerald-600 flex items-center justify-center text-[22px]">
                    <i class="fas fa-print"></i>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] font-bold text-emerald-600 leading-tight text-center">
                    <span class="w-[18px] h-[18px] rounded-full bg-emerald-600 text-white flex items-center justify-center text-[9px] flex-shrink-0">4</span> Cetak<br>Otomatis
                </div>
            </div>

        </div>

        <?php echo $pesan; ?>

        <?php if (empty($berhasil)): ?>
        <!-- Form Container -->
        <form action="ajukan-izin.php" method="POST" id="formIzin" class="bg-white rounded-[24px] p-5 sm:p-7 shadow-sm border border-slate-100">
            
            <!-- Jenis Izin -->
            <div class="mb-6">
                <label for="jenis_izin" class="flex items-center gap-2.5 text-slate-800 text-[13px] font-bold mb-3">
                    <i class="fas fa-list-ul text-blue-500 text-lg"></i> Jenis Izin <span class="text-red-500">*</span>
                </label>
                <select id="jenis_izin" name="jenis_izin" class="bg-white border border-slate-200 rounded-xl w-full py-3.5 px-4 text-slate-700 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all cursor-pointer" required>
                    <option value="">-- Pilih Jenis Izin --</option>
                    <option value="Sakit">Sakit</option>
                    <option value="Izin">Izin (keperluan keluarga, dll)</option>
                    <option value="Dispensasi">Dispensasi (mengikuti lomba, dll)</option>
                </select>
            </div>

            <!-- Detail Alasan -->
            <div class="mb-6 border-t border-slate-100 pt-6">
                <label for="detail_izin" class="flex items-center gap-2.5 text-slate-800 text-[13px] font-bold mb-3">
                    <i class="fas fa-file-invoice text-purple-600 text-lg"></i> Detail Alasan <span class="text-red-500">*</span>
                </label>
                <textarea id="detail_izin" name="detail_izin" rows="4"
                    class="bg-white border border-slate-200 rounded-xl w-full py-3.5 px-4 text-slate-700 text-sm focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-100 transition-all resize-none"
                    placeholder="Jelaskan alasan secara detail..." required></textarea>
            </div>

            <!-- Lokasi Saat Izin -->
            <div class="mb-6 border-t border-slate-100 pt-6">
                <label class="flex items-center gap-2.5 text-slate-800 text-[13px] font-bold mb-3">
                    <i class="fas fa-map-marker-alt text-emerald-500 text-lg"></i> Lokasi Saat Izin
                </label>
                <div class="flex items-center gap-3">
                    <input type="text" id="lokasi_izin" name="lokasi_izin"
                        class="bg-white border border-slate-200 rounded-xl w-full py-3.5 px-4 text-slate-700 text-sm focus:outline-none"
                        placeholder="Koordinat GPS akan muncul di sini" readonly>
                    <button type="button" id="btn-gps"
                        class="bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold py-3.5 px-6 rounded-xl whitespace-nowrap transition-colors flex items-center gap-2">
                        <i class="fas fa-crosshairs"></i> Ambil
                    </button>
                </div>
                <p id="gps-status" class="text-xs text-slate-500 mt-2 ml-1"></p>
            </div>

            <!-- Swafoto (Selfie) -->
            <div class="mb-8 border-t border-slate-100 pt-6">
                <label class="flex items-center gap-2.5 text-slate-800 text-[13px] font-bold mb-1">
                    <i class="fas fa-camera text-purple-600 text-lg"></i> Swafoto (Selfie) <span class="text-red-500">*</span>
                </label>
                <p class="text-xs text-slate-500 mb-4 font-medium">Ambil foto wajah Anda sebagai bukti kondisi saat mengajukan izin.</p>

                <div id="camera-container" class="relative bg-white border-2 border-dashed border-slate-300 rounded-[20px] p-6 text-center transition-colors">
                    
                    <video id="camera-video" autoplay playsinline
                        class="w-full max-w-[280px] mx-auto block bg-slate-900 rounded-2xl shadow-sm"
                        style="aspect-ratio:3/4;object-fit:cover;display:none;"></video>

                    <div id="foto-preview-wrap" class="relative inline-block w-full max-w-[280px] mx-auto">
                        
                        <div id="foto-placeholder" class="flex flex-col items-center justify-center py-6">
                            <div class="w-[72px] h-[72px] rounded-full bg-blue-50 text-blue-500 flex items-center justify-center text-3xl mb-4">
                                <i class="fas fa-camera"></i>
                            </div>
                            <span class="text-slate-800 font-black text-[15px] mb-1">Belum ada foto</span>
                            <span class="text-slate-500 text-xs font-medium">Ketuk untuk mengambil foto</span>
                        </div>
                        
                        <img id="foto-preview" src="" alt="Selfie preview"
                            class="w-full max-w-[280px] mx-auto block rounded-2xl border border-slate-200 shadow-sm"
                            style="display:none;aspect-ratio:3/4;object-fit:cover;">
                        
                        <span id="foto-ok-badge"
                            class="absolute -top-3 -right-3 bg-emerald-500 border-[3px] border-white text-white text-xs px-3 py-1.5 rounded-full shadow-sm font-bold"
                            style="display:none;"><i class="fas fa-check mr-1"></i>OK</span>
                    </div>

                    <canvas id="camera-canvas" style="display:none;"></canvas>
                </div>

                <div class="mt-4">
                    <button type="button" id="btn-buka-kamera"
                        class="w-full bg-purple-50 hover:bg-purple-100 text-purple-600 font-bold py-3.5 px-4 rounded-xl transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-camera"></i> Buka Kamera
                    </button>
                    <button type="button" id="btn-ambil-foto"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3.5 px-4 rounded-xl transition-colors items-center justify-center gap-2 shadow-lg shadow-purple-500/30"
                        style="display:none;">
                        <i class="fas fa-circle"></i> Ambil Foto
                    </button>
                    <button type="button" id="btn-ulangi-foto"
                        class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3.5 px-4 rounded-xl transition-colors items-center justify-center gap-2"
                        style="display:none;">
                        <i class="fas fa-redo"></i> Ulangi Foto
                    </button>
                </div>

                <input type="hidden" name="foto_selfie_data" id="foto_selfie_data">
                <p id="foto-error" class="text-xs text-red-500 mt-3 font-semibold text-center" style="display:none;">
                    <i class="fas fa-exclamation-circle mr-1"></i> Foto selfie wajib diambil sebelum mengirim.
                </p>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="btn-submit"
                class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-500/30 transition-all flex items-center justify-center gap-2 text-base">
                <i class="fas fa-paper-plane"></i> Kirim Pengajuan
            </button>
        </form>
        
        <?php else: ?>
        <!-- Success State -->
        <div class="bg-white rounded-[24px] p-8 shadow-sm border border-slate-100 text-center mt-4">
            <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-500 flex items-center justify-center text-4xl mx-auto mb-5">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="text-xl font-black text-slate-800 mb-2">Pengajuan Berhasil</h2>
            <p class="text-sm text-slate-500 mb-6">Pengajuan izin Anda sudah terkirim dan sedang dalam proses validasi.</p>
            <a href="status-izin.php" class="inline-flex bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3.5 rounded-xl transition shadow-lg shadow-blue-500/30 items-center justify-center gap-2">
                <i class="fas fa-list-alt"></i> Lihat Status Izin
            </a>
        </div>
        <?php endif; ?>

    </div>

    <script>
    // --- GPS ---
    document.getElementById('btn-gps').addEventListener('click', function() {
        const lokasiInput = document.getElementById('lokasi_izin');
        const statusP     = document.getElementById('gps-status');
        if (!navigator.geolocation) {
            statusP.textContent = 'Geolocation tidak didukung oleh browser Anda.';
            statusP.className = 'text-xs text-red-500 mt-2 ml-1'; return;
        }
        statusP.textContent = 'Mencari lokasi…';
        statusP.className = 'text-xs text-blue-500 mt-2 ml-1';
        this.disabled = true;
        navigator.geolocation.getCurrentPosition(pos => {
            lokasiInput.value = pos.coords.latitude + ', ' + pos.coords.longitude;
            statusP.innerHTML = '<i class="fas fa-check-circle"></i> Lokasi berhasil didapatkan.';
            statusP.className = 'text-xs text-emerald-500 mt-2 ml-1 font-semibold';
            document.getElementById('btn-gps').disabled = false;
        }, () => {
            statusP.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Tidak dapat mengambil lokasi. Pastikan GPS aktif.';
            statusP.className = 'text-xs text-red-500 mt-2 ml-1 font-semibold';
            document.getElementById('btn-gps').disabled = false;
        });
    });

    // --- KAMERA / SWAFOTO ---
    let stream = null;

    const video      = document.getElementById('camera-video');
    const canvas     = document.getElementById('camera-canvas');
    const preview    = document.getElementById('foto-preview');
    const placeholder= document.getElementById('foto-placeholder');
    const okBadge    = document.getElementById('foto-ok-badge');
    const fotoInput  = document.getElementById('foto_selfie_data');
    const container  = document.getElementById('camera-container');

    const btnBuka    = document.getElementById('btn-buka-kamera');
    const btnAmbil   = document.getElementById('btn-ambil-foto');
    const btnUlangi  = document.getElementById('btn-ulangi-foto');

    btnBuka.addEventListener('click', async function() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
            video.srcObject = stream;
            video.style.display = 'block';
            placeholder.style.display = 'none';
            preview.style.display     = 'none';
            okBadge.style.display     = 'none';
            btnBuka.style.display     = 'none';
            btnAmbil.style.display    = 'flex';
            btnUlangi.style.display   = 'none';
            container.classList.add('border-blue-300', 'bg-blue-50/30');
        } catch (e) {
            alert('Tidak dapat mengakses kamera: ' + e.message);
        }
    });

    btnAmbil.addEventListener('click', function() {
        canvas.width  = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        // Mirror (selfie)
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        fotoInput.value = dataUrl;

        // Tampilkan preview
        preview.src = dataUrl;
        preview.style.display = 'block';
        okBadge.style.display = 'inline-flex';
        video.style.display   = 'none';
        btnAmbil.style.display  = 'none';
        btnUlangi.style.display = 'flex';
        btnBuka.style.display   = 'none';
        
        container.classList.remove('border-blue-300', 'bg-blue-50/30');
        container.classList.add('border-emerald-300', 'bg-emerald-50/30');

        // Matikan kamera
        if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    });

    btnUlangi.addEventListener('click', function() {
        fotoInput.value = '';
        preview.src = '';
        preview.style.display     = 'none';
        okBadge.style.display     = 'none';
        placeholder.style.display = 'flex';
        video.style.display       = 'none';
        btnBuka.style.display     = 'flex';
        btnAmbil.style.display    = 'none';
        btnUlangi.style.display   = 'none';
        
        container.classList.remove('border-emerald-300', 'bg-emerald-50/30');
    });

    // --- VALIDASI FORM ---
    document.getElementById('formIzin').addEventListener('submit', function(e) {
        if (!fotoInput.value) {
            e.preventDefault();
            document.getElementById('foto-error').style.display = 'block';
            document.getElementById('btn-buka-kamera').closest('.mb-8').scrollIntoView({ behavior: 'smooth' });
        }
    });
    </script>
</body>
</html>