<?php
$host='127.0.0.1'; $user='root'; $pass=''; $db='sijurnal'; $port=3306;
$mysqli = @mysqli_connect($host, $user, $pass, $db, $port);
if ($mysqli) { echo "DIRECT OK\n"; mysqli_close($mysqli); } else { echo "DIRECT FAIL: ".mysqli_connect_error()."\n"; }
?>