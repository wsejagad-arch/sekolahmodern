<?php
require_once __DIR__ . '/bootstrap.php';
require_login();

$id_user = $_SESSION['id_user'] ?? null;
$username = $_SESSION['username'] ?? '';
$nama = $_SESSION['nama'] ?? '';
$hakakses = current_role();

$lembaga = data_lembaga();
?>

<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">

  <title><?= $lembaga['nmsekolah']; ?> - Sistem Manajemen Sekolah</title>
  <link rel="icon" href="img/<?= $lembaga['logo']; ?>" type="image/x-icon">

  <!-- Google Fonts - Plus Jakarta Sans (dimuat lebih awal, non-blocking) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

  <!-- SB Admin 2 Template -->
  <link href="css/sb-admin-2.min.css" rel="stylesheet">

  <!-- DataTables -->
  <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
  <link href="css/responsive.bootstrap4.min.css" rel="stylesheet">

  <!-- Professional School Theme -->
  <link href="css/mycss.css" rel="stylesheet">

  <!-- Fix untuk error 'exports is not defined' -->
  <script>
    // Polyfill untuk CommonJS exports di browser
    if (typeof exports === 'undefined') {
      var exports = {};
    }
    if (typeof module === 'undefined') {
      var module = { exports: exports };
    }
  </script>

  <script src="vendor/chart.js/Chart.min.js"></script>

</head>

<body id="page-top">
<script src="js/sweetalert2.all.min.js"></script>
  <!-- Page Wrapper -->
  <div id="wrapper">
