<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["no_induk"])) {
  header("location: ../../index.php?haruslogin");
  exit;
} else if ($_SESSION['hak_akses'] != 2) { ?>
  <script>window.location='../../404.html';</script>
<?php }

include "../../koneksi.php";
include "../../functions.php";
$lembaga = data_lembaga();

$id = mysqli_real_escape_string($conn, $_GET['id'] ?? '');  // id guru
$noinduk = mysqli_real_escape_string($conn, $_GET['no_induk'] ?? '');  // no induk guru
$sql = mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='".$noinduk."'");
$row = mysqli_fetch_array($sql);
$foto = $row['foto'] ?? '';
$namaguru = $row['nama_guru'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Jadwal Guru - <?= htmlspecialchars($namaguru ?: 'Guru'); ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    body { background-color: #f8f9fa; }
    .header-custom {
      background: linear-gradient(135deg, #0d6efd, #6610f2);
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 0 0 20px 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      position: sticky; top: 0; z-index: 1020;
    }
    .img-profile { width: 42px; height: 42px; object-fit: cover; border-radius: 50%; }
    .card-slim { border: none; border-radius: 14px; box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
    .badge-day { background: #e9f2ff; color: #0d6efd; border: 1px solid transparent; }
    .badge-year { background: #f3e8ff; color: #6f42c1; }
    .schedule-card { transition: transform .15s ease, box-shadow .15s ease; }
    .schedule-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,0.12); }
    /* Highlight jadwal sedang berlangsung */
    .schedule-card.ongoing { animation: pulseGlow 1.2s ease-in-out infinite; border-left-width: 8px !important; }
    @keyframes pulseGlow {
      0% { box-shadow: 0 0 0 0 rgba(220,53,69,0.25); }
      50% { box-shadow: 0 0 0 10px rgba(220,53,69,0.0); }
      100% { box-shadow: 0 0 0 0 rgba(220,53,69,0.25); }
    }
    .status-live { display:inline-flex; align-items:center; gap:.25rem; }
    .status-live .dot { width:8px; height:8px; border-radius:50%; background:#dc3545; display:inline-block; animation: blinkDot 1s step-start infinite; }
    @keyframes blinkDot { 50% { opacity: .3; } }
    .footer-nav { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid #ddd; display: flex; justify-content: space-around; padding: .5rem 0; z-index: 1050; }
    .footer-nav a { color: #6c757d; font-size: 0.9rem; display: flex; flex-direction: column; align-items: center; text-decoration: none; }
    .footer-nav a.active { color: #0d6efd; }

    /* Warna pembeda per hari */
    .day-Senin { border-left: 6px solid #0d6efd; }
    .day-Senin .badge-day { background:#e7f1ff; color:#0d6efd; border-color:#bcd7ff; }

    .day-Selasa { border-left: 6px solid #28a745; }
    .day-Selasa .badge-day { background:#e7f7ee; color:#28a745; border-color:#bde7cd; }

    .day-Rabu { border-left: 6px solid #17a2b8; }
    .day-Rabu .badge-day { background:#e6f6f8; color:#17a2b8; border-color:#b6e5ed; }

    .day-Kamis { border-left: 6px solid #fd7e14; }
    .day-Kamis .badge-day { background:#fff1e6; color:#fd7e14; border-color:#ffd5b3; }

    .day-Jumat { border-left: 6px solid #6f42c1; }
    .day-Jumat .badge-day { background:#f2eaff; color:#6f42c1; border-color:#deccff; }

    .day-Sabtu { border-left: 6px solid #d63384; }
    .day-Sabtu .badge-day { background:#ffe6f1; color:#d63384; border-color:#ffc2dc; }

    .day-Minggu { border-left: 6px solid #dc3545; }
    .day-Minggu .badge-day { background:#fdecef; color:#dc3545; border-color:#f7c7cd; }
  </style>
  </head>
<body>

<div class="container-fluid p-0">
  <div class="header-custom d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center">
      <img src="../../img/<?= htmlspecialchars($lembaga['logo']); ?>" alt="Logo" width="48" class="me-2 rounded-circle bg-white p-1">
      <div>
        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($lembaga['nmsekolah']); ?></h6>
        <small><?= htmlspecialchars($lembaga['alamat']); ?></small>
      </div>
    </div>
    <div class="d-flex align-items-center">
      <?php if(empty($foto)) { ?>
        <img src="../../img/no-photo.png" alt="<?= htmlspecialchars($namaguru); ?>" class="img-profile">
      <?php } else { ?>
        <img src="../../foto/<?= htmlspecialchars($foto); ?>" alt="<?= htmlspecialchars($namaguru); ?>" class="img-profile">
      <?php } ?>
      <span class="ms-2">Hai, <?= htmlspecialchars($_SESSION["nama_guru"] ?? $namaguru ?: 'Guru'); ?></span>
    </div>
  </div>

  <div class="container py-3">
    <!-- Guru Summary -->
    <div class="card card-slim mb-4">
      <div class="card-body d-flex align-items-center justify-content-between">
        <div>
          <h5 class="card-title mb-1">Detail Jadwal Mengajar</h5>
          <div class="text-muted">Nama: <strong><?= htmlspecialchars($namaguru ?: '-'); ?></strong> • NIP/No. Induk: <code><?= htmlspecialchars($noinduk); ?></code></div>
        </div>
        <div class="text-end">
          <?php
            $cntRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM tbl_mapel_ampu WHERE no_induk='".$noinduk."'");
            $cnt = mysqli_fetch_assoc($cntRes)['cnt'] ?? 0;
          ?>
          <span class="badge rounded-pill text-bg-primary me-2"><i class="bi bi-list-check me-1"></i> <?= (int)$cnt; ?> Jadwal</span>
          <a href="../guru/guru.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
      </div>
    </div>

    

    <!-- Schedule List -->
    <div class="row g-3">
      <?php
        $sql2 = mysqli_query($conn, "SELECT * FROM tbl_mapel_ampu WHERE no_induk='".$noinduk."' ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jam_mulai ASC");
        if (mysqli_num_rows($sql2) < 1) {
          echo '<div class="col-12"><div class="alert alert-warning">Belum ada jadwal tersimpan untuk guru ini.</div></div>';
        }
        while ($data = mysqli_fetch_array($sql2)) {
          $hari = $data['hari'] ?? '';
          $hariClass = 'day-'.preg_replace('/[^A-Za-z]/','', $hari);
      ?>
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card schedule-card card-slim h-100 <?= htmlspecialchars($hariClass); ?>" data-hari="<?= htmlspecialchars($hari); ?>" data-mulai="<?= htmlspecialchars($data['jam_mulai']); ?>" data-selesai="<?= htmlspecialchars($data['jam_selesai']); ?>">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge badge-day"><i class="bi bi-calendar-event me-1"></i><?= htmlspecialchars($data['hari']); ?></span>
              <div class="d-flex align-items-center gap-2">
                <span class="badge badge-year"><i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($data['thn_ajaran']); ?></span>
                <span class="badge text-bg-danger status-live d-none"><span class="dot"></span> Sedang berlangsung</span>
              </div>
            </div>
            <h6 class="mb-1"><?= htmlspecialchars($data['nama_mapel']); ?></h6>
            <div class="text-muted mb-3">
              <i class="bi bi-people-fill me-1"></i>Kelas <strong><?= htmlspecialchars($data['kelas']); ?></strong>
            </div>
            <div class="mb-3">
              <span class="badge text-bg-success"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($data['jam_mulai']); ?> - <?= htmlspecialchars($data['jam_selesai']); ?> WIB</span>
            </div>
            <div class="d-flex gap-2">
              <a class="btn btn-sm btn-outline-primary" href="?page=edit-mapel-guru&id_mapel=<?= htmlspecialchars($data['id_mapel']); ?>&id=<?= htmlspecialchars($id); ?>&no_induk=<?= htmlspecialchars($noinduk); ?>">
                <i class="bi bi-pencil-square"></i> Edit
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php } ?>
    </div>
  </div>
</div>

<!-- Modal Cetak Jurnal -->
<div class="modal fade" id="modalCetak" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Cetak Jurnal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <iframe src="" id="frameCetak" frameborder="0" style="width: 100%; height: 80vh;"></iframe>
      </div>
    </div>
  </div>
  </div>

<!-- Footer Navigation -->
<div class="footer-nav">
  <a href="../guru/guru.php">
    <i class="bi bi-house-door-fill"></i>
    <small>Home</small>
  </a>
  <a href="detail-jadwal.php?id=<?= htmlspecialchars($id); ?>&no_induk=<?= htmlspecialchars($noinduk); ?>" class="active">
    <i class="bi bi-calendar-check"></i>
    <small>Detail Jadwal</small>
  </a>
  <a href="../../logout.php" onclick="return confirm('Yakin mau logout?');">
    <i class="bi bi-box-arrow-right"></i>
    <small>Logout</small>
  </a>
  </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  $(function(){
    // Footer Cetak dihapus sesuai permintaan
  });
  </script>
<script>
  // Deteksi jadwal yang sedang berlangsung dan beri efek berkedip
  (function(){
    function indoDayName(d){
      const arr = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
      return arr[d.getDay()];
    }
    function parseHM(str){
      if(!str) return null;
      str = String(str);
      // Ambil pola HH:MM atau HH.MM atau "HH MM"
      let m = str.match(/(\d{1,2})\D(\d{1,2})/);
      if (m) {
        const h = parseInt(m[1],10), mi = parseInt(m[2],10);
        if (!isNaN(h) && !isNaN(mi)) return h*60 + mi;
      }
      // Fallback: ambil dua grup angka berurutan
      const m2 = str.match(/(\d{1,2})/g);
      if (m2 && m2.length>=2) {
        const h = parseInt(m2[0],10), mi = parseInt(m2[1],10);
        if (!isNaN(h) && !isNaN(mi)) return h*60 + mi;
      }
      return null;
    }
    function check(){
      const now = new Date();
      const dayNow = indoDayName(now);
      const minutesNow = now.getHours()*60 + now.getMinutes();
      document.querySelectorAll('.schedule-card').forEach(function(card){
        const hari = card.getAttribute('data-hari') || '';
        const mulai = parseHM(card.getAttribute('data-mulai'));
        const selesai = parseHM(card.getAttribute('data-selesai'));
        let berlangsung = false;
        if (hari === dayNow && mulai !== null && selesai !== null) {
          // Jika selesai < mulai, asumsikan tidak lintas hari; swap untuk safety
          let start = Math.min(mulai, selesai);
          let end = Math.max(mulai, selesai);
          berlangsung = (minutesNow >= start && minutesNow <= end);
        }
        const liveBadge = card.querySelector('.status-live');
        if (berlangsung) {
          card.classList.add('ongoing');
          if (liveBadge) liveBadge.classList.remove('d-none');
        } else {
          card.classList.remove('ongoing');
          if (liveBadge) liveBadge.classList.add('d-none');
        }
      });
    }
    document.addEventListener('DOMContentLoaded', check);
    // Cek berkala setiap 30 detik
    setInterval(check, 30000);
  })();
</script>
</body>
</html>

