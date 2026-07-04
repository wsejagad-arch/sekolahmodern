<?php
header('Content-Type: application/json');
include __DIR__ . '/../koneksi.php';

$idSekolah = mt_current_school_id();
$q = mysqli_query($conn, "SELECT * FROM tbl_presensi_setting WHERE id_sekolah = $idSekolah ORDER BY id DESC LIMIT 1");
if(!$q){ echo json_encode(['success'=>false,'message'=>'DB error']); exit; }
$r = mysqli_fetch_assoc($q);
if(!$r){ echo json_encode(['success'=>false,'message'=>'No setting']); exit; }

// return stored fields (schedule/holidays may be JSON/text)
echo json_encode(['success'=>true,'setting'=>[
  'lat'=>floatval($r['lat']),
  'lng'=>floatval($r['lng']),
  'radius_m'=>intval($r['radius_m']),
  'schedule'=> $r['schedule'] ?? null,
  'holidays'=> $r['holidays'] ?? null,
  'updated_at'=> $r['updated_at'] ?? null
]]);

?>
