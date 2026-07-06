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
include "../../notification_helper.php";
date_default_timezone_set('Asia/Jakarta');

$nis = $_SESSION['no_induk'];
$kelas = $_SESSION['kelas'];
$nama_siswa = $_SESSION['nama_siswa'];

$pesan = '';

// --- ANTI NUMPUK CHECK ---
$today = date('Y-m-d');
$qCekAktif = mysqli_query($conn, "SELECT id_izin, kategori_pengajuan, status_izin FROM tbl_izin_siswa WHERE no_induk_siswa = '$nis' AND tanggal_izin = '$today' AND status_izin IN ('Menunggu Validasi', 'Menunggu', 'Disetujui', 'Disetujui Penuh') ORDER BY waktu_pengajuan DESC LIMIT 1");
$adaIzinAktif = ($qCekAktif && mysqli_num_rows($qCekAktif) > 0);
if ($adaIzinAktif) {
    $rowAktif = mysqli_fetch_assoc($qCekAktif);
    $kategoriAktif = $rowAktif['kategori_pengajuan'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($adaIzinAktif) {
        $pesan = 'Anda masih memiliki pengajuan izin aktif hari ini. Harap tunggu hingga selesai atau batalkan pengajuan sebelumnya.';
    } else {
        $kategori_pengajuan = $_POST['kategori_pengajuan'] ?? '';
        $detail_izin  = $_POST['detail_izin']  ?? '';
        $lokasi_izin  = $_POST['lokasi_izin']  ?? '';
        $foto_selfie_b64 = $_POST['foto_selfie_data'] ?? '';
        $opsi_kembali = $_POST['opsi_kembali'] ?? null;
        $tanggal_izin = date('Y-m-d');
        $waktu_pengajuan = date('Y-m-d H:i:s');
        
        $jenis_izin = $kategori_pengajuan;
        
        // Validation
        $is_valid = true;
        if (empty($kategori_pengajuan) || empty($detail_izin)) {
            $is_valid = false;
        }
        if ($kategori_pengajuan === 'Keluar Sekolah' && empty($lokasi_izin)) {
            $is_valid = false;
        }
        if ($kategori_pengajuan !== 'Dispensasi' && empty($foto_selfie_b64)) {
            $is_valid = false;
        }

        if ($is_valid) {
            $foto_name = '';
            if (!empty($foto_selfie_b64) && preg_match('/^data:image\/(\w+);base64,/', $foto_selfie_b64, $type)) {
                $foto_b64_data = substr($foto_selfie_b64, strpos($foto_selfie_b64, ',') + 1);
                $type = strtolower($type[1]);
                if (in_array($type, [ 'jpg', 'jpeg', 'png' ])) {
                    $foto_data = base64_decode($foto_b64_data);
                    $foto_name = 'izin_' . time() . '_' . $nis . '.' . $type;
                    $upload_dir = '../../uploads/izin/';
                    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                    file_put_contents($upload_dir . $foto_name, $foto_data);
                }
            }

            $sql = "INSERT INTO tbl_izin_siswa (no_induk_siswa, kelas_siswa, jenis_izin, detail_izin, lokasi_izin, foto_selfie, tanggal_izin, waktu_pengajuan, kategori_pengajuan, opsi_kembali, validasi_wali_kelas, validasi_satpam, validasi_guru_bk, status_izin, id_sekolah) 
                    VALUES ('$nis', '$kelas', '$jenis_izin', '$detail_izin', '$lokasi_izin', '$foto_name', '$tanggal_izin', '$waktu_pengajuan', '$kategori_pengajuan', " . ($opsi_kembali ? "'$opsi_kembali'" : "NULL") . ", 'Menunggu', 'Menunggu', 'Menunggu', 'Menunggu Validasi', '".mt_current_school_id()."')";
            
            if (mysqli_query($conn, $sql)) {
                $pesan = 'sukses';
                
                // NOTIF WA WALI KELAS
                $waliQuery = mysqli_query($conn, "SELECT g.no_wa FROM tbl_kelas k JOIN tbl_guru g ON k.nip_wali = g.no_induk WHERE REPLACE(k.kelas, ' ', '') = REPLACE('$kelas', ' ', '') LIMIT 1");
                if ($waliQuery && mysqli_num_rows($waliQuery) > 0) {
                    $rowWali = mysqli_fetch_assoc($waliQuery);
                    $no_wa_wali = $rowWali['no_wa'];
                    if (!empty($no_wa_wali)) {
                        $pesanWA = "Halo Bapak/Ibu Wali Kelas,\n\nSiswa Anda:\nNama: *$nama_siswa*\nKelas: *$kelas*\n\nTelah mengajukan izin *$kategori_pengajuan* dengan keterangan: _{$detail_izin}_.\n\nSilakan periksa sistem untuk validasi.";
                        notif_send_whatsapp($no_wa_wali, "Pengajuan Izin Siswa", $pesanWA, $conn);
                    }
                }
                
                // NOTIF WA GURU BK
                $bkQuery = mysqli_query($conn, "SELECT no_wa FROM tbl_guru WHERE (jabatan LIKE '%BK%' OR tugas_tambahan LIKE '%BK%') AND no_wa != ''");
                if ($bkQuery && mysqli_num_rows($bkQuery) > 0) {
                    while ($rowBk = mysqli_fetch_assoc($bkQuery)) {
                        $no_wa_bk = $rowBk['no_wa'];
                        if (!empty($no_wa_bk)) {
                            $pesanWABK = "Halo Bapak/Ibu Guru BK,\n\nSiswa:\nNama: *$nama_siswa*\nKelas: *$kelas*\n\nTelah mengajukan izin *$kategori_pengajuan* dengan keterangan: _{$detail_izin}_.\n\nSilakan periksa sistem untuk memantau/validasi.";
                            notif_send_whatsapp($no_wa_bk, "Notifikasi Izin Siswa", $pesanWABK, $conn);
                        }
                    }
                }
            } else {
                $pesan = 'Gagal menyimpan ke database: ' . mysqli_error($conn);
            }
        } else {
            $pesan = 'Mohon lengkapi semua data wajib sesuai jenis izin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ajukan Izin - Sistem Jurnal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; }
        .gradient-header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); }
    </style>
</head>
<body class="pb-24">

    <div class="gradient-header pt-12 pb-6 px-6 rounded-b-[32px] shadow-lg shadow-blue-500/20 relative overflow-hidden">
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <a href="siswa.php" class="text-white/80 hover:text-white mb-2 inline-block transition"><i class="fas fa-arrow-left"></i> Kembali</a>
                <h1 class="text-2xl font-black text-white tracking-tight">Ajukan Izin</h1>
                <p class="text-blue-100 text-sm mt-1 opacity-90">Sistem Perizinan Digital</p>
            </div>
            <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm border border-white/30 text-white text-2xl">
                <i class="fas fa-envelope-open-text"></i>
            </div>
        </div>
    </div>

    <div class="px-5 -mt-4 relative z-20">
        <?php if ($pesan === 'sukses'): ?>
        <div class="bg-white rounded-[24px] p-8 shadow-sm border border-slate-100 text-center mt-8">
            <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-500 flex items-center justify-center text-4xl mx-auto mb-5">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="text-xl font-black text-slate-800 mb-2">Pengajuan Berhasil</h2>
            <p class="text-sm text-slate-500 mb-6">Pengajuan izin Anda sudah terkirim ke Wali Kelas dan BK untuk diproses.</p>
            <a href="status-izin.php" class="inline-flex bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3.5 rounded-xl transition shadow-lg shadow-blue-500/30 items-center justify-center gap-2">
                <i class="fas fa-list-alt"></i> Lihat Status Izin
            </a>
        </div>
        <?php else: ?>

        <?php if ($adaIzinAktif && empty($pesan)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5 mb-6 text-yellow-800 mt-8">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-triangle mt-1 text-yellow-600"></i>
                <div>
                    <h3 class="font-bold mb-1">Pengajuan Izin Aktif</h3>
                    <p class="text-sm">Anda masih memiliki pengajuan izin (<strong><?= htmlspecialchars($kategoriAktif) ?></strong>) yang sedang diproses hari ini.</p>
                    <a href="status-izin.php" class="inline-block mt-3 px-4 py-2 bg-yellow-600 text-white text-sm font-semibold rounded-lg hover:bg-yellow-700 transition">Lihat Status</a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <form action="" method="POST" id="formIzin" class="mt-8 space-y-6">
            <?php if ($pesan): ?>
            <div class="bg-red-50 text-red-500 p-4 rounded-xl text-sm font-semibold flex items-center gap-2 border border-red-100">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($pesan) ?>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-[24px] p-6 shadow-sm border border-slate-100">
                <div class="mb-5">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Jenis Izin <span class="text-red-500">*</span></label>
                    <select name="kategori_pengajuan" id="jenis_izin_select" required class="w-full bg-slate-50 border border-slate-200 text-slate-700 rounded-xl px-4 py-3.5 focus:outline-none focus:ring-2 focus:ring-blue-500/50 font-medium appearance-none">
                        <option value="">-- Pilih Jenis Izin --</option>
                        <option value="Sakit 1-2 Hari">Sakit (1-2 Hari)</option>
                        <option value="Sakit 3 Hari Lebih">Sakit (3 Hari atau Lebih)</option>
                        <option value="Keperluan Keluarga">Keperluan Keluarga</option>
                        <option value="Dispensasi">Dispensasi</option>
                        <option value="Keluar Sekolah">Keluar Sekolah (Kembali/Pulang)</option>
                    </select>
                </div>

                <div id="form-lokasi" class="mb-5" style="display:none;">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Lokasi Tujuan <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="text" name="lokasi_izin" id="lokasi_izin" placeholder="Contoh: Puskesmas, Fotokopi" class="w-full bg-slate-50 border border-slate-200 text-slate-700 rounded-xl px-4 py-3.5 focus:outline-none focus:ring-2 focus:ring-blue-500/50 placeholder:text-slate-400">
                        <button type="button" id="btn-gps" class="bg-emerald-100 hover:bg-emerald-200 text-emerald-600 px-4 rounded-xl transition flex-shrink-0" title="Gunakan Lokasi Saat Ini">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                    <p id="gps-status" class="text-xs text-slate-500 mt-2 flex items-center gap-1"></p>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Keterangan / Alasan <span class="text-red-500">*</span></label>
                    <textarea name="detail_izin" required rows="3" placeholder="Tuliskan keterangan detail..." class="w-full bg-slate-50 border border-slate-200 text-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500/50 resize-none placeholder:text-slate-400"></textarea>
                </div>

                <div id="form-kembali" class="mb-5" style="display:none;">
                    <label class="block text-sm font-bold text-slate-700 mb-3">Apakah Akan Kembali ke Sekolah? <span class="text-red-500">*</span></label>
                    <div class="flex gap-4">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="opsi_kembali" value="1" class="peer sr-only" id="radio_kembali">
                            <div class="text-center p-3 rounded-xl border border-slate-200 bg-slate-50 peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-600 transition-all text-slate-600 font-semibold text-sm">
                                <i class="fas fa-undo mr-1"></i> Ya, Kembali
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="opsi_kembali" value="0" class="peer sr-only">
                            <div class="text-center p-3 rounded-xl border border-slate-200 bg-slate-50 peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-600 transition-all text-slate-600 font-semibold text-sm">
                                <i class="fas fa-home mr-1"></i> Pulang
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Kamera Box -->
            <div id="kamera-box" class="bg-white rounded-[24px] p-6 shadow-sm border border-slate-100 mb-8">
                <div class="flex items-center gap-3 mb-5 border-b border-slate-100 pb-4">
                    <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-500 flex items-center justify-center">
                        <i class="fas fa-camera"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-800" id="kamera-title">Bukti Selfie</h2>
                        <p class="text-xs text-slate-500" id="kamera-desc">Wajib lampirkan foto saat ini</p>
                    </div>
                </div>

                <div id="camera-container" class="relative rounded-2xl overflow-hidden bg-slate-100 border-2 border-dashed border-slate-300 aspect-[3/4] flex items-center justify-center transition-colors">
                    <div id="foto-placeholder" class="text-center p-6">
                        <div class="w-16 h-16 bg-slate-200 text-slate-400 rounded-full flex items-center justify-center text-2xl mx-auto mb-3">
                            <i class="fas fa-image"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-500">Belum ada foto</p>
                    </div>

                    <video id="camera-video" class="w-full h-full object-cover" autoplay playsinline style="display:none; transform: scaleX(-1);"></video>
                    
                    <div class="relative w-full h-full" style="display:none;" id="preview-wrapper">
                        <img id="foto-preview" class="w-full h-full object-cover">
                    </div>

                    <canvas id="camera-canvas" style="display:none;"></canvas>
                </div>

                <div class="mt-4">
                    <button type="button" id="btn-buka-kamera" class="w-full bg-purple-50 hover:bg-purple-100 text-purple-600 font-bold py-3.5 px-4 rounded-xl flex items-center justify-center gap-2">
                        <i class="fas fa-camera"></i> Buka Kamera
                    </button>
                    <button type="button" id="btn-ambil-foto" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3.5 px-4 rounded-xl items-center justify-center gap-2" style="display:none;">
                        <i class="fas fa-circle"></i> Ambil Foto
                    </button>
                    <button type="button" id="btn-ulangi-foto" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3.5 px-4 rounded-xl items-center justify-center gap-2" style="display:none;">
                        <i class="fas fa-redo"></i> Ulangi Foto
                    </button>
                </div>

                <input type="hidden" name="foto_selfie_data" id="foto_selfie_data">
                <p id="foto-error" class="text-xs text-red-500 mt-3 font-semibold text-center" style="display:none;">
                    Foto wajib dilampirkan.
                </p>
            </div>

            <button type="submit" id="btn-submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-4 rounded-xl flex items-center justify-center gap-2">
                <i class="fas fa-paper-plane"></i> Kirim Pengajuan
            </button>
        </form>
        <?php endif; ?>

        <script>
            // Dropdown Logic
            const selectIzin = document.getElementById('jenis_izin_select');
            const formLokasi = document.getElementById('form-lokasi');
            const formKembali = document.getElementById('form-kembali');
            const kameraBox = document.getElementById('kamera-box');
            const kameraTitle = document.getElementById('kamera-title');
            const kameraDesc = document.getElementById('kamera-desc');
            const radKembali = document.getElementById('radio_kembali');

            selectIzin.addEventListener('change', function() {
                const val = this.value;
                formLokasi.style.display = (val === 'Keluar Sekolah') ? 'block' : 'none';
                formKembali.style.display = (val === 'Keluar Sekolah') ? 'block' : 'none';
                if(val === 'Keluar Sekolah') radKembali.required = true;
                else radKembali.required = false;

                if (val === 'Dispensasi') {
                    kameraBox.style.display = 'none';
                } else {
                    kameraBox.style.display = 'block';
                    if (val === 'Sakit 3 Hari Lebih') {
                        kameraTitle.innerText = "Surat Dokter";
                        kameraDesc.innerText = "Wajib lampirkan foto surat dokter";
                    } else {
                        kameraTitle.innerText = "Bukti Selfie / Surat";
                        kameraDesc.innerText = "Wajib lampirkan foto bukti";
                    }
                }
            });

            // GPS and Camera JS
            document.getElementById('btn-gps').addEventListener('click', function() {
                const lokasiInput = document.getElementById('lokasi_izin');
                const statusP     = document.getElementById('gps-status');
                if (!navigator.geolocation) return statusP.textContent = 'Geolocation tidak didukung browser.';
                statusP.textContent = 'Mencari lokasi...';
                navigator.geolocation.getCurrentPosition(pos => {
                    lokasiInput.value = pos.coords.latitude + ', ' + pos.coords.longitude;
                    statusP.innerHTML = 'Lokasi didapatkan.';
                }, () => statusP.innerHTML = 'Gagal ambil lokasi.');
            });

            let stream = null;
            const video = document.getElementById('camera-video');
            const canvas = document.getElementById('camera-canvas');
            const preview = document.getElementById('foto-preview');
            const placeholder = document.getElementById('foto-placeholder');
            const previewWrap = document.getElementById('preview-wrapper');
            const fotoInput = document.getElementById('foto_selfie_data');
            const btnBuka = document.getElementById('btn-buka-kamera');
            const btnAmbil = document.getElementById('btn-ambil-foto');
            const btnUlangi = document.getElementById('btn-ulangi-foto');

            btnBuka.addEventListener('click', async function() {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                    video.srcObject = stream;
                    video.style.transform = 'scaleX(1)'; // Environment cam no mirror
                    video.style.display = 'block';
                    placeholder.style.display = 'none';
                    btnBuka.style.display = 'none';
                    btnAmbil.style.display = 'flex';
                } catch (e) { alert('Tidak dapat mengakses kamera.'); }
            });

            btnAmbil.addEventListener('click', function() {
                canvas.width = video.videoWidth; canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                fotoInput.value = canvas.toDataURL('image/jpeg', 0.8);
                preview.src = fotoInput.value;
                previewWrap.style.display = 'block';
                video.style.display = 'none';
                btnAmbil.style.display = 'none';
                btnUlangi.style.display = 'flex';
                if (stream) stream.getTracks().forEach(t => t.stop());
            });

            btnUlangi.addEventListener('click', function() {
                fotoInput.value = ''; preview.src = '';
                previewWrap.style.display = 'none';
                placeholder.style.display = 'flex';
                btnUlangi.style.display = 'none';
                btnBuka.style.display = 'flex';
            });

            document.getElementById('formIzin').addEventListener('submit', function(e) {
                if (selectIzin.value !== 'Dispensasi' && !fotoInput.value) {
                    e.preventDefault();
                    document.getElementById('foto-error').style.display = 'block';
                }
            });
        </script>
        <?php endif; ?>
    </div>
    <br><br>
<?php include 'siswa_footer.php'; ?>
</body>
</html>
