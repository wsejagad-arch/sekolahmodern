<?php
$file = 'c:\xampp\htdocs\jurnal\detail-profil-siswa.php';
$content = file_get_contents($file);

// 1. Remove max-width: 1180px
$content = str_replace("max-width: 1180px;", "/* max-width removed */", $content);

// 2. Make edit mode always true
$content = str_replace('$editMode = ((string) ($_GET[\'edit\'] ?? \'\') === \'1\');', '$editMode = true;', $content);

// 3. Fix the closing tags at the bottom. We need to remove the two closing divs for #content and #content-wrapper before footer.php.
// The end looks like:
/*
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
*/
// We'll replace the two outer closing divs before footer.php
$content = preg_replace("/\s*<\/div>\s*<\/div>\s*<\?php include 'footer\.php'; \?>/", "\n<?php include 'footer.php'; ?>", $content);

// 4. Also, maybe remove the "Tutup Mode Edit" button since it's always edit mode now.
$content = preg_replace("/<a href=\"detail-profil-siswa\.php\?no_induk=.*?class=\"btn btn-sm btn-outline-secondary rounded-pill\">\s*<i class=\"fas fa-eye me-1\"><\/i> Tutup Mode Edit\s*<\/a>/s", "", $content);

file_put_contents($file, $content);
echo "Done";
?>
