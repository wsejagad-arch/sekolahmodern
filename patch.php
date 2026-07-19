<?php
$conn = mysqli_connect("localhost", "root", "", "jurnal");
if(!$conn) { echo "Failed connect"; exit; }
$res = mysqli_query($conn, "ALTER TABLE tbl_materi ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
if($res) echo "OK"; else echo mysqli_error($conn);
