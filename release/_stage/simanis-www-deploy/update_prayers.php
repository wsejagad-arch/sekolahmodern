<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\jurnal-7kih.php';
$content = file_get_contents($file);

$prayer_old = "<button type=\"button\" class=\"habit-btn <?= \$d ? 'done' : ''; ?> text-left border rounded-xl p-3\"";
$prayer_new = "<button type=\"button\" class=\"habit-btn <?= \$d ? 'done bg-emerald-50/50 border-emerald-300' : 'border-slate-200'; ?> text-left border rounded-xl p-3 transition-colors\"";

$content = str_replace($prayer_old, $prayer_new, $content);
file_put_contents($file, $content);
echo "Updated prayers successfully.";
?>
