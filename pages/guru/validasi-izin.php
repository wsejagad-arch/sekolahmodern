<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('Location: ../../login.php?haruslogin');
    exit;
}
require_once __DIR__ . '/../../koneksi.php';
require_once __DIR__ . '/../../functions.php';

date_default_timezone_set('Asia/Jakarta');

$nipGuru = mysqli_real_escape_string($conn, $_SESSION['no_induk']);
$namaGuru = mysqli_real_escape_string($conn, $_SESSION['nama_guru'] ?? ($_SESSION['nama'] ?? 'Guru'));

// Cek apakah user adalah wali kelas dan ambil kelas-kelasnya
$kelas_wali = [];
$qWali = @mysqli_query($conn, "SELECT DISTINCT k.kelas FROM tbl_wali_kelas wk JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas WHERE wk.nip_wali = '$nipGuru' AND k.kelas <> ''");
while ($qWali && ($row = mysqli_fetch_assoc($qWali))) {
    $kelas_wali[] = $row['kelas'];
}
$qWali2 = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas WHERE nip_wali='$nipGuru' AND kelas <> ''");
while ($qWali2 && ($row = mysqli_fetch_assoc($qWali2))) {
    if (!in_array($row['kelas'], $kelas_wali)) $kelas_wali[] = $row['kelas'];
}
$is_wali_kelas = count($kelas_wali) > 0;

// Cek apakah user adalah guru BK
$is_guru_bk = false;
$qBk = mysqli_query($conn, "SELECT id_guru FROM tbl_guru WHERE no_induk = '$nipGuru' AND (jabatan LIKE '%BK%' OR is_guru_bk = 1) LIMIT 1");
if ($qBk && mysqli_num_rows($qBk) > 0) {
    $is_guru_bk = true;
}

if (!$is_wali_kelas && !$is_guru_bk) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location='guru_2026';</script>";
    exit;
}

// === Validasi Izin Logic ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id_izin = (int)$_POST['id_izin'];
    
    // Cek izin saat ini
    $qCek = mysqli_query($conn, "SELECT * FROM tbl_izin_siswa WHERE id_izin = $id_izin");
    $rCek = mysqli_fetch_assoc($qCek);
    
    if ($rCek) {
        if ($action === 'acc_wali' && $is_wali_kelas) {
            $status_baru = 'Menunggu Guru BK';
            if ($rCek['validasi_guru_bk'] === 'Disetujui') {
                $status_baru = ($rCek['kategori_pengajuan'] === 'Keluar Sekolah') ? 'Menunggu Satpam' : 'Disetujui Penuh';
            }
            mysqli_query($conn, "UPDATE tbl_izin_siswa SET validasi_wali_kelas = 'Disetujui', validator_wali_kelas = '$namaGuru', waktu_validasi_wali_kelas = NOW(), status_izin = '$status_baru' WHERE id_izin = $id_izin");
            $msg_validasi = "Izin berhasil disetujui sebagai Wali Kelas.";
        } elseif ($action === 'tolak_wali' && $is_wali_kelas) {
            mysqli_query($conn, "UPDATE tbl_izin_siswa SET validasi_wali_kelas = 'Ditolak', validator_wali_kelas = '$namaGuru', waktu_validasi_wali_kelas = NOW(), status_izin = 'Ditolak' WHERE id_izin = $id_izin");
            $msg_validasi = "Izin berhasil ditolak sebagai Wali Kelas.";
        } elseif ($action === 'acc_bk' && $is_guru_bk) {
            $status_baru = 'Menunggu Wali Kelas';
            if ($rCek['validasi_wali_kelas'] === 'Disetujui') {
                $status_baru = ($rCek['kategori_pengajuan'] === 'Keluar Sekolah') ? 'Menunggu Satpam' : 'Disetujui Penuh';
            }
            mysqli_query($conn, "UPDATE tbl_izin_siswa SET validasi_guru_bk = 'Disetujui', validator_guru_bk = '$namaGuru', waktu_validasi_guru_bk = NOW(), status_izin = '$status_baru' WHERE id_izin = $id_izin AND validasi_guru_bk IN ('Menunggu', 'Menunggu Validasi')");
            if (mysqli_affected_rows($conn) > 0) {
                $msg_validasi = "Izin berhasil disetujui sebagai Guru BK.";
            } else {
                $msg_validasi = "Izin ini sudah diproses oleh Guru BK lain.";
            }
        } elseif ($action === 'tolak_bk' && $is_guru_bk) {
            mysqli_query($conn, "UPDATE tbl_izin_siswa SET validasi_guru_bk = 'Ditolak', validator_guru_bk = '$namaGuru', waktu_validasi_guru_bk = NOW(), status_izin = 'Ditolak' WHERE id_izin = $id_izin AND validasi_guru_bk IN ('Menunggu', 'Menunggu Validasi')");
            if (mysqli_affected_rows($conn) > 0) {
                $msg_validasi = "Izin berhasil ditolak sebagai Guru BK.";
            } else {
                $msg_validasi = "Izin ini sudah diproses oleh Guru BK lain.";
            }
        }
        
        // Auto Absen jika sudah fully disetujui (selain keluar sekolah) dan aksi validasi berhasil
        if (isset($msg_validasi) && strpos($msg_validasi, 'berhasil') !== false) {
            $qCekAkhir = mysqli_query($conn, "SELECT * FROM tbl_izin_siswa WHERE id_izin = $id_izin");
            $rCekAkhir = mysqli_fetch_assoc($qCekAkhir);
            if ($rCekAkhir['validasi_wali_kelas'] === 'Disetujui' && $rCekAkhir['validasi_guru_bk'] === 'Disetujui') {
                $expectedStatus = ($rCekAkhir['kategori_pengajuan'] === 'Keluar Sekolah') ? 'Menunggu Satpam' : 'Disetujui Penuh';
                if ($rCekAkhir['status_izin'] !== $expectedStatus) {
                    mysqli_query($conn, "UPDATE tbl_izin_siswa SET status_izin = '$expectedStatus' WHERE id_izin = $id_izin");
                    $rCekAkhir['status_izin'] = $expectedStatus;
                }
            }

            if (in_array($rCekAkhir['status_izin'], ['Disetujui', 'Disetujui Penuh'], true)) {
                $nis = $rCekAkhir['no_induk_siswa'];
                $tgl = $rCekAkhir['tanggal_izin'];
                $kls = $rCekAkhir['kelas_siswa'];
                
                $kat = strtolower($rCekAkhir['kategori_pengajuan']);
                $kode_absen = 'I';
                if (strpos($kat, 'sakit') !== false) $kode_absen = 'S';
                elseif (strpos($kat, 'dispen') !== false) $kode_absen = 'D'; // D atau I
                
                // Cek apakah sudah ada absen di tbl_absen
                $cekAbsen = mysqli_query($conn, "SELECT id FROM tbl_absen WHERE no_induk = '$nis' AND tanggal = '$tgl' AND id_mapel IS NULL LIMIT 1");
                if (mysqli_num_rows($cekAbsen) > 0) {
                    mysqli_query($conn, "UPDATE tbl_absen SET status = '$kode_absen', sumber = 'Sistem Izin' WHERE no_induk = '$nis' AND tanggal = '$tgl' AND id_mapel IS NULL");
                } else {
                    mysqli_query($conn, "INSERT INTO tbl_absen (id_sekolah, tanggal, kelas, no_induk, status, sumber, created_at) VALUES (1, '$tgl', '$kls', '$nis', '$kode_absen', 'Sistem Izin', NOW())");
                }
            }
        }
    }
}

