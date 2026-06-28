<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["no_induk"])) {
    echo "Tidak login";
    exit;
}

include "../../koneksi.php";
$nipguru = $_SESSION['no_induk'];

$sqlguru = mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$nipguru'");
if (mysqli_num_rows($sqlguru) > 0) {
    $dataguru = mysqli_fetch_array($sqlguru);
    echo "<h3>Data Guru</h3>";
    echo "<pre>";
    print_r($dataguru);
    echo "</pre>";

    echo "<h3>Foto:</h3>";
    $fotoProfil = $dataguru['foto_guru'] ?? 'assets/images/default-profile.png';
    echo "Path: " . $fotoProfil . "<br>";
    echo "File exists: " . (file_exists($fotoProfil) ? 'YA' : 'TIDAK') . "<br>";

    echo "<h3>Columns in tbl_guru:</h3>";
    $desc = mysqli_query($conn, "DESCRIBE tbl_guru");
    while ($col = mysqli_fetch_assoc($desc)) {
        echo $col['Field'] . " (" . $col['Type'] . ")<br>";
    }
} else {
    echo "Guru tidak ditemukan";
}
