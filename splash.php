<?php
require_once __DIR__ . '/bootstrap.php';

// Redirect if already logged in
if (isset($_SESSION['username']) && (is_admin() || is_guru() || is_siswa())) {
    header('Location: home.php');
    exit;
} elseif (isset($_SESSION['username']) && is_admin_pusat()) {
    header('Location: admin-pusat.php');
    exit;
}

// Redirect immediately to login for maximum speed
header('Location: v2/public/', true, 301);
exit;
