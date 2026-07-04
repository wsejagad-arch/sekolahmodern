<?php
include 'koneksi.php';
if (isset($conn) && $conn) {
    echo "OK\n";
} else {
    echo "FAIL: ".(mysqli_connect_error() ? mysqli_connect_error() : 'no error message')."\n";
}
?>