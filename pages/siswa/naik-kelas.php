<?php
session_start();
if (!isset($_SESSION["no_induk"])) {
  header("location: ../../index.php?haruslogin");
  exit;
} else if ($_SESSION['hak_akses'] != 3) {
  echo "<script>window.location = '404.html';</script>";
  exit;
}

include "../../koneksi.php";
include "../../functions.php";

$nis = $_SESSION['no_induk'];
$current_class = $_SESSION['kelas'];

// Hanya izinkan kelas X atau XI
if (!preg_match('/\b(X|XI|10|11)\b/i', $current_class)) {
    echo "<script>alert('Akses Ditolak. Halaman ini hanya untuk siswa kelas X atau XI.'); window.location='siswa.php';</script>";
    exit;
}

// Tentukan tingkat kelas selanjutnya
$next_level = '';
if (preg_match('/\b(X|10)\b/i', $current_class)) {
    $next_level = 'XI';
} else if (preg_match('/\b(XI|11)\b/i', $current_class)) {
    $next_level = 'XII';
}

$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['naik_kelas'])) {
    $new_class = mysqli_real_escape_string($conn, $_POST['kelas_baru']);
    
    // Validasi kelas baru apakah sesuai tingkatnya
    if (strpos($new_class, $next_level) === 0) {
        $update = mysqli_query($conn, "UPDATE tbl_siswa SET kelas = '$new_class' WHERE no_induk = '$nis' AND id_sekolah = $idSekolah");
        if ($update) {
            // Update historical tables and related tables to keep data intact in the new class
            @mysqli_query($conn, "UPDATE tbl_absen SET kelas = '$new_class' WHERE no_induk = '$nis' AND id_sekolah = $idSekolah");
            @mysqli_query($conn, "UPDATE tbl_izin_siswa SET kelas_siswa = '$new_class' WHERE no_induk_siswa = '$nis' AND id_sekolah = $idSekolah");
            @mysqli_query($conn, "UPDATE tbl_pelanggaran_siswa SET kelas = '$new_class' WHERE no_induk = '$nis' AND id_sekolah = $idSekolah");
            @mysqli_query($conn, "UPDATE tbl_siswa_eraport SET kelas = '$new_class' WHERE nis = '$nis' AND id_sekolah = $idSekolah");
            
            $_SESSION['kelas'] = $new_class;
            $current_class = $new_class;
            $message = "<div class='alert alert-success' style='border-radius:12px;'><i class='fas fa-check-circle'></i> Berhasil naik kelas ke <strong>".htmlspecialchars($new_class)."</strong>!</div>";
            echo "<script>setTimeout(function(){ window.location = 'siswa.php'; }, 2000);</script>";
        } else {
            $message = "<div class='alert alert-danger' style='border-radius:12px;'><i class='fas fa-times-circle'></i> Gagal menyimpan perubahan: " . mysqli_error($conn) . "</div>";
        }
    } else {
        $message = "<div class='alert alert-warning' style='border-radius:12px;'><i class='fas fa-exclamation-triangle'></i> Pilihan kelas tidak valid!</div>";
    }
}

// Ambil daftar kelas yang sesuai tingkat berikutnya
$available_classes = [];
if ($next_level) {
    $q_kelas = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas WHERE id_sekolah = $idSekolah AND (kelas LIKE '$next_level %' OR kelas = '$next_level') ORDER BY kelas");
    if ($q_kelas) {
        while($r = mysqli_fetch_assoc($q_kelas)) {
            $available_classes[] = $r['kelas'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Naik Kelas</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    body {
      background: #f8fafc;
      font-family: 'Inter', sans-serif;
      color: #1e293b;
    }
    .app-header {
      background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
      padding: 30px 20px 40px;
      color: #fff;
      border-radius: 0 0 30px 30px;
      margin-bottom: -30px;
      box-shadow: 0 10px 30px rgba(14,165,233,0.3);
    }
    .main-wrap {
      padding: 0 20px 40px;
      position: relative;
      z-index: 10;
    }
    .card-naik-kelas {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.05);
      border: none;
      padding: 25px;
    }
  </style>
</head>
<body>

  <div class="app-header">
    <a href="siswa.php" class="text-white mb-3 d-inline-block" style="font-size:1.2rem;"><i class="fas fa-arrow-left"></i> Kembali</a>
    <h2 style="font-weight:800; font-size:1.5rem; margin-bottom:5px;">Pemilihan Kelas Baru</h2>
    <p style="opacity:0.9; font-size:0.9rem; margin:0;">Selamat! Tentukan kelas baru Anda untuk tahun ajaran ini.</p>
  </div>

  <div class="main-wrap">
    <div class="card card-naik-kelas mt-4">
        <?= $message ?>
        
        <?php if (!empty($next_level) && preg_match('/^(X|XI|10|11)\b/i', $current_class)): ?>
        <form method="post">
            <div class="form-group mb-4">
                <label style="font-size:0.85rem; font-weight:700; color:#64748b; text-transform:uppercase;">Kelas Anda Saat Ini</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($current_class) ?>" readonly style="border-radius:12px; background:#f1f5f9; border-color:#e2e8f0; font-weight:600; color:#475569;">
            </div>
            
            <div class="form-group mb-4">
                <label style="font-size:0.85rem; font-weight:700; color:#64748b; text-transform:uppercase;">Pilih Kelas <?= $next_level ?> Baru Anda</label>
                <select name="kelas_baru" class="form-control form-control-lg" required style="border-radius:12px; border:2px solid #cbd5e1; font-weight:600; color:#0f172a; font-size:1rem;">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach($available_classes as $kls): ?>
                        <option value="<?= htmlspecialchars($kls) ?>"><?= htmlspecialchars($kls) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" name="naik_kelas" class="btn btn-primary btn-block btn-lg mt-2" style="border-radius:15px; font-weight:700; background:linear-gradient(135deg,#3b82f6,#2563eb); border:none; box-shadow:0 8px 20px rgba(37,99,235,0.3);" onclick="return confirm('Apakah Anda yakin memilih kelas ini? Perubahan akan langsung tersimpan.')">
                <i class="fas fa-save mr-2"></i> Simpan Pilihan Kelas
            </button>
        </form>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-check-circle text-success mb-3" style="font-size:3rem;"></i>
            <h5 class="font-weight-bold">Kelas Sudah Diperbarui</h5>
            <p class="text-muted">Anda sudah tercatat di kelas <?= htmlspecialchars($current_class) ?>.</p>
            <a href="siswa.php" class="btn btn-outline-primary mt-2" style="border-radius:12px; font-weight:600;">Kembali ke Dasbor</a>
        </div>
        <?php endif; ?>
    </div>
  </div>

</body>
</html>
