<?php
$file = 'c:\xampp\htdocs\jurnal\pages\guru\inputtugas.php';
$content = file_get_contents($file);

$old = '<div class="form-group">
                        <label for="tanggal_pengumpulan" class="form-label">Tanggal Pengumpulan</label>
                        <input type="date" class="form-control" id="tanggal_pengumpulan" name="tanggal_pengumpulan" min="<?= date(\'Y-m-d\') ?>">
                    </div>';

$new = '<div class="form-group">
                        <label for="batas_waktu" class="form-label">Batas Waktu Pengerjaan (Deadline)</label>
                        <input type="datetime-local" class="form-control" id="batas_waktu" name="batas_waktu" required>
                        <small class="text-muted">Siswa yang mengumpulkan setelah batas waktu ini akan ditandai terlambat.</small>
                    </div>';

if(strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    file_put_contents($file, $content);
    echo "Updated inputtugas.php UI\n";
} else {
    echo "Failed to replace in inputtugas.php UI\n";
}
?>
