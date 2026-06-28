<?php
$file = 'c:\Users\sman1\.gemini\antigravity\brain\10f8b2b4-aed8-4bfd-ab88-9f4f8aad0919\task_izin_keluar.md';
$content = file_get_contents($file);
$content = str_replace('- [ ] 3. Update Halaman Persetujuan Wali Kelas', '- [x] 3. Update Halaman Persetujuan Wali Kelas', $content);
$content = str_replace('- [ ] 4. Update Halaman Validasi Satpam', '- [x] 4. Update Halaman Validasi Satpam', $content);
$content = str_replace('- [ ] 5. Integrasi Jurnal Guru', '- [x] 5. Integrasi Jurnal Guru', $content);
file_put_contents($file, $content);
?>
