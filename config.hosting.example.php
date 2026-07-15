<?php
/**
 * Salin file ini menjadi config.hosting.php lalu isi data database hosting Anda.
 * File config.hosting.php tidak ikut di-commit / jangan di-upload ke repo publik.
 */
return [
    'host'     => 'localhost',
    'port'     => 3306,
    'user'     => 'nama_user_database',
    'password' => 'password_database',
    'database' => 'nama_database',
    // Set 'persistent' => false jika hosting tidak mengizinkan koneksi persistent (p:)
    'persistent' => true,
];
