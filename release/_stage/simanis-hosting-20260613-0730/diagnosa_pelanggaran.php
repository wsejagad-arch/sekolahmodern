<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnosa Pelanggaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2><i class="bi bi-bug"></i> Diagnosa Sistem Pelanggaran</h2>
        <hr>

        <?php
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once 'koneksi.php';

        // Setup session untuk testing (jika belum login)
        if (!isset($_SESSION['no_induk'])) {
            // Ambil guru pertama dari database untuk testing
            $guruQuery = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru LIMIT 1");
            if ($guruData = mysqli_fetch_assoc($guruQuery)) {
                $_SESSION['no_induk'] = $guruData['no_induk'];
                $_SESSION['nama'] = $guruData['nama_guru'];
                $_SESSION['hak_akses'] = 2; // Guru
                echo '<div class="alert alert-info"><i class="bi bi-info-circle"></i> Session testing dibuat: Login sebagai <strong>' . htmlspecialchars($guruData['nama_guru']) . '</strong></div>';
            }
        } else {
            echo '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Session aktif: Login sebagai <strong>' . htmlspecialchars($_SESSION['nama'] ?? 'Unknown') . '</strong></div>';
        }

        // 1. Cek koneksi database
        echo '<div class="card mb-3">';
        echo '<div class="card-header bg-primary text-white"><strong>1. Status Koneksi Database</strong></div>';
        echo '<div class="card-body">';
        if ($conn === null || !($conn instanceof mysqli)) {
            echo '<div class="alert alert-danger">❌ Koneksi database GAGAL!</div>';
            echo '<p>Pastikan MySQL di XAMPP sudah berjalan dan database "sijurnal" sudah dibuat.</p>';
            echo '</div></div>';
            exit;
        } else {
            echo '<div class="alert alert-success">✅ Koneksi database BERHASIL</div>';
            echo '<p>Server: ' . $conn->get_server_info() . '</p>';
        }
        echo '</div></div>';

        // 2. Cek tabel tbl_pelanggaran
        echo '<div class="card mb-3">';
        echo '<div class="card-header bg-info text-white"><strong>2. Status Tabel tbl_pelanggaran</strong></div>';
        echo '<div class="card-body">';
        $check = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pelanggaran'");
        if (mysqli_num_rows($check) == 0) {
            echo '<div class="alert alert-danger">❌ Tabel <strong>tbl_pelanggaran</strong> BELUM ADA di database!</div>';
            echo '<a href="setup_pelanggaran.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Buat Tabel Sekarang</a>';
            echo '</div></div>';
            exit;
        } else {
            echo '<div class="alert alert-success">✅ Tabel <strong>tbl_pelanggaran</strong> sudah ada</div>';

            // Struktur tabel
            $columns = mysqli_query($conn, "DESCRIBE tbl_pelanggaran");
            echo '<h6>Struktur Tabel:</h6>';
            echo '<table class="table table-sm table-bordered">';
            echo '<thead class="table-dark"><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr></thead><tbody>';
            while ($col = mysqli_fetch_assoc($columns)) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div></div>';

        // 3. Jumlah data
        echo '<div class="card mb-3">';
        echo '<div class="card-header bg-success text-white"><strong>3. Data Pelanggaran</strong></div>';
        echo '<div class="card-body">';
        $count = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_pelanggaran");
        $countData = mysqli_fetch_assoc($count);
        echo '<p>Total record: <strong>' . $countData['total'] . '</strong></p>';

        if ($countData['total'] > 0) {
            echo '<h6>5 Data Terakhir:</h6>';
            $data = mysqli_query($conn, "SELECT * FROM tbl_pelanggaran ORDER BY created_at DESC LIMIT 5");
            echo '<div class="table-responsive">';
            echo '<table class="table table-sm table-striped">';
            echo '<thead class="table-primary"><tr><th>ID</th><th>Nama Siswa</th><th>Kelas</th><th>Kategori</th><th>Jenis</th><th>Tanggal</th><th>Status</th></tr></thead><tbody>';
            while ($row = mysqli_fetch_assoc($data)) {
                echo '<tr>';
                echo '<td>' . $row['id_pelanggaran'] . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_siswa']) . '</td>';
                echo '<td>' . htmlspecialchars($row['kelas']) . '</td>';
                echo '<td><span class="badge bg-' .
                    ($row['kategori_pelanggaran'] == 'Berat' ? 'danger' : ($row['kategori_pelanggaran'] == 'Sedang' ? 'warning' : 'info')) . '">' .
                    $row['kategori_pelanggaran'] . '</span></td>';
                echo '<td>' . htmlspecialchars($row['jenis_pelanggaran']) . '</td>';
                echo '<td>' . date('d/m/Y', strtotime($row['tanggal_pelanggaran'])) . '</td>';
                echo '<td>' . htmlspecialchars($row['status_pelanggaran']) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<div class="alert alert-warning">Belum ada data pelanggaran yang tercatat.</div>';
        }
        echo '</div></div>';

        // 4. Status file
        echo '<div class="card mb-3">';
        echo '<div class="card-header bg-warning"><strong>4. Status File System</strong></div>';
        echo '<div class="card-body">';
        $files = [
            'simpan_pelanggaran.php' => __DIR__ . '/simpan_pelanggaran.php',
            'pages/guru/guru.php' => __DIR__ . '/pages/guru/guru.php'
        ];
        foreach ($files as $name => $path) {
            if (file_exists($path)) {
                echo '<p class="text-success">✅ <code>' . $name . '</code> - Ditemukan</p>';
            } else {
                echo '<p class="text-danger">❌ <code>' . $name . '</code> - TIDAK ditemukan</p>';
            }
        }
        echo '</div></div>';

        // 5. Test API
        echo '<div class="card mb-3">';
        echo '<div class="card-header bg-secondary text-white"><strong>5. Test API Endpoint</strong></div>';
        echo '<div class="card-body">';
        echo '<button onclick="testAPI()" class="btn btn-primary"><i class="bi bi-play-circle"></i> Test Simpan Pelanggaran</button>';
        echo '<div id="apiResult" class="mt-3"></div>';
        echo '</div></div>';

        mysqli_close($conn);
        ?>

        <div class="text-center mt-4">
            <a href=<?= guru_page('guru') ?> class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Kembali ke Halaman Guru</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testAPI() {
            const result = document.getElementById('apiResult');
            result.innerHTML = '<div class="alert alert-info">⏳ Testing API...</div>';

            // Test dengan data dummy (siswa tidak ada, hanya untuk test endpoint)
            const formData = new FormData();
            formData.append('kelas', 'TEST');
            formData.append('no_induk', '99999');
            formData.append('kategori_pelanggaran', 'Ringan');
            formData.append('jenis_pelanggaran', 'Test API');
            formData.append('deskripsi_pelanggaran', 'Test deskripsi');
            formData.append('tindakan_guru', 'Test tindakan');
            formData.append('tanggal_pelanggaran', '2026-01-16');
            formData.append('status_pelanggaran', 'Aktif');

            fetch('simpan_pelanggaran.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        result.innerHTML = '<div class="alert alert-success">✅ <strong>API Berhasil!</strong><pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
                    } else {
                        result.innerHTML = '<div class="alert alert-warning">⚠️ <strong>Response dari API:</strong><pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
                    }
                })
                .catch(error => {
                    result.innerHTML = '<div class="alert alert-danger">❌ <strong>Error:</strong> ' + error.message + '</div>';
                });
        }
    </script>
</body>

</html>