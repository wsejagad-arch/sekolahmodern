<?php

/**
 * GURU HEADER
 * Include di awal setiap halaman guru
 * Menyediakan DOCTYPE, meta tags, Bootstrap, dan Icons
 */

if (!isset($notifikasiData)) {
    $notifikasiData = [];
}
if (!isset($totalNotifikasi)) {
    $totalNotifikasi = count($notifikasiData);
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Jurnal' : 'Dashboard Guru - Jurnal' ?></title>
    <link rel="icon" href="../../img/<?= htmlspecialchars($lembaga['logo'] ?? 'favicon.ico'); ?>">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
            overflow-x: hidden;
        }

        /* Smooth transitions */
        * {
            transition: color 0.2s ease, background-color 0.2s ease;
        }
    </style>
</head>

<body>