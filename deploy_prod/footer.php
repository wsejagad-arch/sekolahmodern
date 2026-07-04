<!-- Footer -->
<footer class="sticky-footer bg-white">
  <div class="container my-auto">
    <div class="copyright text-center my-auto">
      <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; padding:10px 0 6px;">
        <span style="font-size:13px; color:#64748b; font-weight:600;">Copyright &copy; TIM IT SMAN1S <?= date('Y'); ?></span>
        <div style="display:flex; align-items:center; justify-content:center; gap:10px; flex-wrap:wrap;">
          <a href="https://www.instagram.com/sman1sumber_rembang" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="border-radius:999px; background:#fdf2f8; color:#db2777; border:1px solid #f9a8d4; font-weight:700; padding:6px 12px;">
            <i class="fab fa-instagram mr-1"></i>@sman1sumber_rembang
          </a>
          <a href="https://www.facebook.com/sman1sumber_rembang" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="border-radius:999px; background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; font-weight:700; padding:6px 12px;">
            <i class="fab fa-facebook-square mr-1"></i>Facebook
          </a>
          <a href="https://www.tiktok.com/@sman1sumber_rembang" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="border-radius:999px; background:#f8fafc; color:#111827; border:1px solid #cbd5e1; font-weight:700; padding:6px 12px;">
            <i class="fab fa-tiktok mr-1"></i>TikTok
          </a>
          <a href="https://sman1sumber.sch.id" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="border-radius:999px; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-weight:700; padding:6px 12px;">
            <i class="fas fa-globe mr-1"></i>sman1sumber.sch.id
          </a>
        </div>
      </div>
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

<!-- Bootstrap core JavaScript - MENGGUNAKAN CDN UNTUK HOSTING -->
<!-- jQuery 3.6 dari CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"
  integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
  crossorigin="anonymous"></script>

<!-- Bootstrap 4.6.2 Bundle (include Popper) dari CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct"
  crossorigin="anonymous"></script>

<!-- jQuery Easing -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages - coba dari vendor dulu, fallback ke inline -->
<script src="js/sb-admin-2.min.js" onerror="console.warn('sb-admin-2.min.js not found, sidebar toggle might not work')"></script>

<!-- DataTables dari CDN -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap4.min.js"></script>



<!-- Page level custom scripts -->
<script src="js/demo/datatables-demo.js"></script>

<!-- Sidebar Collapse Fix Script for Hosting -->
<script>
  (function() {
    'use strict';

    // Fungsi untuk inisialisasi sidebar
    function initializeSidebar() {
      console.log('Initializing sidebar collapse...');

      // Cek apakah Bootstrap sudah ter-load
      if (typeof $.fn.collapse === 'undefined') {
        console.error('Bootstrap collapse not available!');
        console.log('Checking Bootstrap object:', typeof bootstrap);
        console.log('Checking jQuery:', typeof $);
        return false;
      }

      // Inisialisasi manual setiap link collapse
      var collapseLinks = document.querySelectorAll('.nav-link[data-toggle="collapse"]');
      console.log('Found ' + collapseLinks.length + ' collapse links');

      collapseLinks.forEach(function(link) {
        var targetId = link.getAttribute('data-target');
        if (!targetId) return;

        var target = document.querySelector(targetId);
        if (!target) {
          console.warn('Target not found for:', targetId);
          return;
        }

        // Hapus event handler lama jika ada
        $(link).off('click.sidebar');

        // Tambah event handler baru
        $(link).on('click.sidebar', function(e) {
          e.preventDefault();
          e.stopPropagation();

          console.log('Toggling:', targetId);

          // Toggle dengan Bootstrap collapse
          $(target).collapse('toggle');

          // Toggle class
          $(link).toggleClass('collapsed');

          return false;
        });
      });

      console.log('✅ Sidebar collapse initialized successfully');
      return true;
    }

    // Retry mechanism untuk menunggu Bootstrap ter-load
    var retryCount = 0;
    var maxRetries = 10;

    function tryInitialize() {
      if (initializeSidebar()) {
        console.log('✅ Sidebar initialized on attempt ' + (retryCount + 1));
      } else if (retryCount < maxRetries) {
        retryCount++;
        console.log('Retry ' + retryCount + '/' + maxRetries + ' - waiting for Bootstrap...');
        setTimeout(tryInitialize, 300);
      } else {
        console.error('⚠️ Failed to initialize sidebar after ' + maxRetries + ' attempts');
        console.error('Please check if Bootstrap bundle is loaded correctly');
      }
    }

    // Mulai inisialisasi saat document ready
    $(document).ready(function() {
      // Tunggu sebentar untuk memastikan semua script ter-load
      setTimeout(tryInitialize, 100);
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
  $(document).ready(function() {

    $("#kategoriUser").change(function() {
      var deptid = $(this).val();

      $.ajax({
        url: 'phpajax/getUser.php',
        type: 'post',
        data: {
          depart: deptid
        },
        dataType: 'json',
        success: function(response) {

          var len = response.length;

          $("#userName").empty();
          $("#userName").append("<option selected disabled>" + "-- pilih --" + "</option>");
          for (var i = 0; i < len; i++) {
            var id = response[i]['id'];
            var name = response[i]['name'];
            $("#userName").append("<option value='" + id + "'>" + id + " " + name + "</option>");
          }
        }
      });
    });

  });
</script>
<!-- End of script auto load nama user -->

<!-- Script autofill nama dan kelas -->
<script type="text/javascript">
  $(document).ready(function() {

    $("#userName").change(function() {
      var nip = $(this).val();

      $.ajax({
        url: 'phpajax/autofill.php',
        type: 'post',
        data: {
          nip: nip
        },
        dataType: 'json',
        success: function(response) {

          var len = response.length;

          for (var i = 0; i < len; i++) {
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
      alert("Tanggal awal dan tanggal akhir tidak boleh kosong!");
      return false;
    }

    if (start_date > end_date) {
      alert("Tanggal awal tidak boleh lebih besar dari tanggal akhir!");
      return false;
    }
    return true;
  }
</script>
<!-- End of validasi cetak log -->

<!-- Select2 CSS (load di head lebih baik, tapi ini fallback) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Select2 JS (pastikan load setelah jQuery yang sudah ada di atas) -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>

<script>
  $(function() {
    console.log("✅ jQuery version:", $.fn.jquery);
    console.log("✅ Select2 loaded?", typeof $.fn.select2);
  });
</script>

</body>

</html>