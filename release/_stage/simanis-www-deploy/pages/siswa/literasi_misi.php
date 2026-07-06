<?php
// pages/siswa/literasi_misi.php
require_once __DIR__ . '/../../auth_helper.php';
require_once __DIR__ . '/../../bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 3) {
    header('Location: ../../login.php?haruslogin');
    exit;
}

$nis = $_SESSION['no_induk'];
$nisEsc = mysqli_real_escape_string($conn, $nis);
$id = (int)($_GET['id'] ?? 0);
$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;

$qTugas = mysqli_query($conn, "SELECT * FROM tbl_literasi_tugas WHERE id=$id AND id_sekolah=$idSekolah");
if (mysqli_num_rows($qTugas) == 0) {
    echo "Misi tidak ditemukan."; exit;
}
$tugas = mysqli_fetch_assoc($qTugas);

$is_selesai = false;
$skor_evaluasi = 0;
// Check / Update Progress
$qProg = mysqli_query($conn, "SELECT * FROM tbl_literasi_progress WHERE id_tugas=$id AND no_induk_siswa='$nisEsc'");
if (mysqli_num_rows($qProg) == 0) {
    mysqli_query($conn, "INSERT INTO tbl_literasi_progress (id_tugas, no_induk_siswa, status, waktu_mulai, id_sekolah) VALUES ($id, '$nisEsc', 'membaca', CURRENT_TIMESTAMP, $idSekolah)");
    $qProg = mysqli_query($conn, "SELECT * FROM tbl_literasi_progress WHERE id_tugas=$id AND no_induk_siswa='$nisEsc'");
    $prog = mysqli_fetch_assoc($qProg);
} else {
    $prog = mysqli_fetch_assoc($qProg);
    if ($prog['status'] === 'selesai') {
        $is_selesai = true;
        $skor_evaluasi = $prog['skor_evaluasi'];
    }
    if (empty($prog['waktu_mulai'])) {
        mysqli_query($conn, "UPDATE tbl_literasi_progress SET waktu_mulai=CURRENT_TIMESTAMP WHERE id={$prog['id']}");
        $prog['waktu_mulai'] = date('Y-m-d H:i:s');
    }
}

