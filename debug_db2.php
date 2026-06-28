<?php require "koneksi.php"; $res = mysqli_query($conn, "SELECT * FROM tbl_izin_siswa ORDER BY id_izin DESC LIMIT 5"); while($r = mysqli_fetch_assoc($res)) { echo json_encode($r) . "\n"; }
