<?php
// Authentication check - same pattern as guru.php
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION["no_induk"])) {
    header('Location: index.php?haruslogin');
    exit;
} else if($_SESSION['hak_akses'] != 2) {
    header('Location: 403.php');
    exit;
}

// Include required files
if (!isset($conn)) {
    require_once __DIR__ . '/koneksi.php';
}
if (!function_exists('data_lembaga')) {
    require_once __DIR__ . '/functions.php';
}

// Get current teacher's no_induk
$no_induk_guru = $_SESSION['no_induk'] ?? '';

// Ensure table exists with updated schema (same as admin version)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pengumuman (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  isi TEXT NOT NULL,
  penting TINYINT(1) DEFAULT 0,
  mulai DATE NOT NULL,
  selesai DATE NOT NULL,
  target_scope ENUM('SEMUA','KELAS','TINGKAT','GURU') DEFAULT 'SEMUA',
  target_value VARCHAR(100) DEFAULT NULL,
  lampiran VARCHAR(255) DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARSET=utf8mb4");

// Check if new columns exist, if not add them (for existing tables)
$result = mysqli_query($conn, "SHOW COLUMNS FROM tbl_pengumuman LIKE 'target_scope'");
if (mysqli_num_rows($result) == 0) {
    mysqli_query($conn, "ALTER TABLE tbl_pengumuman 
        ADD COLUMN target_scope ENUM('SEMUA','KELAS','TINGKAT','GURU') DEFAULT 'SEMUA' AFTER selesai,
        ADD COLUMN target_value VARCHAR(100) DEFAULT NULL AFTER target_scope,
        ADD COLUMN lampiran VARCHAR(255) DEFAULT NULL AFTER target_value");
}

// Get teacher's assigned classes for filtering
$kelasOptions = [];
$qKelas = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk = '$no_induk_guru' ORDER BY kelas");
while ($row = mysqli_fetch_assoc($qKelas)) {
    $kelasOptions[] = $row['kelas'];
}

// File upload handling helper
function handle_upload($field){
  if(empty($_FILES[$field]['name'])) return null;
  $fname = $_FILES[$field]['name'];
  $tmp = $_FILES[$field]['tmp_name'];
  $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
  $allowed = ['pdf'];
  if(!in_array($ext,$allowed)) return null;
  $new = 'GURU_'.date('Ymd_His').'_' . substr(md5($fname.microtime()),0,6) . '.' . $ext;
  $destDir = __DIR__.'/materi'; if(!is_dir($destDir)) @mkdir($destDir,0775,true);
  if(move_uploaded_file($tmp, $destDir.'/'.$new)) return $new;
  return null;
}

$msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $mode = $_POST['mode'] ?? 'add';
  $judul = trim($_POST['judul'] ?? '');
  $isi = trim($_POST['isi'] ?? '');
  $penting = isset($_POST['penting']) ? 1 : 0;
  $mulai = $_POST['mulai'] ?? '';
  $selesai = $_POST['selesai'] ?? '';
  $target_scope = $_POST['target_scope'] ?? 'KELAS';
  $target_value = trim($_POST['target_value'] ?? '');
  
  // Validate that guru only targets their own classes
  if($target_scope === 'KELAS' && !in_array($target_value, $kelasOptions)){
      $err = 'Anda hanya dapat mengirim pengumuman ke kelas yang Anda ampu.';
  }
  
  if($target_scope==='SEMUA') $target_value=null; 
  if($target_value==='') $target_value=null;
  
  if($judul==='' || $isi==='' || !$mulai || !$selesai){ 
      $err='Lengkapi semua field'; 
  } else if(strlen($judul) > 150){ 
      $err='Judul terlalu panjang'; 
  } else if(!$err) {
    $judulEsc = mysqli_real_escape_string($conn,$judul);
    $isiEsc = mysqli_real_escape_string($conn,$isi);
    $mulaiEsc = mysqli_real_escape_string($conn,$mulai);
    $selesaiEsc = mysqli_real_escape_string($conn,$selesai);
    $tScopeEsc = mysqli_real_escape_string($conn,$target_scope);
    $tValueEsc = $target_value? "'".mysqli_real_escape_string($conn,$target_value)."'" : 'NULL';
    $createdByEsc = mysqli_real_escape_string($conn,$no_induk_guru);
    $lampiranFile = handle_upload('lampiran');
    
    if($mode==='edit') {
      $idEdit = (int)($_POST['id_edit'] ?? 0);
      if($idEdit>0){
        // Check if this announcement belongs to current teacher
        $checkQ = mysqli_query($conn, "SELECT created_by FROM tbl_pengumuman WHERE id=$idEdit LIMIT 1");
        if($checkQ && mysqli_num_rows($checkQ)==1){
            $checkR = mysqli_fetch_assoc($checkQ);
            if($checkR['created_by'] === $no_induk_guru){
                $setLamp = $lampiranFile? ", lampiran='".mysqli_real_escape_string($conn,$lampiranFile)."'" : '';
                $qUpd = "UPDATE tbl_pengumuman SET judul='$judulEsc', isi='$isiEsc', penting=$penting, mulai='$mulaiEsc', selesai='$selesaiEsc', target_scope='$tScopeEsc', target_value=$tValueEsc $setLamp WHERE id=$idEdit LIMIT 1";
                if(mysqli_query($conn,$qUpd)) $msg='Pengumuman diperbarui'; else $err='Gagal update: '.mysqli_error($conn);
            } else {
                $err='Anda hanya dapat mengedit pengumuman yang Anda buat.';
            }
        } else {
            $err='Pengumuman tidak ditemukan.';
        }
      }
    } else {
      $lampiranEsc = $lampiranFile? "'".mysqli_real_escape_string($conn,$lampiranFile)."'" : 'NULL';
      $qIns = "INSERT INTO tbl_pengumuman(judul,isi,penting,mulai,selesai,target_scope,target_value,lampiran,created_by) VALUES('$judulEsc','$isiEsc',$penting,'$mulaiEsc','$selesaiEsc','$tScopeEsc',$tValueEsc,$lampiranEsc,'$createdByEsc')";
      if(mysqli_query($conn,$qIns)) $msg='Pengumuman tersimpan'; else $err='Gagal simpan: '.mysqli_error($conn);
    }
  }
}

