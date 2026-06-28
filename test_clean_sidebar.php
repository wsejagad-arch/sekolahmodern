<?php
// Test halaman minimal tanpa include-include kompleks
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Clean Test - No Includes</title>
    
    <!-- HANYA CSS YANG ESSENTIAL -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <!-- TIDAK INCLUDE MYCSS DULU -->
    
    <style>
        /* Inline CSS untuk sidebar gradient */
        .backgroundna {
            background: linear-gradient(90deg, #5D54A4, #7C78B8);
            animation: anim 2.5s infinite ease-in-out;
        }
        @keyframes anim {
            0% { background-position: 0 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0 50%; }
        }
        
        /* Fix sidebar z-index */
        #accordionSidebar {
            position: relative;
            z-index: 1000;
        }
        #accordionSidebar .nav-link,
        #accordionSidebar .collapse-item,
        #accordionSidebar a {
            pointer-events: auto !important;
            cursor: pointer !important;
            position: relative;
            z-index: 1001;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav backgroundna sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="home.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Ruang Admin</div>
            </a>
            
            <hr class="sidebar-divider my-0">
            
            <li class="nav-item active">
                <a class="nav-link" href="home.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <hr class="sidebar-divider">
            
            <div class="sidebar-heading">Data Guru dan Siswa</div>
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseGuru">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Data Guru</span>
                </a>
                <div id="collapseGuru" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Rincian:</h6>
                        <a class="collapse-item" href="home.php?page=data-guru">Lihat Data Guru</a>
                        <a class="collapse-item" href="home.php?page=tambah-guru">Tambah Data Guru</a>
                    </div>
                </div>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSiswa">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Data Siswa</span>
                </a>
                <div id="collapseSiswa" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Rincian:</h6>
                        <a class="collapse-item" href="home.php?page=data-siswa">Lihat Data Siswa</a>
                        <a class="collapse-item" href="home.php?page=tambah-siswa">Tambah Data Siswa</a>
                    </div>
                </div>
            </li>
        </ul>
        <!-- End of Sidebar -->
        
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="container-fluid mt-5">
                    <h1 class="h3 mb-4 text-gray-800">✅ Test Clean - Sidebar Berfungsi?</h1>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Instruksi Test</h6>
                        </div>
                        <div class="card-body">
                            <ol>
                                <li><strong>Buka Browser Console (F12)</strong></li>
                                <li><strong>Klik menu "Data Guru" di sidebar</strong> - Harus expand</li>
                                <li><strong>Klik menu "Data Siswa" di sidebar</strong> - Harus expand</li>
                                <li><strong>Cek console</strong> - TIDAK boleh ada CSS content muncul</li>
                            </ol>
                            
                            <div class="alert alert-info mt-3">
                                <strong>INFO:</strong> File ini <strong>TIDAK</strong> meng-include <code>mycss.css</code>. 
                                Semua CSS ada inline di dalam file ini.
                            </div>
                            
                            <div id="testResult" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap core JavaScript - MINIMAL -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>
        console.clear(); // Clear console
        console.log('%c========================================', 'color: #4e73df; font-weight: bold;');
        console.log('%c✅ CLEAN TEST - NO MYCSS.CSS', 'color: #1cc88a; font-weight: bold;');
        console.log('%c========================================', 'color: #4e73df; font-weight: bold;');
        console.log('jQuery version:', $.fn.jquery);
        console.log('Bootstrap loaded:', typeof $.fn.collapse !== 'undefined');
        console.log('');
        
        $(document).ready(function() {
            console.log('DOM Ready');
            console.log('Sidebar found:', $('#accordionSidebar').length > 0);
            console.log('Collapse links:', $('.nav-link[data-toggle="collapse"]').length);
            
            // Test sidebar clicks
            $('.nav-link[data-toggle="collapse"]').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('data-target');
                console.log('✅ Clicked:', target);
                
                // Manual toggle
                $(target).collapse('toggle');
                
                return false;
            });
            
            // Display result
            $('#testResult').html('<div class="alert alert-success"><i class="fas fa-check-circle"></i> <strong>Sidebar initialized successfully!</strong><br>Cek console untuk melihat log klik menu.</div>');
            
            console.log('%c========================================', 'color: #4e73df; font-weight: bold;');
            console.log('%c✅ READY - Click sidebar menus now!', 'color: #1cc88a; font-weight: bold;');
            console.log('%c========================================', 'color: #4e73df; font-weight: bold;');
        });
    </script>
</body>
</html>
