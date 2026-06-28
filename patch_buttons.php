<?php
$content = file_get_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php');

$jsToAdd = <<<EOT

// Share Folder Umum
window.shareFolderUmum = function(tipeInduk, labelName) {
    $.post('?ajax=create_share', {
        tipe: tipeInduk + '_umum',
        sumber_id: 'umum',
        label: labelName,
        data_json: ''
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert('Link share publik untuk keseluruhan ' + labelName + ' berhasil disalin ke clipboard!\\n\\n' + path);
        } else {
            alert('Gagal membuat link share');
        }
    }, 'json');
};

EOT;

$insertPos = strpos($content, '$(document).ready(function() {');
$content = substr($content, 0, $insertPos) . $jsToAdd . substr($content, $insertPos);

// Now let's do the UI replacements!
// Perangkat Ajar
$content = str_replace(
    '<h4 class="fw-bold mb-3"><i class="bi bi-file-earmark-code text-teal"></i> Perangkat Pembelajaran Otomatis</h4>',
    '<div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-code text-teal"></i> Perangkat Pembelajaran Otomatis</h4>
                    <button class="btn btn-sm btn-outline-primary" onclick="shareFolderUmum(\'perangkat\', \'Repository Perangkat Ajar\')"><i class="bi bi-share"></i> Share Keseluruhan</button>
                </div>',
    $content
);

// Sertifikat Pelatihan
$content = str_replace(
    '<h4 class="fw-bold mb-0"><i class="bi bi-patch-check text-teal"></i> Sertifikat Pelatihan Guru</h4>',
    '<h4 class="fw-bold mb-0"><i class="bi bi-patch-check text-teal"></i> Sertifikat Pelatihan Guru</h4>
                    <button class="btn btn-sm btn-outline-primary" onclick="shareFolderUmum(\'sertifikat\', \'Sertifikat Pelatihan Guru\')"><i class="bi bi-share"></i> Share Keseluruhan</button>',
    $content
);

// Laporan Wali Kelas
$content = str_replace(
    '<h4 class="fw-bold mb-3"><i class="bi bi-shield-check text-teal"></i> Laporan Analisis &amp; Tindak Lanjut Wali Kelas</h4>',
    '<div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0"><i class="bi bi-shield-check text-teal"></i> Laporan Wali Kelas</h4>
                    <button class="btn btn-sm btn-outline-primary" onclick="shareFolderUmum(\'walikelas\', \'Laporan Wali Kelas\')"><i class="bi bi-share"></i> Share Keseluruhan</button>
                </div>',
    $content
);

// Laporan Ekstra
$content = str_replace(
    '<h4 class="fw-bold mb-3"><i class="bi bi-puzzle text-teal"></i> Laporan Kegiatan Ekstrakurikuler Resmi</h4>',
    '<div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0"><i class="bi bi-puzzle text-teal"></i> Laporan Ekstrakurikuler</h4>
                    <button class="btn btn-sm btn-outline-primary" onclick="shareFolderUmum(\'ekstra\', \'Laporan Ekstrakurikuler\')"><i class="bi bi-share"></i> Share Keseluruhan</button>
                </div>',
    $content
);

// Laporan Supervisi
$content = str_replace(
    '<h4 class="fw-bold mb-3"><i class="bi bi-eye text-teal"></i> Laporan &amp; Ceklis Supervisi Akademik</h4>',
    '<div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0"><i class="bi bi-eye text-teal"></i> Laporan Supervisi Akademik</h4>
                    <button class="btn btn-sm btn-outline-primary" onclick="shareFolderUmum(\'supervisi\', \'Laporan Supervisi\')"><i class="bi bi-share"></i> Share Keseluruhan</button>
                </div>',
    $content
);

file_put_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php', $content);
echo "Buttons added";
?>
