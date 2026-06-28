<?php
// Test halaman pengumuman dengan koneksi langsung
session_start();

// Set session untuk admin (untuk testing)
$_SESSION['hak_akses'] = 1;
$_SESSION['username'] = 'admin_test';
$_SESSION['nama'] = 'Administrator Test';

// Koneksi database langsung untuk testing
$host = "localhost";
$user = "root";
$password = "";
$database = "sijurnal";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

mysqli_set_charset($conn, "utf8");

// Simulasi parameter page
$_GET['page'] = 'pengumuman';

// Include header dan sidebar seperti di home.php
include "header.php";
include "sidebar.php";
?>

<div id="content-wrapper" class="d-flex flex-column">
  <div id="content">
    <?php
    include "topbar.php";
    ?>

    <!-- Test include halaman pengumuman -->
    <div class="container-fluid">
      <h1 class="h3 mb-4 text-gray-800">🧪 Test Halaman Pengumuman Admin (Koneksi Langsung)</h1>

      <div class="alert alert-info">
        <strong>Status Koneksi:</strong> Berhasil terhubung ke database lokal
      </div>

      <?php
      // Include halaman pengumuman
      include "pages/admin/pengumuman.php";
      ?>
    </div>

    <?php
    include "footer.php";
    ?>