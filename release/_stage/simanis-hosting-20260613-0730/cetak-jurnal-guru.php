<?php
if (!isset($_SESSION["username"])) {
    header("location: index.php?haruslogin");
    exit;
} else if ($hakakses != 1) { ?>
  <script>window.location='404.html';</script>
<?php }

include "koneksi.php";

// Inisialisasi variabel filter
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : date('Y-m-d');
$filter_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$filter_guru = isset($_GET['guru']) ? $_GET['guru'] : '';

// Ambil data untuk dropdown
$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$query_kelas = "SELECT DISTINCT kelas FROM tbl_kelas WHERE id_sekolah = $idSekolah ORDER BY kelas";
$result_kelas = mysqli_query($conn, $query_kelas);

$query_guru = "SELECT id_guru, nama_guru FROM tbl_guru WHERE id_sekolah = $idSekolah ORDER BY nama_guru";
$result_guru = mysqli_query($conn, $query_guru);

// Query untuk jurnal dengan filter
$data_jurnal = [];
$total_data = 0;

if (isset($_GET['tampilkan']) || isset($_GET['export_pdf'])) {
    $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
    $where_conditions = ["1=1", "m.id_sekolah = $idSekolah"];
    
    // Filter tanggal
    if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) {
        // tbl_materi menggunakan kolom `tanggal`
        $where_conditions[] = "m.tanggal BETWEEN '" . mysqli_real_escape_string($conn, $tanggal_mulai) . "' AND '" . mysqli_real_escape_string($conn, $tanggal_selesai) . "'";
    }
    
    // Filter kelas
    if (!empty($filter_kelas)) {
        $kelas_escaped = mysqli_real_escape_string($conn, $filter_kelas);
        $where_conditions[] = "m.kelas = '$kelas_escaped'";
    }
    
    // Filter guru
    if (!empty($filter_guru)) {
        $guru_escaped = mysqli_real_escape_string($conn, $filter_guru);
        $where_conditions[] = "g.id_guru = '$guru_escaped'";
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $query_jurnal = "
        SELECT 
            m.id_materi AS id_jurnal,
            m.`tanggal` AS tanggal,
            m.kelas      AS kelas,
            m.nama_mapel AS mata_pelajaran,
            m.materi     AS materi,
            m.kegiatan   AS kegiatan,
            -- Mapping: tidak ada kolom kendala/solusi di tbl_materi standar
            m.keterangan AS kendala,
            ''           AS solusi,
            ma.jam_mulai,
            g.nama_guru,
            COALESCE(g.nip_guru, g.no_induk) AS nip
        FROM tbl_materi m
        LEFT JOIN tbl_mapel_ampu ma ON m.id_mapel = ma.id_mapel
        LEFT JOIN tbl_guru g ON m.no_induk = g.no_induk
        WHERE $where_clause
        ORDER BY m.`tanggal` DESC, ma.jam_mulai ASC
    ";
    
    $result_jurnal = mysqli_query($conn, $query_jurnal);
    if ($result_jurnal) {
        while ($row = mysqli_fetch_assoc($result_jurnal)) {
            $data_jurnal[] = $row;
            $total_data++;
        }
    } else {
        // Tampilkan alasan error pada developer console agar mudah dilacak
        echo "<script>console.error('Query jurnal gagal: " . addslashes(mysqli_error($conn)) . "');</script>";
    }
}

