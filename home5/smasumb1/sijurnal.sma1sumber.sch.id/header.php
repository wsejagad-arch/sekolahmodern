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

  <title><?= $lembaga['nmsekolah']; ?></title>
  <link rel="icon" href="img/<?= $lembaga['logo']; ?>" type="image/x-icon">

  <!-- Custom fonts for this template-->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

  <!-- Custom styles for this template-->
  <link href="css/sb-admin-2.min.css" rel="stylesheet">

  <!-- Custom styles for this page -->
  <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
  <link href="css/responsive.bootstrap4.min.css" rel="stylesheet">
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
<!-- Toast container (global) -->
<div aria-live="polite" aria-atomic="true" style="position: fixed; top: 1rem; right: 1rem; z-index: 1080;">
  <div id="globalToastContainer"></div>
</div>
<script src="js/toast-helper.js"></script>
<script src="js/confirm-helper.js"></script>
  <!-- Page Wrapper -->
  <div id="wrapper">
