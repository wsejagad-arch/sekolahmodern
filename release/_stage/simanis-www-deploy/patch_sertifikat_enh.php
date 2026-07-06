<?php
$content = file_get_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php');

// 1. Update backend logic for 'upload'
$searchUpload = <<<EOT
        if (\$act === 'upload') {
            \$folderName = trim(\$_POST['folder_name'] ?? 'Umum');
            if (empty(\$folderName)) \$folderName = 'Umum';
EOT;

$replaceUpload = <<<EOT
        if (\$act === 'upload') {
            \$folderName = trim(\$_POST['folder_name'] ?? 'Umum');
            if (\$folderName === 'other') {
                \$folderName = trim(\$_POST['other_folder_name'] ?? 'Umum');
            }
            if (empty(\$folderName)) \$folderName = 'Umum';
EOT;

$content = str_replace($searchUpload, $replaceUpload, $content);


// 2. Update modal HTML
$searchModal = <<<EOT
                        <label for="folder_dest" class="form-label fw-semibold">Destinasi Folder</label>
                        <select class="form-select" id="folder_dest" name="folder_name">
                            <?php foreach (\$folders as \$f): ?>
                                <option value="<?= htmlspecialchars(\$f) ?>"><?= htmlspecialchars(\$f) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="file_sertifikat" class="form-label fw-semibold">File Berkas (PDF/Gambar)</label>
EOT;

$replaceModal = <<<EOT
                        <label for="folder_dest" class="form-label fw-semibold">Destinasi Folder (Kegiatan)</label>
                        <select class="form-select" id="folder_dest" name="folder_name" onchange="if(this.value==='other') {\$('#other_folder_group').removeClass('d-none'); $('#other_folder_name').prop('required', true);} else {\$('#other_folder_group').addClass('d-none'); $('#other_folder_name').prop('required', false);}">
                            <?php foreach (\$folders as \$f): ?>
                                <option value="<?= htmlspecialchars(\$f) ?>"><?= htmlspecialchars(\$f) ?></option>
                            <?php endforeach; ?>
                            <option value="other">-- Other / Ketik Baru... --</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="other_folder_group">
                        <label for="other_folder_name" class="form-label fw-semibold">Nama Kegiatan / Folder Baru</label>
                        <input type="text" class="form-control" id="other_folder_name" name="other_folder_name" placeholder="Contoh: Bimbingan Teknis Kurikulum Merdeka">
                    </div>
                    <div class="mb-3">
                        <label for="file_sertifikat" class="form-label fw-semibold">File Berkas (PDF/Gambar)</label>
EOT;

$content = str_replace($searchModal, $replaceModal, $content);

file_put_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php', $content);
echo "Sertifikat Enhancement Patched";
?>
