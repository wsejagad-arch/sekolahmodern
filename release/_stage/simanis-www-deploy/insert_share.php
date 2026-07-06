<?php
$content = file_get_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php');
$toAdd = "\nwindow.sharePerangkatFolder = function(id, nama) {\n" . 
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

$insertPos = strpos($content, '$(document).ready(function() {');
if ($insertPos !== false) {
    $content = substr($content, 0, $insertPos) . $toAdd . "\n" . substr($content, $insertPos);
    file_put_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php', $content);
    echo "Added successfully.";
} else {
    echo "Marker not found.";
}
?>
