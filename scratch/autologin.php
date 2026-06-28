<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['no_induk'] = '196612022014111001';
$_SESSION['nama_guru'] = 'Amanda, S.Pd.';
$_SESSION['hak_akses'] = 2; // Level Guru

// Redirect to pages/guru/guru which goes to guru_2026.php
header("Location: ../pages/guru/guru");
exit;
?>
