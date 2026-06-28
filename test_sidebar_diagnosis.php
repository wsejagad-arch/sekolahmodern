<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Diagnosis Sidebar</title>
    
    <!-- Bootstrap CSS -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="css/mycss.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav backgroundna sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
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
            
            <div class="sidebar-heading">
                Data Guru dan Siswa
            </div>
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Data Guru</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Rincian:</h6>
                        <a class="collapse-item" href="home.php?page=data-guru">Lihat Data Guru</a>
                        <a class="collapse-item" href="home.php?page=tambah-guru">Tambah Data Guru</a>
                    </div>
                </div>
            </li>
        </ul>
        <!-- End of Sidebar -->
        
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Test Sidebar & Console</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Instruksi Testing</h6>
                        </div>
                        <div class="card-body">
                            <ol>
                                <li>Buka Browser Console (F12)</li>
                                <li>Klik menu "Data Guru" di sidebar</li>
                                <li>Lihat apakah ada CSS content muncul di console</li>
                                <li>Lihat apakah menu expand/collapse bekerja</li>
                            </ol>
                            
                            <div id="consoleOutput" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    
    <script>
        console.log('========================================');
        console.log('TEST SIDEBAR - START');
        console.log('========================================');
        console.log('jQuery version:', $.fn.jquery);
        console.log('Bootstrap collapse available:', typeof $.fn.collapse);
        console.log('');
        
        $(document).ready(function() {
            console.log('DOM Ready');
            console.log('Sidebar element:', $('#accordionSidebar').length > 0 ? 'Found' : 'Not found');
            console.log('Collapse links:', $('.nav-link[data-toggle="collapse"]').length);
            console.log('');
            
            // Tunggu Bootstrap selesai load
            setTimeout(function() {
                if (typeof $.fn.collapse === 'undefined') {
                    console.error('❌ Bootstrap collapse NOT available!');
                    $('#consoleOutput').html('<div class="alert alert-danger"><strong>❌ Error!</strong><br>Bootstrap collapse plugin tidak tersedia. Periksa apakah bootstrap.bundle.min.js dimuat dengan benar.</div>');
                    return;
                }
                
                console.log('✅ Bootstrap collapse is available');
                
                // Test collapse functionality - HANYA jika collapse tersedia
                $('.nav-link[data-toggle="collapse"]').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var target = $(this).attr('data-target');
                    console.log('✅ Clicked sidebar menu:', target);
                    
                    try {
                        $(target).collapse('toggle');
                        $(this).toggleClass('collapsed');
                    } catch (error) {
                        console.error('Error toggling collapse:', error);
                    }
                    
                    return false;
                });
                
                console.log('✅ Sidebar initialized');
                console.log('========================================');
                console.log('TEST SIDEBAR - END');
                console.log('========================================');
                
                // Display in page
                var output = document.getElementById('consoleOutput');
                output.innerHTML = '<div class="alert alert-success"><strong>✅ Page loaded successfully!</strong><br>Check browser console (F12) for detailed logs.<br><br><strong>Test:</strong> Klik menu "Data Guru" di sidebar untuk test collapse.</div>';
            }, 100); // Tunggu 100ms untuk Bootstrap selesai load
        });
    </script>
</body>
</html>
