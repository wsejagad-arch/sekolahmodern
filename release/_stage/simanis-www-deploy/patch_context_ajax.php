<?php
$content = file_get_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php');

$ajaxAdd = <<<EOT
    if (\$action === 'perangkat_rename_item') {
        \$id = (int)(\$_POST['id'] ?? 0);
        \$nama_baru = mysqli_real_escape_string(\$conn, trim(\$_POST['nama_baru'] ?? ''));
        if (\$id > 0 && !empty(\$nama_baru)) {
            \$q = mysqli_query(\$conn, "UPDATE tbl_ekinerja_dokumen SET nama_file='\$nama_baru' WHERE id=\$id AND no_induk_guru='\$nipEsc'");
            if (\$q) {
                echo json_encode(['status'=>'success']);
            } else {
                echo json_encode(['status'=>'error', 'message'=>mysqli_error(\$conn)]);
            }
        } else {
            echo json_encode(['status'=>'error', 'message'=>'ID atau nama tidak valid']);
        }
        exit;
    }

    if (\$action === 'perangkat_copy_item') {
        \$id = (int)(\$_POST['id'] ?? 0);
        if (\$id > 0) {
            // Get original item
            \$qGet = mysqli_query(\$conn, "SELECT * FROM tbl_ekinerja_dokumen WHERE id=\$id AND no_induk_guru='\$nipEsc'");
            if (\$qGet && mysqli_num_rows(\$qGet) > 0) {
                \$row = mysqli_fetch_assoc(\$qGet);
                \$tipe_dokumen = mysqli_real_escape_string(\$conn, \$row['tipe_dokumen']);
                \$sumber_id = mysqli_real_escape_string(\$conn, \$row['sumber_id']);
                \$nama_file = mysqli_real_escape_string(\$conn, "Copy of " . \$row['nama_file']);
                \$label = mysqli_real_escape_string(\$conn, \$row['label']);
                \$data_json = mysqli_real_escape_string(\$conn, \$row['data_json']);
                
                \$qInsert = mysqli_query(\$conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, label, data_json) VALUES ('\$nipEsc', '\$tipe_dokumen', '\$sumber_id', '\$nama_file', '\$label', '\$data_json')");
                
                if (\$qInsert) {
                    \$newId = mysqli_insert_id(\$conn);
                    // if it's a folder, also copy its files
                    if (\$tipe_dokumen === 'perangkat_folder') {
                        \$qFiles = mysqli_query(\$conn, "SELECT * FROM tbl_ekinerja_dokumen WHERE sumber_id='\$id' AND no_induk_guru='\$nipEsc'");
                        while(\$rf = mysqli_fetch_assoc(\$qFiles)) {
                            \$t_dok = mysqli_real_escape_string(\$conn, \$rf['tipe_dokumen']);
                            \$n_file = mysqli_real_escape_string(\$conn, \$rf['nama_file']);
                            \$lbl = mysqli_real_escape_string(\$conn, \$rf['label']);
                            \$dj = mysqli_real_escape_string(\$conn, \$rf['data_json']);
                            mysqli_query(\$conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, label, data_json) VALUES ('\$nipEsc', '\$t_dok', '\$newId', '\$n_file', '\$lbl', '\$dj')");
                        }
                    }
                    echo json_encode(['status'=>'success']);
                } else {
                    echo json_encode(['status'=>'error', 'message'=>mysqli_error(\$conn)]);
                }
            } else {
                echo json_encode(['status'=>'error', 'message'=>'Item tidak ditemukan']);
            }
        }
        exit;
    }
EOT;

$search = "if (\$action === 'perangkat_delete_file') {";
$content = str_replace($search, $ajaxAdd . "\n\n    " . $search, $content);
file_put_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php', $content);
echo "Added AJAX endpoints for context menu.";
?>
