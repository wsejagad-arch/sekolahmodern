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
                <p class="text-muted small">Tambahkan koordinat (Latitude & Longitude) mushola sekolah. Siswa harus berada di dalam radius ini untuk bisa mengirim absensi Sholat Dzuhur dan Jumat.</p>
                
                <div id="mushola-container">
                    <?php if (empty($musholas)): ?>
                        <div class="row mb-3 mushola-row">
                            <div class="col-md-3">
                                <label>Nama Tempat</label>
                                <input type="text" name="nama_mushola[]" class="form-control" placeholder="Masjid Raya" required>
                            </div>
                            <div class="col-md-3">
                                <label>Latitude</label>
                                <input type="text" name="lat[]" class="form-control" placeholder="-6.200000" required>
                            </div>
                            <div class="col-md-3">
                                <label>Longitude</label>
                                <input type="text" name="lng[]" class="form-control" placeholder="106.816666" required>
                            </div>
                            <div class="col-md-2">
                                <label>Radius (m)</label>
                                <input type="number" name="radius[]" class="form-control" value="50" required>
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
                                    <input type="text" name="nama_mushola[]" class="form-control" value="<?= htmlspecialchars($m['nama'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label>Latitude</label>
                                    <input type="text" name="lat[]" class="form-control" value="<?= htmlspecialchars($m['lat']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label>Longitude</label>
                                    <input type="text" name="lng[]" class="form-control" value="<?= htmlspecialchars($m['lng']) ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Radius (m)</label>
                                    <input type="number" name="radius[]" class="form-control" value="<?= htmlspecialchars($m['radius'] ?? 50) ?>" required>
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
        container.appendChild(newRow);
        toggleRemoveButtons();
    });

    container.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove')) {
            e.target.closest('.mushola-row').remove();
            toggleRemoveButtons();
        }
    });

    toggleRemoveButtons();
});
</script>
