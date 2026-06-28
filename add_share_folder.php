<?php
$content = file_get_contents('c:\xampp\htdocs\jurnal\js_drive_patch.js');
$content .= "\nwindow.sharePerangkatFolder = function(id, nama) {\n" . 
"    $.post('?ajax=create_share', {\n" .
"        tipe: 'perangkat_folder',\n" .
"        sumber_id: id,\n" .
"        label: 'Folder ' + nama,\n" .
"        data_json: ''\n" .
"    }, function(res) {\n" .
"        if(res.status === 'success') {\n" .
"            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;\n" .
"            navigator.clipboard.writeText(path);\n" .
"            alert('Link share publik untuk folder berhasil disalin ke clipboard!\\n\\n' + path);\n" .
"        } else {\n" .
"            alert('Gagal membuat link share');\n" .
"        }\n" .
"    }, 'json');\n" .
"};\n";
file_put_contents('c:\xampp\htdocs\jurnal\js_drive_patch.js', $content);
echo "Added";
?>
