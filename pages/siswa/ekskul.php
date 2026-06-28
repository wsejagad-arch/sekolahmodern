<?php
// pages/siswa/ekskul.php
require_once __DIR__ . '/../../koneksi.php';
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] == '') {
    die("Akses ditolak");
}

$nissiswa = $_SESSION['no_induk'];
$datasiswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_siswa WHERE no_induk='$nissiswa'"));

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'presensi_mandiri') {
        $id_ekskul = (int)$_POST['id_ekskul'];
        $tanggal = date('Y-m-d');
        
        // Simpan presensi dengan bukti status 'Hadir' dan flag foto_bukti = 'Selfie_Validated'
        // Karena user minta foto tidak disimpan, kita simpan string penanda saja
        mysqli_query($conn, "INSERT INTO tbl_presensi_ekskul (id_ekskul, no_induk_siswa, tanggal, waktu, status, foto_bukti) VALUES ($id_ekskul, '$nissiswa', '$tanggal', CURTIME(), 'Hadir', 'Selfie_Validated') ON DUPLICATE KEY UPDATE status='Hadir', foto_bukti='Selfie_Validated'");
        
        echo "<script>alert('Presensi berhasil dikonfirmasi melalui deteksi wajah!'); window.location='ekskul.php?id_ekskul=$id_ekskul';</script>";
        exit;
    }
}

// Fetch Ekskul yang diikuti
$q_myekskul = mysqli_query($conn, "SELECT e.* FROM tbl_ekskul e JOIN tbl_anggota_ekskul a ON e.id_ekskul = a.id_ekskul WHERE a.no_induk_siswa = '$nissiswa'");
$my_ekskul = [];
while($r = mysqli_fetch_assoc($q_myekskul)) {
    $my_ekskul[] = $r;
}

