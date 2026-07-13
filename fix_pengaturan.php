<?php
require 'koneksi.php';
$q = mysqli_query($conn, "UPDATE tbl_pengaturan SET id_sekolah=1 WHERE kunci='izin_edit_profil' AND (id_sekolah IS NULL OR id_sekolah=0)");
echo "Updated: " . mysqli_affected_rows($conn) . " rows";
?>
