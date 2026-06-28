<?php
$file = 'c:\xampp\htdocs\jurnal\pages\admin\cek-nilai.php';
$content = file_get_contents($file);

// Find the start of the data rendering section
$search = '<?php if (mysqli_num_rows($pertemuan) === 0) { ?>';
$replace = '<?php 
  $isFiltered = ($tanggal !== \'\' || $kelas !== \'\' || $idmapel > 0);
  if (!$isFiltered) {
?>
  <div class="alert alert-secondary">Silakan pilih filter (Tanggal, Kelas, atau Mapel) terlebih dahulu untuk menampilkan data nilai.</div>
<?php } else { ?>
  <?php if (mysqli_num_rows($pertemuan) === 0) { ?>';

$content = str_replace($search, $replace, $content);

// Find the end of the while loop to close the else block
$searchEnd = '<?php } // end while ?>';
$replaceEnd = '<?php } // end while ?>
<?php } // end if isFiltered ?>';

$content = str_replace($searchEnd, $replaceEnd, $content);

file_put_contents($file, $content);
echo "cek-nilai.php updated";
?>
