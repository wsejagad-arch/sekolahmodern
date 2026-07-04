<?php
$c = @mysqli_connect('localhost','root','','jurnal');
if (!$c) { echo "CONNERR: " . mysqli_connect_error() . PHP_EOL; exit(1); }
// Show columns
$res = mysqli_query($c, "SHOW COLUMNS FROM tbl_materi");
if (!$res) { echo "SHOW COLUMNS failed: " . mysqli_error($c) . PHP_EOL; }
else {
  echo "COLUMNS:\n";
  while ($r = mysqli_fetch_assoc($res)) { echo "- " . $r['Field'] . " (" . $r['Type'] . ")" . PHP_EOL; }
}
// Show sample rows
$res2 = mysqli_query($c, "SELECT * FROM tbl_materi ORDER BY id_materi DESC LIMIT 10");
if (!$res2) { echo "SELECT rows failed: " . mysqli_error($c) . PHP_EOL; }
else {
  echo "\nSAMPLE ROWS:\n";
  while ($r = mysqli_fetch_assoc($res2)) { print_r($r); }
}
$today = date('Y-m-d');
// Count for today using the actual `tanggal` column
$today = date('Y-m-d');
echo "\nCOUNTS FOR TODAY ($today):\n";
$q = "SELECT COUNT(*) AS cnt FROM tbl_materi WHERE `tanggal` = '$today'";
$r = @mysqli_query($c, $q);
if (!$r) {
  echo "- Query failed: " . mysqli_error($c) . " -> $q\n";
} else {
  $row = mysqli_fetch_assoc($r);
  echo "- cnt = " . ($row['cnt'] ?? 'NULL') . "  Query: $q\n";
}
// Also show any rows that match exactly
$r2 = @mysqli_query($c, "SELECT id_materi,id_mapel,no_induk,nama_mapel,`tanggal` AS `date`,kelas,file_materi FROM tbl_materi WHERE `tanggal` = '$today' ORDER BY id_materi DESC LIMIT 20");
if (!$r2) {
  echo "- SELECT rows failed: " . mysqli_error($c) . "\n";
} else {
  echo "\nROWS FOR TODAY:\n";
  while ($row = mysqli_fetch_assoc($r2)) { print_r($row); }
}

mysqli_close($c);
