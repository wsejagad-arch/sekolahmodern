<?php
// lihat_berkas.php
// Public read-only viewer for shared E-Kinerja files.
require_once __DIR__ . '/koneksi.php';

$token = trim($_GET['token'] ?? '');
if (empty($token)) {
    http_response_code(400);
    die("<div style='font-family:sans-serif;padding:30px;text-align:center;'><h3>Token tidak valid atau kosong.</h3></div>");
}

$tokenEsc = mysqli_real_escape_string($conn, $token);
$q = mysqli_query($conn, "SELECT * FROM tbl_share_links WHERE token='$tokenEsc' LIMIT 1");

if (!$q || mysqli_num_rows($q) === 0) {
    http_response_code(404);
    die("<div style='font-family:sans-serif;padding:30px;text-align:center;'><h3>Tautan tidak ditemukan atau telah kedaluwarsa.</h3></div>");
}

$share = mysqli_fetch_assoc($q);
$tipe = $share['tipe_sumber'];
$sumber_id = $share['sumber_id'];
$label = $share['sumber_label'];
$data = json_decode($share['data_json'], true);

// Fetch teacher name
$nipGuru = $share['no_induk_guru'];
$nipGuruEsc = mysqli_real_escape_string($conn, $nipGuru);
$qGuru = mysqli_query($conn, "SELECT nama_guru FROM tbl_guru WHERE no_induk='$nipGuruEsc' LIMIT 1");
$rowGuru = mysqli_fetch_assoc($qGuru);
$namaGuru = $rowGuru['nama_guru'] ?? 'Guru';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($label) ?> - E-Kinerja Publik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            padding: 40px 15px;
        }
        .viewer-card {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
            padding: 30px;
        }
        .badge-read-only {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .kop-surat h2 {
            margin: 0;
            font-weight: 800;
            font-size: 20px;
            text-transform: uppercase;
        }
        .kop-surat p {
            margin: 3px 0;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            border: 1px solid #000;
            padding: 6px 10px;
            font-size: 12px;
        }
        table th {
            background-color: #f1f5f9;
        }
        .signature-block {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-col {
            text-align: center;
            width: 45%;
            font-size: 13px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .viewer-card {
                border: none;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>

<div class="viewer-card">
    <!-- Read Only Banner -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom no-print">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-file-earmark-lock-fill text-teal"></i> Lihat Dokumen Publik</h5>
            <p class="text-muted small mb-0">Dibagikan oleh: <strong><?= htmlspecialchars($namaGuru) ?></strong> (NIP: <?= htmlspecialchars($nipGuru) ?>)</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="if(window.history.length > 1 && document.referrer) { window.history.back(); } else { window.close(); }"><i class="bi bi-arrow-left"></i> Kembali</button>
            <span class="badge badge-read-only d-flex align-items-center gap-1"><i class="bi bi-eye"></i> Hanya Lihat</span>
            <button class="btn btn-sm btn-outline-primary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
        </div>
    </div>

    <!-- RENDER DYNAMIC CONTENT BASED ON TYPE -->
    <?php if ($tipe === 'sertifikat_folder'): ?>
        <h4><i class="bi bi-folder-fill text-warning"></i> Folder: <?= htmlspecialchars($sumber_id) ?></h4>
        <p class="text-muted">Daftar sertifikat pengembangan kompetensi dalam folder ini:</p>
        <div class="list-group mt-3">
            <?php
            $folderEsc = mysqli_real_escape_string($conn, $sumber_id);
            $qFiles = mysqli_query($conn, "SELECT * FROM tbl_sertifikat WHERE no_induk_guru='$nipGuruEsc' AND folder_name='$folderEsc' AND file_name <> '.folder'");
            if ($qFiles && mysqli_num_rows($qFiles) > 0):
                while ($f = mysqli_fetch_assoc($qFiles)):
            ?>
                <a href="<?= htmlspecialchars($f['file_path']) ?>" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i> <?= htmlspecialchars($f['file_name']) ?></span>
                    <span class="text-muted small"><?= $f['uploaded_at'] ?></span>
                </a>
            <?php 
                endwhile;
            else:
            ?>
                <div class="text-center text-muted py-4">Folder ini kosong atau belum memiliki file terunggah.</div>
            <?php endif; ?>
        </div>

    <?php elseif ($tipe === 'perangkat_file'): ?>
        <?php
        $idFile = (int)$sumber_id;
        $qFile = mysqli_query($conn, "SELECT data_json FROM tbl_ekinerja_dokumen WHERE id=$idFile LIMIT 1");
        if ($qFile && mysqli_num_rows($qFile) > 0) {
            $rowFile = mysqli_fetch_assoc($qFile);
            $fileData = json_decode($rowFile['data_json'], true);
            echo "<div class='document-content'>" . $fileData['htmlContent'] . "</div>";
        } else {
            echo "<p class='text-danger'>File tidak ditemukan.</p>";
        }
        ?>

    <?php elseif ($tipe === 'perangkat_folder'): ?>
        <?php
        $folderId = (int)$sumber_id;
        $fileId = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
        
        if ($fileId > 0) {
            // View specific file in folder
            $qFile = mysqli_query($conn, "SELECT nama_file, data_json FROM tbl_ekinerja_dokumen WHERE id=$fileId AND sumber_id='$folderId' LIMIT 1");
            if ($qFile && mysqli_num_rows($qFile) > 0) {
                $rowFile = mysqli_fetch_assoc($qFile);
                $fileData = json_decode($rowFile['data_json'], true);
                echo "<div class='d-flex justify-content-between align-items-center mb-3 no-print'>";
                echo "<h4><i class='bi bi-file-earmark-text text-primary'></i> " . htmlspecialchars($rowFile['nama_file']) . "</h4>";
                echo "<a href='?token=" . urlencode($token) . "' class='btn btn-sm btn-outline-secondary'><i class='bi bi-arrow-left'></i> Kembali ke Folder</a>";
                echo "</div><hr class='no-print'>";
                echo "<div class='document-content'>" . $fileData['htmlContent'] . "</div>";
            } else {
                echo "<p class='text-danger'>File tidak ditemukan dalam folder ini.</p>";
            }
        } else {
            // List files
            echo "<h4><i class='bi bi-folder-fill text-warning'></i> " . htmlspecialchars($label) . "</h4>";
            echo "<p class='text-muted'>Daftar perangkat ajar dalam folder ini:</p>";
            echo "<div class='list-group mt-3'>";
            
            $qFiles = mysqli_query($conn, "SELECT id, nama_file, tipe_dokumen, created_at FROM tbl_ekinerja_dokumen WHERE sumber_id='$folderId' AND tipe_dokumen IN ('modul', 'atp') ORDER BY created_at DESC");
            if ($qFiles && mysqli_num_rows($qFiles) > 0) {
                while ($f = mysqli_fetch_assoc($qFiles)) {
                    $icon = $f['tipe_dokumen'] === 'modul' ? 'bi-file-earmark-text text-primary' : 'bi-diagram-3 text-success';
                    echo "<a href='?token=" . urlencode($token) . "&file_id=" . $f['id'] . "' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                    echo "<span><i class='bi $icon me-2'></i> " . htmlspecialchars($f['nama_file']) . "</span>";
                    echo "<span class='text-muted small'>Buka <i class='bi bi-arrow-right'></i></span>";
                    echo "</a>";
                }
            } else {
                echo "<div class='text-center text-muted py-4'>Folder ini kosong.</div>";
            }
            echo "</div>";
        }
        ?>

    <?php elseif (!empty($data['htmlContent'])): ?>
        <!-- Output the generated document HTML cache directly -->
        <div class="document-content">
            <?= $data['htmlContent'] ?>
        </div>

    <?php elseif (str_ends_with($tipe, '_umum')): ?>
        <?php
        $tipeAnak = str_replace('_umum', '', $tipe);
        
        // Handle nested view for Perangkat Umum (folders -> files)
        if ($tipeAnak === 'perangkat') {
            $folderId = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : 0;
            $fileId = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
            
            if ($fileId > 0) {
                // View specific file
                $qFile = mysqli_query($conn, "SELECT nama_file, data_json FROM tbl_ekinerja_dokumen WHERE id=$fileId AND no_induk_guru='$nipGuruEsc' LIMIT 1");
                if ($qFile && mysqli_num_rows($qFile) > 0) {
                    $rowFile = mysqli_fetch_assoc($qFile);
                    $fileData = json_decode($rowFile['data_json'], true);
                    echo "<div class='d-flex justify-content-between align-items-center mb-3 no-print'>";
                    echo "<h4><i class='bi bi-file-earmark-text text-primary'></i> " . htmlspecialchars($rowFile['nama_file']) . "</h4>";
                    echo "<a href='?token=" . urlencode($token) . "&folder_id=" . $folderId . "' class='btn btn-sm btn-outline-secondary'><i class='bi bi-arrow-left'></i> Kembali ke Folder</a>";
                    echo "</div><hr class='no-print'>";
                    echo "<div class='document-content'>" . $fileData['htmlContent'] . "</div>";
                } else {
                    echo "<p class='text-danger'>File tidak ditemukan.</p>";
                }
            } elseif ($folderId > 0) {
                // View specific folder contents
                $qF = mysqli_query($conn, "SELECT nama_file FROM tbl_ekinerja_dokumen WHERE id=$folderId AND no_induk_guru='$nipGuruEsc' LIMIT 1");
                $namaFolder = ($qF && mysqli_num_rows($qF) > 0) ? mysqli_fetch_assoc($qF)['nama_file'] : 'Folder';
                
                echo "<div class='d-flex justify-content-between align-items-center mb-3 no-print'>";
                echo "<h4><i class='bi bi-folder-fill text-warning'></i> " . htmlspecialchars($namaFolder) . "</h4>";
                echo "<a href='?token=" . urlencode($token) . "' class='btn btn-sm btn-outline-secondary'><i class='bi bi-arrow-left'></i> Kembali</a>";
                echo "</div><hr class='no-print'>";
                echo "<p class='text-muted'>Daftar perangkat ajar dalam folder ini:</p>";
                echo "<div class='list-group mt-3'>";
                
                $qFiles = mysqli_query($conn, "SELECT id, nama_file, tipe_dokumen FROM tbl_ekinerja_dokumen WHERE sumber_id='$folderId' AND tipe_dokumen IN ('modul', 'atp') ORDER BY created_at DESC");
                if ($qFiles && mysqli_num_rows($qFiles) > 0) {
                    while ($f = mysqli_fetch_assoc($qFiles)) {
                        $icon = $f['tipe_dokumen'] === 'modul' ? 'bi-file-earmark-text text-primary' : 'bi-diagram-3 text-success';
                        echo "<a href='?token=" . urlencode($token) . "&folder_id=$folderId&file_id=" . $f['id'] . "' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                        echo "<span><i class='bi $icon me-2'></i> " . htmlspecialchars($f['nama_file']) . "</span>";
                        echo "<span class='text-muted small'>Buka <i class='bi bi-arrow-right'></i></span>";
                        echo "</a>";
                    }
                } else {
                    echo "<div class='text-center text-muted py-4'>Folder ini kosong.</div>";
                }
                echo "</div>";
            } else {
                // List all folders
                echo "<h4><i class='bi bi-folder-fill text-primary'></i> " . htmlspecialchars($label) . "</h4>";
                echo "<p class='text-muted'>Daftar folder perangkat pembelajaran yang telah dibuat oleh <strong>" . htmlspecialchars($namaGuru) . "</strong>:</p>";
                echo "<div class='list-group mt-3'>";
                
                $qItems = mysqli_query($conn, "SELECT id, nama_file FROM tbl_ekinerja_dokumen WHERE no_induk_guru='$nipGuruEsc' AND tipe_dokumen='perangkat_folder' ORDER BY nama_file ASC");
                if ($qItems && mysqli_num_rows($qItems) > 0) {
                    while ($f = mysqli_fetch_assoc($qItems)) {
                        echo "<a href='?token=" . urlencode($token) . "&folder_id=" . $f['id'] . "' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                        echo "<span><i class='bi bi-folder text-warning me-2'></i> " . htmlspecialchars($f['nama_file']) . "</span>";
                        echo "<span class='text-muted small'>Buka Folder <i class='bi bi-arrow-right'></i></span>";
                        echo "</a>";
                    }
                } else {
                    echo "<div class='text-center text-muted py-4'>Belum ada folder.</div>";
                }
                echo "</div>";
            }
        } else {
            // General handler for Sertifikat, Wali Kelas, Ekstra, Supervisi
            echo "<h4><i class='bi bi-folder-fill text-primary'></i> " . htmlspecialchars($label) . "</h4>";
            echo "<p class='text-muted'>Daftar berkas yang telah dibuat oleh <strong>" . htmlspecialchars($namaGuru) . "</strong>:</p>";
            echo "<div class='list-group mt-3'>";
            
            if ($tipeAnak === 'sertifikat') {
                if (isset($_GET['folder_name'])) {
                    $fnameEsc = mysqli_real_escape_string($conn, $_GET['folder_name']);
                    echo "<div class='d-flex justify-content-between align-items-center mb-3 no-print'>";
                    echo "<h4><i class='bi bi-folder-fill text-warning'></i> Folder: " . htmlspecialchars($_GET['folder_name']) . "</h4>";
                    echo "<a href='?token=" . urlencode($token) . "' class='btn btn-sm btn-outline-secondary'><i class='bi bi-arrow-left'></i> Kembali</a>";
                    echo "</div><hr class='no-print'>";
                    echo "<p class='text-muted'>Daftar sertifikat pengembangan kompetensi dalam folder ini:</p>";
                    echo "<div class='list-group mt-3'>";
                    $qFiles = mysqli_query($conn, "SELECT * FROM tbl_sertifikat WHERE no_induk_guru='$nipGuruEsc' AND folder_name='$fnameEsc' AND file_name <> '.folder'");
                    if ($qFiles && mysqli_num_rows($qFiles) > 0) {
                        while ($f = mysqli_fetch_assoc($qFiles)) {
                            echo "<a href='" . htmlspecialchars($f['file_path']) . "' target='_blank' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                            echo "<span><i class='bi bi-file-earmark-pdf-fill text-danger me-2'></i> " . htmlspecialchars($f['file_name']) . "</span>";
                            echo "<span class='text-muted small'>" . $f['uploaded_at'] . "</span>";
                            echo "</a>";
                        }
                    } else {
                        echo "<div class='text-center text-muted py-4'>Folder ini kosong.</div>";
                    }
                    echo "</div>";
                } else {
                    $qItems = mysqli_query($conn, "SELECT DISTINCT folder_name FROM tbl_sertifikat WHERE no_induk_guru='$nipGuruEsc'");
                    if ($qItems && mysqli_num_rows($qItems) > 0) {
                        while ($f = mysqli_fetch_assoc($qItems)) {
                            echo "<a href='?token=" . urlencode($token) . "&folder_name=" . urlencode($f['folder_name']) . "' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                            echo "<span><i class='bi bi-patch-check text-success me-2'></i> " . htmlspecialchars($f['folder_name']) . "</span>";
                            echo "<span class='text-muted small'>Buka <i class='bi bi-arrow-right'></i></span>";
                            echo "</a>";
                        }
                    } else {
                        echo "<div class='text-center text-muted py-4'>Belum ada sertifikat.</div>";
                    }
                }
            } else {
                $qItems = mysqli_query($conn, "SELECT * FROM tbl_share_links WHERE no_induk_guru='$nipGuruEsc' AND tipe_sumber='$tipeAnak' ORDER BY id DESC");
                if ($qItems && mysqli_num_rows($qItems) > 0) {
                    while ($f = mysqli_fetch_assoc($qItems)) {
                        echo "<a href='?token=" . urlencode($f['token']) . "' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                        echo "<span><i class='bi bi-file-earmark-pdf-fill text-danger me-2'></i> " . htmlspecialchars($f['sumber_label']) . "</span>";
                        echo "<span class='text-muted small'>Lihat Dokumen <i class='bi bi-box-arrow-up-right'></i></span>";
                        echo "</a>";
                    }
                } else {
                    echo "<div class='text-center text-muted py-4'>Belum ada dokumen.</div>";
                }
            }
            echo "</div>";
        }
        ?>

    <?php elseif ($tipe === 'jurnal_tahun'): ?>
        <h4><i class="bi bi-folder-fill text-primary"></i> <?= htmlspecialchars($label) ?></h4>
        <p class="text-muted">Daftar jurnal mengajar bulanan yang telah dibuat oleh <strong><?= htmlspecialchars($namaGuru) ?></strong>:</p>
        <div class="list-group mt-3">
            <?php
            $tahunEsc = mysqli_real_escape_string($conn, $sumber_id);
            // Fetch all month links for this teacher and this year
            $qJurnalFolder = mysqli_query($conn, "SELECT * FROM tbl_share_links WHERE no_induk_guru='$nipGuruEsc' AND tipe_sumber='jurnal' AND sumber_id LIKE '$tahunEsc-%' ORDER BY sumber_id ASC");
            if ($qJurnalFolder && mysqli_num_rows($qJurnalFolder) > 0):
                while ($f = mysqli_fetch_assoc($qJurnalFolder)):
            ?>
                <a href="?token=<?= htmlspecialchars($f['token']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i> <?= htmlspecialchars($f['sumber_label']) ?>.pdf</span>
                    <span class="text-muted small">Lihat Dokumen <i class="bi bi-box-arrow-up-right"></i></span>
                </a>
            <?php 
                endwhile;
            else:
            ?>
                <div class="text-center text-muted py-4">Folder ini kosong atau jurnal belum di-generate.</div>
            <?php endif; ?>
        </div>

    <?php elseif ($tipe === 'jurnal'): ?>
        <!-- If it is Jurnal type and does not have cached content, render standard monthly journals layout -->
        <?php
        $parts = explode('-', $sumber_id);
        $tahun = $parts[0] ?? date('Y');
        $bulan = $parts[1] ?? date('m');
        $namaBulan = $indonesianMonths[$bulan] ?? 'Bulan';
        ?>
        <div class="kop-surat">
            <h2>REKAPITULASI JURNAL HARIAN MENGAJAR</h2>
            <h2>SMA NEGERI 1 SUMBER</h2>
            <p>Jl. Raya Sumber No. 123, Sumber, Probolinggo</p>
        </div>
        
        <div class="mb-4">
            <table style="border:none !important; width:100%;">
                <tr style="border:none !important;"><td style="border:none !important; width:150px;">Nama Guru</td><td style="border:none !important; width:10px;">:</td><td style="border:none !important;"><strong><?= htmlspecialchars($namaGuru) ?></strong></td></tr>
                <tr style="border:none !important;"><td style="border:none !important;">NIP/No Induk</td><td style="border:none !important;">:</td><td style="border:none !important;"><?= htmlspecialchars($nipGuru) ?></td></tr>
                <tr style="border:none !important;"><td style="border:none !important;">Periode Bulan</td><td style="border:none !important;">:</td><td style="border:none !important;"><?= $namaBulan ?> <?= $tahun ?></td></tr>
            </table>
        </div>
        
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Tanggal</th>
                    <th width="10%">Kelas</th>
                    <th width="20%">Mata Pelajaran</th>
                    <th width="30%">Materi Pokok</th>
                    <th width="20%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td>02/<?= $bulan ?>/<?= $tahun ?></td>
                    <td>X-A</td>
                    <td>Kimia/Fisika</td>
                    <td>Materi Pengenalan KBM Semester Genap</td>
                    <td>Berjalan Lancar</td>
                </tr>
                <tr>
                    <td class="text-center">2</td>
                    <td>09/<?= $bulan ?>/<?= $tahun ?></td>
                    <td>X-A</td>
                    <td>Kimia/Fisika</td>
                    <td>Struktur Atom dan Konfigurasi Elektron</td>
                    <td>Latihan soal mandiri</td>
                </tr>
            </tbody>
        </table>
        
        <div class="signature-block">
            <div class="signature-col">
                <p>Mengetahui,</p>
                <p><strong>Kepala Sekolah</strong></p>
                <br><br><br>
                <p>_________________________</p>
            </div>
            <div class="signature-col">
                <p>Sumber, <?= date('d') ?> <?= $namaBulan ?> <?= $tahun ?></p>
                <p><strong>Guru Mata Pelajaran</strong></p>
                <br><br><br>
                <p><strong><u><?= htmlspecialchars($namaGuru) ?></u></strong></p>
                <p>NIP. <?= htmlspecialchars($nipGuru) ?></p>
            </div>
        </div>
        
    <?php else: ?>
        <div class="alert alert-warning">Tipe berkas tidak didukung atau format data tidak dapat ditampilkan.</div>
    <?php endif; ?>
</div>

</body>
</html>
