<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'koneksi.php';

// Cek koneksi database
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Query untuk mengambil semua data dari tbl_mapel_ampu
$query = "SELECT * FROM tbl_mapel_ampu ORDER BY no_induk, hari, jam_mulai";
$result = mysqli_query($conn, $query);

// Hitung total records
$total_records = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Tabel tbl_mapel_ampu</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-responsive {
            margin-top: 20px;
        }
        .summary-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .debug-info {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h2 class="mt-4 mb-4">📊 Data Tabel tbl_mapel_ampu</h2>

                <!-- Debug Information -->
                <div class="debug-info">
                    <strong>Debug Information:</strong><br>
                    • Database: jurnal<br>
                    • Table: tbl_mapel_ampu<br>
                    • Total Records: <?php echo $total_records; ?><br>
                    • Query: <?php echo htmlspecialchars($query); ?><br>
                    • Connection Status: <?php echo $conn ? 'Connected' : 'Disconnected'; ?>
                </div>

                <!-- Summary Card -->
                <div class="summary-card">
                    <h5>📈 Ringkasan Data</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Total Jadwal:</strong> <?php echo $total_records; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Status Query:</strong>
                            <span class="badge <?php echo $result ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $result ? 'Success' : 'Failed'; ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <strong>Last Update:</strong> <?php echo date('d/m/Y H:i:s'); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>PHP Version:</strong> <?php echo phpversion(); ?>
                        </div>
                    </div>
                </div>

                <?php if ($result && $total_records > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>ID Mapel</th>
                                    <th>No Induk Guru</th>
                                    <th>Nama Mapel</th>
                                    <th>Kelas</th>
                                    <th>Hari</th>
                                    <th>Jam Mulai</th>
                                    <th>Jam Selesai</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($result)):
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['id_mapel'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['no_induk'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_mapel'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['kelas'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['hari'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['jam_mulai'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['jam_selesai'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge badge-success">Aktif</span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Additional Statistics -->
                    <div class="summary-card">
                        <h5>📊 Statistik Detail</h5>
                        <?php
                        // Reset result pointer
                        mysqli_data_seek($result, 0);

                        // Hitung statistik
                        $guru_count = [];
                        $mapel_count = [];
                        $kelas_count = [];
                        $hari_count = [];

                        while ($row = mysqli_fetch_assoc($result)) {
                            $guru_count[$row['no_induk']] = ($guru_count[$row['no_induk']] ?? 0) + 1;
                            $mapel_count[$row['nama_mapel']] = ($mapel_count[$row['nama_mapel']] ?? 0) + 1;
                            $kelas_count[$row['kelas']] = ($kelas_count[$row['kelas']] ?? 0) + 1;
                            $hari_count[$row['hari']] = ($hari_count[$row['hari']] ?? 0) + 1;
                        }
                        ?>

                        <div class="row">
                            <div class="col-md-3">
                                <strong>Jumlah Guru:</strong><br>
                                <?php echo count($guru_count); ?> guru
                            </div>
                            <div class="col-md-3">
                                <strong>Jumlah Mapel:</strong><br>
                                <?php echo count($mapel_count); ?> mata pelajaran
                            </div>
                            <div class="col-md-3">
                                <strong>Jumlah Kelas:</strong><br>
                                <?php echo count($kelas_count); ?> kelas
                            </div>
                            <div class="col-md-3">
                                <strong>Jumlah Hari:</strong><br>
                                <?php echo count($hari_count); ?> hari
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Detail per Guru:</strong><br>
                                <small>
                                <?php foreach ($guru_count as $nip => $count): ?>
                                    • <?php echo $nip; ?>: <?php echo $count; ?> jadwal<br>
                                <?php endforeach; ?>
                                </small>
                            </div>
                            <div class="col-md-6">
                                <strong>Detail per Hari:</strong><br>
                                <small>
                                <?php foreach ($hari_count as $hari => $count): ?>
                                    • <?php echo $hari; ?>: <?php echo $count; ?> jadwal<br>
                                <?php endforeach; ?>
                                </small>
                            </div>
                        </div>
                    </div>

                <?php elseif ($result && $total_records == 0): ?>
                    <div class="alert alert-warning">
                        <h4>⚠️ Tidak Ada Data</h4>
                        <p>Tabel <code>tbl_mapel_ampu</code> masih kosong. Tidak ada jadwal mengajar yang terdaftar.</p>
                        <p><strong>Solusi:</strong> Tambahkan data jadwal mengajar melalui menu "Tambah Mapel Guru" atau import data.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <h4>❌ Error Query Database</h4>
                        <p>Terjadi kesalahan saat mengambil data dari database:</p>
                        <code><?php echo mysqli_error($conn); ?></code>
                        <br><br>
                        <p><strong>Debug Info:</strong></p>
                        <ul>
                            <li>Pastikan tabel <code>tbl_mapel_ampu</code> sudah ada di database</li>
                            <li>Periksa koneksi database di file <code>koneksi.php</code></li>
                            <li>Pastikan MySQL service sedang berjalan</li>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Export Options -->
                <div class="summary-card">
                    <h5>💾 Opsi Export</h5>
                    <div class="btn-group">
                        <button class="btn btn-primary" onclick="window.print()">🖨️ Print</button>
                        <button class="btn btn-success" onclick="exportToCSV()">📊 Export CSV</button>
                        <button class="btn btn-info" onclick="exportToJSON()">📋 Export JSON</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.js"></script>
    <script>
        function exportToCSV() {
            const table = document.querySelector('table');
            let csv = [];

            // Header
            const headers = [];
            table.querySelectorAll('thead th').forEach(th => {
                headers.push(th.textContent);
            });
            csv.push(headers.join(','));

            // Data
            table.querySelectorAll('tbody tr').forEach(tr => {
                const row = [];
                tr.querySelectorAll('td').forEach(td => {
                    row.push('"' + td.textContent.replace(/"/g, '""') + '"');
                });
                csv.push(row.join(','));
            });

            // Download
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'tbl_mapel_ampu_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function exportToJSON() {
            const table = document.querySelector('table');
            const data = [];

            table.querySelectorAll('tbody tr').forEach(tr => {
                const row = {};
                const tds = tr.querySelectorAll('td');
                row.no = tds[0].textContent;
                row.id_mapel = tds[1].textContent;
                row.no_induk = tds[2].textContent;
                row.nama_mapel = tds[3].textContent;
                row.kelas = tds[4].textContent;
                row.hari = tds[5].textContent;
                row.jam_mulai = tds[6].textContent;
                row.jam_selesai = tds[7].textContent;
                data.push(row);
            });

            const jsonContent = JSON.stringify(data, null, 2);
            const blob = new Blob([jsonContent], { type: 'application/json' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'tbl_mapel_ampu_' + new Date().toISOString().split('T')[0] + '.json';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>

<?php
// Tutup koneksi
if ($result) {
    mysqli_free_result($result);
}
mysqli_close($conn);
?>