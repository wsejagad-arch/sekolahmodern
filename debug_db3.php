<?php require "koneksi.php"; $res = mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas FROM tbl_siswa WHERE no_induk='5607'"); print_r(mysqli_fetch_assoc($res));
