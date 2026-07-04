<?php
// Skip auth check if already handled by parent context
if (!isset($skip_auth_check) || !$skip_auth_check) {
    require_once __DIR__ . '/bootstrap.php';
    require_admin();
} else {
    // Just ensure we have the necessary includes
    if (!isset($conn)) {
        require_once __DIR__ . '/koneksi.php';
    }
    if (!function_exists('data_lembaga')) {
        require_once __DIR__ . '/functions.php';
    }
}

// Ensure table exists with updated schema
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
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARSET=utf8mb4");

// Check if new columns exist, if not add them (for existing tables)
$result = mysqli_query($conn, "SHOW COLUMNS FROM tbl_pengumuman LIKE 'target_scope'");
if (mysqli_num_rows($result) == 0) {
    // Add missing columns to existing table
    mysqli_query($conn, "ALTER TABLE tbl_pengumuman 
        ADD COLUMN target_scope ENUM('SEMUA','KELAS','TINGKAT','GURU') DEFAULT 'SEMUA' AFTER selesai,
        ADD COLUMN target_value VARCHAR(100) DEFAULT NULL AFTER target_scope,
        ADD COLUMN lampiran VARCHAR(255) DEFAULT NULL AFTER target_value");
}

// Check if updated_at column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM tbl_pengumuman LIKE 'updated_at'");
if (mysqli_num_rows($result) == 0) {
    mysqli_query($conn, "ALTER TABLE tbl_pengumuman 
        ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
}

// Ensure read tracking table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pengumuman_read (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pengumuman_id INT NOT NULL,
  no_induk VARCHAR(50) NOT NULL,
  read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_read (pengumuman_id, no_induk),
  FOREIGN KEY (pengumuman_id) REFERENCES tbl_pengumuman(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4");

// File upload handling helper
function handle_upload($field){
  if(empty($_FILES[$field]['name'])) return null;
  $fname = $_FILES[$field]['name'];
  $tmp = $_FILES[$field]['tmp_name'];
  $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
  $allowed = ['pdf'];
  if(!in_array($ext,$allowed)) return null;
  $new = 'ANN_'.date('Ymd_His').'_' . substr(md5($fname.microtime()),0,6) . '.' . $ext;
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
  $target_scope = $_POST['target_scope'] ?? 'SEMUA';
  $target_value = trim($_POST['target_value'] ?? ''); if($target_scope==='SEMUA') $target_value=null; if($target_value==='') $target_value=null;
  if($judul==='' || $isi==='' || !$mulai || !$selesai){ $err='Lengkapi semua field'; }
  else if(strlen($judul) > 150){ $err='Judul terlalu panjang'; }
  else {
    $judulEsc = mysqli_real_escape_string($conn,$judul);
    $isiEsc = mysqli_real_escape_string($conn,$isi);
    $mulaiEsc = mysqli_real_escape_string($conn,$mulai);
    $selesaiEsc = mysqli_real_escape_string($conn,$selesai);
    $tScopeEsc = mysqli_real_escape_string($conn,$target_scope);
    $tValueEsc = $target_value? "'".mysqli_real_escape_string($conn,$target_value)."'" : 'NULL';
    $lampiranFile = handle_upload('lampiran');
    if($mode==='edit') {
      $idEdit = (int)($_POST['id_edit'] ?? 0);
      if($idEdit>0){
        $setLamp = $lampiranFile? ", lampiran='".mysqli_real_escape_string($conn,$lampiranFile)."'" : '';
        $qUpd = "UPDATE tbl_pengumuman SET judul='$judulEsc', isi='$isiEsc', penting=$penting, mulai='$mulaiEsc', selesai='$selaiEsc', target_scope='$tScopeEsc', target_value=$tValueEsc $setLamp WHERE id=$idEdit LIMIT 1";
        // Fix typo: $selaiEsc -> $selesaiEsc
        $qUpd = str_replace("$selaiEsc","$selesaiEsc",$qUpd);
        if(mysqli_query($conn,$qUpd)) $msg='Pengumuman diperbarui'; else $err='Gagal update: '.mysqli_error($conn);
      }
    } else {
      $lampiranEsc = $lampiranFile? "'".mysqli_real_escape_string($conn,$lampiranFile)."'" : 'NULL';
      $qIns = "INSERT INTO tbl_pengumuman(judul,isi,penting,mulai,selesai,target_scope,target_value,lampiran) VALUES('$judulEsc','$isiEsc',$penting,'$mulaiEsc','$selesaiEsc','$tScopeEsc',$tValueEsc,$lampiranEsc)";
      if(mysqli_query($conn,$qIns)) $msg='Pengumuman tersimpan'; else $err='Gagal simpan: '.mysqli_error($conn);
    }
  }
}

// Delete
if(isset($_GET['hapus'])){
  $id = (int)$_GET['hapus'];
  mysqli_query($conn, "DELETE FROM tbl_pengumuman WHERE id=$id LIMIT 1");
  header('Location: pengumuman-admin.php?deleted=1'); exit;
}

$today = date('Y-m-d');
$res = mysqli_query($conn, "SELECT * FROM tbl_pengumuman ORDER BY penting DESC, created_at DESC");
$rows=[]; while($r=mysqli_fetch_assoc($res)) $rows[]=$r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin Pengumuman</title>
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
</style>
</head>
<body>
<div class="container py-4">
  <h3 class="mb-4 fw-semibold d-flex align-items-center gap-2"><span class="text-primary">Manajemen Pengumuman</span></h3>
  <?php if(isset($_GET['deleted'])) echo '<div class="alert alert-warning">Pengumuman dihapus.</div>'; ?>
  <?php if($msg) echo '<div class="alert alert-success">'.htmlspecialchars($msg).'</div>'; ?>
  <?php if($err) echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>'; ?>
  <div class="card mb-4">
    <div class="card-header"><strong>Tambah / Edit Pengumuman</strong></div>
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
            <option value="SEMUA">Semua</option>
            <option value="KELAS">Kelas</option>
            <option value="TINGKAT">Tingkat</option>
            <option value="GURU">Guru (No Induk)</option>
          </select>
        </div>
        <div class="col-md-3 d-none" id="targetValueWrap">
          <label class="form-label small fw-semibold">Nilai Target</label>
          <input type="text" name="target_value" class="form-control" placeholder="XI IPA 1 / 11 / 1987xxxx">
        </div>
        <div class="col-12">
          <label class="form-label small fw-semibold">Isi Pengumuman</label>
          <textarea name="isi" class="form-control" rows="4" placeholder="Gunakan enter untuk paragraf baru atau tanda - untuk bullet"></textarea>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Lampiran (PDF)</label>
          <input type="file" name="lampiran" accept="application/pdf" class="form-control form-control-sm">
        </div>
        <div class="col-md-4 d-flex align-items-center gap-2">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="penting" id="pentingChk">
            <label class="form-check-label" for="pentingChk">Tandai Penting</label>
          </div>
          <button class="btn btn-primary ms-auto" type="submit">Simpan</button>
        </div>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><strong>Daftar Pengumuman</strong></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th>#</th><th>Judul</th><th>Periode</th><th>Target</th><th>Penting</th><th>Lampiran</th><th>Dibuat</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if(!$rows){ echo '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada pengumuman.</td></tr>'; }
            $no=1; foreach($rows as $r): $aktif = ($today >= $r['mulai'] && $today <= $r['selesai']); ?>
            <tr class="<?= $aktif ? 'ann-row-new':''; ?>">
              <td><?= $no++; ?></td>
              <td>
                <div class="fw-semibold small mb-1"><?= htmlspecialchars($r['judul']); ?></div>
                <div class="text-muted small" style="max-width:380px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                  <?= htmlspecialchars(substr(preg_replace('/\s+/',' ', $r['isi']),0,120)); ?>
                </div>
              </td>
              <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['mulai']); ?> - <?= htmlspecialchars($r['selesai']); ?></span></td>
              <td class="small"><?= htmlspecialchars($r['target_scope']); ?><?= $r['target_value']?'<br><span class="text-muted">'.htmlspecialchars($r['target_value']).'</span>':''; ?></td>
              <td><?= $r['penting']?'<span class="badge badge-penting">Penting</span>':'<span class="badge bg-secondary">Biasa</span>'; ?></td>
              <td><?= $r['lampiran']?'<a class="btn btn-sm btn-outline-secondary" target="_blank" href="materi/'.htmlspecialchars($r['lampiran']).'"><i class="bi bi-file-earmark-pdf"></i></a>':'-'; ?></td>
              <td class="small text-muted"><?= htmlspecialchars($r['created_at']); ?></td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-primary btn-edit" data-id='<?= $r['id']; ?>' data-json='<?= htmlspecialchars(json_encode($r),ENT_QUOTES); ?>'><i class="bi bi-pencil"></i></button>
                  <a onclick="return confirm('Hapus pengumuman ini?')" href="pengumuman-admin.php?hapus=<?= $r['id']; ?>" class="btn btn-outline-danger"><i class="bi bi-trash"></i></a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const targetScope = document.getElementById('targetScope');
  const targetWrap = document.getElementById('targetValueWrap');
  function toggleTarget(){
    if(['KELAS','TINGKAT','GURU'].includes(targetScope.value)) targetWrap.classList.remove('d-none'); else targetWrap.classList.add('d-none');
  }
  targetScope.addEventListener('change', toggleTarget); toggleTarget();
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
      document.getElementById('targetScope').value=data.target_scope || 'SEMUA';
      toggleTarget();
      if(data.target_value) document.querySelector('#formPengumuman [name=target_value]').value=data.target_value; else document.querySelector('#formPengumuman [name=target_value]').value='';
      window.scrollTo({top:0,behavior:'smooth'});
    });
  });
});
</script>
</html>