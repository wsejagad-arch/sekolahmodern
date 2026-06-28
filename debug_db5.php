<?php require "koneksi.php"; $q=mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE kelas LIKE '%XI F%'"); while($r=mysqli_fetch_assoc($q)) echo $r['kelas']." ";