$tipe = $tugas['tipe_media'];
$durasi = (int)$tugas['durasi_minimal'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Siswa - Misi Literasi</title>
  <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
  <style>
      body { background-color: #f9fafb; font-family: 'Nunito', sans-serif; }
      .bg-mission { background: linear-gradient(135deg, #6ee7b7 0%, #10b981 100%); color: white; border-radius: 0 0 24px 24px; box-shadow: 0 4px 20px rgba(16,185,129,0.2); }
      .media-container { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); overflow: hidden; padding: 15px; text-align: center; border: 1px solid #f3f4f6; }
      .timer-box { font-size: 1.2rem; font-weight: 800; background: #fef3c7; color: #92400e; padding: 12px 24px; border-radius: 50px; display: inline-block; border: 1px solid #fde68a; }
      iframe, img, embed { max-width: 100%; height: auto; border-radius: 12px; }
      embed { min-height: 600px; width: 100%; }
      .yt-wrapper { position: relative; padding-bottom: 56.25%; height: 0; border-radius: 12px; overflow: hidden; }
      .yt-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
      .btn-tutup { background: rgba(255,255,255,0.2); color: white; border-radius: 50px; padding: 8px 20px; font-weight: 800; font-size: 0.9rem; border: 1px solid rgba(255,255,255,0.4); transition: all 0.3s ease; display: inline-flex; align-items: center; justify-content: center; backdrop-filter: blur(5px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
      .btn-tutup:hover { background: white; color: #10b981; text-decoration: none; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.1); }
      .btn-tutup i { font-size: 1.2rem; margin-right: 8px; }
  </style>
</head>
<body>

<div class="bg-mission p-4 mb-4">
    <div class="container d-flex flex-wrap justify-content-between align-items-center">
        <div class="mr-2 mb-2 mb-md-0" style="flex: 1; min-width: 200px;">
            <h5 class="mb-1 font-weight-bold" style="letter-spacing: 0.5px;"><?= htmlspecialchars($tugas['judul']) ?></h5>
            <p class="mb-0" style="font-size: 0.9rem; color: rgba(255,255,255,0.9);"><?= htmlspecialchars($tugas['deskripsi']) ?></p>
        </div>
        <a href="literasi.php" class="btn-tutup mt-2 mt-md-0" style="white-space: nowrap; flex-shrink: 0;"><i class="far fa-times-circle"></i> Tutup</a>
    </div>
</div>

<div class="container pb-5 text-center">
    
    <?php if ($is_selesai): ?>
        <div class="timer-box mb-4 px-4 py-3 font-weight-bold shadow-sm" id="timerDisplay" style="background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; font-size: 1rem; border-radius: 50px;">
            <i class="fas fa-check-circle mr-1"></i> Status: Sudah Dikerjakan (Nilai: <?= $skor_evaluasi ?>)
        </div>
    <?php else: ?>
        <div class="timer-box mb-4 shadow-sm" id="timerDisplay">
            <i class="fas fa-hourglass-half fa-spin mr-2"></i> <span id="timeVal"><?= $durasi ?></span> Detik Tersisa
        </div>
    <?php endif; ?>

    <div class="media-container mb-4 mx-auto" style="max-width: 900px; position:relative;">
        <?php if ($tipe === 'pdf'): ?>
            <embed src="../../<?= htmlspecialchars($tugas['file_media']) ?>#toolbar=0" type="application/pdf">
        <?php elseif ($tipe === 'gambar'): ?>
            <img src="../../<?= htmlspecialchars($tugas['file_media']) ?>" alt="Infografis">
        <?php elseif ($tipe === 'video'): 
            // extract Youtube ID
            $yt_url = $tugas['file_media'];
            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $yt_url, $match);
            $yt_id = $match[1] ?? '';
        ?>
            <?php if ($is_selesai): ?>
                <div class="yt-wrapper">
                    <img src="https://img.youtube.com/vi/<?= $yt_id ?>/maxresdefault.jpg" onerror="this.onerror=null; this.src='https://img.youtube.com/vi/<?= $yt_id ?>/0.jpg';" style="width:100%; height:100%; object-fit:cover; position:absolute; top:0; left:0; border-radius: 12px;" alt="Video Thumbnail">
                    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:rgba(255,255,255,0.85); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); color:#111827; padding:15px 30px; border-radius:20px; font-weight:800; display:flex; align-items:center; gap:10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                        <i class="fas fa-check-circle fa-2x text-success"></i> <span style="font-size:1.1rem; letter-spacing: 0.5px;">Video Sudah Ditonton</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="yt-wrapper">
                    <!-- Using controls=0 to prevent seeking -->
                    <iframe id="ytplayer" src="https://www.youtube.com/embed/<?= $yt_id ?>?enablejsapi=1&controls=0&disablekb=1&rel=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <script src="https://www.youtube.com/iframe_api"></script>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if ($is_selesai): ?>
        <button class="btn btn-light btn-lg disabled font-weight-bold px-5 py-3 shadow-sm rounded-pill text-gray-500" disabled style="background: #f3f4f6; border: 1px solid #e5e7eb;">
            <i class="fas fa-check-double mr-2"></i> Misi Selesai
        </button>
    <?php else: ?>
        <button id="btnEvaluasi" class="btn btn-light btn-lg disabled font-weight-bold px-5 py-3 shadow-sm rounded-pill text-gray-500" disabled style="background: #f3f4f6; border: 1px solid #e5e7eb;" onclick="goEvaluasi()">
            <i class="fas fa-lock mr-2" id="btnIcon"></i> Selesaikan Bacaan/Tontonan
        </button>
    <?php endif; ?>
</div>

<script src="../../vendor/jquery/jquery.min.js"></script>
<script>
    let timeLeft = <?= $durasi ?>;
    let timerId;
    let isVideo = <?= ($tipe === 'video') ? 'true' : 'false' ?>;
    let isSelesai = <?= $is_selesai ? 'true' : 'false' ?>;

    function unlockEvaluation() {
        $('#btnEvaluasi').removeClass('disabled').removeAttr('disabled').removeClass('btn-light text-gray-500').addClass('btn-primary').css({'background': '#3b82f6', 'border': '1px solid #2563eb', 'color': '#ffffff'});
        $('#btnEvaluasi').html('<i class="fas fa-unlock mr-2"></i> Mulai Evaluasi Sekarang!');
        $('#timerDisplay').removeClass('timer-box').addClass('px-4 py-3 font-weight-bold shadow-sm').css({'background': '#d1fae5', 'color': '#065f46', 'border': '1px solid #a7f3d0', 'border-radius': '50px', 'font-size': '1rem'}).html('<i class="fas fa-check-circle mr-1"></i> Waktu Minimal Tercapai');
    }

    if (!isSelesai) {
        if (!isVideo) {
            timerId = setInterval(function() {
                timeLeft--;
                $('#timeVal').text(timeLeft);
                if (timeLeft <= 0) {
                    clearInterval(timerId);
                    unlockEvaluation();
                }
            }, 1000);
        } else {
            $('#timeVal').text("Menunggu Video Diputar...");
            var player;
            function onYouTubeIframeAPIReady() {
                player = new YT.Player('ytplayer', {
                    events: {
                        'onStateChange': onPlayerStateChange
                    }
                });
            }
            function onPlayerStateChange(event) {
                if (event.data == YT.PlayerState.PLAYING) {
                    $('#timeVal').text("Sedang Menonton...");
                    // Start tracking video time
                    if(!timerId) {
                        timerId = setInterval(function() {
                            let dur = player.getDuration();
                            let curr = player.getCurrentTime();
                            // unlock if watched 90%
                            if (dur > 0 && curr > (dur * 0.9)) {
                                clearInterval(timerId);
                                unlockEvaluation();
                            }
                        }, 1000);
                    }
                } else if (event.data == YT.PlayerState.ENDED) {
                    clearInterval(timerId);
                    unlockEvaluation();
                } else if (event.data == YT.PlayerState.PAUSED) {
                    $('#timeVal').text("Video Dijeda");
                }
            }
        }
    }

    function goEvaluasi() {
        window.location.href = "literasi_evaluasi.php?id=<?= $id ?>";
    }
</script>
</body>
</html>
