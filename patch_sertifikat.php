<?php
$content = file_get_contents('c:\xampp\htdocs\jurnal\lihat_berkas.php');

$search = <<<EOT
            if (\$tipeAnak === 'sertifikat') {
                \$qItems = mysqli_query(\$conn, "SELECT DISTINCT folder_name FROM tbl_sertifikat WHERE no_induk_guru='\$nipGuruEsc'");
                if (\$qItems && mysqli_num_rows(\$qItems) > 0) {
                    while (\$f = mysqli_fetch_assoc(\$qItems)) {
                        \$fname = mysqli_real_escape_string(\$conn, \$f['folder_name']);
                        \$qTk = mysqli_query(\$conn, "SELECT token FROM tbl_share_links WHERE no_induk_guru='\$nipGuruEsc' AND tipe_sumber='sertifikat_folder' AND sumber_id='\$fname' LIMIT 1");
                        if(\$qTk && mysqli_num_rows(\$qTk) > 0) {
                            \$tk = mysqli_fetch_assoc(\$qTk)['token'];
                            echo "<a href='?token=" . urlencode(\$tk) . "' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                            echo "<span><i class='bi bi-patch-check text-success me-2'></i> " . htmlspecialchars(\$f['folder_name']) . "</span>";
                            echo "<span class='text-muted small'>Buka <i class='bi bi-arrow-right'></i></span>";
                            echo "</a>";
                        }
                    }
                } else {
                    echo "<div class='text-center text-muted py-4'>Belum ada sertifikat.</div>";
                }
            } else {
EOT;

$replace = <<<EOT
            if (\$tipeAnak === 'sertifikat') {
                if (isset(\$_GET['folder_name'])) {
                    \$fnameEsc = mysqli_real_escape_string(\$conn, \$_GET['folder_name']);
                    echo "<div class='d-flex justify-content-between align-items-center mb-3 no-print'>";
                    echo "<h4><i class='bi bi-folder-fill text-warning'></i> Folder: " . htmlspecialchars(\$_GET['folder_name']) . "</h4>";
                    echo "<a href='?token=" . urlencode(\$token) . "' class='btn btn-sm btn-outline-secondary'><i class='bi bi-arrow-left'></i> Kembali</a>";
                    echo "</div><hr class='no-print'>";
                    echo "<p class='text-muted'>Daftar sertifikat pengembangan kompetensi dalam folder ini:</p>";
                    echo "<div class='list-group mt-3'>";
                    \$qFiles = mysqli_query(\$conn, "SELECT * FROM tbl_sertifikat WHERE no_induk_guru='\$nipGuruEsc' AND folder_name='\$fnameEsc' AND file_name <> '.folder'");
                    if (\$qFiles && mysqli_num_rows(\$qFiles) > 0) {
                        while (\$f = mysqli_fetch_assoc(\$qFiles)) {
                            echo "<a href='" . htmlspecialchars(\$f['file_path']) . "' target='_blank' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                            echo "<span><i class='bi bi-file-earmark-pdf-fill text-danger me-2'></i> " . htmlspecialchars(\$f['file_name']) . "</span>";
                            echo "<span class='text-muted small'>" . \$f['uploaded_at'] . "</span>";
                            echo "</a>";
                        }
                    } else {
                        echo "<div class='text-center text-muted py-4'>Folder ini kosong.</div>";
                    }
                    echo "</div>";
                } else {
                    \$qItems = mysqli_query(\$conn, "SELECT DISTINCT folder_name FROM tbl_sertifikat WHERE no_induk_guru='\$nipGuruEsc'");
                    if (\$qItems && mysqli_num_rows(\$qItems) > 0) {
                        while (\$f = mysqli_fetch_assoc(\$qItems)) {
                            echo "<a href='?token=" . urlencode(\$token) . "&folder_name=" . urlencode(\$f['folder_name']) . "' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>";
                            echo "<span><i class='bi bi-patch-check text-success me-2'></i> " . htmlspecialchars(\$f['folder_name']) . "</span>";
                            echo "<span class='text-muted small'>Buka <i class='bi bi-arrow-right'></i></span>";
                            echo "</a>";
                        }
                    } else {
                        echo "<div class='text-center text-muted py-4'>Belum ada sertifikat.</div>";
                    }
                }
            } else {
EOT;

$content = str_replace($search, $replace, $content);
file_put_contents('c:\xampp\htdocs\jurnal\lihat_berkas.php', $content);
echo "Sertifikat logic patched in lihat_berkas.php";
?>
