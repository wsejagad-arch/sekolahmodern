<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>Detail Jadwal Guru - <?= htmlspecialchars($namaguru ?: 'Guru'); ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    body { 
      background-color: #f8f9fa; 
      padding-bottom: 90px;
    }
    
    .img-profile { 
      width: 40px; 
      height: 40px; 
      object-fit: cover; 
      border-radius: 50%; 
    }
    
    .header-custom {
      background: linear-gradient(135deg, #0d6efd, #6610f2);
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 0 0 20px 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 1020;
    }
    
    .header-custom img {
      background: #fff;
      padding: 4px;
      border-radius: 50%;
      margin-right: 10px;
    }
    
    /* Responsive header for mobile */
    .first-name-mobile { display:none; font-weight:600; }
    .person-icon-mobile { display:none; }
    
    @media (max-width: 576px) {
      .header-custom small { display:none; }
      .header-custom .full-name-desktop { display:none !important; }
      .first-name-mobile { display:inline; }
      .person-icon-mobile { display:inline-flex; font-size:1.8rem; color:#fff; margin-right:6px; }
      .header-custom img.img-profile { display:none; }
      .header-custom { padding: .75rem 1rem; }
      .header-custom h6 { font-size:.95rem; }
    }
    
    @media (min-width: 577px){
      .first-name-mobile { display:none !important; }
      .person-icon-mobile { display:none !important; }
    }
    
    /* Modern card styling */
    .summary-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.08);
      padding: 1.5rem;
      margin-bottom: 2rem;
      border: none;
    }
    
    .schedule-card {
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
      border: 1px solid #e8eaed;
      background: #fff;
      height: 100%;
    }
    
    .schedule-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    }
    
    /* Status indicators */
    .status-live { 
      display: inline-flex; 
      align-items: center; 
      gap: 0.25rem;
      background: rgba(220,53,69,0.1);
      color: #dc3545;
      padding: 4px 8px;
      border-radius: 16px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    
    .status-live .dot { 
      width: 6px; 
      height: 6px; 
      border-radius: 50%; 
      background: #dc3545; 
      animation: blinkDot 1s infinite;
    }
    
    @keyframes blinkDot { 
      0%, 50% { opacity: 1; } 
      51%, 100% { opacity: 0.3; } 
    }
    
    /* Day color coding */
    .day-badge {
      background: rgba(13, 110, 253, 0.1);
      color: #0d6efd;
      border: 1px solid rgba(13, 110, 253, 0.2);
      padding: 4px 10px;
      border-radius: 16px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    
    .time-badge {
      background: rgba(40, 167, 69, 0.1);
      color: #28a745;
      border: 1px solid rgba(40, 167, 69, 0.2);
      padding: 4px 10px;
      border-radius: 16px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    
    .year-badge {
      background: rgba(111, 66, 193, 0.1);
      color: #6f42c1;
      border: 1px solid rgba(111, 66, 193, 0.2);
      padding: 4px 10px;
      border-radius: 16px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    
    /* Day specific styling */
    .day-Senin { border-left: 4px solid #0d6efd; }
    .day-Selasa { border-left: 4px solid #28a745; }
    .day-Rabu { border-left: 4px solid #17a2b8; }
    .day-Kamis { border-left: 4px solid #fd7e14; }
    .day-Jumat { border-left: 4px solid #6f42c1; }
    .day-Sabtu { border-left: 4px solid #d63384; }
    .day-Minggu { border-left: 4px solid #dc3545; }
    
    /* Ongoing schedule animation */
    .schedule-card.ongoing {
      animation: pulseGlow 2s ease-in-out infinite;
      border-left-width: 6px !important;
    }
    
    @keyframes pulseGlow {
      0% { box-shadow: 0 4px 12px rgba(0,0,0,0.05), 0 0 0 0 rgba(220,53,69,0.25); }
      50% { box-shadow: 0 6px 16px rgba(0,0,0,0.1), 0 0 0 10px rgba(220,53,69,0.0); }
      100% { box-shadow: 0 4px 12px rgba(0,0,0,0.05), 0 0 0 0 rgba(220,53,69,0.25); }
    }
    
    /* Footer Navigation - Modern Design */
    .footer-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #fff;
      box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-around;
      padding: 0.8rem 0;
      z-index: 1050;
      border-radius: 20px 20px 0 0;
      transition: all 0.3s ease;
      font-family: "Poppins", sans-serif;
    }

    .footer-nav a {
      color: #6c757d;
      font-size: 0.75rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      padding: 0.4rem 0.8rem;
      border-radius: 12px;
      transition: all 0.3s ease;
      position: relative;
      font-family: "Poppins", sans-serif;
    }

    .footer-nav a i {
      font-size: 1.2rem;
      margin-bottom: 0.3rem;
      transition: all 0.3s ease;
    }

    .footer-nav a.active {
      color: #0d6efd;
      background: rgba(13, 110, 253, 0.1);
      transform: translateY(-5px);
    }

    .footer-nav a.active i {
      transform: scale(1.2);
      color: #0d6efd;
    }

    .footer-nav a:not(.active):hover {
      color: #495057;
      background: rgba(108, 117, 125, 0.05);
    }

    /* Active indicator */
    .footer-nav a.active::before {
      content: '';
      position: absolute;
      top: -8px;
      width: 20px;
      height: 3px;
      background: #0d6efd;
      border-radius: 3px;
    }

    /* Interaction feedback animation */
    @keyframes footerBounce {
      0% { transform: translateY(0); }
      50% { transform: translateY(-8px); }
      100% { transform: translateY(-5px); }
    }

    .footer-nav a.active {
      animation: footerBounce 0.5s ease forwards;
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
      .footer-nav {
        padding: 0.7rem 0;
      }
      
      .footer-nav a {
        padding: 0.3rem 0.6rem;
        font-size: 0.7rem;
      }
      
      .footer-nav a i {
        font-size: 1.1rem;
      }
      
      .footer-nav a.active::before {
        top: -6px;
        width: 16px;
        height: 2px;
      }
    }
    
    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: #6c757d;
    }
    
    .empty-state i {
      font-size: 4rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }
    
    /* Floating Action Button */
    .fab {
      position: fixed;
      bottom: 100px;
      right: 20px;
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #28a745, #20c997);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
      text-decoration: none;
      box-shadow: 0 4px 20px rgba(40, 167, 69, 0.4);
      transition: all 0.3s ease;
      z-index: 1040;
    }
    
    .fab:hover {
      transform: scale(1.1) translateY(-2px);
      box-shadow: 0 6px 25px rgba(40, 167, 69, 0.5);
      color: white;
    }
    
    .fab i {
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
  </style>
</head>
<body>
<div class="container-fluid p-0">
  <div class="header-custom d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center">
      <img src="../../img/<?= htmlspecialchars($lembaga['logo']); ?>" alt="Logo" width="50" class="me-2">
      <div>
        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($lembaga['nmsekolah']); ?></h6>
        <small><?= htmlspecialchars($lembaga['alamat']); ?></small>
      </div>
    </div>
    <div class="d-flex align-items-center">
      <i class="bi bi-person-circle person-icon-mobile"></i>
      <?php if(empty($foto)) { ?>
        <img src="../../img/no-photo.png" alt="<?= htmlspecialchars($namaguru); ?>" class="img-profile">
      <?php } else { ?>
        <img src="../../foto/<?= htmlspecialchars($foto); ?>" alt="<?= htmlspecialchars($namaguru); ?>" class="img-profile">
      <?php } ?>
      <span class="ms-2 full-name-desktop">Hai, <?= htmlspecialchars($_SESSION["nama_guru"] ?? $namaguru ?: 'Guru'); ?></span>
      <span class="ms-2 first-name-mobile">Hai, <?= htmlspecialchars(explode(' ', $_SESSION["nama_guru"] ?? $namaguru ?: 'Guru')[0]); ?></span>
    </div>
  </div>

  <div class="container px-3" style="margin-top:-10px;">
    <!-- Summary Card -->
    <div class="summary-card">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
          <h5 class="mb-2 fw-bold text-primary">
            <i class="bi bi-calendar-week me-2"></i>Detail Jadwal Mengajar
          </h5>
          <div class="text-muted mb-2">
            <i class="bi bi-person-badge me-1"></i>
            <strong><?= htmlspecialchars($namaguru ?: '-'); ?></strong>
          </div>
          <div class="text-muted">
            <i class="bi bi-hash me-1"></i>
            NIP/No. Induk: <code class="bg-light px-2 py-1 rounded"><?= htmlspecialchars($noinduk); ?></code>
          </div>
        </div>
        <div class="text-end">
          <?php
            $cntRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM tbl_mapel_ampu WHERE no_induk='".$noinduk."'");
            $cnt = mysqli_fetch_assoc($cntRes)['cnt'] ?? 0;
          ?>
          <div class="mb-2">
            <span class="badge text-bg-primary px-3 py-2">
              <i class="bi bi-list-check me-1"></i><?= (int)$cnt; ?> Total Jadwal
            </span>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="tambah-jadwal.php" class="btn btn-success btn-sm">
              <i class="bi bi-plus-lg me-1"></i>Tambah Jadwal
            </a>
            <a href="guru.php" class="btn btn-outline-primary btn-sm">
              <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Schedule Grid -->
    <div class="row g-3">
      <?php
        $sql2 = mysqli_query($conn, "SELECT * FROM tbl_mapel_ampu WHERE no_induk='".$noinduk."' ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jam_mulai ASC");
        if (mysqli_num_rows($sql2) < 1) {
      ?>
          <div class="col-12">
            <div class="empty-state">
              <i class="bi bi-calendar-x"></i>
              <h5>Belum Ada Jadwal</h5>
              <p class="text-muted">Belum ada jadwal tersimpan untuk guru ini.</p>
            </div>
          </div>
      <?php
        } else {
          while ($data = mysqli_fetch_array($sql2)) {
            $hari = $data['hari'] ?? '';
            $hariClass = 'day-'.preg_replace('/[^A-Za-z]/','', $hari);
      ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card schedule-card <?= htmlspecialchars($hariClass); ?>" data-hari="<?= htmlspecialchars($hari); ?>" data-mulai="<?= htmlspecialchars($data['jam_mulai']); ?>" data-selesai="<?= htmlspecialchars($data['jam_selesai']); ?>">
            <div class="card-body">
              <!-- Header with badges -->
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex flex-wrap gap-2">
                  <span class="day-badge">
                    <i class="bi bi-calendar-event me-1"></i><?= htmlspecialchars($data['hari']); ?>
                  </span>
                  <span class="year-badge">
                    <i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($data['thn_ajaran']); ?>
                  </span>
                </div>
                <span class="status-live d-none">
                  <span class="dot"></span> Berlangsung
                </span>
              </div>
              
              <!-- Subject Info -->
              <h6 class="fw-bold text-primary mb-2"><?= htmlspecialchars($data['nama_mapel']); ?></h6>
              
              <div class="mb-3">
                <div class="d-flex align-items-center mb-2">
                  <i class="bi bi-people-fill text-muted me-2"></i>
                  <span class="text-muted">Kelas </span>
                  <strong class="ms-1"><?= htmlspecialchars($data['kelas']); ?></strong>
                </div>
                
                <div class="d-flex align-items-center">
                  <span class="time-badge">
                    <i class="bi bi-clock me-1"></i><?= htmlspecialchars($data['jam_mulai']); ?> - <?= htmlspecialchars($data['jam_selesai']); ?> WIB
                  </span>
                </div>
              </div>
              
              <!-- Actions -->
              <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-primary" href="edit-jadwal.php?id_mapel=<?= htmlspecialchars($data['id_mapel']); ?>&no_induk=<?= htmlspecialchars($noinduk); ?>">
                  <i class="bi bi-pencil-square me-1"></i>Edit
                </a>
                <button class="btn btn-sm btn-outline-danger" onclick="hapusJadwal(<?= htmlspecialchars($data['id_mapel']); ?>, '<?= htmlspecialchars($data['nama_mapel']); ?>', '<?= htmlspecialchars($data['hari']); ?>', '<?= htmlspecialchars($data['jam_mulai']); ?>')">
                  <i class="bi bi-trash"></i>
                </button>
                <button class="btn btn-sm btn-primary" onclick="window.location.href='guru.php'">
                  <i class="bi bi-journal-text"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php 
          }
        } 
      ?>
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

<!-- Floating Action Button -->
<a href="tambah-jadwal.php" class="fab" title="Tambah Jadwal Baru">
  <i class="bi bi-plus-lg"></i>
</a>

<!-- Footer Navigation -->
<div class="footer-nav">
  <a href="guru.php">
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
  // Fungsi hapus jadwal dengan konfirmasi
  function hapusJadwal(idMapel, namaMapel, hari, jamMulai) {
    // Cek apakah ada jurnal untuk jadwal ini
    let confirmMessage = `Yakin ingin menghapus jadwal:\n${namaMapel}\n${hari}, ${jamMulai}\n\n`;
    
    // Tambahkan peringatan jika ada jurnal (akan dicek di server)
    confirmMessage += `Jadwal akan dihapus permanen dari database.\n`;
    confirmMessage += `Jika ada jurnal tercatat, data jurnal akan tetap tersimpan.\n\n`;
    confirmMessage += `Tindakan ini tidak dapat dibatalkan!`;
    
    showConfirm(confirmMessage).then(function(ok){
      if (!ok) return;
      // Kirim request hapus via AJAX
      $.ajax({
        url: 'hapus-jadwal.php',
        method: 'POST',
        data: {
          id_mapel: idMapel,
          no_induk: '<?= htmlspecialchars($noinduk); ?>'
        },
        success: function(response) {
          const result = JSON.parse(response);
          if (result.success) {
            showToast(result.message || 'Berhasil menghapus jadwal', 'success');
            setTimeout(function(){ location.reload(); }, 800);
          } else {
            showToast('Gagal menghapus jadwal: ' + result.message, 'error');
          }
        },
        error: function() {
          showToast('Terjadi kesalahan saat menghapus jadwal!', 'error');
        }
      });
    });
  }
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
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>

