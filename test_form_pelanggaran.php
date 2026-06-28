<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'koneksi.php';

// Setup session untuk testing (ambil guru pertama)
if (!isset($_SESSION['no_induk'])) {
    $guruQuery = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru LIMIT 1");
    if ($guruData = mysqli_fetch_assoc($guruQuery)) {
        $_SESSION['no_induk'] = $guruData['no_induk'];
        $_SESSION['nama'] = $guruData['nama_guru'];
        $_SESSION['hak_akses'] = 2;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Form Pelanggaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>🧪 Test Form Pelanggaran dengan Data Real</h2>
        <hr>
        
        <?php if (isset($_SESSION['no_induk'])): ?>
            <div class="alert alert-success">
                ✅ Login sebagai: <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong> (<?= htmlspecialchars($_SESSION['no_induk']) ?>)
            </div>
        <?php else: ?>
            <div class="alert alert-danger">❌ Session tidak ditemukan!</div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5>Form Test Pelanggaran</h5>
            </div>
            <div class="card-body">
                <!-- Pilih Kelas dan Siswa Real -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Pilih Kelas:</label>
                    <select id="selectKelas" class="form-select">
                        <option value="">-- Pilih Kelas --</option>
                        <?php
                        $kelasQuery = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_siswa ORDER BY kelas");
                        while ($kls = mysqli_fetch_assoc($kelasQuery)) {
                            echo '<option value="' . htmlspecialchars($kls['kelas']) . '">' . htmlspecialchars($kls['kelas']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Pilih Siswa:</label>
                    <select id="selectSiswa" class="form-select" disabled>
                        <option value="">-- Pilih kelas terlebih dahulu --</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Kategori:</label>
                    <select id="kategori" class="form-select">
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Ringan">Ringan</option>
                        <option value="Sedang">Sedang</option>
                        <option value="Berat">Berat</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Jenis Pelanggaran:</label>
                    <input type="text" id="jenis" class="form-control" placeholder="Misal: Terlambat masuk kelas">
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Deskripsi:</label>
                    <textarea id="deskripsi" class="form-control" rows="2" placeholder="Detail pelanggaran..."></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Tindakan:</label>
                    <textarea id="tindakan" class="form-control" rows="2" placeholder="Tindakan yang diambil..."></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Tanggal:</label>
                    <input type="date" id="tanggal" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                
                <button onclick="submitForm()" class="btn btn-danger btn-lg w-100">
                    💾 Simpan Pelanggaran
                </button>
            </div>
        </div>
        
        <div id="result" class="mt-3"></div>
        
        <div class="mt-3">
            <a href=<?= guru_page('guru') ?> class="btn btn-outline-secondary">← Kembali ke Halaman Guru</a>
            <a href="diagnosa_pelanggaran.php" class="btn btn-outline-info">🔍 Lihat Diagnosa</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Load siswa saat kelas dipilih
    document.getElementById('selectKelas').addEventListener('change', function() {
        const kelas = this.value;
        const selectSiswa = document.getElementById('selectSiswa');
        
        if (!kelas) {
            selectSiswa.innerHTML = '<option value="">-- Pilih kelas terlebih dahulu --</option>';
            selectSiswa.disabled = true;
            return;
        }
        
        selectSiswa.innerHTML = '<option value="">⏳ Memuat...</option>';
        selectSiswa.disabled = true;
        
        fetch('get_siswa_by_kelas.php?kelas=' + encodeURIComponent(kelas))
            .then(response => response.json())
            .then(data => {
                if (data.success && data.siswa && data.siswa.length > 0) {
                    selectSiswa.innerHTML = '<option value="">-- Pilih Siswa --</option>';
                    data.siswa.forEach(siswa => {
                        const option = document.createElement('option');
                        option.value = siswa.no_induk;
                        option.textContent = siswa.nama_siswa + ' (' + siswa.no_induk + ')';
                        selectSiswa.appendChild(option);
                    });
                    selectSiswa.disabled = false;
                } else {
                    selectSiswa.innerHTML = '<option value="">-- Tidak ada siswa --</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                selectSiswa.innerHTML = '<option value="">-- Error memuat data --</option>';
            });
    });
    
    function submitForm() {
        const kelas = document.getElementById('selectKelas').value;
        const no_induk = document.getElementById('selectSiswa').value;
        const kategori = document.getElementById('kategori').value;
        const jenis = document.getElementById('jenis').value;
        const deskripsi = document.getElementById('deskripsi').value;
        const tindakan = document.getElementById('tindakan').value;
        const tanggal = document.getElementById('tanggal').value;
        const result = document.getElementById('result');
        
        // Validasi
        if (!kelas || !no_induk || !kategori || !jenis || !tanggal) {
            result.innerHTML = '<div class="alert alert-warning">⚠️ Semua field wajib diisi!</div>';
            return;
        }
        
        result.innerHTML = '<div class="alert alert-info">⏳ Menyimpan...</div>';
        
        // Submit
        const formData = new FormData();
        formData.append('kelas', kelas);
        formData.append('no_induk', no_induk);
        formData.append('kategori_pelanggaran', kategori);
        formData.append('jenis_pelanggaran', jenis);
        formData.append('deskripsi_pelanggaran', deskripsi);
        formData.append('tindakan_guru', tindakan);
        formData.append('tanggal_pelanggaran', tanggal);
        formData.append('status_pelanggaran', 'Aktif');
        
        fetch('simpan_pelanggaran.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                result.innerHTML = '<div class="alert alert-success">✅ <strong>Berhasil!</strong><pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
                // Reset form
                document.getElementById('selectKelas').value = '';
                document.getElementById('selectSiswa').innerHTML = '<option value="">-- Pilih kelas terlebih dahulu --</option>';
                document.getElementById('selectSiswa').disabled = true;
                document.getElementById('kategori').value = '';
                document.getElementById('jenis').value = '';
                document.getElementById('deskripsi').value = '';
                document.getElementById('tindakan').value = '';
            } else {
                result.innerHTML = '<div class="alert alert-danger">❌ <strong>Gagal!</strong><br>' + data.message + '<pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
            }
        })
        .catch(error => {
            result.innerHTML = '<div class="alert alert-danger">❌ <strong>Error:</strong> ' + error.message + '</div>';
        });
    }
    </script>
</body>
</html>
