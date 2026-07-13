<?php
if (!isset($_SESSION['hak_akses']) || (int)$_SESSION['hak_akses'] !== 1) {
    echo '<p class="text-danger p-3">Akses ditolak.</p>';
    return;
}

include_once __DIR__ . '/../../koneksi.php';

// Cek apakah tbl_app_config ada
$qCheck = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_app_config'");
if (!$qCheck || mysqli_num_rows($qCheck) == 0) {
    @mysqli_query($conn, "CREATE TABLE tbl_app_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kunci VARCHAR(100) UNIQUE NOT NULL,
        nilai TEXT
    )");
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sholat'])) {
    $settings = [
        'sholat_dzuhur_active' => isset($_POST['dzuhur_active']) ? '1' : '0',
        'sholat_dzuhur_start' => trim($_POST['dzuhur_start'] ?? '11:45'),
        'sholat_dzuhur_end' => trim($_POST['dzuhur_end'] ?? '13:30'),
        'sholat_dzuhur_days' => json_encode($_POST['dzuhur_days'] ?? ['Senin','Selasa','Rabu','Kamis']),
        
        'sholat_jumat_active' => isset($_POST['jumat_active']) ? '1' : '0',
        'sholat_jumat_start' => trim($_POST['jumat_start'] ?? '11:45'),
        'sholat_jumat_end' => trim($_POST['jumat_end'] ?? '13:30'),
        'sholat_jumat_days' => json_encode($_POST['jumat_days'] ?? ['Jumat'])
    ];
    
    foreach ($settings as $kunci => $nilai) {
        $kEsc = mysqli_real_escape_string($conn, $kunci);
        $vEsc = mysqli_real_escape_string($conn, $nilai);
        @mysqli_query($conn, "INSERT INTO tbl_app_config (kunci, nilai) VALUES ('$kEsc', '$vEsc') ON DUPLICATE KEY UPDATE nilai='$vEsc'");
    }

    // Save mushola locations
    $lats = $_POST['lat'] ?? [];
    $lngs = $_POST['lng'] ?? [];
    $rads = $_POST['radius'] ?? [];
    $names = $_POST['nama_mushola'] ?? [];

    $musholas = [];
    foreach ($lats as $i => $lat) {
        if (!empty($lat) && !empty($lngs[$i])) {
            $musholas[] = [
                'nama' => trim($names[$i] ?? "Mushola " . ($i + 1)),
                'lat' => (float)$lat,
                'lng' => (float)$lngs[$i],
                'radius' => (int)($rads[$i] ?? 50)
            ];
        }
    }
    
    $jsonVal = mysqli_real_escape_string($conn, json_encode($musholas));
    @mysqli_query($conn, "INSERT INTO tbl_app_config (kunci, nilai) VALUES ('7kih_mushola_locations', '$jsonVal') ON DUPLICATE KEY UPDATE nilai='$jsonVal'");

    $msg = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Pengaturan Presensi Sholat dan Lokasi berhasil disimpan.</div>';
}

// Load current settings
$currentSettings = [];
$qVal = @mysqli_query($conn, "SELECT kunci, nilai FROM tbl_app_config WHERE kunci IN (
    'sholat_dzuhur_active', 'sholat_dzuhur_start', 'sholat_dzuhur_end', 'sholat_dzuhur_days',
    'sholat_jumat_active', 'sholat_jumat_start', 'sholat_jumat_end', 'sholat_jumat_days',
    '7kih_mushola_locations'
)");
if ($qVal) {
    while ($r = mysqli_fetch_assoc($qVal)) {
        $currentSettings[$r['kunci']] = $r['nilai'];
    }
}

$dzActive = ($currentSettings['sholat_dzuhur_active'] ?? '0') === '1';
$dzStart = $currentSettings['sholat_dzuhur_start'] ?? '11:45';
$dzEnd = $currentSettings['sholat_dzuhur_end'] ?? '13:30';
$dzDays = json_decode($currentSettings['sholat_dzuhur_days'] ?? '["Senin","Selasa","Rabu","Kamis"]', true) ?: [];

