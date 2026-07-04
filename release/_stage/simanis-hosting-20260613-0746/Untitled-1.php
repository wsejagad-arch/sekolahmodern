<?php
$koneksi = mysqli_connect("localhost", "username", "password", "nama_database");
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}