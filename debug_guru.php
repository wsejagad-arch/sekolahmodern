<?php
require 'koneksi.php';
$q = mysqli_query($conn, "SELECT no_induk, nama FROM tbl_guru ORDER BY nama ASC");
echo "<table border='1' cellpadding='5'><tr><th>NIP/No Induk</th><th>Nama di Database</th></tr>";
while($r = mysqli_fetch_assoc($q)) {
    echo "<tr><td>{$r['no_induk']}</td><td>{$r['nama']}</td></tr>";
}
echo "</table>";
