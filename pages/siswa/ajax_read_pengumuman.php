<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["no_induk"]) || (int)($_SESSION['hak_akses'] ?? 0) !== 3) {
    exit('Unauthorized');
}

require_once '../../koneksi.php';

$nis = $_SESSION['no_induk'];
$id_pengumuman = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;

if ($id_pengumuman > 0) {
    $nisEsc = mysqli_real_escape_string($conn, $nis);
    
    // Check if table exists
    $cek = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pengumuman_read'");
    if ($cek && mysqli_num_rows($cek) > 0) {
        $q = "INSERT IGNORE INTO tbl_pengumuman_read (pengumuman_id, no_induk, id_sekolah) VALUES ($id_pengumuman, '$nisEsc', $tenantId)";
        @mysqli_query($conn, $q);
        echo "OK";
    } else {
        echo "Table not found";
    }
} else {
    echo "Invalid ID";
}
