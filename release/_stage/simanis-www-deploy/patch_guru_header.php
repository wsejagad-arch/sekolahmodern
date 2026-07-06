<?php
$file = 'c:\xampp\htdocs\jurnal\pages\guru\guru_header.php';
$content = file_get_contents($file);

$logic = <<<PHP
<?php
\$is_wali_kelas_or_bk = false;
\$nip_check = \$_SESSION['username'] ?? '';
if (!empty(\$nip_check) && isset(\$conn)) {
    \$qWk = mysqli_query(\$conn, "SELECT id_kelas FROM tbl_kelas WHERE nip_wali = '\$nip_check' LIMIT 1");
    if (\$qWk && mysqli_num_rows(\$qWk) > 0) {
        \$is_wali_kelas_or_bk = true;
    } else {
        \$qBk = mysqli_query(\$conn, "SELECT id_guru FROM tbl_guru WHERE no_induk = '\$nip_check' AND (jabatan LIKE '%BK%' OR tugas_tambahan LIKE '%BK%') LIMIT 1");
        if (\$qBk && mysqli_num_rows(\$qBk) > 0) {
            \$is_wali_kelas_or_bk = true;
        }
    }
}
?>
PHP;

$content = str_replace('<body class="modern-theme <?= $guruLayoutVisible ? \'\' : \'layout-hidden\' ?>">', '<body class="modern-theme <?= $guruLayoutVisible ? \'\' : \'layout-hidden\' ?>">' . "\n" . $logic, $content);

$old_link = '<a href="<?= guru_nav_url(\'validasi-izin\'); ?>" class="sidebar-link"><i class="bi bi-patch-check"></i> <span>Validasi Izin</span></a>';
$new_link = '<?php if($is_wali_kelas_or_bk): ?><a href="<?= guru_nav_url(\'validasi-izin\'); ?>" class="sidebar-link"><i class="bi bi-patch-check"></i> <span>Validasi Izin</span></a><?php endif; ?>';
$content = str_replace($old_link, $new_link, $content);

file_put_contents($file, $content);
echo "Updated guru_header.php\n";
?>
