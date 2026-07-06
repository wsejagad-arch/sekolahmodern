<?php
$file = 'c:\Users\sman1\.gemini\antigravity\brain\10f8b2b4-aed8-4bfd-ab88-9f4f8aad0919\task_refactor_izin.md';
$content = file_get_contents($file);
$content = str_replace("- `[/]` Implement 'Auto-Alpa' cron logic in `koneksi.php` or `auth_helper.php`", "- `[x]` Implement 'Auto-Alpa' cron logic in `koneksi.php`", $content);
$content = str_replace("- `[/]` In `satpam.php`, update logic so clicking 'Sudah Kembali'", "- `[x]` In `satpam.php`, update logic so clicking 'Sudah Kembali'", $content);
$content = str_replace("- `[ ]` Ensure Satpam approval accurately inserts 'I' to `tbl_absen`.", "- `[x]` Ensure Satpam approval accurately inserts 'I' to `tbl_absen`.", $content);
$content = str_replace("- `[ ]` Add UI feedback (blinking/pulsing) to notification dot", "- `[x]` Add UI feedback (blinking/pulsing) to notification dot", $content);
$content = str_replace("- `[ ]` Fix the logic in `walikelas.php` and BK validation", "- `[x]` Fix the logic in `validasi-izin.php` for Wali Kelas and BK validation", $content);
file_put_contents($file, $content);
echo "Updated task list.\n";
?>
