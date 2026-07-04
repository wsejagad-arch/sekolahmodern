<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION["username"])) {
	header("location: index.php?haruslogin");
	exit;
}

$id_user=$_SESSION["id_user"];
$username=$_SESSION["username"];
$nama=$_SESSION["nama"];
$hakakses =$_SESSION["hak_akses"];

include "functions.php";
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

  <!-- DataTables CSS dari CDN untuk hosting -->
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css" rel="stylesheet">
  <link href="css/mycss.css" rel="stylesheet">

  <script src="vendor/chart.js/Chart.min.js"></script>

</head>

<body id="page-top">
<script src="js/sweetalert2.all.min.js"></script>
  <!-- Page Wrapper -->
  <div id="wrapper">
