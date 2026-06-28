<!-- Footer -->
<footer class="sticky-footer bg-white">
        <div class="container my-auto">
          <div class="copyright text-center my-auto">
            <span>Copyright &copy; TIM IT SMAN1S <?= date('Y'); ?></span>
          </div>
        </div>
      </footer>
      <!-- End of Footer -->

    </div>
    <!-- End of Content Wrapper -->

  </div>
  <!-- End of Page Wrapper -->

  <!-- Scroll to Top Button-->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <!-- Logout Modal-->
  <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Yakin mau keluar?</h5>
          <button class="close" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <div class="modal-body">Dengan menekan tombol "Keluar", akan mengakhiri sesi anda.</div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
          <a class="btn btn-primary" href="logout.php">Keluar</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap core JavaScript-->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Core plugin JavaScript-->
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- Custom scripts for all pages-->
  <script src="js/sb-admin-2.min.js"></script>

  <!-- Page level plugins -->
  <script src="vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="vendor/datatables/dataTables.responsive.min.js"></script>
  <script src="vendor/bootstrap/js/responsive.bootstrap4.min.js"></script>
  
  

  <!-- Page level custom scripts -->
  <script src="js/demo/datatables-demo.js"></script>
  
  <!-- Initialize sidebar with native Bootstrap or fallback to vanilla JS -->
  <script>
  (function() {
    'use strict';
    
    $(document).ready(function() {
      console.log('Footer: jQuery version:', $.fn.jquery);
      console.log('Footer: Bootstrap collapse available?', typeof $.fn.collapse !== 'undefined');
      
      // Wait a bit for all plugins to load
      setTimeout(function() {
        // Check if Bootstrap collapse is available
        if (typeof $.fn.collapse !== 'undefined') {
          console.log('✅ Using Bootstrap native collapse');
          
          // Monitor collapse events
          $('#accordionSidebar .collapse').on('show.bs.collapse', function() {
            console.log('✅ Sidebar opening:', this.id);
          }).on('hide.bs.collapse', function() {
            console.log('✅ Sidebar closing:', this.id);
          });
        } else {
          // Fallback to vanilla JavaScript if Bootstrap collapse not available
          console.warn('⚠️ Bootstrap collapse not available - using vanilla JS fallback');
          
          // Setup vanilla JS collapse handlers
          document.querySelectorAll('.nav-link[data-toggle="collapse"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
              e.preventDefault();
              
              var targetId = this.getAttribute('data-target');
              var target = document.querySelector(targetId);
              
              if (!target) return;
              
              // Toggle visibility
              if (target.style.display === 'block') {
                target.style.display = 'none';
                this.classList.add('collapsed');
              } else {
                // Close all other collapses
                document.querySelectorAll('#accordionSidebar .collapse').forEach(function(collapse) {
                  collapse.style.display = 'none';
                });
                
                // Open this one
                target.style.display = 'block';
                this.classList.remove('collapsed');
              }
              
              console.log('✅ Toggled (vanilla JS):', targetId);
            });
          });
          
          console.log('✅ Vanilla JS fallback initialized');
        }
      }, 500); // Wait 500ms for Bootstrap to fully load
      
      console.log('✅ Footer scripts initialized');
    });
  })();
  </script>

  <!-- Jquery untuk validasi form tambah rekom -->
  <script>
