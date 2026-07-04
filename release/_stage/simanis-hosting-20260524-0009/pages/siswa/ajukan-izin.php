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
        #camera-container video, #camera-container canvas { border-radius: 10px; }
        .step-badge { display:inline-flex;align-items:center;gap:6px; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="w-full max-w-2xl mx-auto p-4 pb-10">
        <div class="bg-white rounded-2xl p-6 shadow-lg">
            <!-- Header -->
            <header class="flex justify-between items-center mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-file-medical text-green-500 mr-2"></i>Form Pengajuan Izin</h1>
                    <p class="text-xs text-gray-500 mt-1">Alur: Siswa → Wali Kelas → Guru BK → Cetak Otomatis</p>
                </div>
                <a href="siswa.php" class="text-blue-500 hover:text-blue-700 text-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
            </header>

            <!-- Alur Izin -->
            <div class="flex flex-wrap items-center gap-2 mb-5 bg-blue-50 rounded-xl px-4 py-3 text-sm">
                <span class="step-badge bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-semibold">
                    <i class="fas fa-user-graduate"></i> Siswa Ajukan
                </span>
                <i class="fas fa-arrow-right text-gray-400"></i>
                <span class="step-badge bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full font-semibold">
                    <i class="fas fa-chalkboard-teacher"></i> Wali Kelas Validasi
                </span>
                <i class="fas fa-arrow-right text-gray-400"></i>
                <span class="step-badge bg-purple-100 text-purple-700 px-3 py-1 rounded-full font-semibold">
                    <i class="fas fa-user-tie"></i> Guru BK Validasi
                </span>
                <i class="fas fa-arrow-right text-gray-400"></i>
                <span class="step-badge bg-green-100 text-green-700 px-3 py-1 rounded-full font-semibold">
                    <i class="fas fa-print"></i> Cetak Otomatis
                </span>
            </div>

            <?php echo $pesan; ?>

            <?php if (empty($berhasil)): ?>
            <form action="ajukan-izin.php" method="POST" id="formIzin" class="mt-4">
                <!-- Jenis Izin -->
                <div class="mb-4">
                    <label for="jenis_izin" class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fas fa-list-ul text-blue-500 mr-1"></i> Jenis Izin <span class="text-red-500">*</span>
                    </label>
                    <select id="jenis_izin" name="jenis_izin" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:border-blue-400" required>
                        <option value="">-- Pilih Jenis Izin --</option>
                        <option value="Sakit">🤒 Sakit</option>
                        <option value="Izin">📋 Izin (keperluan keluarga, dll)</option>
                        <option value="Dispensasi">🏆 Dispensasi (mengikuti lomba, dll)</option>
                    </select>
                </div>

                <!-- Detail Izin -->
                <div class="mb-4">
                    <label for="detail_izin" class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fas fa-align-left text-blue-500 mr-1"></i> Detail Alasan <span class="text-red-500">*</span>
                    </label>
                    <textarea id="detail_izin" name="detail_izin" rows="3"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:border-blue-400"
                        placeholder="Jelaskan alasan secara detail..." required></textarea>
                </div>

                <!-- Lokasi GPS -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fas fa-map-marker-alt text-red-500 mr-1"></i> Lokasi Saat Izin
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="text" id="lokasi_izin" name="lokasi_izin"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none"
                            placeholder="Koordinat GPS akan muncul di sini" readonly>
                        <button type="button" id="btn-gps"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-3 rounded whitespace-nowrap">
                            <i class="fas fa-crosshairs"></i> Ambil
                        </button>
                    </div>
                    <p id="gps-status" class="text-xs text-gray-500 mt-1"></p>
                </div>

                <!-- ===== SWAFOTO ===== -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fas fa-camera text-purple-500 mr-1"></i> Swafoto (Selfie) <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-500 mb-3">Ambil foto wajah Anda sebagai bukti kondisi saat mengajukan izin.</p>

                    <div id="camera-container" class="relative">
                        <!-- Video preview -->
                        <video id="camera-video" autoplay playsinline
                            class="w-full max-w-xs mx-auto block bg-black rounded-lg"
                            style="aspect-ratio:4/3;object-fit:cover;display:none;"></video>

                        <!-- Preview hasil foto -->
                        <div id="foto-preview-wrap" class="relative inline-block w-full max-w-xs mx-auto">
                            <div id="foto-placeholder"
                                class="w-full max-w-xs mx-auto flex flex-col items-center justify-center bg-gray-100 rounded-lg border-2 border-dashed border-gray-300"
                                style="aspect-ratio:4/3;">
                                <i class="fas fa-camera text-gray-400 text-4xl mb-2"></i>
                                <span class="text-gray-400 text-sm">Belum ada foto</span>
                            </div>
                            <img id="foto-preview" src="" alt="Selfie preview"
                                class="w-full max-w-xs mx-auto block rounded-lg border-2 border-green-400"
                                style="display:none;aspect-ratio:4/3;object-fit:cover;">
                            <span id="foto-ok-badge"
                                class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full"
                                style="display:none;"><i class="fas fa-check mr-1"></i>Foto OK</span>
                        </div>

                        <!-- Canvas tersembunyi untuk capture -->
                        <canvas id="camera-canvas" style="display:none;"></canvas>
                    </div>

                    <!-- Tombol kamera -->
                    <div class="flex flex-wrap gap-2 mt-3 justify-center">
                        <button type="button" id="btn-buka-kamera"
                            class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg">
                            <i class="fas fa-camera mr-1"></i> Buka Kamera
                        </button>
                        <button type="button" id="btn-ambil-foto"
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg"
                            style="display:none;">
                            <i class="fas fa-circle mr-1"></i> Ambil Foto
                        </button>
                        <button type="button" id="btn-ulangi-foto"
                            class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded-lg"
                            style="display:none;">
                            <i class="fas fa-redo mr-1"></i> Ulangi Foto
                        </button>
                    </div>

                    <!-- Hidden input untuk data foto -->
                    <input type="hidden" name="foto_selfie_data" id="foto_selfie_data">
                    <p id="foto-error" class="text-xs text-red-500 mt-2" style="display:none;">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Foto selfie wajib diambil sebelum mengirim.
                    </p>
                </div>
                <!-- ===== END SWAFOTO ===== -->

                <div class="flex items-center justify-center pt-2">
                    <button type="submit" id="btn-submit"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-full focus:outline-none focus:shadow-outline text-lg">
                        <i class="fas fa-paper-plane mr-2"></i> Kirim Pengajuan
                    </button>
                </div>
            </form>
            <?php else: ?>
            <div class="text-center mt-6 py-4">
                <i class="fas fa-check-circle text-green-500 text-5xl mb-3"></i>
                <p class="text-gray-600 mb-4">Pengajuan izin Anda sudah terkirim dan sedang dalam proses validasi.</p>
                <a href="status-izin.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-full transition">
                    <i class="fas fa-list-alt mr-2"></i> Lihat Status Izin
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // --- GPS ---
    document.getElementById('btn-gps').addEventListener('click', function() {
        const lokasiInput = document.getElementById('lokasi_izin');
        const statusP     = document.getElementById('gps-status');
        if (!navigator.geolocation) {
            statusP.textContent = 'Geolocation tidak didukung oleh browser Anda.';
            statusP.className = 'text-xs text-red-500 mt-1'; return;
        }
        statusP.textContent = 'Mencari lokasi…';
        statusP.className = 'text-xs text-blue-500 mt-1';
        this.disabled = true;
        navigator.geolocation.getCurrentPosition(pos => {
            lokasiInput.value = pos.coords.latitude + ', ' + pos.coords.longitude;
            statusP.textContent = '✅ Lokasi berhasil didapatkan.';
            statusP.className = 'text-xs text-green-500 mt-1';
            document.getElementById('btn-gps').disabled = false;
        }, () => {
            statusP.textContent = '⚠️ Tidak dapat mengambil lokasi. Pastikan GPS aktif.';
            statusP.className = 'text-xs text-red-500 mt-1';
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
            btnAmbil.style.display    = 'inline-flex';
            btnUlangi.style.display   = 'none';
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
        btnUlangi.style.display = 'inline-flex';
        btnBuka.style.display   = 'none';

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
        btnBuka.style.display     = 'inline-flex';
        btnAmbil.style.display    = 'none';
        btnUlangi.style.display   = 'none';
    });

    // --- VALIDASI FORM ---
    document.getElementById('formIzin').addEventListener('submit', function(e) {
        if (!fotoInput.value) {
            e.preventDefault();
            document.getElementById('foto-error').style.display = 'block';
            document.getElementById('btn-buka-kamera').closest('.mb-6').scrollIntoView({ behavior: 'smooth' });
        }
    });
    </script>
</body>
</html>
