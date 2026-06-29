<?php
$_GET['type'] = 'guru';
$_GET['page'] = 'setting-jadwal';

$_SERVER['SERVER_ADDR'] = '1.2.3.4'; // simulate production

session_start();
$_SESSION['no_induk'] = '123';
$_SESSION['hak_akses'] = 2;

require 'router.php';
