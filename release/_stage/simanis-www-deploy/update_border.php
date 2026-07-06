<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\jurnal-7kih.php';
$content = file_get_contents($file);

$parent_old = "<div class=\"border border-slate-200 rounded-2xl p-3 <?= \$isAllPrayersDone ? 'bg-emerald-50/50' : '' ?>\">";
$parent_new = "<div class=\"border rounded-2xl p-3 transition-colors <?= \$isAllPrayersDone ? 'bg-emerald-50/50 border-emerald-300' : 'border-slate-200' ?>\">";

$content = str_replace($parent_old, $parent_new, $content);

$btn_old = "<button type=\"button\" class=\"habit-btn <?= \$d ? 'done bg-emerald-50/50' : ''; ?> <?= \$disabled ?> w-full text-left border border-slate-200 rounded-2xl p-3 flex gap-3 items-start transition-colors\" data-habit=\"<?= kihs_h(\$key); ?>\" data-prayer=\"\" data-title=\"<?= kihs_h(\$habit['label']); ?>\">";
$btn_new = "<button type=\"button\" class=\"habit-btn <?= \$d ? 'done bg-emerald-50/50 border-emerald-300' : 'border-slate-200'; ?> <?= \$disabled ?> w-full text-left border rounded-2xl p-3 flex gap-3 items-start transition-colors\" data-habit=\"<?= kihs_h(\$key); ?>\" data-prayer=\"\" data-title=\"<?= kihs_h(\$habit['label']); ?>\">";

$content = str_replace($btn_old, $btn_new, $content);

file_put_contents($file, $content);
echo "Updated borders successfully.";
?>