// Disable form submissions if there are invalid fields
(function() {
  'use strict';
  window.addEventListener('load', function() {
    // Get the forms we want to add validation styles to
    var forms = document.getElementsByClassName('needs-validation');
    // Loop over them and prevent submission
    var validation = Array.prototype.filter.call(forms, function(form) {
      form.addEventListener('submit', function(event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }, false);
})();
</script>
<!-- End of Jquery validasi tambah rekom -->

<!-- Script auto load nama user -->
<script type="text/javascript">
        $(document).ready(function(){

            $("#kategoriUser").change(function(){
                var deptid = $(this).val();

                $.ajax({
                    url: 'phpajax/getUser.php',
                    type: 'post',
                    data: {depart:deptid},
                    dataType: 'json',
                    success:function(response){

                        var len = response.length;

                        $("#userName").empty();
						$("#userName").append("<option selected disabled>"+"-- pilih --"+"</option>");
                        for( var i = 0; i<len; i++){
                            var id = response[i]['id'];
                            var name = response[i]['name'];
                            $("#userName").append("<option value='"+id+"'>"+id+" "+name+"</option>");
                        }
                    }
                });
            });

        });
    </script>
<!-- End of script auto load nama user -->

<!-- Script autofill nama dan kelas -->
<script type="text/javascript">
        $(document).ready(function(){

            $("#userName").change(function(){
                var nip = $(this).val();

                $.ajax({
                    url: 'phpajax/autofill.php',
                    type: 'post',
                    data: {nip:nip},
                    dataType: 'json',
                    success:function(response){

                        var len = response.length;
						
                        for( var i = 0; i<len; i++){
                            var nama = response[i]['nama'];
                            var kelas = response[i]['kelas'];
                            $("#nama").val(nama);
							$("#kelas").val(kelas);
                        }
                    }
                });
            });

        });
    </script>
<!-- End of autofill nama dan kelas -->

<!-- Validasi cetak log -->
<script type="text/javascript">
  function validasiCetakLog() {
    var start_date = document.getElementById("tglAwal").value;
    var end_date = document.getElementById("tglAkhir").value;

    if (start_date == "" || end_date == "") {
      if (typeof showToast === 'function') showToast('Tanggal awal dan tanggal akhir tidak boleh kosong!', 'warning'); else alert('Tanggal awal dan tanggal akhir tidak boleh kosong!');
  	  return false;
    }

    if (start_date > end_date) {
      if (typeof showToast === 'function') showToast('Tanggal awal tidak boleh lebih besar dari tanggal akhir!', 'warning'); else alert('Tanggal awal tidak boleh lebih besar dari tanggal akhir!');
  	  return false;
    } 
	return true;
  }
</script>
<!-- End of validasi cetak log -->

<!-- Select2 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>

<script>
$(function() {
    console.log("✅ jQuery version:", $.fn.jquery);
    console.log("✅ Select2 loaded?", typeof $.fn.select2);
    console.log("✅ DataTable loaded?", typeof $.fn.DataTable);
});

<?php if (isset($_GET['page']) && $_GET['page'] == 'rekap_absen_siswa'): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    $('#pilihSiswa').select2({
        placeholder: "Cari Siswa...",
        ajax: {
            url: "cari_siswa.php",
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) {
                if (data.error) {
                    console.error('Error loading siswa:', data.error);
                    return { results: [] };
                }
                return data;
            },
            error: function(xhr, status, error) {
              console.error('AJAX Error:', status, error);
              if (typeof showToast === 'function') showToast('Error memuat data siswa: ' + error, 'error'); else alert('Error memuat data siswa: ' + error);
            }
        }
    });

    <?php
    $noInduk = $_GET['no_induk'] ?? "";
    if ($noInduk): ?>
    $.ajax({
        url: "cari_siswa.php?q=<?= urlencode($noInduk) ?>",
        dataType: 'json'
    }).done(function(data) {
        if (data.results && data.results.length > 0) {
            var option = new Option(data.results[0].text, data.results[0].id, true, true);
            $('#pilihSiswa').append(option).trigger('change');
        } else {
            console.warn('Siswa dengan no_induk <?= $noInduk ?> tidak ditemukan');
        }
    }).fail(function(xhr, status, error) {
        console.error('Error loading selected siswa:', status, error);
    });
    <?php endif; ?>

    <?php
    include "koneksi.php";
    $bulan = $_GET['bulan'] ?? date("Y-m");
    $qGrafik = mysqli_query($conn, "
        SELECT s.nama_siswa, COUNT(*) as total_alpha
        FROM tbl_absen a
        JOIN tbl_siswa s ON a.no_induk = s.no_induk
        WHERE a.status = 'Alpha'
          AND DATE_FORMAT(a.tanggal, '%Y-%m') = '$bulan'
        GROUP BY s.no_induk, s.nama_siswa
        ORDER BY total_alpha DESC
        LIMIT 5
    ");
    $labels = [];
    $values = [];
    if ($qGrafik) {
        while ($row = mysqli_fetch_assoc($qGrafik)) {
            $labels[] = $row['nama_siswa'];
            $values[] = (int)$row['total_alpha'];
        }
    }
    mysqli_close($conn);
    if (!empty($labels) && !(count($labels) == 1 && $labels[0] == 'Tidak ada data')): ?>
    new Chart(document.getElementById('chartAlpha'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Total Alpha',
                data: <?= json_encode($values) ?>,
                backgroundColor: 'rgba(220, 53, 69, 0.7)',
                borderColor: 'rgb(220, 53, 69)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    <?php endif; ?>
});
</script>
<?php endif; ?>

</body>

</html>
