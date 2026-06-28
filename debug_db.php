<?php require "koneksi.php"; $q=mysqli_query($conn, "SHOW CREATE TABLE tbl_izin_siswa"); $r=mysqli_fetch_row($q); echo $r[1];