// Export PDF
if (isset($_GET['export_pdf']) && !empty($data_jurnal)) {
    // Include simple PDF library
    include_once 'lib/SimplePDF.php';
    
    // Get guru name for filter
    $nama_guru_filter = 'Semua Guru';
    if ($filter_guru) {
        $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
        $guru_info = mysqli_query($conn, "SELECT nama_guru FROM tbl_guru WHERE id_guru = '$filter_guru' AND id_sekolah = $idSekolah");
        if ($guru_info && mysqli_num_rows($guru_info) > 0) {
            $nama_guru_filter = mysqli_fetch_assoc($guru_info)['nama_guru'];
        }
    }
    
    // Membuat HTML untuk PDF
    $html_content = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Laporan Jurnal Guru</title>
    </head>
    <body>
        <div class="header">
            <h2>LAPORAN JURNAL GURU</h2>
            <h3>SMA NEGERI 1 SUMBER</h3>
            <p>Jl. Raya Sumber No. 123, Sumber, Probolinggo</p>
        </div>
        
        <div class="info">
            <table style="border: none; width: 100%;">
                <tr style="border: none;">
                    <td style="border: none; width: 20%;"><strong>Periode</strong></td>
                    <td style="border: none; width: 3%;">:</td>
                    <td style="border: none;">' . date('d/m/Y', strtotime($tanggal_mulai)) . ' s/d ' . date('d/m/Y', strtotime($tanggal_selesai)) . '</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none;"><strong>Kelas</strong></td>
                    <td style="border: none;">:</td>
                    <td style="border: none;">' . ($filter_kelas ? $filter_kelas : 'Semua Kelas') . '</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none;"><strong>Guru</strong></td>
                    <td style="border: none;">:</td>
                    <td style="border: none;">' . $nama_guru_filter . '</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none;"><strong>Total Data</strong></td>
                    <td style="border: none;">:</td>
                    <td style="border: none;">' . $total_data . ' jurnal</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none;"><strong>Dicetak</strong></td>
                    <td style="border: none;">:</td>
                    <td style="border: none;">' . date('d/m/Y H:i:s') . '</td>
                </tr>
            </table>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th width="4%">No</th>
                    <th width="8%">Tanggal</th>
                    <th width="12%">Guru</th>
                    <th width="6%">Kelas</th>
                    <th width="10%">Mata Pelajaran</th>
                    <th width="25%">Materi</th>
                    <th width="15%">Kegiatan</th>
                    <th width="10%">Kendala</th>
                    <th width="10%">Solusi</th>
                </tr>
            </thead>
            <tbody>';
    
    $no = 1;
    foreach ($data_jurnal as $jurnal) {
        $html_content .= '
                <tr>
                    <td class="text-center">' . $no++ . '</td>
                    <td>' . date('d/m/Y', strtotime($jurnal['tanggal'])) . '</td>
                    <td class="small">' . htmlspecialchars($jurnal['nama_guru']) . '<br><span style="font-size: 8px;">NIP: ' . htmlspecialchars($jurnal['nip']) . '</span></td>
                    <td class="text-center">' . htmlspecialchars($jurnal['kelas']) . '</td>
                    <td class="small">' . htmlspecialchars($jurnal['mata_pelajaran']) . '</td>
                    <td class="small">' . htmlspecialchars(substr($jurnal['materi'], 0, 200)) . (strlen($jurnal['materi']) > 200 ? '...' : '') . '</td>
                    <td class="small">' . htmlspecialchars(substr($jurnal['kegiatan'], 0, 150)) . (strlen($jurnal['kegiatan']) > 150 ? '...' : '') . '</td>
                    <td class="small">' . htmlspecialchars(substr($jurnal['kendala'], 0, 100)) . (strlen($jurnal['kendala']) > 100 ? '...' : '') . '</td>
                    <td class="small">' . htmlspecialchars(substr($jurnal['solusi'], 0, 100)) . (strlen($jurnal['solusi']) > 100 ? '...' : '') . '</td>
                </tr>';
    }
    
    $html_content .= '
            </tbody>
        </table>
        
        <div class="signature">
            <p>Sumber, ' . date('d F Y') . '</p>
            <br><br><br>
            <p>
                <strong>Kepala Sekolah</strong><br>
                <br><br><br>
                <u>_________________________</u><br>
                NIP. 
            </p>
        </div>
        
        <div class="no-print">
            <button onclick="window.print()" class="btn">🖨️ Print ke PDF</button>
            <button onclick="window.close()" class="btn">❌ Tutup</button>
        </div>
    </body>
    </html>';
    
    // Generate PDF
    echo SimplePDF::generatePDF($html_content, 'Laporan_Jurnal_Guru_' . date('Y-m-d') . '.pdf');
    exit;
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">📊 Cetak Jurnal Guru</h1>
    <div>
        <a href="?page=cetak-jurnal-guru&<?= http_build_query($_GET) ?>&export_pdf=1" 
           class="btn btn-danger btn-sm <?= empty($data_jurnal) ? 'disabled' : '' ?>" 
           <?= empty($data_jurnal) ? 'disabled' : '' ?>>
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
    </div>
</div>

<!-- Filter Form -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">🔍 Filter Laporan</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <input type="hidden" name="page" value="cetak-jurnal-guru">
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="tanggal_mulai">📅 Tanggal Mulai:</label>
                        <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" 
                               value="<?= htmlspecialchars($tanggal_mulai) ?>" required>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="tanggal_selesai">📅 Tanggal Selesai:</label>
                        <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" 
                               value="<?= htmlspecialchars($tanggal_selesai) ?>" required>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="kelas">🏫 Kelas:</label>
                        <select class="form-control" id="kelas" name="kelas">
                            <option value="">-- Semua Kelas --</option>
                            <?php if ($result_kelas): ?>
                                <?php while ($kelas = mysqli_fetch_assoc($result_kelas)): ?>
                                    <option value="<?= htmlspecialchars($kelas['kelas']) ?>" 
                                            <?= $filter_kelas == $kelas['kelas'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kelas['kelas']) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="guru">👨‍🏫 Guru:</label>
                        <select class="form-control" id="guru" name="guru">
                            <option value="">-- Semua Guru --</option>
                            <?php if ($result_guru): ?>
                                <?php while ($guru = mysqli_fetch_assoc($result_guru)): ?>
                                    <option value="<?= htmlspecialchars($guru['id_guru']) ?>" 
                                            <?= $filter_guru == $guru['id_guru'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($guru['nama_guru']) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <button type="submit" name="tampilkan" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tampilkan Data
                    </button>
                    <button type="reset" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-undo"></i> Reset Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (isset($_GET['tampilkan'])): ?>
<!-- Hasil Filter -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">📋 Hasil Laporan Jurnal</h6>
        <div>
            <span class="badge badge-info">Total: <?= $total_data ?> jurnal</span>
        </div>
    </div>
    <div class="card-body">
        
        <!-- Info Laporan -->
        <div class="alert alert-info">
            <strong>📊 Detail Laporan:</strong><br>
            <i class="fas fa-calendar"></i> <strong>Periode:</strong> <?= date('d/m/Y', strtotime($tanggal_mulai)) ?> - <?= date('d/m/Y', strtotime($tanggal_selesai)) ?><br>
            <i class="fas fa-school"></i> <strong>Kelas:</strong> <?= $filter_kelas ? $filter_kelas : 'Semua Kelas' ?><br>
            <i class="fas fa-user"></i> <strong>Guru:</strong> 
            <?php 
            if ($filter_guru) {
                $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
                $guru_info = mysqli_query($conn, "SELECT nama_guru FROM tbl_guru WHERE id_guru = '$filter_guru' AND id_sekolah = $idSekolah");
                if ($guru_info && mysqli_num_rows($guru_info) > 0) {
                    echo mysqli_fetch_assoc($guru_info)['nama_guru'];
                } else {
                    echo 'Guru tidak ditemukan';
                }
            } else {
                echo 'Semua Guru';
            }
            ?>
        </div>
        
        <?php if (!empty($data_jurnal)): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable">
                <thead class="thead-light">
                    <tr>
                        <th width="3%">No</th>
                        <th width="8%">Tanggal</th>
                        <th width="12%">Guru</th>
                        <th width="8%">Kelas</th>
                        <th width="12%">Mata Pelajaran</th>
                        <th width="20%">Materi</th>
                        <th width="15%">Kegiatan</th>
                        <th width="12%">Kendala</th>
                        <th width="10%">Solusi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($data_jurnal as $jurnal): 
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= date('d/m/Y', strtotime($jurnal['tanggal'])) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($jurnal['nama_guru']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($jurnal['nip']) ?></small>
                        </td>
                        <td><span class="badge badge-primary"><?= htmlspecialchars($jurnal['kelas']) ?></span></td>
                        <td><?= htmlspecialchars($jurnal['mata_pelajaran']) ?></td>
                        <td>
                            <div style="max-width: 200px; max-height: 60px; overflow-y: auto;">
                                <?= htmlspecialchars($jurnal['materi']) ?>
                            </div>
                        </td>
                        <td>
                            <div style="max-width: 150px; max-height: 60px; overflow-y: auto;">
                                <?= htmlspecialchars($jurnal['kegiatan']) ?>
                            </div>
                        </td>
                        <td>
                            <div style="max-width: 120px; max-height: 60px; overflow-y: auto;">
                                <?= htmlspecialchars($jurnal['kendala']) ?>
                            </div>
                        </td>
                        <td>
                            <div style="max-width: 120px; max-height: 60px; overflow-y: auto;">
                                <?= htmlspecialchars($jurnal['solusi']) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Action Buttons -->
        <div class="mt-3 text-center">
            <a href="?page=cetak-jurnal-guru&<?= http_build_query($_GET) ?>&export_pdf=1" 
               class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Export ke PDF
            </a>
            <button type="button" class="btn btn-success" onclick="window.print()">
                <i class="fas fa-print"></i> Print Halaman
            </button>
        </div>
        
        <?php else: ?>
        <div class="alert alert-warning text-center">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Data tidak ditemukan!</strong><br>
            Tidak ada jurnal yang sesuai dengan filter yang dipilih.
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

</div>
<!-- /.container-fluid -->

<script>
function resetForm() {
    document.getElementById('tanggal_mulai').value = '<?= date('Y-m-01') ?>';
    document.getElementById('tanggal_selesai').value = '<?= date('Y-m-d') ?>';
    document.getElementById('kelas').value = '';
    document.getElementById('guru').value = '';
}

// Auto submit form saat filter berubah (optional)
document.addEventListener('DOMContentLoaded', function() {
    const filters = ['kelas', 'guru'];
    filters.forEach(function(filterId) {
        document.getElementById(filterId).addEventListener('change', function() {
            // Auto submit bisa diaktifkan jika diinginkan
            // document.querySelector('form').submit();
        });
    });
});

// DataTable initialization jika ada data
<?php if (!empty($data_jurnal)): ?>
$(document).ready(function() {
    $('#dataTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
        },
        "pageLength": 25,
        "ordering": true,
        "searching": true,
        "responsive": true
    });
});
<?php endif; ?>
</script>

<style>
@media print {
    .btn, .card-header, .alert-info { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>