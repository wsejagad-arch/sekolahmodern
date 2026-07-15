<?php
require_once "bootstrap.php";
$conn = mysqli_connect("localhost", "root", "", "simanis_db");
if (!$conn) die("Connection failed");

$result = mysqli_query($conn, "SELECT id_kelas, kelas FROM tbl_kelas LIMIT 5");
while ($row = mysqli_fetch_assoc($result)) {
    echo "Kelas: " . $row["kelas"] . " (ID: " . $row["id_kelas"] . ")\n";
}