// Fetch pending izin for Wali Kelas
$list_izin_wali = [];
if ($is_wali_kelas) {
    $kelas_in = "'" . implode("','", array_map(function($k) use ($conn) { return mysqli_real_escape_string($conn, str_replace(' ', '', $k)); }, $kelas_wali)) . "'";
    $qWaliIzin = mysqli_query($conn, "SELECT i.*, s.nama_siswa FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk WHERE REPLACE(s.kelas, ' ', '') IN ($kelas_in) AND i.validasi_wali_kelas = 'Menunggu' ORDER BY i.waktu_pengajuan ASC");
    if ($qWaliIzin) {
        while ($row = mysqli_fetch_assoc($qWaliIzin)) {
            $list_izin_wali[] = $row;
        }
    }
}

// Fetch pending izin for Guru BK
$list_izin_bk = [];
if ($is_guru_bk) {
    $qBkIzin = mysqli_query($conn, "SELECT i.*, s.nama_siswa FROM tbl_izin_siswa i JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk WHERE i.validasi_guru_bk = 'Menunggu' ORDER BY i.waktu_pengajuan ASC");
    if ($qBkIzin) {
        while ($row = mysqli_fetch_assoc($qBkIzin)) {
            $list_izin_bk[] = $row;
        }
    }
}

// ── HISTORY FILTERS ──
$hist_bulan  = isset($_GET['bulan'])  && preg_match('/^\d{4}-\d{2}$/', $_GET['bulan'])  ? $_GET['bulan']  : date('Y-m');
$hist_tgl    = isset($_GET['tgl'])    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tgl']) ? $_GET['tgl'] : '';
$hist_status = isset($_GET['status']) && in_array($_GET['status'], ['semua','acc','ditolak']) ? $_GET['status'] : 'semua';

// Tentukan klausa WHERE status
$status_where = '';
if ($hist_status === 'acc') {
    $status_where = " AND (i.validasi_wali_kelas = 'Disetujui' OR i.validasi_guru_bk = 'Disetujui')";
} elseif ($hist_status === 'ditolak') {
    $status_where = " AND (i.validasi_wali_kelas = 'Ditolak' OR i.validasi_guru_bk = 'Ditolak' OR i.status_izin = 'Ditolak')";
} else {
    $status_where = " AND (i.validasi_wali_kelas <> 'Menunggu' OR i.validasi_guru_bk <> 'Menunggu' OR i.status_izin NOT IN ('Menunggu Validasi','Menunggu Wali Kelas','Menunggu Guru BK'))";
}

