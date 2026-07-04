<?php
// Proteksi: halaman ini hanya untuk admin (hak_akses = 1)
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 1) {
    echo '<p class="text-danger p-3">Akses ditolak.</p>'; return;
}
include_once __DIR__ . '/../koneksi.php';
include_once __DIR__ . '/../functions.php';

// ── Pastikan tabel ada ────────────────────────────────────────────────────────
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_presensi_setting (
  id INT PRIMARY KEY AUTO_INCREMENT,
  lat DOUBLE,
  lng DOUBLE,
  radius_m INT,
  schedule TEXT,
  holidays TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Default: SMA Negeri 1 Sumber, Cirebon
$defLat    = -6.7656;
$defLng    = 108.3891;
$defRadius = 30000;
$defSched  = json_encode([
    'monday'    => ['in'=>'07:00','out'=>'15:00'],
    'tuesday'   => ['in'=>'07:00','out'=>'15:00'],
    'wednesday' => ['in'=>'07:00','out'=>'15:00'],
    'thursday'  => ['in'=>'07:00','out'=>'15:00'],
    'friday'    => ['in'=>'07:00','out'=>'12:00'],
]);

$q   = mysqli_query($conn, "SELECT * FROM tbl_presensi_setting ORDER BY id DESC LIMIT 1");
$row = mysqli_fetch_assoc($q);

$lat      = $row['lat']      ?? $defLat;
$lng      = $row['lng']      ?? $defLng;
$radius   = $row['radius_m'] ?? $defRadius;
$schedule = $row['schedule'] ?? $defSched;
$holidays = $row['holidays'] ?? '';

// Bila belum ada baris, masukkan default
if (!$row) {
    $schedEsc = mysqli_real_escape_string($conn, $defSched);
    mysqli_query($conn, "INSERT INTO tbl_presensi_setting (lat, lng, radius_m, schedule, holidays)
        VALUES ($defLat, $defLng, $defRadius, '$schedEsc', '')");
    $lat = $defLat; $lng = $defLng; $radius = $defRadius; $schedule = $defSched;
}

$schedArr = [];
if($schedule){
  $tmp = json_decode($schedule, true);
  if(is_array($tmp)) $schedArr = $tmp;
}
?>
<!-- Leaflet CSS (peta) — FontAwesome sudah ada di header.php -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  #map { height: 340px; border-radius: .375rem; border: 1px solid #dee2e6; }
  .ps-radius-circle { fill: #4e73df; fill-opacity: 0.08; stroke: #4e73df; stroke-width: 2; stroke-dasharray: 6 4; }
  .ps-day-card { border: 1px solid #dee2e6; border-radius: .375rem; padding: .75rem; margin-bottom: .5rem; background: #fff; }
  .ps-day-card .day-title { font-weight: 600; font-size: .85rem; margin-bottom: .4rem; color: #5a5c69; }
  #ps-radius-label { font-weight: 700; color: #4e73df; }
</style>

<!-- ── Begin Page Content ──────────────────────────────────────────────────── -->
<div class="container-fluid">

  <!-- Page Heading -->
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
      <i class="fas fa-map-marker-alt text-primary mr-2"></i>Pengaturan Presensi Mandiri
    </h1>
    <a href="home.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
      <i class="fas fa-arrow-left fa-sm mr-1"></i>Kembali ke Dashboard
    </a>
  </div>

  <form id="form" onsubmit="return false;">
  <div class="row">
    <div class="col-lg-8 col-md-10">

      <!-- ── Peta Lokasi Sekolah ─────────────────────────────────────────── -->
      <div class="card shadow mb-4">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-map mr-1"></i>Lokasi Sekolah
          </h6>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-3">Klik peta atau geser penanda untuk memilih koordinat sekolah. Lingkaran biru menunjukkan radius presensi.</p>
          <div id="map" class="mb-3"></div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label class="small font-weight-bold">Latitude</label>
              <input name="lat" id="lat" type="number" step="any"
                     value="<?= htmlspecialchars($lat) ?>" placeholder="-6.7656"
                     class="form-control form-control-sm">
            </div>
            <div class="form-group col-md-6">
              <label class="small font-weight-bold">Longitude</label>
              <input name="lng" id="lng" type="number" step="any"
                     value="<?= htmlspecialchars($lng) ?>" placeholder="108.3891"
                     class="form-control form-control-sm">
            </div>
          </div>

          <div class="form-group mb-1">
            <label class="small font-weight-bold">
              Radius Presensi (meter) &nbsp;
              <span id="ps-radius-label"><?= number_format($radius/1000, 0, ',', '.') ?> km</span>
            </label>
            <input type="range" id="radius_range" class="custom-range"
                   min="100" max="50000" step="100" value="<?= (int)$radius ?>"
                   oninput="onRadiusChange(this.value)">
            <div class="input-group input-group-sm mt-1" style="max-width:180px">
              <input type="number" name="radius_m" id="radius_m"
                     value="<?= (int)$radius ?>" min="100" max="100000" step="100"
                     class="form-control form-control-sm"
                     oninput="onRadiusChange(this.value)">
              <div class="input-group-append"><span class="input-group-text">m</span></div>
            </div>
            <small class="text-muted">Default: 30.000 m (30 km) — SMA Negeri 1 Sumber</small>
          </div>

          <button type="button" onclick="useCurrentLocation()" class="btn btn-sm btn-outline-primary mt-2">
            <i class="fas fa-location-arrow mr-1"></i>Gunakan lokasi perangkat saat ini
          </button>
        </div>
      </div>

      <!-- ── Jadwal Aktif Per Hari ───────────────────────────────────────── -->
      <div class="card shadow mb-4">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-clock mr-1"></i>Jadwal Aktif Per Hari
          </h6>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-3">Kosongkan kolom jam jika hari tersebut libur. Format: HH:MM (24 jam).</p>
          <div class="row">
          <?php
          $days = ['monday'=>'Senin','tuesday'=>'Selasa','wednesday'=>'Rabu','thursday'=>'Kamis','friday'=>'Jumat','saturday'=>'Sabtu','sunday'=>'Minggu'];
          foreach ($days as $key => $label):
            $in  = $schedArr[$key]['in']  ?? '';
            $out = $schedArr[$key]['out'] ?? '';
            $active = ($in !== '' || $out !== '');
          ?>
            <div class="col-md-6">
              <div class="ps-day-card">
                <div class="d-flex align-items-center mb-2">
                  <div class="custom-control custom-switch mr-2">
                    <input type="checkbox" class="custom-control-input" id="chk_<?= $key ?>"
                           <?= $active ? 'checked' : '' ?> onchange="toggleDay('<?= $key ?>')">
                    <label class="custom-control-label" for="chk_<?= $key ?>"></label>
                  </div>
                  <span class="day-title"><?= $label ?></span>
                </div>
                <div class="form-row" id="row_<?= $key ?>" <?= !$active ? 'style="display:none"' : '' ?>>
                  <div class="col">
                    <label class="small text-muted mb-0">Masuk</label>
                    <input name="day_<?= $key ?>_in" id="day_<?= $key ?>_in"
                           class="form-control form-control-sm" placeholder="07:00"
                           value="<?= htmlspecialchars($in) ?>">
                  </div>
                  <div class="col">
                    <label class="small text-muted mb-0">Pulang</label>
                    <input name="day_<?= $key ?>_out" id="day_<?= $key ?>_out"
                           class="form-control form-control-sm" placeholder="15:00"
                           value="<?= htmlspecialchars($out) ?>">
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
          <input type="hidden" id="schedule" name="schedule" value='<?= htmlspecialchars($schedule) ?>'/>
        </div>
      </div>

      <!-- ── Hari Libur ──────────────────────────────────────────────────── -->
      <div class="card shadow mb-4">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-danger">
            <i class="fas fa-calendar-times mr-1"></i>Hari Libur
          </h6>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-2">Satu tanggal per baris, format: <code>YYYY-MM-DD</code></p>
          <textarea id="holidays" name="holidays" class="form-control" rows="5"
                    placeholder="Contoh:&#10;2026-01-01&#10;2026-08-17"><?= htmlspecialchars($holidays) ?></textarea>
        </div>
      </div>

      <!-- Tombol Simpan -->
      <div class="d-flex mb-2">
        <button id="saveBtn" class="btn btn-primary mr-2">
          <i class="fas fa-save mr-1"></i>Simpan Pengaturan
        </button>
        <a href="home.php" class="btn btn-secondary">Batal</a>
      </div>
      <div id="msg" class="mb-3"></div>

    </div><!-- /col-lg-8 -->

    <!-- ── Tabel Ringkasan Pengaturan Tersimpan ──────────────────────────── -->
    <div class="col-lg-4 col-md-10">
      <div class="card shadow mb-4" id="resultTable" <?= $row ? '' : 'style="display:none"' ?>>
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-table mr-1"></i>Pengaturan Tersimpan
          </h6>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm table-borderless mb-0" style="font-size:.82rem">
            <tbody id="resultBody">
            <?php if ($row): ?>
              <tr class="border-bottom">
                <td class="pl-3 text-muted" style="width:38%"><i class="fas fa-map-marker-alt mr-1 text-primary"></i>Latitude</td>
                <td class="font-weight-bold" id="tbl_lat"><?= htmlspecialchars($row['lat']) ?></td>
              </tr>
              <tr class="border-bottom">
                <td class="pl-3 text-muted"><i class="fas fa-map-marker-alt mr-1 text-primary"></i>Longitude</td>
                <td class="font-weight-bold" id="tbl_lng"><?= htmlspecialchars($row['lng']) ?></td>
              </tr>
              <tr class="border-bottom">
                <td class="pl-3 text-muted"><i class="fas fa-circle mr-1 text-primary" style="font-size:9px"></i>Radius</td>
                <td class="font-weight-bold" id="tbl_radius"><?= number_format((int)$row['radius_m']) ?> m (<?= number_format($row['radius_m']/1000,1) ?> km)</td>
              </tr>
              <tr class="border-bottom">
                <td class="pl-3 text-muted align-top"><i class="fas fa-clock mr-1 text-primary"></i>Jadwal</td>
                <td id="tbl_schedule">
                  <?php
                  $dNm = ['monday'=>'Sen','tuesday'=>'Sel','wednesday'=>'Rab','thursday'=>'Kam','friday'=>'Jum','saturday'=>'Sab','sunday'=>'Min'];
                  $sCnt = 0;
                  foreach ($schedArr as $dk => $dv) {
                      if (empty($dv['in']) && empty($dv['out'])) continue;
                      $dn = $dNm[$dk] ?? $dk;
                      echo '<span class="badge badge-success mr-1 mb-1" style="font-size:10px">'
                           .$dn.' '.htmlspecialchars($dv['in']??'').'–'.htmlspecialchars($dv['out']??'')
                           .'</span>';
                      $sCnt++;
                  }
                  if (!$sCnt) echo '<span class="text-muted">-</span>';
                  ?>
                </td>
              </tr>
              <tr class="border-bottom">
                <td class="pl-3 text-muted align-top"><i class="fas fa-ban mr-1 text-danger"></i>Libur</td>
                <td id="tbl_holidays" style="font-size:.78rem"><?= $row['holidays'] ? nl2br(htmlspecialchars($row['holidays'])) : '<span class="text-muted">-</span>' ?></td>
              </tr>
              <tr>
                <td class="pl-3 text-muted"><i class="fas fa-history mr-1"></i>Update</td>
                <td class="text-muted" id="tbl_updated" style="font-size:.78rem"><?= $row['updated_at'] ? date('d M Y, H:i', strtotime($row['updated_at'])) : '-' ?></td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- /col-lg-4 -->

  </div><!-- /row -->
  </form><!-- /form -->

</div><!-- /container-fluid -->

<!-- ── Success Modal ──────────────────────────────────────────────────────── -->
<div id="successModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:.5rem;padding:2rem 1.75rem;max-width:380px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.22);text-align:center;">
    <div style="width:64px;height:64px;background:#d4edda;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
      <i class="fas fa-check" style="font-size:1.75rem;color:#155724;"></i>
    </div>
    <h5 class="font-weight-bold mb-1">Pengaturan Berhasil Disimpan!</h5>
    <p class="text-muted small mb-3" id="modalDesc">Data presensi telah diperbarui.</p>
    <div class="d-flex" style="gap:.75rem;justify-content:center;">
      <button onclick="closeModal()" class="btn btn-primary">
        <i class="fas fa-check mr-1"></i>OK
      </button>
      <a href="home.php" class="btn btn-light border">
        <i class="fas fa-home mr-1"></i>Dashboard
      </a>
    </div>
  </div>
</div>
<script>
// ── Leaflet map ────────────────────────────────────────────────────────────────
const initLat = <?= (float)$lat ?>;
const initLng = <?= (float)$lng ?>;
const initRadius = <?= (int)$radius ?>;

const map = L.map('map').setView([initLat, initLng], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

const marker = L.marker([initLat, initLng], {draggable: true}).addTo(map)
    .bindPopup('Lokasi Sekolah').openPopup();
let radiusCircle = L.circle([initLat, initLng], {
    radius: initRadius,
    className: 'ps-radius-circle'
}).addTo(map);

// Update inputs when marker is dragged
marker.on('dragend', function(e) {
    const pos = marker.getLatLng();
    document.getElementById('lat').value = pos.lat.toFixed(7);
    document.getElementById('lng').value = pos.lng.toFixed(7);
    radiusCircle.setLatLng(pos);
});

// Click map to reposition marker
map.on('click', function(e) {
    marker.setLatLng(e.latlng);
    radiusCircle.setLatLng(e.latlng);
    document.getElementById('lat').value = e.latlng.lat.toFixed(7);
    document.getElementById('lng').value = e.latlng.lng.toFixed(7);
});

// Sync manual lat/lng inputs
['lat','lng'].forEach(id => {
    document.getElementById(id).addEventListener('input', function() {
        const la = parseFloat(document.getElementById('lat').value);
        const ln = parseFloat(document.getElementById('lng').value);
        if (!isNaN(la) && !isNaN(ln)) {
            marker.setLatLng([la, ln]);
            radiusCircle.setLatLng([la, ln]);
            map.panTo([la, ln]);
        }
    });
});

// Radius change
function onRadiusChange(val) {
    val = parseInt(val) || 100;
    document.getElementById('radius_m').value     = val;
    document.getElementById('radius_range').value = val;
    const km = (val/1000).toFixed(val < 1000 ? 1 : 0);
    document.getElementById('ps-radius-label').textContent = km + ' km';
    radiusCircle.setRadius(val);
}

// Use current device location
function useCurrentLocation() {
    if (!navigator.geolocation) { alert('Browser tidak mendukung GPS.'); return; }
    navigator.geolocation.getCurrentPosition(function(pos) {
        const la = pos.coords.latitude, ln = pos.coords.longitude;
        document.getElementById('lat').value = la.toFixed(7);
        document.getElementById('lng').value = ln.toFixed(7);
        marker.setLatLng([la, ln]);
        radiusCircle.setLatLng([la, ln]);
        map.setView([la, ln], 15);
    }, function(e) { alert('Gagal mendapatkan lokasi: ' + e.message); });
}

// ── Toggle hari aktif ─────────────────────────────────────────────────────────
function toggleDay(day) {
    const chk = document.getElementById('chk_' + day);
    const lbl = document.getElementById('lbl_' + day);
    const row = document.getElementById('row_' + day);
    const active = chk.checked;
    if (active) {
        lbl.style.cssText = 'background:#dcfce7;border-color:#86efac;color:#166534';
        if (row) row.style.display = '';
    } else {
        lbl.style.cssText = 'background:#f3f4f6;border-color:#d1d5db;color:#6b7280';
        if (row) row.style.display = 'none';
        document.getElementById('day_' + day + '_in').value  = '';
        document.getElementById('day_' + day + '_out').value = '';
    }
}

// ── Save ───────────────────────────────────────────────────────────────────────
document.getElementById('saveBtn').addEventListener('click', async () => {
    const days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    const sched = {};
    const timeOk = t => !t || /^\d{2}:\d{2}$/.test(t);
    let validErr = null;
    days.forEach(d => {
        const active = document.getElementById('chk_' + d).checked;
        const inV  = document.getElementById('day_'+d+'_in').value.trim();
        const outV = document.getElementById('day_'+d+'_out').value.trim();
        if (active) {
            if (!timeOk(inV))  { validErr = 'Format jam masuk salah untuk hari ' + d + ' (gunakan HH:MM)'; }
            if (!timeOk(outV)) { validErr = 'Format jam pulang salah untuk hari ' + d + ' (gunakan HH:MM)'; }
            sched[d] = { active: true, in: inV || null, out: outV || null };
        }
    });
    if (validErr) { document.getElementById('msg').innerHTML = '<div class="alert alert-danger py-1 px-2 small">' + validErr + '</div>'; return; }
    document.getElementById('schedule').value = JSON.stringify(sched);

    const msg  = document.getElementById('msg');
    const btn  = document.getElementById('saveBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan…';
    btn.disabled = true;

    const data = new FormData(document.getElementById('form'));
    try {
        const res = await fetch('api/save_presensi_setting.php', { method:'POST', body: data });
        const j   = await res.json();
        if (j && j.success) {
            msg.innerHTML = '';
            updateResultTable(j.data);
            showModal(j.data);
        } else {
            msg.innerHTML = '<div class="alert alert-danger py-1 px-2 small">' + (j.message || 'Gagal menyimpan') + '</div>';
        }
    } catch(e) {
        msg.innerHTML = '<div class="alert alert-danger py-1 px-2 small">Error: ' + e.message + '</div>';
    }
    btn.innerHTML = '<i class="fas fa-save mr-1"></i>Simpan Pengaturan';
    btn.disabled = false;
});

// ── Modal helpers ─────────────────────────────────────────────────────────────
function showModal(data) {
    const modal = document.getElementById('successModal');
    const desc  = document.getElementById('modalDesc');
    if (data) {
        const radKm = (parseInt(data.radius_m)/1000).toFixed(1);
        desc.textContent = 'Radius: ' + radKm + ' km  ·  Lat: ' + parseFloat(data.lat).toFixed(5) + '  ·  Lng: ' + parseFloat(data.lng).toFixed(5);
    }
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    document.getElementById('successModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('successModal').addEventListener('click', function(e){
    if (e.target === this) closeModal();
});

// ── Update result table after save ───────────────────────────────────────────
function updateResultTable(data) {
    if (!data) return;
    const tbl = document.getElementById('resultTable');
    tbl.style.display = '';
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.innerHTML = v; };

    const radKm = (parseInt(data.radius_m)/1000).toFixed(1);
    set('tbl_lat',    parseFloat(data.lat).toFixed(7));
    set('tbl_lng',    parseFloat(data.lng).toFixed(7));
    set('tbl_radius', parseInt(data.radius_m).toLocaleString('id') + ' meter (' + radKm + ' km)');

    const dNm = {monday:'Senin',tuesday:'Selasa',wednesday:'Rabu',thursday:'Kamis',friday:'Jumat',saturday:'Sabtu',sunday:'Minggu'};
    let schedHtml = '';
    try {
        const sc = JSON.parse(data.schedule || '{}');
        Object.keys(sc).forEach(dk => {
            const dv = sc[dk];
            if (!dv || (!dv.in && !dv.out)) return;
            schedHtml += `<span style="display:inline-flex;align-items:center;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:11px;font-weight:600;padding:2px 9px;border-radius:999px;margin:2px;">`
                       + `${dNm[dk]||dk} ${dv.in||''}&ndash;${dv.out||''}</span>`;
        });
    } catch(e){}
    set('tbl_schedule', schedHtml || '<span style="color:#9ca3af">-</span>');

    const hol = (data.holidays || '').trim();
    set('tbl_holidays', hol ? hol.replace(/\n/g,'<br>') : '<span style="color:#9ca3af">-</span>');

    const now = new Date();
    const pad = n => String(n).padStart(2,'0');
    const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    set('tbl_updated', pad(now.getDate())+' '+bulan[now.getMonth()]+' '+now.getFullYear()+', '+pad(now.getHours())+':'+pad(now.getMinutes()));

    tbl.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>
