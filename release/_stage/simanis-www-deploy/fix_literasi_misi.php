<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\literasi_misi.php';
$content = file_get_contents($file);

$old_html = '<div class="container d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1 font-weight-bold" style="letter-spacing: 0.5px;"><?= htmlspecialchars($tugas[\'judul\']) ?></h5>
            <p class="mb-0" style="font-size: 0.9rem; color: rgba(255,255,255,0.9);"><?= htmlspecialchars($tugas[\'deskripsi\']) ?></p>
        </div>
        <a href="literasi.php" class="btn-tutup"><i class="far fa-times-circle"></i> Tutup</a>
    </div>';

$new_html = '<div class="container d-flex flex-wrap justify-content-between align-items-center">
        <div class="mr-2 mb-2 mb-md-0" style="flex: 1; min-width: 200px;">
            <h5 class="mb-1 font-weight-bold" style="letter-spacing: 0.5px;"><?= htmlspecialchars($tugas[\'judul\']) ?></h5>
            <p class="mb-0" style="font-size: 0.9rem; color: rgba(255,255,255,0.9);"><?= htmlspecialchars($tugas[\'deskripsi\']) ?></p>
        </div>
        <a href="literasi.php" class="btn-tutup mt-2 mt-md-0" style="white-space: nowrap; flex-shrink: 0;"><i class="far fa-times-circle"></i> Tutup</a>
    </div>';

$content = str_replace($old_html, $new_html, $content);
file_put_contents($file, $content);
echo "Fixed overlap in literasi_misi.php";
?>