$jmActive = ($currentSettings['sholat_jumat_active'] ?? '0') === '1';
$jmStart = $currentSettings['sholat_jumat_start'] ?? '11:45';
$jmEnd = $currentSettings['sholat_jumat_end'] ?? '13:30';
$jmDays = json_decode($currentSettings['sholat_jumat_days'] ?? '["Jumat"]', true) ?: [];

$musholas = json_decode($currentSettings['7kih_mushola_locations'] ?? '[]', true) ?: [];

$hariOptions = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
?>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-mosque text-success"></i> Pengaturan Presensi Sholat</h1>
    </div>

    <?= $msg ?>

    <form method="POST">
        <div class="row">
            <!-- Pengaturan Dzuhur -->
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100 border-left-primary">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Presensi Sholat Dzuhur</h6>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="dzuhur_active" name="dzuhur_active" <?= $dzActive ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="dzuhur_active">Aktifkan</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <label>Waktu Mulai</label>
                                <input type="time" name="dzuhur_start" class="form-control" value="<?= htmlspecialchars($dzStart) ?>">
                            </div>
                            <div class="col-6">
                                <label>Waktu Selesai</label>
                                <input type="time" name="dzuhur_end" class="form-control" value="<?= htmlspecialchars($dzEnd) ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Tampil Pada Hari:</label><br>
                            <?php foreach($hariOptions as $h): ?>
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input type="checkbox" class="custom-control-input" id="dz_<?= $h ?>" name="dzuhur_days[]" value="<?= $h ?>" <?= in_array($h, $dzDays) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="dz_<?= $h ?>"><?= $h ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Jika diaktifkan, tombol absen Dzuhur akan muncul di halaman presensi siswa pada jam dan hari di atas. Fitur Jurnal 7 KAIH otomatis menyembunyikan sholat ini.</small>
                    </div>
                </div>
            </div>

            <!-- Pengaturan Jumat -->
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100 border-left-success">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-success">Presensi Sholat Jumat</h6>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="jumat_active" name="jumat_active" <?= $jmActive ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="jumat_active">Aktifkan</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <label>Waktu Mulai</label>
                                <input type="time" name="jumat_start" class="form-control" value="<?= htmlspecialchars($jmStart) ?>">
                            </div>
                            <div class="col-6">
                                <label>Waktu Selesai</label>
                                <input type="time" name="jumat_end" class="form-control" value="<?= htmlspecialchars($jmEnd) ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Tampil Pada Hari:</label><br>
                            <?php foreach($hariOptions as $h): ?>
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input type="checkbox" class="custom-control-input" id="jm_<?= $h ?>" name="jumat_days[]" value="<?= $h ?>" <?= in_array($h, $jmDays) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="jm_<?= $h ?>"><?= $h ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Sama seperti Dzuhur, namun khusus untuk hari Jumat.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pengaturan Lokasi -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-map-marker-alt"></i> Lokasi Mushola/Masjid Sekolah (Untuk Validasi GPS)</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">Tambahkan koordinat (Latitude & Longitude) mushola sekolah. Siswa harus berada di dalam radius ini untuk bisa mengirim absensi Sholat Dzuhur dan Jumat. Klik pada peta untuk memperbarui titik koordinat baris yang sedang aktif.</p>
                
                <!-- Search Box -->
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" id="map-search-input" class="form-control" placeholder="Cari nama kota, daerah, atau nama tempat...">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button" id="btn-map-search"><i class="fas fa-search"></i> Cari Lokasi</button>
                        </div>
                    </div>
                    <small id="map-search-status" class="text-muted"></small>
                </div>

                <div id="map" style="height: 400px; border-radius: 8px; z-index: 1;" class="mb-4 shadow-sm border"></div>

                <div id="mushola-container">
                    <?php if (empty($musholas)): ?>
                        <div class="row mb-3 mushola-row">
                            <div class="col-md-3">
                                <label>Nama Tempat</label>
                                <input type="text" name="nama_mushola[]" class="form-control map-input-name" placeholder="Masjid Raya" required>
                            </div>
                            <div class="col-md-3">
                                <label>Latitude <button type="button" class="btn btn-xs btn-outline-info btn-pick" title="Pilih di peta" style="padding:0 5px;"><i class="fas fa-crosshairs"></i></button></label>
                                <input type="text" name="lat[]" class="form-control map-input-lat" placeholder="-6.200000" required>
                            </div>
                            <div class="col-md-3">
                                <label>Longitude</label>
                                <input type="text" name="lng[]" class="form-control map-input-lng" placeholder="106.816666" required>
                            </div>
                            <div class="col-md-2">
                                <label>Radius (m)</label>
                                <input type="number" name="radius[]" class="form-control map-input-rad" value="50" required>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-danger btn-remove" style="display:none;"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach($musholas as $i => $m): ?>
                            <div class="row mb-3 mushola-row">
                                <div class="col-md-3">
                                    <label>Nama Tempat</label>
                                    <input type="text" name="nama_mushola[]" class="form-control map-input-name" value="<?= htmlspecialchars($m['nama'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label>Latitude <button type="button" class="btn btn-xs btn-outline-info btn-pick" title="Pilih di peta" style="padding:0 5px;"><i class="fas fa-crosshairs"></i></button></label>
                                    <input type="text" name="lat[]" class="form-control map-input-lat" value="<?= htmlspecialchars($m['lat']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label>Longitude</label>
                                    <input type="text" name="lng[]" class="form-control map-input-lng" value="<?= htmlspecialchars($m['lng']) ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Radius (m)</label>
                                    <input type="number" name="radius[]" class="form-control map-input-rad" value="<?= htmlspecialchars($m['radius'] ?? 50) ?>" required>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger btn-remove"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="mb-4 mt-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-mushola"><i class="fas fa-plus"></i> Tambah Titik Lokasi</button>
                </div>

                <button type="submit" name="save_sholat" class="btn btn-success btn-lg px-5"><i class="fas fa-save"></i> Simpan Semua Pengaturan</button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('mushola-container');
    let map = null;
    let markers = [];
    let circles = [];
    let activeRow = null;
    let defaultLat = -6.7656;
    let defaultLng = 108.3891;
    
    // Inisialisasi peta
    function initMap() {
        map = L.map('map').setView([defaultLat, defaultLng], 16);
        
        // Peta Standar (OpenStreetMap)
        const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        });
        
        // Peta Satelit (Google Maps Hybrid)
        const satelliteLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            attribution: '© Google Maps',
            maxZoom: 20
        });

        // Tampilkan default
        satelliteLayer.addTo(map);

        // Control Panel
        const baseMaps = {
            "Satelit": satelliteLayer,
            "Peta Standar": osmLayer
        };
        L.control.layers(baseMaps).addTo(map);

        map.on('click', function(e) {
            if (activeRow) {
                const latInput = activeRow.querySelector('.map-input-lat');
                const lngInput = activeRow.querySelector('.map-input-lng');
                latInput.value = e.latlng.lat.toFixed(6);
                lngInput.value = e.latlng.lng.toFixed(6);
                updateMapMarkers();
            } else {
                alert('Silakan klik ikon "Target/Crosshair" di salah satu baris terlebih dahulu untuk memilih titik.');
            }
        });
        
        // Paskan view ke lokasi awal (jika ada)
        setTimeout(updateMapMarkers, 500);
    }
    
    // Pencarian Lokasi
    document.getElementById('btn-map-search').addEventListener('click', doSearch);
    document.getElementById('map-search-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Mencegah form tersubmit
            doSearch();
        }
    });

    function doSearch() {
        const query = document.getElementById('map-search-input').value.trim();
        if (!query) return;

        const status = document.getElementById('map-search-status');
        status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    map.setView([lat, lon], 16);
                    status.innerHTML = `<span class="text-success"><i class="fas fa-check"></i> Ditemukan: ${data[0].display_name}</span>`;
                } else {
                    status.innerHTML = '<span class="text-danger"><i class="fas fa-times"></i> Lokasi tidak ditemukan.</span>';
                }
            })
            .catch(err => {
                status.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Terjadi kesalahan saat mencari.</span>';
            });
    }

    function updateMapMarkers() {
        if (!map) return;
        // Bersihkan marker lama
        markers.forEach(m => map.removeLayer(m));
        circles.forEach(c => map.removeLayer(c));
        markers = [];
        circles = [];
        
        const rows = container.querySelectorAll('.mushola-row');
        let bounds = L.latLngBounds();
        let hasValidCoords = false;
        
        rows.forEach((row, i) => {
            const name = row.querySelector('.map-input-name').value || `Mushola ${i+1}`;
            const lat = parseFloat(row.querySelector('.map-input-lat').value);
            const lng = parseFloat(row.querySelector('.map-input-lng').value);
            const rad = parseFloat(row.querySelector('.map-input-rad').value) || 50;
            
            if (!isNaN(lat) && !isNaN(lng)) {
                hasValidCoords = true;
                const latlng = [lat, lng];
                bounds.extend(latlng);
                
                const marker = L.marker(latlng).addTo(map).bindPopup(`<b>${name}</b>`);
                const circle = L.circle(latlng, {
                    color: '#28a745',
                    fillColor: '#28a745',
                    fillOpacity: 0.2,
                    radius: rad
                }).addTo(map);
                
                markers.push(marker);
                circles.push(circle);
            }
        });
        
        if (hasValidCoords) {
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 18 });
        }
    }
    
    function toggleRemoveButtons() {
        const rows = container.querySelectorAll('.mushola-row');
        rows.forEach(row => {
            const btn = row.querySelector('.btn-remove');
            if (btn) {
                btn.style.display = rows.length > 1 ? 'block' : 'none';
            }
        });
    }

    document.getElementById('btn-add-mushola').addEventListener('click', function() {
        const firstRow = container.querySelector('.mushola-row');
        if (!firstRow) return;
        
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('input').forEach(input => {
            if (input.name !== 'radius[]') input.value = '';
        });
        
        // Bersihkan status aktif
        newRow.classList.remove('bg-light', 'border', 'border-info');
        
        container.appendChild(newRow);
        toggleRemoveButtons();
        
        // Langsung jadikan row baru aktif untuk diisi dari peta
        setActiveRow(newRow);
    });

    container.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove')) {
            e.target.closest('.mushola-row').remove();
            toggleRemoveButtons();
            updateMapMarkers();
        } else if (e.target.closest('.btn-pick')) {
            setActiveRow(e.target.closest('.mushola-row'));
        }
    });
    
    container.addEventListener('input', function(e) {
        if (e.target.classList.contains('map-input-lat') || e.target.classList.contains('map-input-lng') || e.target.classList.contains('map-input-rad') || e.target.classList.contains('map-input-name')) {
            updateMapMarkers();
        }
    });

    function setActiveRow(row) {
        // Hapus styling aktif dari semua row
        container.querySelectorAll('.mushola-row').forEach(r => {
            r.classList.remove('bg-light', 'border-left-info', 'shadow-sm');
            r.style.borderLeft = '';
        });
        
        activeRow = row;
        activeRow.classList.add('bg-light', 'shadow-sm');
        activeRow.style.borderLeft = '4px solid #36b9cc';
        
        alert('Baris terpilih! Silakan klik di atas peta untuk mengatur titik lokasinya.');
    }

    toggleRemoveButtons();
    initMap();
});
</script>
