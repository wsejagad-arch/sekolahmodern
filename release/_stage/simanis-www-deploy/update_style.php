<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\jurnal-7kih.php';
$content = file_get_contents($file);

$style_old = ".habit-btn.done { border-color:#16a34a; background:#f0fdf4; }";
$style_new = ".habit-btn.done, .habit-block.done { border-color:#16a34a; background:#f0fdf4; }";
$content = str_replace($style_old, $style_new, $content);

$div_old = "<div class=\"border rounded-2xl p-3 transition-colors <?= \$isAllPrayersDone ? 'bg-emerald-50/50 border-emerald-300' : 'border-slate-200' ?>\">";
$div_new = "<div class=\"habit-block border rounded-2xl p-3 transition-colors <?= \$isAllPrayersDone ? 'done' : 'border-slate-200' ?>\">";
$content = str_replace($div_old, $div_new, $content);

file_put_contents($file, $content);
echo "Fixed border style for Beribadah.";
?>
