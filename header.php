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

  <!-- PWA & Mobile Fullscreen Meta Tags -->
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="SIMANIS">
  <link rel="apple-touch-icon" href="<?= asset_url('img/' . htmlspecialchars($lembaga['logo'] ?? '6695f027d063a.png')); ?>">
  <link rel="manifest" href="<?= asset_url('manifest.json'); ?>">

  <title><?= $lembaga['nmsekolah']; ?> - Sistem Manajemen Sekolah</title>
  <link rel="icon" href="<?= asset_url('img/' . $lembaga['logo']); ?>" type="image/x-icon">

  <script>
    // Register Service Worker for PWA installation
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js').catch((err) => {
          console.log('SW registration failed: ', err);
        });
      });
    }
  </script>

  <?php if (!empty($lembaga['adsense_enabled']) && !empty($lembaga['adsense_script'])): ?>
  <!-- Google AdSense -->
  <?= $lembaga['adsense_script'] ?>
  <?php endif; ?>

  <!-- Google Fonts - Plus Jakarta Sans (dimuat lebih awal, non-blocking) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link href="<?= asset_url('vendor/fontawesome-free/css/all.min.css'); ?>" rel="stylesheet" type="text/css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- SB Admin 2 Template -->
  <link href="<?= asset_url('css/sb-admin-2.min.css'); ?>" rel="stylesheet">

  <!-- DataTables -->
  <link href="<?= asset_url('vendor/datatables/dataTables.bootstrap4.min.css'); ?>" rel="stylesheet">
  <link href="<?= asset_url('css/responsive.bootstrap4.min.css'); ?>" rel="stylesheet">

  <!-- Professional School Theme -->
  <link href="<?= asset_url('css/mycss.css'); ?>" rel="stylesheet">

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

  <script src="<?= asset_url('vendor/chart.js/Chart.min.js'); ?>"></script>

  <!-- Global JS Variables untuk WebView APK -->
  <script>
    window.currentUserId = "<?= htmlspecialchars($id_user ?? '') ?>";
    window.currentUserNoInduk = "<?= htmlspecialchars($_SESSION['no_induk'] ?? '') ?>";
    window.currentUserRole = "<?= htmlspecialchars($hakakses ?? '') ?>";
    
    // Fungsi untuk navigasi link terenkripsi (Anti-hover detection)
    function secNav(el) {
        var t = el.getAttribute('data-sec-target');
        if (t) {
            window.location.href = atob(t);
        }
    }
  </script>
</head>

<body id="page-top" data-user-id="<?= htmlspecialchars($id_user ?? '') ?>" data-no-induk="<?= htmlspecialchars($_SESSION['no_induk'] ?? '') ?>" data-role="<?= htmlspecialchars($hakakses ?? '') ?>">
<script src="<?= asset_url('js/sweetalert2.all.min.js'); ?>"></script>
  <!-- Page Wrapper -->
  <div id="wrapper">
