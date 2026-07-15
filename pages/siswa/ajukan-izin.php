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
$kategori = $_GET['kategori'] ?? '';

    // --- ANTI NUMPUK CHECK ---
    // Cek apakah ada izin aktif hari ini
    $today = date('Y-m-d');
    $qCekAktif = mysqli_query($conn, "SELECT id_izin, kategori_pengajuan, status_izin FROM tbl_izin_siswa WHERE no_induk_siswa = '$nis' AND tanggal_izin = '$today' AND status_izin IN ('Menunggu Wali Kelas', 'Menunggu Guru BK', 'Menunggu Satpam', 'Menunggu Validasi', 'Menunggu', 'Disetujui', 'Disetujui Penuh') ORDER BY waktu_pengajuan DESC LIMIT 1");
    $adaIzinAktif = ($qCekAktif && mysqli_num_rows($qCekAktif) > 0);
    if ($adaIzinAktif) {
        $rowAktif = mysqli_fetch_assoc($qCekAktif);
        $kategoriAktif = $rowAktif['kategori_pengajuan'];
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($adaIzinAktif) {
        $pesan = 'Anda masih memiliki pengajuan izin aktif hari ini. Harap tunggu hingga selesai atau batalkan pengajuan sebelumnya.';
    } else {
    $kategori_pengajuan = $_POST['kategori_pengajuan'] ?? 'Tidak Masuk';
    $jenis_izin   = $_POST['jenis_izin']   ?? '';
    $detail_izin  = $_POST['detail_izin']  ?? '';
    $lokasi_izin  = $_POST['lokasi_izin']  ?? '';
    $foto_selfie_b64 = $_POST['foto_selfie_data'] ?? '';
    $opsi_kembali = $_POST['opsi_kembali'] ?? null;
    $tanggal_izin = date('Y-m-d');
    $waktu_pengajuan = date('Y-m-d H:i:s');

    // Khusus izin keluar, default ke Izin Keluar untuk jenisnya jika kosong
    if ($kategori_pengajuan === 'Keluar Sekolah') {
        $jenis_izin = 'Izin Keluar';
    } else if ($kategori_pengajuan === 'Dispen') {
        $jenis_izin = 'Dispen';
    }

    if (!empty($jenis_izin) && !empty($detail_izin) && !empty($foto_selfie_b64)) {
        
        // Simpan foto
        $foto_name = '';
        if (preg_match('/^data:image\/(\w+);base64,/', $foto_selfie_b64, $type)) {
            $foto_b64_data = substr($foto_selfie_b64, strpos($foto_selfie_b64, ',') + 1);
            $type = strtolower($type[1]);
            if (in_array($type, [ 'jpg', 'jpeg', 'png' ])) {
                $foto_data = base64_decode($foto_b64_data);
                $foto_name = 'selfie_' . time() . '_' . $nis . '.' . $type;
                $upload_dir = '../../uploads/izin/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                file_put_contents($upload_dir . $foto_name, $foto_data);
            }
        }

        if (!empty($foto_name)) {
            $acc_wali = 'Pending';
            $acc_satpam = ($kategori_pengajuan === 'Keluar Sekolah') ? 'Pending' : NULL;

            // Pastikan kolom sudah ada
            $cekCols = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_izin_siswa LIKE 'kategori_pengajuan'");
            if ($cekCols && mysqli_num_rows($cekCols) > 0) {
                $sql = "INSERT INTO tbl_izin_siswa (no_induk_siswa, kelas_siswa, jenis_izin, detail_izin, lokasi_izin, foto_selfie, tanggal_izin, waktu_pengajuan, kategori_pengajuan, opsi_kembali, validasi_wali_kelas, validasi_satpam, status_izin, id_sekolah) 
                        VALUES ('$nis', '$kelas', '$jenis_izin', '$detail_izin', '$lokasi_izin', '$foto_name', '$tanggal_izin', '$waktu_pengajuan', '$kategori_pengajuan', " . ($opsi_kembali ? "'$opsi_kembali'" : "NULL") . ", 'Menunggu', 'Menunggu', 'Menunggu Validasi', '".mt_current_school_id()."')";
            } 

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
                // Cek guru BK yang ditugaskan ke kelas ini
                $qBkKelas = mysqli_query($conn, "SELECT g.no_wa FROM tbl_guru_bk b JOIN tbl_guru g ON b.no_induk = g.no_induk WHERE b.kelas = '$kelas' AND g.no_wa != ''");
                
                if ($qBkKelas && mysqli_num_rows($qBkKelas) > 0) {
                    $bkQuery = $qBkKelas;
                } else {
                    // Fallback ke deteksi lama (semua guru BK) jika belum ada di tabel tbl_guru_bk
                    $bkQuery = mysqli_query($conn, "SELECT no_wa FROM tbl_guru WHERE (jabatan LIKE '%BK%' OR is_guru_bk = 1) AND no_wa != ''");
                }
                
                if ($bkQuery && mysqli_num_rows($bkQuery) > 0) {
                    while ($rowBk = mysqli_fetch_assoc($bkQuery)) {
                        $no_wa_bk = $rowBk['no_wa'];
                        if (!empty($no_wa_bk)) {
                            $pesanWABK = "Halo Bapak/Ibu Guru BK,\n\nSiswa:\nNama: *$nama_siswa*\nKelas: *$kelas*\n\nTelah mengajukan izin *$kategori_pengajuan* dengan keterangan: _{$detail_izin}_.\n\nSilakan periksa sistem untuk memantau.";
                            notif_send_whatsapp($no_wa_bk, "Notifikasi Izin Siswa", $pesanWABK, $conn);
                        }
                    }
                }
            } else {
                $pesan = 'Gagal menyimpan ke database.';
            }
        } else {
            $pesan = 'Gagal memproses foto selfie.';
        }
    } else {
        $pesan = 'Mohon lengkapi semua data wajib beserta foto.';
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

    <!-- Header -->
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

    <!-- Content -->
    <div class="px-5 -mt-4 relative z-20">
        
        <?php if ($pesan === 'sukses'): ?>
        <div class="bg-white rounded-[24px] p-8 shadow-sm border border-slate-100 text-center mt-8">
            <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-500 flex items-center justify-center text-4xl mx-auto mb-5">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="text-xl font-black text-slate-800 mb-2">Pengajuan Berhasil</h2>
            <p class="text-sm text-slate-500 mb-6">Pengajuan izin Anda sudah terkirim ke Wali Kelas untuk diproses.</p>
            <a href="status-izin.php" class="inline-flex bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3.5 rounded-xl transition shadow-lg shadow-blue-500/30 items-center justify-center gap-2">
                <i class="fas fa-list-alt"></i> Lihat Status Izin
            </a>
        </div>
        <?php elseif (empty($kategori)): ?>
        
        <!-- Link to History -->
        <a href="status-izin.php" class="block bg-blue-50 hover:bg-blue-100 p-4 rounded-xl border border-blue-200 mb-6 flex items-center justify-between transition-colors mt-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center">
                    <i class="fas fa-history"></i>
                </div>
                <div>
                    <h4 class="font-bold text-blue-800 text-sm">Riwayat & Status Izin</h4>
                    <p class="text-xs text-blue-600">Cek status pengajuan izin Anda</p>
                </div>
            </div>
            <i class="fas fa-chevron-right text-blue-400 text-sm"></i>
        </a>

        <!-- Menu Kategori Izin -->
        <h3 class="text-slate-700 font-bold text-lg mb-4 px-2">Pilih Jenis Izin</h3>
        
        <a href="?kategori=tidak_masuk" class="block bg-white p-5 rounded-[20px] shadow-sm border border-slate-100 mb-4 flex items-center justify-between active:scale-95 transition-transform">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center text-xl">
                    <i class="fas fa-house-user"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800">Izin Tidak Masuk</h4>
                    <p class="text-xs text-slate-500">Izin tidak hadir ke sekolah (Sakit/Acara Keluarga)</p>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-300"></i>
        </a>

        <a href="?kategori=keluar" class="block bg-white p-5 rounded-[20px] shadow-sm border border-slate-100 mb-4 flex items-center justify-between active:scale-95 transition-transform">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-500 flex items-center justify-center text-xl">
                    <i class="fas fa-person-walking-arrow-right"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800">Izin Keluar Sekolah</h4>
                    <p class="text-xs text-slate-500">Izin keluar di jam pelajaran (Fotokopi/Pulang)</p>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-300"></i>
        </a>

        <a href="?kategori=dispen" class="block bg-white p-5 rounded-[20px] shadow-sm border border-slate-100 mb-4 flex items-center justify-between active:scale-95 transition-transform">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-purple-100 text-purple-500 flex items-center justify-center text-xl">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800">Izin Dispen</h4>
                    <p class="text-xs text-slate-500">Dispensasi kegiatan sekolah/lomba</p>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-300"></i>
        </a>

        <?php else: ?>

        <?php
            $kat_label = 'Tidak Masuk Sekolah';
            $kat_val = 'Tidak Masuk';
            if ($kategori === 'keluar') { $kat_label = 'Keluar Sekolah'; $kat_val = 'Keluar Sekolah'; }
            if ($kategori === 'dispen') { $kat_label = 'Dispensasi'; $kat_val = 'Dispen'; }
        ?>

                <?php if ($adaIzinAktif && empty($pesan)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5 mb-6 text-yellow-800">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-triangle mt-1 text-yellow-600"></i>
                <div>
                    <h3 class="font-bold mb-1">Pengajuan Izin Aktif</h3>
                    <p class="text-sm">Anda masih memiliki pengajuan izin (<strong><?= htmlspecialchars($kategoriAktif) ?></strong>) yang sedang diproses hari ini. Anda tidak dapat mengajukan izin baru sebelum izin tersebut selesai atau dibatalkan.</p>
                    <a href="status-izin.php" class="inline-block mt-3 px-4 py-2 bg-yellow-600 text-white text-sm font-semibold rounded-lg hover:bg-yellow-700 transition">Lihat Status Izin</a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <form action="" method="POST" id="formIzin" class="mt-8 space-y-6">
            <input type="hidden" name="kategori_pengajuan" value="<?= $kat_val ?>">
            
            <?php if ($pesan): ?>
            <div class="bg-red-50 text-red-500 p-4 rounded-xl text-sm font-semibold flex items-center gap-2 border border-red-100">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($pesan) ?>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-[24px] p-6 shadow-sm border border-slate-100">
                <div class="flex items-center gap-3 mb-5 border-b border-slate-100 pb-4">
                    <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-800">Form Izin <?= $kat_label ?></h2>
                        <p class="text-xs text-slate-500">Lengkapi data di bawah ini</p>
                    </div>
                </div>

                <?php if ($kategori === 'tidak_masuk'): ?>
                <div class="mb-5">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Jenis Izin <span class="text-red-500">*</span></label>
                    <select name="jenis_izin" required class="w-full bg-slate-50 border border-slate-200 text-slate-700 rounded-xl px-4 py-3.5 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 font-medium">
                        <option value="">-- Pilih Jenis --</option>
                        <option value="Sakit">Sakit</option>
                        <option value="Izin">Izin (Acara Keluarga, dsb)</option>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ($kategori === 'keluar'): ?>
                <div class="mb-5">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Opsi Kembali <span class="text-red-500">*</span></label>
                    <select name="opsi_kembali" required class="w-full bg-slate-50 border border-slate-200 text-slate-700 rounded-xl px-4 py-3.5 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 font-medium">
                        <option value="">-- Pilih Opsi --</option>
                        <option value="Ya">Kembali Lagi (Keluar Sementara)</option>
                        <option value="Tidak">Tidak Kembali (Langsung Pulang)</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="mb-5">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Keterangan/Alasan <span class="text-red-500">*</span></label>
                    <textarea name="detail_izin" required rows="3" placeholder="Tuliskan alasan izin Anda dengan jelas..."
                        class="w-full bg-slate-50 border border-slate-200 text-slate-700 rounded-xl px-4 py-3.5 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 font-medium resize-none"></textarea>
                </div>

                <div class="mb-2">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Lokasi Anda Saat Ini</label>
                    <div class="flex gap-2">
                        <input type="text" id="lokasi_izin" name="lokasi_izin" readonly placeholder="Klik tombol lokasi"
                            class="w-full bg-slate-50 border border-slate-200 text-slate-500 rounded-xl px-4 py-3.5 focus:outline-none text-sm font-medium">
                        <button type="button" id="btn-gps" class="bg-blue-100 hover:bg-blue-200 text-blue-600 px-5 rounded-xl transition flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                    <p id="gps-status" class="text-xs text-slate-400 mt-2 ml-1">Lokasi opsional namun disarankan.</p>
                </div>
            </div>

            <!-- Kamera Box -->
            <div class="bg-white rounded-[24px] p-6 shadow-sm border border-slate-100 mb-8">
                <div class="flex items-center gap-3 mb-5 border-b border-slate-100 pb-4">
                    <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-500 flex items-center justify-center">
                        <i class="fas fa-camera"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-800">Bukti Selfie</h2>
                        <p class="text-xs text-slate-500">Wajib lampirkan foto saat ini</p>
                    </div>
                </div>

                <div id="camera-container" class="relative rounded-2xl overflow-hidden bg-slate-100 border-2 border-dashed border-slate-300 aspect-[3/4] flex items-center justify-center transition-colors">
                    
                    <div id="foto-placeholder" class="text-center p-6">
                        <div class="w-16 h-16 bg-slate-200 text-slate-400 rounded-full flex items-center justify-center text-2xl mx-auto mb-3">
                            <i class="fas fa-user"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-500">Belum ada foto</p>
                        <p class="text-xs text-slate-400 mt-1">Gunakan tombol di bawah untuk membuka kamera</p>
                    </div>

                    <video id="camera-video" class="w-full h-full object-cover" autoplay playsinline style="display:none; transform: scaleX(-1);"></video>
                    
                    <div class="relative w-full h-full" style="display:none;" id="preview-wrapper">
                        <img id="foto-preview" class="w-full h-full object-cover">
                        <span id="foto-ok-badge" class="absolute -top-3 -right-3 bg-emerald-500 border-[3px] border-white text-white text-xs px-3 py-1.5 rounded-full shadow-sm font-bold"><i class="fas fa-check mr-1"></i>OK</span>
                    </div>

                    <canvas id="camera-canvas" style="display:none;"></canvas>
                </div>

                <div class="mt-4">
                    <button type="button" id="btn-buka-kamera" class="w-full bg-purple-50 hover:bg-purple-100 text-purple-600 font-bold py-3.5 px-4 rounded-xl transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-camera"></i> Buka Kamera
                    </button>
                    <button type="button" id="btn-ambil-foto" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3.5 px-4 rounded-xl transition-colors items-center justify-center gap-2 shadow-lg shadow-purple-500/30" style="display:none;">
                        <i class="fas fa-circle"></i> Ambil Foto
                    </button>
                    <button type="button" id="btn-ulangi-foto" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3.5 px-4 rounded-xl transition-colors items-center justify-center gap-2" style="display:none;">
                        <i class="fas fa-redo"></i> Ulangi Foto
                    </button>
                </div>

                <input type="hidden" name="foto_selfie_data" id="foto_selfie_data">
                <p id="foto-error" class="text-xs text-red-500 mt-3 font-semibold text-center" style="display:none;">
                    <i class="fas fa-exclamation-circle mr-1"></i> Foto selfie wajib diambil.
                </p>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="btn-submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-500/30 transition-all flex items-center justify-center gap-2 text-base">
                <i class="fas fa-paper-plane"></i> Kirim Pengajuan
            </button>
        </form>
        <?php endif; ?>

        <script>
            // GPS and Camera JS
            document.getElementById('btn-gps').addEventListener('click', function() {
                const lokasiInput = document.getElementById('lokasi_izin');
                const statusP     = document.getElementById('gps-status');
                if (!navigator.geolocation) {
                    statusP.textContent = 'Geolocation tidak didukung browser.';
                    return;
                }
                statusP.textContent = 'Mencari lokasi...';
                this.disabled = true;
                navigator.geolocation.getCurrentPosition(pos => {
                    lokasiInput.value = pos.coords.latitude + ', ' + pos.coords.longitude;
                    statusP.innerHTML = '<i class="fas fa-check-circle text-emerald-500"></i> Lokasi didapatkan.';
                    document.getElementById('btn-gps').disabled = false;
                }, () => {
                    statusP.innerHTML = '<i class="fas fa-exclamation-triangle text-red-500"></i> Gagal ambil lokasi.';
                    document.getElementById('btn-gps').disabled = false;
                });
            });

            let stream = null;
            const video = document.getElementById('camera-video');
            const canvas = document.getElementById('camera-canvas');
            const preview = document.getElementById('foto-preview');
            const placeholder = document.getElementById('foto-placeholder');
            const previewWrap = document.getElementById('preview-wrapper');
            const fotoInput = document.getElementById('foto_selfie_data');
            const container = document.getElementById('camera-container');

            const btnBuka = document.getElementById('btn-buka-kamera');
            const btnAmbil = document.getElementById('btn-ambil-foto');
            const btnUlangi = document.getElementById('btn-ulangi-foto');

            // Fallback input file untuk akses kamera native / galeri (sangat berguna jika HTTP / error)
            const fotoFile = document.createElement('input');
            fotoFile.type = 'file';
            fotoFile.accept = 'image/*';
            fotoFile.capture = 'user';
            fotoFile.style.display = 'none';
            document.body.appendChild(fotoFile);

            fotoFile.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        const img = new Image();
                        img.onload = function() {
                            // Resize gambar agar tidak terlalu besar
                            const MAX_WIDTH = 800;
                            const MAX_HEIGHT = 800;
                            let width = img.width;
                            let height = img.height;
                            if (width > height) {
                                if (width > MAX_WIDTH) { height *= MAX_WIDTH / width; width = MAX_WIDTH; }
                            } else {
                                if (height > MAX_HEIGHT) { width *= MAX_HEIGHT / height; height = MAX_HEIGHT; }
                            }
                            canvas.width = width;
                            canvas.height = height;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0, width, height);
                            
                            fotoInput.value = canvas.toDataURL('image/jpeg', 0.8);
                            preview.src = fotoInput.value;
                            previewWrap.style.display = 'block';
                            placeholder.style.display = 'none';
                            video.style.display = 'none';
                            btnBuka.style.display = 'none';
                            btnAmbil.style.display = 'none';
                            btnUlangi.style.display = 'flex';
                            if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
                        };
                        img.src = ev.target.result;
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });

            async function startCamera() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    // Jika browser tidak mendukung (misal karena pakai HTTP bukan HTTPS) -> Auto buka kamera HP
                    fotoFile.click();
                    return;
                }
                try {
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
                    video.srcObject = stream;
                    video.style.display = 'block';
                    placeholder.style.display = 'none';
                    previewWrap.style.display = 'none';
                    btnBuka.style.display = 'none';
                    btnAmbil.style.display = 'flex';
                } catch (e) { 
                    console.error('Kamera web gagal:', e);
                    // Jika error (diblokir / tidak diizinkan) -> Fallback ke aplikasi kamera bawaan HP
                    fotoFile.click(); 
                }
            }

            btnBuka.addEventListener('click', startCamera);

            // AUTO START CAMERA jika didukung (agar user tidak perlu klik "Buka Kamera" jika sudah diizinkan sebelumnya)
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
                .then(s => {
                    stream = s;
                    video.srcObject = stream;
                    video.style.display = 'block';
                    placeholder.style.display = 'none';
                    previewWrap.style.display = 'none';
                    btnBuka.style.display = 'none';
                    btnAmbil.style.display = 'flex';
                }).catch(e => {
                    // Abaikan error auto-start, user tetap bisa klik tombol "Buka Kamera" atau pakai fallback
                });
            }

            btnAmbil.addEventListener('click', function() {
                canvas.width = video.videoWidth; canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.translate(canvas.width, 0); ctx.scale(-1, 1);
                ctx.drawImage(video, 0, 0);
                fotoInput.value = canvas.toDataURL('image/jpeg', 0.8);

                preview.src = fotoInput.value;
                previewWrap.style.display = 'block';
                video.style.display = 'none';
                btnAmbil.style.display = 'none';
                btnUlangi.style.display = 'flex';
                if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
            });

            btnUlangi.addEventListener('click', function() {
                fotoInput.value = ''; preview.src = '';
                previewWrap.style.display = 'none';
                placeholder.style.display = 'flex';
                btnUlangi.style.display = 'none';
                btnBuka.style.display = 'flex';
                startCamera(); // Auto nyalakan kamera lagi saat klik ulangi
            });

            document.getElementById('formIzin').addEventListener('submit', function(e) {
                if (!fotoInput.value) {
                    e.preventDefault();
                    document.getElementById('foto-error').style.display = 'block';
                }
            });
        </script>
        <?php endif; ?>

    </div>
    <br><br><br>
<?php include 'siswa_footer.php'; ?>
</body>
</html>