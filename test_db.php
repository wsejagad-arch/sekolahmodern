<?php
$host = "127.0.0.1";
$port = 3306;
$user = "smasumb1_simanis1";
$password = "W@hyu1234!";
$database = "smasumb1_simanis";

$conn = new mysqli($host, $user, $password, $database, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$r = mysqli_query($conn, "SHOW TABLES");
while($row = mysqli_fetch_row($r)) {
    echo $row[0] . "\n";
}
?>