$id_ekskul_active = isset($_GET['id_ekskul']) ? (int)$_GET['id_ekskul'] : ($my_ekskul[0]['id_ekskul'] ?? 0);
$hari_ini = array('Sun'=>'Minggu','Mon'=>'Senin','Tue'=>'Selasa','Wed'=>'Rabu','Thu'=>'Kamis','Fri'=>'Jumat','Sat'=>'Sabtu')[date('D')];
$tgl_sekarang = date('Y-m-d');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ekstrakurikuler - Siswa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; margin: 0; padding-bottom: 80px; }
        .topbar { background: #EC4899; color: white; padding: 15px 20px; display: flex; align-items: center; gap: 15px; position: sticky; top: 0; z-index: 100; }
        .topbar a { color: white; text-decoration: none; font-size: 1.2rem; }
        .topbar h1 { margin: 0; font-size: 1.2rem; font-weight: 600; }
        .container { padding: 20px; }
        .card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .btn-ekskul { display: inline-block; padding: 10px 20px; background: #EC4899; color: white; border-radius: 8px; text-decoration: none; border: none; font-weight: 600; cursor: pointer; }
        .btn-ekskul:disabled { background: #ccc; cursor: not-allowed; }
        select.form-select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #d1d5db; margin-bottom: 20px; font-family: inherit; font-size: 1rem; }
        
        #video-container { position: relative; width: 100%; max-width: 400px; border-radius: 12px; overflow: hidden; display: none; margin-top: 15px; }
        video { width: 100%; height: auto; }
        canvas { position: absolute; top: 0; left: 0; }
        #status-text { margin-top: 10px; font-weight: 600; color: #EC4899; }
    </style>
</head>
<body>

<div class="topbar">
    <a href="siswa.php"><i class="fas fa-arrow-left"></i></a>
    <h1>Ekstrakurikuler</h1>
</div>

<div class="container">
    <?php if (empty($my_ekskul)): ?>
        <div class="card">
            <h3 style="margin-top:0;">Belum Ada Ekskul</h3>
            <p>Anda belum terdaftar di ekstrakurikuler manapun. Hubungi pembina ekskul untuk mendaftarkan Anda.</p>
        </div>
    <?php else: ?>
        <select class="form-select" onchange="window.location='ekskul.php?id_ekskul='+this.value">
            <?php foreach($my_ekskul as $ekskul): ?>
                <option value="<?= $ekskul['id_ekskul'] ?>" <?= $ekskul['id_ekskul'] == $id_ekskul_active ? 'selected' : '' ?>><?= htmlspecialchars($ekskul['nama_ekskul']) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Cek Jadwal Hari Ini -->
        <?php
        $q_jadwal = mysqli_query($conn, "SELECT * FROM tbl_jadwal_ekskul WHERE id_ekskul=$id_ekskul_active AND hari='$hari_ini'");
        $jadwal_hari_ini = mysqli_fetch_assoc($q_jadwal);
        
        // Cek apakah sudah absen hari ini
        $q_absen = mysqli_query($conn, "SELECT * FROM tbl_presensi_ekskul WHERE id_ekskul=$id_ekskul_active AND no_induk_siswa='$nissiswa' AND tanggal='$tgl_sekarang'");
        $sudah_absen = mysqli_fetch_assoc($q_absen);
        ?>

        <div class="card">
            <h3 style="margin-top:0; color: #EC4899;">Presensi Hari Ini</h3>
            <?php if ($sudah_absen && $sudah_absen['foto_bukti'] == 'Selfie_Validated'): ?>
                <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-check-circle" style="font-size:1.5rem;"></i>
                    <div>
                        <strong>Sudah Presensi</strong><br>
                        Waktu: <?= $sudah_absen['waktu'] ?> WIB
                    </div>
                </div>
            <?php elseif ($jadwal_hari_ini): ?>
                <p>Ekskul ini dijadwalkan hari ini (<?= $jadwal_hari_ini['jam_mulai'] ?> - <?= $jadwal_hari_ini['jam_selesai'] ?>).</p>
                <p>Silakan lakukan presensi wajah mandiri.</p>
                <button class="btn-ekskul" id="btn-start-camera" onclick="startPresensi()">Mulai Presensi Wajah</button>
                
                <div id="video-container">
                    <video id="video" autoplay muted playsinline></video>
                    <div id="status-text">Memuat model kamera... harap tunggu.</div>
                </div>
                
                <form id="form-presensi" method="post" style="display:none;">
                    <input type="hidden" name="action" value="presensi_mandiri">
                    <input type="hidden" name="id_ekskul" value="<?= $id_ekskul_active ?>">
                </form>
            <?php else: ?>
                <p>Tidak ada jadwal ekstrakurikuler ini untuk hari ini (<?= $hari_ini ?>).</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 style="margin-top:0; color: #374151;">Jadwal Ekskul</h3>
            <ul style="padding-left: 20px; color: #4b5563;">
                <?php
                $q_all_jadwal = mysqli_query($conn, "SELECT * FROM tbl_jadwal_ekskul WHERE id_ekskul=$id_ekskul_active ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')");
                while($aj = mysqli_fetch_assoc($q_all_jadwal)):
                ?>
                <li><strong><?= $aj['hari'] ?>:</strong> <?= $aj['jam_mulai'] ?> - <?= $aj['jam_selesai'] ?> WIB</li>
                <?php endwhile; ?>
            </ul>
        </div>

        <div class="card">
            <h3 style="margin-top:0; color: #374151;">Histori Tugas</h3>
            <?php
            $q_tugas = mysqli_query($conn, "SELECT * FROM tbl_tugas_ekskul WHERE id_ekskul=$id_ekskul_active ORDER BY tanggal DESC");
            if (mysqli_num_rows($q_tugas) > 0) {
                while($t = mysqli_fetch_assoc($q_tugas)):
            ?>
                <div style="border-bottom: 1px solid #e5e7eb; padding: 10px 0;">
                    <div style="font-size: 0.85rem; color: #6b7280;"><?= $t['tanggal'] ?></div>
                    <div style="font-weight: 600; color: #111827;"><?= htmlspecialchars($t['judul']) ?></div>
                    <div style="font-size: 0.95rem; color: #4b5563;"><?= nl2br(htmlspecialchars($t['deskripsi'])) ?></div>
                </div>
            <?php 
                endwhile;
            } else {
                echo "<p>Belum ada tugas yang diberikan.</p>";
            }
            ?>
        </div>
        
    <?php endif; ?>
</div>

<script>
async function startPresensi() {
    const videoContainer = document.getElementById('video-container');
    const video = document.getElementById('video');
    const statusText = document.getElementById('status-text');
    const btnStart = document.getElementById('btn-start-camera');
    
    btnStart.style.display = 'none';
    videoContainer.style.display = 'block';

    try {
        // Load models directly from face-api CDN weights
        statusText.innerText = "Mengunduh model pengenalan wajah...";
        await faceapi.nets.tinyFaceDetector.loadFromUri('https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights');
        await faceapi.nets.faceLandmark68Net.loadFromUri('https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights');
        
        statusText.innerText = "Meminta akses kamera...";
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        video.srcObject = stream;
        
        video.addEventListener('play', () => {
            const canvas = faceapi.createCanvasFromMedia(video);
            videoContainer.append(canvas);
            const displaySize = { width: video.videoWidth, height: video.videoHeight };
            faceapi.matchDimensions(canvas, displaySize);
            
            statusText.innerText = "Mendeteksi wajah... Pastikan wajah (mata, hidung, mulut) terlihat jelas dan lurus.";
            
            const interval = setInterval(async () => {
                const detections = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
                if (detections) {
                    // Face detected with landmarks (eyes, nose, mouth present)
                    clearInterval(interval);
                    statusText.innerText = "Wajah terdeteksi! Memproses presensi...";
                    statusText.style.color = "green";
                    
                    // Stop camera
                    stream.getTracks().forEach(track => track.stop());
                    
                    // Submit form
                    document.getElementById('form-presensi').submit();
                }
            }, 500);
        });
        
    } catch (err) {
        statusText.innerText = "Error: " + err.message + " (Pastikan Anda memberi izin kamera)";
        statusText.style.color = "red";
    }
}
</script>

</body>
</html>
