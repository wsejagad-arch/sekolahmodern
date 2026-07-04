<?php
include "koneksi.php";

$bulan   = $_GET['bulan'] ?? date("Y-m");
$noInduk = $_GET['no_induk'] ?? "";
?>

<style>
    html, body { height: 100%; }
    /* full width container */
    .page-wrapper { min-height: 100vh; display: flex; flex-direction: column; padding: 1rem; }
    .content { flex: 1 1 auto; display: flex; flex-direction: column; gap: 1rem; }
    .chart-container { position: relative; margin: auto; width: 100%; height: 40vh; min-height: 260px; }
    .table-v-align td { vertical-align: middle; }
    /* make table scrollable within available space */
    .table-scroll { max-height: 45vh; overflow: auto; }
    /* ensure select2 and form controls expand */
    .form-select, .form-control { width: 100%; }
    @media (max-width: 768px) {
        .chart-container { height: 36vh; }
        .table-scroll { max-height: 40vh; }
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="page-wrapper">
<div class="container-fluid">
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h3>Rekap Absensi Siswa</h3>
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="rekap_absen_siswa">
            <div class="col-md-4">
                <label class="form-label font-weight-bold">Pilih Siswa:</label>
                <select name="no_induk" id="pilihSiswa" class="form-select"></select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Bulan:</label>
                <input type="month" name="bulan" class="form-control" value="<?= $bulan ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>
    </div>
</div>
</div>
<div class="content container-fluid">
<?php
if ($noInduk) {
    $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
    $qSiswa = mysqli_query($conn, "SELECT nama_siswa, kelas FROM tbl_siswa WHERE no_induk='$noInduk' AND id_sekolah = $idSekolah LIMIT 1");
    $siswa  = mysqli_fetch_assoc($qSiswa);

    if ($siswa) {
        echo "<div class='card shadow-sm mb-4'><div class='card-body'>";
        echo "<h4>Laporan: {$siswa['nama_siswa']} ({$siswa['kelas']})</h4>";

        // QUERY DENGAN JAM PELAJARAN
        $detail = mysqli_query($conn, "
            SELECT a.no_induk, s.nama_siswa, a.tanggal, ma.nama_mapel, ma.jam_mulai, ma.jam_selesai, g.nama_guru, a.status
            FROM tbl_absen a
            LEFT JOIN tbl_siswa s ON a.no_induk = s.no_induk AND s.id_sekolah = a.id_sekolah
            LEFT JOIN tbl_mapel_ampu ma ON a.id_mapel = ma.id_mapel AND ma.id_sekolah = a.id_sekolah
            LEFT JOIN tbl_guru g ON ma.no_induk = g.no_induk AND g.id_sekolah = a.id_sekolah
            WHERE a.no_induk='$noInduk'
              AND a.id_sekolah = $idSekolah
              AND DATE_FORMAT(a.tanggal, '%Y-%m')='$bulan'
            ORDER BY a.tanggal ASC, ma.jam_mulai ASC
        ");

        if (!$detail) {
            echo "<div class='alert alert-danger'>Error dalam query detail absen: " . mysqli_error($conn) . "</div>";
        } else {
            echo "<div class='table-responsive table-scroll mt-3'>
                <table class='table table-hover table-bordered table-v-align'>
                        <thead class='table-dark text-center'>
                            <tr>
                                <th>No</th>
                                <th>No Induk</th>
                                <th>Nama Siswa</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Mapel</th>
                                <th>Guru</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>";

            if(mysqli_num_rows($detail) > 0){
                $no = 1;
                while($d = mysqli_fetch_assoc($detail)) {
                    $badge = ($d['status'] == 'Alpha') ? 'bg-danger' : (($d['status'] == 'Hadir') ? 'bg-success' : 'bg-warning text-dark');

                    // Format Jam agar lebih rapi (misal 07:00 - 08:30)
                    $jam = (!empty($d['jam_mulai'])) ? date('H:i', strtotime($d['jam_mulai'])) . " - " . date('H:i', strtotime($d['jam_selesai'])) : "-";

                    echo "<tr>
                            <td class='text-center'>{$no}</td>
                            <td class='text-center'>{$d['no_induk']}</td>
                            <td>{$d['nama_siswa']}</td>
                            <td class='text-center'>".date('d/m/Y', strtotime($d['tanggal']))."</td>
                            <td class='text-center small'><strong>$jam</strong></td>
                            <td>{$d['nama_mapel']}</td>
                            <td>{$d['nama_guru']}</td>
                            <td class='text-center'><span class='badge $badge'>{$d['status']}</span></td>
                          </tr>";
                    $no++;
                }
            } else {
                echo "<tr><td colspan='8' class='text-center'>Tidak ada data absen bulan ini.</td></tr>";
            }
            echo "</tbody></table></div>";
        }

        // Ringkasan
        $total = mysqli_query($conn, "SELECT
              SUM(status='Hadir') AS hadir, SUM(status='Ijin') AS ijin,
              SUM(status='Sakit') AS sakit, SUM(status='Dispen') AS dispen,
              SUM(status='Alpha') AS alpha
            FROM tbl_absen WHERE no_induk='$noInduk' AND id_sekolah = $idSekolah AND DATE_FORMAT(tanggal, '%Y-%m')='$bulan'");

        if (!$total) {
            echo "<div class='alert alert-danger mt-2'>Error dalam query ringkasan: " . mysqli_error($conn) . "</div>";
        } else {
            $rekap = mysqli_fetch_assoc($total);
            ?>
            <div class="mt-2 border-top pt-3">
                <strong>Ringkasan Kehadiran:</strong>
                <span class="badge bg-success ms-2">Hadir: <?= $rekap['hadir'] ?? 0 ?></span>
                <span class="badge bg-danger">Alpha: <?= $rekap['alpha'] ?? 0 ?></span>
                <span class="badge bg-info text-dark">Ijin: <?= $rekap['ijin'] ?? 0 ?></span>
                <span class="badge bg-warning text-dark">Sakit: <?= $rekap['sakit'] ?? 0 ?></span>
            </div>

            <!-- Rekap Total Keseluruhan -->
            <?php
            $totalAll = mysqli_query($conn, "SELECT
                  SUM(status='Hadir') AS hadir, SUM(status='Ijin') AS ijin,
                  SUM(status='Sakit') AS sakit, SUM(status='Dispen') AS dispen,
                  SUM(status='Alpha') AS alpha
                FROM tbl_absen WHERE no_induk='$noInduk' AND id_sekolah = $idSekolah");

            if (!$totalAll) {
                echo "<div class='alert alert-danger mt-2'>Error dalam query rekap total: " . mysqli_error($conn) . "</div>";
            } else {
                $rekapAll = mysqli_fetch_assoc($totalAll);
                ?>
                <div class="mt-3 border-top pt-3">
                    <strong>Rekap Total Keseluruhan (Dari Awal Jurnal):</strong>
                    <span class="badge bg-success ms-2">Hadir: <?= $rekapAll['hadir'] ?? 0 ?></span>
                    <span class="badge bg-danger">Alpha: <?= $rekapAll['alpha'] ?? 0 ?></span>
                    <span class="badge bg-info text-dark">Ijin: <?= $rekapAll['ijin'] ?? 0 ?></span>
                    <span class="badge bg-warning text-dark">Sakit: <?= $rekapAll['sakit'] ?? 0 ?></span>
                    <span class="badge bg-secondary text-white">Dispen: <?= $rekapAll['dispen'] ?? 0 ?></span>
                </div>
                <?php
            }
            ?>
            <?php
        }
        echo "</div></div>";
    } else {
        echo "<div class='card shadow-sm mb-4'><div class='card-body'>";
        echo "<div class='alert alert-warning'>Siswa dengan nomor induk '$noInduk' tidak ditemukan.</div>";
        echo "</div></div>";
    }
} else {
    // Tampilkan informasi ketika belum ada siswa yang dipilih
    echo "<div class='card shadow-sm mb-4'><div class='card-body'>";
    echo "<h4 class='text-center'>Rekap Absensi Siswa</h4>";
    echo "<div class='alert alert-info'>";
    echo "<strong>Petunjuk Penggunaan:</strong><br>";
    echo "1. Pilih siswa dari dropdown di atas<br>";
    echo "2. Pilih bulan yang diinginkan<br>";
    echo "3. Klik 'Tampilkan' untuk melihat detail absensi siswa<br>";
    echo "4. Gunakan chart di bawah untuk melihat siswa yang sering alpha";
    echo "</div>";

    $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
    // Cek apakah ada data siswa
    $cekSiswa = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_siswa WHERE status='Aktif' AND id_sekolah = $idSekolah");
    $siswaCount = mysqli_fetch_assoc($cekSiswa)['total'];

    if ($siswaCount == 0) {
        echo "<div class='alert alert-warning'>";
        echo "<strong>Perhatian:</strong> Belum ada data siswa yang aktif dalam sistem.";
        echo "</div>";
    } else {
        // Cek total data absen
        $cekAbsen = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_absen WHERE id_sekolah = $idSekolah");
        $absenCount = mysqli_fetch_assoc($cekAbsen)['total'];

        if ($absenCount == 0) {
            echo "<div class='alert alert-info'>";
            echo "<strong>Info:</strong> Belum ada data absensi yang tercatat.";
            echo "</div>";
        } else {
            echo "<div class='alert alert-success'>";
            echo "<strong>Data tersedia:</strong> {$absenCount} record absensi dari {$siswaCount} siswa aktif.";
            echo "</div>";
        }
    }

    // Tampilkan statistik umum bulan ini
    $statistik = mysqli_query($conn, "SELECT
          COUNT(*) as total_absen,
          SUM(status='Hadir') AS hadir,
          SUM(status='Ijin') AS ijin,
          SUM(status='Sakit') AS sakit,
          SUM(status='Dispen') AS dispen,
          SUM(status='Alpha') AS alpha
        FROM tbl_absen WHERE id_sekolah = $idSekolah AND DATE_FORMAT(tanggal, '%Y-%m')='$bulan'");

    if (!$statistik) {
        echo "<div class='alert alert-danger'>Error dalam query statistik: " . mysqli_error($conn) . "</div>";
    } else if ($row = mysqli_fetch_assoc($statistik)) {
        if ($row['total_absen'] > 0) {
            echo "<div class='row text-center mt-3'>";
            echo "<div class='col-md-2'><div class='card bg-light'><div class='card-body'><h5>Total Absen</h5><h3>{$row['total_absen']}</h3></div></div></div>";
            echo "<div class='col-md-2'><div class='card bg-success text-white'><div class='card-body'><h5>Hadir</h5><h3>{$row['hadir']}</h3></div></div></div>";
            echo "<div class='col-md-2'><div class='card bg-danger text-white'><div class='card-body'><h5>Alpha</h5><h3>{$row['alpha']}</h3></div></div></div>";
            echo "<div class='col-md-2'><div class='card bg-info text-white'><div class='card-body'><h5>Ijin</h5><h3>{$row['ijin']}</h3></div></div></div>";
            echo "<div class='col-md-2'><div class='card bg-warning text-white'><div class='card-body'><h5>Sakit</h5><h3>{$row['sakit']}</h3></div></div></div>";
            echo "<div class='col-md-2'><div class='card bg-secondary text-white'><div class='card-body'><h5>Dispen</h5><h3>{$row['dispen']}</h3></div></div></div>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning mt-3'>";
            echo "<strong>Belum ada data absensi</strong> untuk bulan " . date("F Y", strtotime($bulan."-01")) . ".";
            echo "<br>Silakan pilih bulan lain atau tunggu data absensi diisi.";

            // Cek bulan-bulan yang memiliki data
            $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
            $bulanAdaData = mysqli_query($conn, "SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') as bulan FROM tbl_absen WHERE id_sekolah = $idSekolah ORDER BY bulan DESC LIMIT 6");
            if ($bulanAdaData && mysqli_num_rows($bulanAdaData) > 0) {
                echo "<br><br><strong>Bulan yang memiliki data:</strong><br>";
                while ($b = mysqli_fetch_assoc($bulanAdaData)) {
                    $namaBulan = date("F Y", strtotime($b['bulan']."-01"));
                    echo "<a href='?page=rekap_absen_siswa&bulan={$b['bulan']}' class='badge bg-primary me-1'>{$namaBulan}</a>";
                }
            } else {
                echo "<br><br><strong>Untuk testing:</strong> <a href='tambah_sample_absen.php' class='btn btn-sm btn-success'>Tambah Data Sample</a>";
            }

            echo "</div>";
        }
    }

    echo "</div></div>";
}
?>

<?php
// Query untuk chart top 5 siswa sering alpha
$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$qGrafik = mysqli_query($conn, "
    SELECT s.nama_siswa, COUNT(*) as total_alpha
    FROM tbl_absen a
    JOIN tbl_siswa s ON a.no_induk = s.no_induk AND s.id_sekolah = a.id_sekolah
    WHERE a.status = 'Alpha'
      AND a.id_sekolah = $idSekolah
      AND DATE_FORMAT(a.tanggal, '%Y-%m') = '$bulan'
    GROUP BY s.no_induk, s.nama_siswa
    ORDER BY total_alpha DESC
    LIMIT 5
");
$labels = [];
$values = [];
if ($qGrafik) {
    while ($row = mysqli_fetch_assoc($qGrafik)) {
        $labels[] = $row['nama_siswa'];
        $values[] = (int)$row['total_alpha'];
    }
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h4 class="card-title text-center">Top 5 Siswa Sering Alpha (<?= date("F Y", strtotime($bulan."-01")) ?>)</h4>
        <?php if (empty($labels) || (count($labels) == 1 && $labels[0] == 'Tidak ada data')): ?>
        <div class="alert alert-success text-center">
            <strong>🎉 Bagus!</strong><br>
            Tidak ada siswa yang tercatat alpha pada bulan <?= date("F Y", strtotime($bulan."-01")) ?>.
        </div>
        <?php else: ?>
        <div class="chart-container">
            <canvas id="chartAlpha"></canvas>
        </div>
        <?php endif; ?>
    </div>
</div>
</div> <!-- .content -->
