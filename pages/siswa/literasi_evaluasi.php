<?php
// pages/siswa/literasi_evaluasi.php
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

// Check if progress exists
$qProg = mysqli_query($conn, "SELECT * FROM tbl_literasi_progress WHERE id_tugas=$id AND no_induk_siswa='$nisEsc'");
if (mysqli_num_rows($qProg) == 0) {
    echo "Anda harus memulai misi terlebih dahulu."; exit;
}
$prog = mysqli_fetch_assoc($qProg);
if ($prog['status'] === 'selesai') {
    echo "<script>alert('Anda sudah menyelesaikan misi ini.'); window.location='literasi.php';</script>"; exit;
}

$soals = [];
$qSoal = mysqli_query($conn, "SELECT * FROM tbl_literasi_soal WHERE id_tugas=$id ORDER BY id ASC");
while($s = mysqli_fetch_assoc($qSoal)){
    $soals[] = $s;
}

// Processing Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $benar = 0;
    $total = count($soals);
    
    foreach ($soals as $s) {
        $ans = $_POST['jawaban_'.$s['id']] ?? '';
        if ($ans === $s['jawaban_benar']) {
            $benar++;
        }
    }
    
    $nilai = ($total > 0) ? round(($benar / $total) * 100) : 100;
    
    // Get waktu_mulai
    $waktu_mulai_str = $prog['waktu_mulai'];
    if (empty($waktu_mulai_str)) {
        $waktu_mulai = time() - 10;
    } else {
        $waktu_mulai = strtotime($waktu_mulai_str);
    }
    
    $waktu_selesai = time();
    $durasi_detik = max(0, $waktu_selesai - $waktu_mulai);
    
    // Calculate skor_durasi based on durasi_minimal
    $durasi_minimal = (int)$tugas['durasi_minimal'];
    $skor_durasi = 0;
    
    if ($durasi_minimal > 0) {
        if ($durasi_detik >= $durasi_minimal) {
            // Read duration >= minimal required
            // base score is 80, linearly scales up to 100 if they read up to 1.5x of minimum duration
            $extra_time = $durasi_detik - $durasi_minimal;
            $scale_limit = $durasi_minimal * 0.5; // 50% more time
            if ($scale_limit > 0) {
                $skor_durasi = min(100, 80 + round(($extra_time / $scale_limit) * 20));
            } else {
                $skor_durasi = 100;
            }
        } else {
            // Read duration < minimal required (normally not possible due to frontend lock, but just in case)
            $skor_durasi = max(0, round(($durasi_detik / $durasi_minimal) * 80));
        }
    } else {
        // if no minimum duration is set, give 100 if they spent at least 15 seconds
        if ($durasi_detik >= 15) {
            $skor_durasi = 100;
        } else {
            $skor_durasi = max(0, round(($durasi_detik / 15) * 100));
        }
    }
    
    // Combined score: 40% reading duration score + 60% quiz score
    $skor_literasi = round((0.40 * $skor_durasi) + (0.60 * $nilai));
    
    // Save to database
    mysqli_query($conn, "UPDATE tbl_literasi_progress SET 
        status='selesai', 
        skor_evaluasi=$nilai, 
        waktu_selesai=CURRENT_TIMESTAMP,
        durasi_detik=$durasi_detik,
        skor_durasi=$skor_durasi,
        skor_literasi=$skor_literasi
        WHERE id={$prog['id']}");
    
    echo "<script>alert('Evaluasi Berhasil! Nilai Akhir Anda: $skor_literasi (Kuis: $nilai, Waktu Baca: $skor_durasi)'); window.location='literasi.php';</script>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Siswa - Evaluasi Literasi</title>
  <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
  <style>
      body { background-color: #f8fafc; }
      .bg-eval { background: linear-gradient(135deg, #16a34a, #047857); color: white; padding: 30px 0; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
      .soal-card { border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
      .opsi-label { display: block; padding: 15px 20px; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all 0.2s; font-weight: 600; }
      .opsi-label:hover { background: #f1f5f9; border-color: #cbd5e1; }
      input[type="radio"]:checked + .opsi-label { background: #eff6ff; border-color: #3b82f6; color: #1d4ed8; }
      input[type="radio"] { display: none; }
  </style>
</head>
<body>

<div class="bg-eval mb-5">
    <div class="container text-center">
        <h2 class="font-weight-bold mb-2"><i class="fas fa-clipboard-check"></i> Evaluasi Misi</h2>
        <p class="mb-0 text-white-50"><?= htmlspecialchars($tugas['judul']) ?></p>
    </div>
</div>

<div class="container pb-5" style="max-width: 800px;">
    <?php if (empty($soals)): ?>
        <div class="alert alert-info text-center">
            Misi ini tidak memiliki soal evaluasi. Tekan tombol di bawah untuk menyelesaikannya.
            <form method="post" class="mt-3">
                <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check"></i> Selesaikan Misi</button>
            </form>
        </div>
    <?php else: ?>
        <form method="post" onsubmit="return confirm('Yakin ingin mengumpulkan jawaban?');">
            <?php foreach($soals as $idx => $s): ?>
            <div class="card soal-card p-4">
                <h5 class="font-weight-bold mb-4 text-gray-800"><?= ($idx+1) ?>. <?= htmlspecialchars($s['pertanyaan']) ?></h5>
                
                <label class="mb-2">
                    <input type="radio" name="jawaban_<?= $s['id'] ?>" value="A" required>
                    <div class="opsi-label">A. <?= htmlspecialchars($s['opsi_a']) ?></div>
                </label>
                <label class="mb-2">
                    <input type="radio" name="jawaban_<?= $s['id'] ?>" value="B">
                    <div class="opsi-label">B. <?= htmlspecialchars($s['opsi_b']) ?></div>
                </label>
                <label class="mb-2">
                    <input type="radio" name="jawaban_<?= $s['id'] ?>" value="C">
                    <div class="opsi-label">C. <?= htmlspecialchars($s['opsi_c']) ?></div>
                </label>
                <label class="mb-0">
                    <input type="radio" name="jawaban_<?= $s['id'] ?>" value="D">
                    <div class="opsi-label">D. <?= htmlspecialchars($s['opsi_d']) ?></div>
                </label>
            </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn btn-primary btn-lg w-100 font-weight-bold shadow-lg py-3 mt-3"><i class="fas fa-paper-plane"></i> Kumpulkan Jawaban & Selesaikan Misi</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
