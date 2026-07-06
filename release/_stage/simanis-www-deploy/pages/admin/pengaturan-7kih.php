<?php
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] != 1) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mushola'])) {
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
    $msg = '<div class="alert alert-success">Lokasi mushola berhasil disimpan.</div>';
}

$qVal = @mysqli_query($conn, "SELECT nilai FROM tbl_app_config WHERE kunci='7kih_mushola_locations'");
$musholas = [];
if ($qVal && ($row = mysqli_fetch_assoc($qVal))) {
    $musholas = json_decode($row['nilai'], true) ?: [];
}
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-map-marker-alt text-success"></i> Pengaturan Jurnal 7 KAIH</h1>
    </div>

    <?= $msg ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Lokasi Mushola/Masjid Sekolah (Untuk Validasi Sholat Dzuhur/Jumat)</h6>
        </div>
        <div class="card-body">
            <p class="text-muted small">Tambahkan koordinat (Latitude & Longitude) mushola sekolah. Siswa harus berada di dalam radius ini untuk bisa mengirim jurnal Sholat Dzuhur dan Jumat.</p>
            <form method="POST">
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
                
                <div class="mb-4">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-mushola"><i class="fas fa-plus"></i> Tambah Lokasi</button>
                </div>

                <button type="submit" name="save_mushola" class="btn btn-success"><i class="fas fa-save"></i> Simpan Pengaturan</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('mushola-container');
    
    // Toggle remove button visibility
    function toggleRemoveButtons() {
        const rows = container.querySelectorAll('.mushola-row');
        rows.forEach(row => {
            const btn = row.querySelector('.btn-remove');
            if (btn) {
                btn.style.display = rows.length > 1 ? 'block' : 'none';
            }
        });
    }

    // Add new row
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

    // Remove row
    container.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove')) {
            e.target.closest('.mushola-row').remove();
            toggleRemoveButtons();
        }
    });

    toggleRemoveButtons();
});
</script>
