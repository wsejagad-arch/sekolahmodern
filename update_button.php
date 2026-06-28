<?php
$content = file_get_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php');

$search = "onclick=\"deletePerangkatFolder(\${f.id}, '\${f.nama}')\"><i class=\"bi bi-trash\"></i> Hapus Folder</button>";
$replace = "onclick=\"sharePerangkatFolder(\${f.id}, '\${f.nama}')\"><i class=\"bi bi-share\"></i> Share Folder</button>\n                                        <button class=\"btn btn-sm btn-outline-danger\" onclick=\"deletePerangkatFolder(\${f.id}, '\${f.nama}')\"><i class=\"bi bi-trash\"></i> Hapus Folder</button>";

$content = str_replace($search, $replace, $content);
file_put_contents('c:\xampp\htdocs\jurnal\pages\guru\ekinerja.php', $content);
echo "Button added.";
?>
