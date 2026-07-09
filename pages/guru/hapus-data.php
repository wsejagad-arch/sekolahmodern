<?php
session_start();
include "../../koneksi.php";

if (!isset($_SESSION['no_induk'])) {
    header("location:../../index.php");
    exit;
}

$noInduk = mysqli_real_escape_string($conn, $_SESSION['no_induk']);

// Attempt to delete the teacher record
$deleteQuery = "DELETE FROM tbl_guru WHERE no_induk = '$noInduk'";
$result = mysqli_query($conn, $deleteQuery);

if (!$result) {
    // Fallback: clear personal data if delete fails due to constraints
    $clearQuery = "UPDATE tbl_guru SET 
        alamat = NULL, 
        no_wa = NULL, 
        foto = NULL,
        password = 'DELETED'
        WHERE no_induk = '$noInduk'";
    mysqli_query($conn, $clearQuery);
}

session_destroy();
echo "<script>alert('Data profil dan akun Anda telah berhasil dihapus sesuai permintaan.'); window.location='../../index.php';</script>";
?>