// Tentukan klausa tanggal
if (!empty($hist_tgl)) {
    $tgl_esc   = mysqli_real_escape_string($conn, $hist_tgl);
    $date_where = " AND i.tanggal_izin = '$tgl_esc'";
} else {
    $bln_esc   = mysqli_real_escape_string($conn, $hist_bulan);
    $date_where = " AND DATE_FORMAT(i.tanggal_izin, '%Y-%m') = '$bln_esc'";
}

// Fetch history untuk Wali Kelas
$history_wali = [];
if ($is_wali_kelas) {
    $kelas_h = "'" . implode("','", array_map(function($k) use ($conn) { return mysqli_real_escape_string($conn, str_replace(' ', '', $k)); }, $kelas_wali)) . "'";
    $qH = mysqli_query($conn, "SELECT i.*, s.nama_siswa
        FROM tbl_izin_siswa i
        JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk
        WHERE REPLACE(s.kelas, ' ', '') IN ($kelas_h)
        $status_where $date_where
        ORDER BY i.waktu_pengajuan DESC");
    if ($qH) while ($r = mysqli_fetch_assoc($qH)) $history_wali[] = $r;
}

// Fetch history untuk Guru BK (semua kelas)
$history_bk = [];
if ($is_guru_bk) {
    $qHB = mysqli_query($conn, "SELECT i.*, s.nama_siswa
        FROM tbl_izin_siswa i
        JOIN tbl_siswa s ON i.no_induk_siswa = s.no_induk
        WHERE 1=1
        $status_where $date_where
        ORDER BY i.waktu_pengajuan DESC");
    if ($qHB) while ($r = mysqli_fetch_assoc($qHB)) $history_bk[] = $r;
}

$title = 'Validasi Izin Siswa';
?>


<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> - SIMANIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --bg:#f1f5f9;--surf:#fff;--border:#e2e8f0;
            --text:#0f172a;--muted:#64748b;
            --blue:#2563eb;--blue-l:#eff6ff;
            --green:#16a34a;--green-l:#f0fdf4;
            --red:#dc2626;--red-l:#fef2f2;
            --r:18px;
        }
        body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
        .vi-wrap{max-width:640px;margin:0 auto;padding:20px 16px 80px}
        /* BACK */
        .vi-back{display:inline-flex;align-items:center;gap:8px;color:var(--muted);font-size:13px;font-weight:600;text-decoration:none;margin-bottom:20px;transition:color .2s}
        .vi-back:hover{color:var(--blue)}
        /* HEADER */
        .vi-hdr{text-align:center;margin-bottom:28px}
        .vi-hdr h1{font-size:clamp(20px,5vw,26px);font-weight:800;margin-bottom:4px}
        .vi-hdr p{color:var(--muted);font-size:14px}
        /* DIVIDER LABEL */
        .vi-sep{display:flex;align-items:center;gap:10px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:14px}
        .vi-sep::before,.vi-sep::after{content:'';flex:1;height:1px;background:var(--border)}
        /* EMPTY */
        .vi-empty{background:var(--surf);border:1.5px dashed var(--border);border-radius:var(--r);padding:36px 20px;text-align:center;color:var(--muted)}
        .vi-empty i{font-size:36px;opacity:.3;display:block;margin-bottom:12px}
        .vi-empty p{font-size:14px;font-weight:500}
        /* CARD */
        .vi-card{background:var(--surf);border:1px solid var(--border);border-radius:var(--r);margin-bottom:16px;overflow:hidden;box-shadow:0 2px 16px rgba(15,23,42,.07);transition:box-shadow .25s,transform .2s}
        .vi-card:hover{box-shadow:0 8px 32px rgba(15,23,42,.13);transform:translateY(-1px)}
        .vi-card.wali{border-top:3px solid var(--blue)}
        .vi-card.bk{border-top:3px solid var(--green)}
        /* HEAD */
        .vi-head{display:flex;align-items:center;gap:13px;padding:16px 18px 0}
        .vi-av{width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800;color:#fff;flex-shrink:0}
        .vi-av.wali{background:linear-gradient(135deg,#2563eb,#1e40af)}
        .vi-av.bk{background:linear-gradient(135deg,#16a34a,#15803d)}
        .vi-name{font-size:15px;font-weight:800;line-height:1.2}
        .vi-sub{font-size:12px;color:var(--muted);margin-top:2px}
        /* BADGE */
        .vi-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;white-space:nowrap;margin-left:auto;flex-shrink:0}
        .vi-badge.s{background:#fef9c3;color:#854d0e}
        .vi-badge.d{background:#ede9fe;color:#5b21b6}
        .vi-badge.k{background:#fce7f3;color:#9d174d}
        .vi-badge.e{background:#ffedd5;color:#9a3412}
        .vi-badge.x{background:#f1f5f9;color:#475569}
        /* INFO GRID */
        .vi-info{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:14px 18px}
        .vi-info-item.full{grid-column:1/-1}
        .vi-lbl{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px}
        .vi-val{font-size:13px;font-weight:600}
        /* DIVIDER */
        .vi-line{height:1px;background:var(--border);margin:0 18px}
        /* BUKTI */
        .vi-bukti{display:flex;align-items:center;justify-content:center;gap:8px;margin:12px 18px;padding:10px;border:1.5px solid var(--border);border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;background:transparent;width:calc(100% - 36px);transition:all .2s}
        .vi-bukti.wali{color:var(--blue)}
        .vi-bukti.wali:hover{background:var(--blue-l);border-color:var(--blue)}
        .vi-bukti.bk{color:var(--green)}
        .vi-bukti.bk:hover{background:var(--green-l);border-color:var(--green)}
        /* ACTIONS */
        .vi-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:14px 18px 18px}
        .vi-btn{display:flex;align-items:center;justify-content:center;gap:7px;padding:13px 12px;border-radius:14px;font-size:14px;font-weight:800;border:none;cursor:pointer;width:100%;transition:opacity .2s,transform .15s}
        .vi-btn:hover{opacity:.88;transform:scale(1.02)}
        .vi-btn:active{transform:scale(.97)}
        .vi-btn.acc{background:var(--green);color:#fff}
        .vi-btn.tolak{background:var(--red-l);color:var(--red);border:1.5px solid #fca5a5}
        /* WALI STATUS */
        .ws{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px}
        .ws.ok{background:#dcfce7;color:#166534}
        .ws.wait{background:#fef9c3;color:#854d0e}
        .ws.no{background:#fee2e2;color:#991b1b}
        /* TOAST */
        .vi-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#0f172a;color:#fff;padding:12px 22px;border-radius:14px;font-size:14px;font-weight:600;box-shadow:0 8px 32px rgba(0,0,0,.3);z-index:9000;white-space:nowrap;animation:tIn .3s cubic-bezier(.34,1.56,.64,1)}
        @keyframes tIn{from{opacity:0;transform:translateX(-50%) translateY(20px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
        /* LIGHTBOX */
        #lbOv{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.88);backdrop-filter:blur(8px);align-items:center;justify-content:center;padding:16px}
        #lbOv.on{display:flex}
        #lbBox{position:relative;max-width:min(92vw,640px);background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 30px 80px rgba(0,0,0,.6);animation:lbPop .28s cubic-bezier(.34,1.56,.64,1)}
        @keyframes lbPop{from{opacity:0;transform:scale(.82)}to{opacity:1;transform:scale(1)}}
        #lbImg{display:block;max-width:100%;max-height:78vh;object-fit:contain}
        #lbClose{position:absolute;top:10px;right:10px;background:rgba(0,0,0,.5);color:#fff;border:none;width:34px;height:34px;border-radius:50%;font-size:17px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s}
        #lbClose:hover{background:rgba(220,38,38,.85)}
        #lbLbl{padding:10px 16px;font-size:13px;font-weight:600;background:#f8fafc;color:#475569;border-top:1px solid #e2e8f0}
        @media(max-width:480px){.vi-info{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="vi-wrap">

<a href="guru_2026" class="vi-back"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>

<div class="vi-hdr">
    <h1>Validasi Pengajuan Izin</h1>
    <p>Periksa dan setujui atau tolak pengajuan izin siswa</p>
</div>

<?php if(isset($msg_validasi)): ?>
<div id="viToast" class="vi-toast"><i class="fas fa-check-circle" style="margin-right:8px"></i><?= htmlspecialchars($msg_validasi) ?></div>
<script>setTimeout(()=>{const t=document.getElementById('viToast');if(t){t.style.transition='opacity .4s';t.style.opacity='0';setTimeout(()=>t.remove(),400);}},3000);</script>
<?php endif; ?>

<?php
function viBadge(string $k):string{
    $l=strtolower($k);
    if(str_contains($l,'sakit'))   return '<span class="vi-badge s"><i class="fas fa-heartbeat fa-xs"></i>Sakit</span>';
    if(str_contains($l,'dispen'))  return '<span class="vi-badge d"><i class="fas fa-star fa-xs"></i>Dispen</span>';
    if(str_contains($l,'keluarga'))return '<span class="vi-badge k"><i class="fas fa-home fa-xs"></i>Kep. Keluarga</span>';
    if(str_contains($l,'keluar'))  return '<span class="vi-badge e"><i class="fas fa-door-open fa-xs"></i>Keluar Sekolah</span>';
    return '<span class="vi-badge x">'.htmlspecialchars(ucfirst($k)).'</span>';
}
function viIni(string $n):string{
    $p=explode(' ',trim($n));
    return strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):''));
}
function viConfirm(string $n):string{
    return htmlspecialchars(addslashes($n),ENT_QUOTES);
}
?>

<?php if($is_wali_kelas): ?>
<div class="vi-sep">Wali Kelas &mdash; <?= implode(', ',array_map('htmlspecialchars',$kelas_wali)) ?></div>
<?php if(empty($list_izin_wali)): ?>
<div class="vi-empty"><i class="fas fa-clipboard-check"></i><p>Tidak ada izin menunggu persetujuan Anda</p></div>
<?php else: ?>
<?php foreach($list_izin_wali as $izin): ?>
<div class="vi-card wali">
    <div class="vi-head">
        <div class="vi-av wali"><?= viIni($izin['nama_siswa']) ?></div>
        <div style="flex:1;min-width:0">
            <div class="vi-name"><?= htmlspecialchars($izin['nama_siswa']) ?></div>
            <div class="vi-sub"><i class="fas fa-users fa-xs"></i> <?= htmlspecialchars($izin['kelas_siswa']) ?></div>
        </div>
        <?= viBadge($izin['kategori_pengajuan']) ?>
    </div>
    <div class="vi-info">
        <div class="vi-info-item">
            <div class="vi-lbl">Tanggal Izin</div>
            <div class="vi-val"><i class="fas fa-calendar-day fa-xs" style="color:var(--blue)"></i> <?= date('d M Y',strtotime($izin['tanggal_izin'])) ?></div>
        </div>
        <div class="vi-info-item">
            <div class="vi-lbl">Diajukan</div>
            <div class="vi-val"><?= date('d M, H:i',strtotime($izin['waktu_pengajuan'])) ?></div>
        </div>
        <div class="vi-info-item full">
            <div class="vi-lbl">Alasan</div>
            <div class="vi-val" style="color:var(--muted);font-weight:500"><?= htmlspecialchars($izin['detail_izin']?:'-') ?></div>
        </div>
        <?php if(!empty($izin['opsi_kembali'])): ?>
        <div class="vi-info-item full">
            <div class="vi-lbl">Estimasi Kembali</div>
            <div class="vi-val"><?= htmlspecialchars($izin['opsi_kembali']) ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php if(!empty($izin['foto_selfie'])): ?>
    <div class="vi-line"></div>
    <button class="vi-bukti wali" onclick="openLB('../../uploads/izin/<?= htmlspecialchars(addslashes($izin['foto_selfie'])) ?>','<?= viConfirm($izin['nama_siswa']) ?>')"><i class="fas fa-image"></i> Lihat Bukti Dukung</button>
    <?php endif; ?>
    <div class="vi-line"></div>
    <div class="vi-actions">
        <form method="POST">
            <input type="hidden" name="id_izin" value="<?= $izin['id_izin'] ?>">
            <input type="hidden" name="action" value="acc_wali">
            <button class="vi-btn acc" onclick="return confirm('Setujui izin <?= viConfirm($izin['nama_siswa']) ?>?')"><i class="fas fa-check-circle"></i> Setujui</button>
        </form>
        <form method="POST">
            <input type="hidden" name="id_izin" value="<?= $izin['id_izin'] ?>">
            <input type="hidden" name="action" value="tolak_wali">
            <button class="vi-btn tolak" onclick="return confirm('Tolak izin <?= viConfirm($izin['nama_siswa']) ?>?')"><i class="fas fa-times-circle"></i> Tolak</button>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>

<?php if($is_guru_bk): ?>
<?php if($is_wali_kelas): ?><div style="height:10px"></div><?php endif; ?>
<div class="vi-sep">Guru BK</div>
<?php if(empty($list_izin_bk)): ?>
<div class="vi-empty"><i class="fas fa-clipboard-check"></i><p>Tidak ada izin menunggu persetujuan BK</p></div>
<?php else: ?>
<?php foreach($list_izin_bk as $izin): ?>
<div class="vi-card bk">
    <div class="vi-head">
        <div class="vi-av bk"><?= viIni($izin['nama_siswa']) ?></div>
        <div style="flex:1;min-width:0">
            <div class="vi-name"><?= htmlspecialchars($izin['nama_siswa']) ?></div>
            <div class="vi-sub"><i class="fas fa-users fa-xs"></i> <?= htmlspecialchars($izin['kelas_siswa']) ?></div>
        </div>
        <?= viBadge($izin['kategori_pengajuan']) ?>
    </div>
    <div class="vi-info">
        <div class="vi-info-item">
            <div class="vi-lbl">Tanggal Izin</div>
            <div class="vi-val"><i class="fas fa-calendar-day fa-xs" style="color:var(--green)"></i> <?= date('d M Y',strtotime($izin['tanggal_izin'])) ?></div>
        </div>
        <div class="vi-info-item">
            <div class="vi-lbl">ACC Wali Kelas</div>
            <div class="vi-val">
                <?php $ws=$izin['validasi_wali_kelas']??'Menunggu';$wc=strtolower($ws)==='disetujui'?'ok':(strtolower($ws)==='ditolak'?'no':'wait'); ?>
                <span class="ws <?= $wc ?>"><?= htmlspecialchars($ws) ?></span>
                <?php if(!empty($izin['validator_wali_kelas'])): ?>
                <div style="font-size:10px;color:var(--muted);margin-top:2px"><i class="fas fa-check-circle fa-xs"></i> <?= htmlspecialchars($izin['validator_wali_kelas']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="vi-info-item">
            <div class="vi-lbl">ACC Guru BK</div>
            <div class="vi-val">
                <?php $bs=$izin['validasi_guru_bk']??'Menunggu';$bc=strtolower($bs)==='disetujui'?'ok':(strtolower($bs)==='ditolak'?'no':'wait'); ?>
                <span class="ws <?= $bc ?>"><?= htmlspecialchars($bs) ?></span>
                <?php if(!empty($izin['validator_guru_bk'])): ?>
                <div style="font-size:10px;color:var(--muted);margin-top:2px"><i class="fas fa-check-circle fa-xs"></i> <?= htmlspecialchars($izin['validator_guru_bk']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="vi-info-item full">
            <div class="vi-lbl">Alasan</div>
            <div class="vi-val" style="color:var(--muted);font-weight:500"><?= htmlspecialchars($izin['detail_izin']?:'-') ?></div>
        </div>
    </div>
    <?php if(!empty($izin['foto_selfie'])): ?>
    <div class="vi-line"></div>
    <button class="vi-bukti bk" onclick="openLB('../../uploads/izin/<?= htmlspecialchars(addslashes($izin['foto_selfie'])) ?>','<?= viConfirm($izin['nama_siswa']) ?>')"><i class="fas fa-image"></i> Lihat Bukti Dukung</button>
    <?php endif; ?>
    <div class="vi-line"></div>
    <div class="vi-actions">
        <form method="POST">
            <input type="hidden" name="id_izin" value="<?= $izin['id_izin'] ?>">
            <input type="hidden" name="action" value="acc_bk">
            <button class="vi-btn acc" onclick="return confirm('Setujui izin <?= viConfirm($izin['nama_siswa']) ?>?')"><i class="fas fa-check-circle"></i> Setujui</button>
        </form>
        <form method="POST">
            <input type="hidden" name="id_izin" value="<?= $izin['id_izin'] ?>">
            <input type="hidden" name="action" value="tolak_bk">
            <button class="vi-btn tolak" onclick="return confirm('Tolak izin <?= viConfirm($izin['nama_siswa']) ?>?')"><i class="fas fa-times-circle"></i> Tolak</button>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>


<?php if ($is_wali_kelas || $is_guru_bk): ?>
<!-- ══════════════════════════════════════ HISTORY SECTION ══════════════════════════════════════ -->
<style>
/* ── HISTORY ── */
.hist-wrap{margin-top:32px}
.hist-title{font-size:17px;font-weight:800;color:var(--text);margin-bottom:16px;display:flex;align-items:center;gap:8px}
.hist-title i{color:var(--blue)}
/* Filter bar */
.hist-filter{background:var(--surf);border:1px solid var(--border);border-radius:14px;padding:14px 16px;margin-bottom:16px;display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end}
.hist-filter-group{display:flex;flex-direction:column;gap:4px;flex:1;min-width:130px}
.hist-filter-group label{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em}
.hist-filter-group input,.hist-filter-group select{border:1.5px solid var(--border);border-radius:10px;padding:8px 12px;font-size:13px;font-weight:500;color:var(--text);background:#fff;outline:none;transition:border-color .2s;font-family:inherit}
.hist-filter-group input:focus,.hist-filter-group select:focus{border-color:var(--blue)}
.hist-filter-btn{display:flex;align-items:center;justify-content:center;gap:7px;padding:9px 18px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .2s;white-space:nowrap;align-self:flex-end}
.hist-filter-btn:hover{opacity:.85}
.hist-filter-reset{background:transparent;color:var(--muted);border:1.5px solid var(--border);padding:9px 14px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;align-self:flex-end;transition:all .2s}
.hist-filter-reset:hover{border-color:var(--red);color:var(--red)}
/* Filter pills */
.hist-pills{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap}
.hist-pill{padding:6px 16px;border-radius:999px;font-size:12px;font-weight:700;border:1.5px solid var(--border);cursor:pointer;text-decoration:none;color:var(--muted);background:#fff;transition:all .2s}
.hist-pill.active-semua{background:var(--blue);color:#fff;border-color:var(--blue)}
.hist-pill.active-acc{background:var(--green);color:#fff;border-color:var(--green)}
.hist-pill.active-ditolak{background:var(--red);color:#fff;border-color:var(--red)}
.hist-pill:not([class*="active"]):hover{border-color:var(--blue);color:var(--blue)}
/* Summary count bar */
.hist-count{font-size:12px;color:var(--muted);font-weight:500;margin-bottom:12px;padding-left:2px}
/* Row item */
.hist-item{background:var(--surf);border:1px solid var(--border);border-radius:14px;padding:14px 16px;margin-bottom:10px;display:flex;align-items:center;gap:12px;transition:box-shadow .2s,transform .2s}
.hist-item:hover{box-shadow:0 4px 20px rgba(15,23,42,.09);transform:translateY(-1px)}
/* Left icon */
.hist-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;color:#fff;flex-shrink:0}
.hist-icon.acc{background:linear-gradient(135deg,#16a34a,#15803d)}
.hist-icon.tolak{background:linear-gradient(135deg,#dc2626,#b91c1c)}
.hist-icon.sebagian{background:linear-gradient(135deg,#d97706,#b45309)}
.hist-icon.proses{background:linear-gradient(135deg,#2563eb,#1d4ed8)}
/* Body */
.hist-body{flex:1;min-width:0}
.hist-nama{font-size:14px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.hist-meta{font-size:11.5px;color:var(--muted);margin-top:2px;display:flex;flex-wrap:wrap;gap:6px}
.hist-meta span{display:inline-flex;align-items:center;gap:3px}
/* Right side */
.hist-right{text-align:right;flex-shrink:0}
.hist-status{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;display:inline-block}
.hs-acc{background:#dcfce7;color:#166534}
.hs-tolak{background:#fee2e2;color:#991b1b}
.hs-proses{background:#dbeafe;color:#1e40af}
.hs-sebagian{background:#fef9c3;color:#854d0e}
.hist-date{font-size:11px;color:var(--muted);margin-top:4px;font-weight:500}
/* No data */
.hist-nodata{text-align:center;padding:32px;color:var(--muted)}
.hist-nodata i{font-size:32px;opacity:.3;display:block;margin-bottom:10px}
</style>

<div class="hist-wrap">
    <div class="hist-title"><i class="fas fa-history"></i> Riwayat Validasi Izin</div>

    <!-- Filter Form -->
    <form method="GET" id="histFilterForm">
        <div class="hist-filter">
            <div class="hist-filter-group">
                <label for="fBulan"><i class="fas fa-calendar-alt fa-xs"></i> Bulan</label>
                <input type="month" id="fBulan" name="bulan" value="<?= htmlspecialchars($hist_bulan) ?>">
            </div>
            <div class="hist-filter-group">
                <label for="fTgl"><i class="fas fa-calendar-day fa-xs"></i> Tanggal Spesifik</label>
                <input type="date" id="fTgl" name="tgl" value="<?= htmlspecialchars($hist_tgl) ?>" placeholder="Opsional">
            </div>
            <input type="hidden" name="status" id="fStatus" value="<?= htmlspecialchars($hist_status) ?>">
            <button type="submit" class="hist-filter-btn"><i class="fas fa-search fa-xs"></i> Terapkan</button>
            <a href="validasi-izin" class="hist-filter-reset"><i class="fas fa-undo fa-xs"></i> Reset</a>
        </div>
    </form>

    <!-- Status Pills -->
    <div class="hist-pills">
        <?php
        function histPillUrl(string $s, string $bulan, string $tgl): string {
            $params = ['status' => $s, 'bulan' => $bulan];
            if ($tgl) $params['tgl'] = $tgl;
            return 'validasi-izin?' . http_build_query($params);
        }
        ?>
        <a href="<?= histPillUrl('semua', $hist_bulan, $hist_tgl) ?>" class="hist-pill <?= $hist_status==='semua' ? 'active-semua' : '' ?>">
            <i class="fas fa-list fa-xs"></i> Semua
        </a>
        <a href="<?= histPillUrl('acc', $hist_bulan, $hist_tgl) ?>" class="hist-pill <?= $hist_status==='acc' ? 'active-acc' : '' ?>">
            <i class="fas fa-check fa-xs"></i> Disetujui
        </a>
        <a href="<?= histPillUrl('ditolak', $hist_bulan, $hist_tgl) ?>" class="hist-pill <?= $hist_status==='ditolak' ? 'active-ditolak' : '' ?>">
            <i class="fas fa-times fa-xs"></i> Ditolak
        </a>
    </div>

    <?php
    // Helper: tentukan icon class dan label status akhir
    function histStatusInfo(array $iz): array {
        $wk = $iz['validasi_wali_kelas'] ?? 'Menunggu';
        $bk = $iz['validasi_guru_bk']   ?? 'Menunggu';
        $st = $iz['status_izin']        ?? '';

        if ($wk === 'Ditolak' || $bk === 'Ditolak' || $st === 'Ditolak') {
            return ['tolak', 'hs-tolak', 'Ditolak'];
        }
        if ($wk === 'Disetujui' && $bk === 'Disetujui') {
            return ['acc', 'hs-acc', 'Disetujui'];
        }
        if ($wk === 'Disetujui' && $bk === 'Menunggu') {
            return ['sebagian', 'hs-sebagian', 'Menunggu BK'];
        }
        if ($bk === 'Disetujui' && $wk === 'Menunggu') {
            return ['sebagian', 'hs-sebagian', 'Menunggu Wali'];
        }
        return ['proses', 'hs-proses', ucwords(str_replace('_',' ', $st ?: 'Diproses'))];
    }
    function histKategoriIcon(string $k): string {
        $l = strtolower($k);
        if (str_contains($l,'sakit'))    return '<i class="fas fa-heartbeat" style="color:#854d0e"></i>';
        if (str_contains($l,'dispen'))   return '<i class="fas fa-star" style="color:#5b21b6"></i>';
        if (str_contains($l,'keluarga'))return '<i class="fas fa-home" style="color:#9d174d"></i>';
        if (str_contains($l,'keluar'))   return '<i class="fas fa-door-open" style="color:#9a3412"></i>';
        return '<i class="fas fa-file-alt" style="color:#475569"></i>';
    }

    // Gabungkan list history (wali + bk, deduplikasi berdasar id_izin)
    $allHistory = [];
    $seenIds    = [];
    foreach (array_merge($history_wali, $history_bk) as $r) {
        if (!in_array($r['id_izin'], $seenIds)) {
            $allHistory[] = $r;
            $seenIds[]    = $r['id_izin'];
        }
    }
    // Sort by tanggal_izin DESC
    usort($allHistory, fn($a, $b) => strtotime($b['waktu_pengajuan']) - strtotime($a['waktu_pengajuan']));

    $totalHist = count($allHistory);
    $label_bln = !empty($hist_tgl)
        ? date('d M Y', strtotime($hist_tgl))
        : date('M Y', strtotime($hist_bulan . '-01'));
    ?>

    <div class="hist-count">
        <?php if ($totalHist > 0): ?>
        Menampilkan <strong><?= $totalHist ?></strong> data untuk <strong><?= htmlspecialchars($label_bln) ?></strong>
        <?php if ($hist_status !== 'semua'): ?>
        &mdash; filter: <strong><?= $hist_status === 'acc' ? 'Disetujui' : 'Ditolak' ?></strong>
        <?php endif; ?>
        <?php else: ?>
        Tidak ada data untuk periode ini.
        <?php endif; ?>
    </div>

    <?php if (empty($allHistory)): ?>
    <div class="hist-nodata">
        <i class="fas fa-inbox"></i>
        <p>Belum ada riwayat izin pada periode yang dipilih</p>
    </div>
    <?php else: ?>
    <?php foreach($allHistory as $hz):
        [$iconCls, $statusCls, $statusLabel] = histStatusInfo($hz);
    ?>
    <div class="hist-item">
        <div class="hist-icon <?= $iconCls ?>">
            <?php if($iconCls==='acc'): ?><i class="fas fa-check"></i>
            <?php elseif($iconCls==='tolak'): ?><i class="fas fa-times"></i>
            <?php elseif($iconCls==='sebagian'): ?><i class="fas fa-hourglass-half"></i>
            <?php else: ?><i class="fas fa-circle-notch"></i><?php endif; ?>
        </div>
        <div class="hist-body">
            <div class="hist-nama"><?= htmlspecialchars($hz['nama_siswa']) ?></div>
            <div class="hist-meta">
                <span><i class="fas fa-users fa-xs"></i> <?= htmlspecialchars($hz['kelas_siswa']) ?></span>
                <span><?= histKategoriIcon($hz['kategori_pengajuan']) ?> <?= htmlspecialchars($hz['kategori_pengajuan']) ?></span>
                <span><i class="fas fa-calendar fa-xs"></i> <?= date('d M Y', strtotime($hz['tanggal_izin'])) ?></span>
            </div>
            <?php if(!empty($hz['detail_izin'])): ?>
            <div style="font-size:11.5px;color:var(--muted);margin-top:4px;font-style:italic"><?= htmlspecialchars(mb_strimwidth($hz['detail_izin'], 0, 80, '…')) ?></div>
            <?php endif; ?>
            <?php if(!empty($hz['validator_wali_kelas']) && $hz['validasi_wali_kelas'] !== 'Menunggu'): ?>
            <div style="font-size:10.5px;color:var(--muted);margin-top:3px"><i class="fas fa-user-tie fa-xs"></i> Wali: <?= htmlspecialchars($hz['validator_wali_kelas']) ?> <?= $hz['validasi_wali_kelas'] === 'Ditolak' ? '(Menolak)' : '' ?></div>
            <?php endif; ?>
            <?php if(!empty($hz['validator_guru_bk']) && $hz['validasi_guru_bk'] !== 'Menunggu'): ?>
            <div style="font-size:10.5px;color:var(--muted);margin-top:3px"><i class="fas fa-user-shield fa-xs"></i> BK: <?= htmlspecialchars($hz['validator_guru_bk']) ?> <?= $hz['validasi_guru_bk'] === 'Ditolak' ? '(Menolak)' : '' ?></div>
            <?php endif; ?>
        </div>
        <div class="hist-right">
            <span class="hist-status <?= $statusCls ?>"><?= htmlspecialchars($statusLabel) ?></span>
            <div class="hist-date"><?= date('H:i', strtotime($hz['waktu_pengajuan'])) ?></div>
            <?php if(!empty($hz['foto_selfie'])): ?>
            <button onclick="openLB('../../uploads/izin/<?= htmlspecialchars(addslashes($hz['foto_selfie'])) ?>','<?= htmlspecialchars(addslashes($hz['nama_siswa'])) ?>')"
                style="margin-top:5px;background:none;border:none;cursor:pointer;font-size:11px;color:var(--blue);font-weight:700;padding:0">
                <i class="fas fa-image fa-xs"></i> Bukti
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div><!-- /.hist-wrap -->
<?php endif; ?>

</div><!-- /.vi-wrap -->

<!-- LIGHTBOX -->
<div id="lbOv" onclick="closeLB(event)">
    <div id="lbBox">
        <button id="lbClose" onclick="closeLB()" title="Tutup">&#x2715;</button>
        <img id="lbImg" src="" alt="Bukti">
        <div id="lbLbl">Bukti Dukung</div>
    </div>
</div>

<script>
function openLB(s,n){
    document.getElementById('lbImg').src=s;
    document.getElementById('lbLbl').textContent='Bukti Dukung \u2014 '+n;
    document.getElementById('lbOv').classList.add('on');
    document.body.style.overflow='hidden';
}
function closeLB(e){
    if(e&&e.target!==document.getElementById('lbOv')&&e.target!==document.getElementById('lbClose'))return;
    document.getElementById('lbOv').classList.remove('on');
    document.getElementById('lbImg').src='';
    document.body.style.overflow='';
}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeLB({target:document.getElementById('lbOv')});});
</script>

<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
