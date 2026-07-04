<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include "koneksi.php";
require_once __DIR__ . '/auth_helper.php';

if (function_exists('mark_current_user_offline')) {
    mark_current_user_offline($conn ?? null);
}

session_destroy();

header("location: login.php?logout");