// Delete
if(isset($_GET['hapus'])){
  $id = (int)$_GET['hapus'];
  // Check if this announcement belongs to current teacher
  $checkQ = mysqli_query($conn, "SELECT created_by FROM tbl_pengumuman WHERE id=$id LIMIT 1");
  if($checkQ && mysqli_num_rows($checkQ)==1){
      $checkR = mysqli_fetch_assoc($checkQ);
      if($checkR['created_by'] === $no_induk_guru){
          mysqli_query($conn, "DELETE FROM tbl_pengumuman WHERE id=$id LIMIT 1");
          header('Location: home.php?page=pengumuman&mode=guru&deleted=1'); exit;
      } else {
          $err = 'Anda hanya dapat menghapus pengumuman yang Anda buat.';
      }
  }
}

$today = date('Y-m-d');
// Only show announcements created by this teacher
$res = mysqli_query($conn, "SELECT * FROM tbl_pengumuman WHERE created_by = '$no_induk_guru' ORDER BY penting DESC, created_at DESC");
$rows=[]; while($r=mysqli_fetch_assoc($res)) $rows[]=$r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Beri Pengumuman - Guru</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<style>
body { background:#f1f5f9; font-family: system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif; }
.container { max-width:1100px; }
.card { border:1px solid #e2e8f0; border-radius:18px; overflow:hidden; box-shadow:0 4px 18px -6px rgba(0,0,0,.08); }
.card-header { background:linear-gradient(135deg,#ffffff,#f1f5f9); }
.badge-penting { background:linear-gradient(135deg,#ef4444,#dc2626); }
.table thead th { background:#f8fafc; font-size:.75rem; letter-spacing:.5px; text-transform:uppercase; }
.ann-row-new { animation:flashRow 1s ease-in-out 2; }
@keyframes flashRow { 0%,100%{background:#fff;} 50%{background:#fde68a;} }
textarea { resize:vertical; }
.guru-note { background:#e0f2fe; border:1px solid #81d4fa; border-radius:12px; padding:12px; margin-bottom:20px; }
</style>
</head>
<body>
<div class="container py-4">
  <h3 class="mb-3 fw-semibold d-flex align-items-center gap-2">
    <span class="text-primary">Beri Pengumuman</span>
    <small class="badge bg-info">Guru</small>
  </h3>
  
  <div class="guru-note">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Info:</strong> Anda dapat memberikan pengumuman kepada siswa di kelas yang Anda ampu.
    <?php if($kelasOptions): ?>
      Kelas yang dapat dipilih: <strong><?= implode(', ', $kelasOptions); ?></strong>
    <?php else: ?>
      <span class="text-warning">Tidak ada kelas yang diampu.</span>
    <?php endif; ?>
  </div>
  
  <?php if(isset($_GET['deleted'])) echo '<div class="alert alert-warning">Pengumuman dihapus.</div>'; ?>
  <?php if($msg) echo '<div class="alert alert-success">'.htmlspecialchars($msg).'</div>'; ?>
  <?php if($err) echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>'; ?>
  
  <div class="card mb-4">
    <div class="card-header"><strong>Buat Pengumuman Baru</strong></div>
    <div class="card-body">
      <form method="post" class="row g-3" enctype="multipart/form-data" id="formPengumuman">
        <input type="hidden" name="mode" value="add" id="modeField">
        <input type="hidden" name="id_edit" id="idEditField">
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Judul</label>
          <input type="text" name="judul" class="form-control" maxlength="150" required>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Mulai</label>
          <input type="date" name="mulai" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Selesai</label>
          <input type="date" name="selesai" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Target</label>
          <select name="target_scope" id="targetScope" class="form-select">
            <option value="KELAS">Kelas Tertentu</option>
          </select>
        </div>
        <div class="col-md-3" id="targetValueWrap">
          <label class="form-label small fw-semibold">Pilih Kelas</label>
          <select name="target_value" class="form-select" required>
            <option value="">-- Pilih Kelas --</option>
            <?php foreach($kelasOptions as $kelas): ?>
              <option value="<?= htmlspecialchars($kelas); ?>"><?= htmlspecialchars($kelas); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label small fw-semibold">Isi Pengumuman</label>
          <textarea name="isi" class="form-control" rows="4" placeholder="Tulis pengumuman untuk siswa..." required></textarea>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Lampiran (PDF)</label>
          <input type="file" name="lampiran" accept="application/pdf" class="form-control form-control-sm">
        </div>
        <div class="col-md-4 d-flex align-items-center gap-2">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="penting" id="pentingChk">
            <label class="form-check-label" for="pentingChk">Penting</label>
          </div>
          <button class="btn btn-primary ms-auto" type="submit">Kirim Pengumuman</button>
        </div>
      </form>
    </div>
  </div>
  
  <div class="card">
    <div class="card-header"><strong>Pengumuman Saya</strong></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th>#</th><th>Judul</th><th>Periode</th><th>Target</th><th>Penting</th><th>Lampiran</th><th>Dibuat</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if(!$rows){ echo '<tr><td colspan="8" class="text-center text-muted py-4">Belum ada pengumuman.</td></tr>'; }
            $no=1; foreach($rows as $r): $aktif = ($today >= $r['mulai'] && $today <= $r['selesai']); ?>
            <tr class="<?= $aktif ? 'ann-row-new':''; ?>">
              <td><?= $no++; ?></td>
              <td>
                <div class="fw-semibold small mb-1"><?= htmlspecialchars($r['judul']); ?></div>
                <div class="text-muted small" style="max-width:280px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                  <?= htmlspecialchars(substr(preg_replace('/\s+/',' ', $r['isi']),0,80)); ?>
                </div>
              </td>
              <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['mulai']); ?> - <?= htmlspecialchars($r['selesai']); ?></span></td>
              <td class="small"><?= htmlspecialchars($r['target_scope']); ?><?= $r['target_value']?'<br><span class="text-muted">'.htmlspecialchars($r['target_value']).'</span>':''; ?></td>
              <td><?= $r['penting']?'<span class="badge badge-penting">Ya</span>':'<span class="badge bg-secondary">Tidak</span>'; ?></td>
              <td><?= $r['lampiran']?'<a class="btn btn-sm btn-outline-secondary" target="_blank" href="materi/'.htmlspecialchars($r['lampiran']).'"><i class="bi bi-file-earmark-pdf"></i></a>':'-'; ?></td>
              <td class="small text-muted"><?= htmlspecialchars($r['created_at']); ?></td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-primary btn-edit" data-id='<?= $r['id']; ?>' data-json='<?= htmlspecialchars(json_encode($r),ENT_QUOTES); ?>'><i class="bi bi-pencil"></i></button>
                  <a onclick="return confirm('Hapus pengumuman ini?')" href="home.php?page=pengumuman&mode=guru&hapus=<?= $r['id']; ?>" class="btn btn-outline-danger"><i class="bi bi-trash"></i></a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <div class="mt-4 text-center">
    <a href=<?= guru_page('guru') ?> class="btn btn-secondary">Kembali ke Dashboard</a>
  </div>
</div>
</body>
</html>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.btn-edit').forEach(btn=>{
    btn.addEventListener('click',()=>{
      const data = JSON.parse(btn.getAttribute('data-json'));
      document.querySelector('#formPengumuman [name=judul]').value = data.judul;
      document.querySelector('#formPengumuman [name=mulai]').value = data.mulai;
      document.querySelector('#formPengumuman [name=selesai]').value = data.selesai;
      document.querySelector('#formPengumuman [name=isi]').value = data.isi;
      document.querySelector('#formPengumuman [name=penting]').checked = data.penting==1;
      document.getElementById('modeField').value='edit';
      document.getElementById('idEditField').value=data.id;
      if(data.target_value) document.querySelector('#formPengumuman [name=target_value]').value=data.target_value;
      window.scrollTo({top:0,behavior:'smooth'});
    });
  });
});
</script>