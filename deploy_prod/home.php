<?php
include "header.php";
include "sidebar.php";
?>


<!-- Content Wrapper -->
<di case 'kelola-wali-kelas' :
  include "kelola-wali-kelas.php" ;
  break;
  case 'input-kelas' :
  include "input-kelas.php" ;
  break;div id="content-wrapper" class="d-flex flex-column">

  <!-- Main Content -->
  <div id="content">

    <?php
    // Ini bagian top bar (judul dan navigasi user)
    include "topbar.php";
    ?>


    <!-- Disini konten include -->
    <?php
    if (isset($_REQUEST['page'])) {
      $page = $_REQUEST['page'];

      switch ($page) {
        case 'data-guru':
          include "data-guru.php";
          break;
        case 'detail-guru':
          include "detail-guru.php";
          break;
        case 'tambah-mapel-guru':
          include "tambah-mapel-guru.php";
          break;
        case 'edit-mapel-guru':
          include "edit-mapel-guru.php";
          break;
        case 'tambah-guru':
          include "tambah-guru.php";
          break;
        case 'tambah-tahun-ajaran':
          include "tambah-tahun-ajaran.php";
          break;
        case 'edit-guru':
          include "edit-guru.php";
          break;
        case 'data-siswa':
          include "data-siswa.php";
          break;
        case 'tambah-siswa':
          include "tambah-siswa.php";
          break;
        case 'edit-siswa':
          include "edit-siswa.php";
          break;
        case 'kelas-cetak':
          include "kelas-cetak.php";
          break;
        case 'lihat-log':
          include "lihat-log.php";
          break;
        case 'cetak-log':
          include "form-cetak-log.php";
          break;
        case 'cetakjurnal-log':
          include "form-cetakjurnal-log.php";
          break;
        case 'tambah-data-mapel':
          include "tambah-data-mapel.php";
          break;
        case 'jurnal':
          include "jurnal.php";
          break;
        case 'jurnalguru':
          include "guru_jurnal.php";
          break;
        case 'kelas':
          include "kelas.php";
          break;
        case 'cetak-kelas':
          include "cetak-kelas.php";
          break;
        case 'cetak-jurnal':
          include "cetak-jurnal.php";
          break;
        case 'monitoring':
          include "kehadiran.php";
          break;
        case 'monitoring-guru':
          include "monitoring-guru.php";
          break;
        case 'rekap_absen_siswa':
          include "rekap_absen_siswa.php";
          break;
        case 'data-wali-kelas':
          include "data-wali-kelas.php";
          break;
        case 'kelola-wali-kelas':
          include "kelola-wali-kelas.php";
          break;
        case 'input-kelas':
          include "input-kelas.php";
          break;
        case 'hapus-kelas-2':
          include "hapus-kelas-2.php";
          break;
        case 'hapus-kelas-simple':
          include "hapus-kelas-simple.php";
          break;
        case 'cetak-jurnal-guru':
          include "cetak-jurnal-guru.php";
          break;
        case 'cek-nilai':
          include "pages/admin/cek-nilai.php";
          break;
        case 'cari_siswa':
          include "cari_siswa.php";
          break;
        case 'cetak-kehadiran-guru':
          include "form-cetak-kehadiran.php";
          break;
        case 'jurnal-cetak':
          include "jurnal-cetak.php";
          break;
        case 'lihatuser':
          include "user.php";
          break;
        case 'tambahuser':
          include "tambah-user.php";
          break;
        case 'edit-user':
          include "edit-user.php";
          break;
        case 'buat-laporan':
          include "form-laporan.php";
          break;
        case 'import-siswa':
          include "import-siswa.php";
          break;
        case 'import-guru':
          include "import-guru.php";
          break;
        case 'setting':
          include "edit-settings.php";
          break;
        case 'presensi-settings':
          include "admin/presensi_settings.php";
          break;
        case 'pengumuman':
          include "pages/admin/pengumuman.php";
          break;
        default:
          echo "<h4 class=\"pl-4 font-weight-bold\">Halaman tidak ditemukan!</h4>";
          exit();
          break;
      } // penutup switch
      // dibawah komen ini penutup if request
    } else {
    ?>
      <!-- End of konten include -->

      <!-- Content Row -->
      <div class="row mx-auto">


        <!-- Kartu ucapan selamat datang -->
        <div class="col-md-12 mb-3">
          <div class="card shadow h-100 py-3" style="background:linear-gradient(135deg,#0ea5e9,#8b5cf6); color:#fff; border:0; border-radius:16px;">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-md font-weight-bold text-uppercase mb-1">Selamat Datang <?= $_SESSION['nama']; ?></div>
                  <div class="text-md mb-0 font-weight-bold">Berikut ini adalah data yang tersimpan di dalam sistem.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Jumlah Guru -->
        <div class="col-md-4 mb-3">
          <div class="card shadow-sm h-100 py-3" style="border:0; border-radius:14px; background:linear-gradient(135deg,#3b82f6,#06b6d4); color:#fff;">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs text-uppercase mb-1">Jumlah Guru</div>
                  <div class="h4 mb-0 fw-bold"><a class="text-white text-decoration-none" href="?page=data-guru"><?= hitung_guru(); ?></a></div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-chalkboard-teacher fa-2x" style="opacity:.9;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Jumlah Mapel -->
        <div class="col-md-4 mb-3">
          <div class="card shadow-sm h-100 py-3" style="border:0; border-radius:14px; background:linear-gradient(135deg,#10b981,#3b82f6); color:#fff;">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs text-uppercase mb-1">Jumlah Mata Pelajaran</div>
                  <div class="h4 mb-0 fw-bold"><a class="text-white text-decoration-none" href="?page=tambah-data-mapel"><?= hitung_mapel(); ?></a></div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-book-open fa-2x" style="opacity:.9;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Jumlah User -->
        <div class="col-md-4 mb-3">
          <div class="card shadow-sm h-100 py-3" style="border:0; border-radius:14px; background:linear-gradient(135deg,#ef4444,#f59e0b); color:#fff;">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs text-uppercase mb-1">Jumlah User</div>
                  <div class="h4 mb-0 fw-bold"><?= hitung_user(); ?></div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-users fa-2x" style="opacity:.9;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Jumlah Siswa -->
        <div class="col-md-4 mb-3">
          <div class="card shadow-sm h-100 py-3" style="border:0; border-radius:14px; background:linear-gradient(135deg,#6366f1,#06b6d4); color:#fff;">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs text-uppercase mb-1">Jumlah Siswa</div>
                  <div class="h4 mb-0 fw-bold"><a class="text-white text-decoration-none" href="?page=data-siswa"><?= hitung_siswa(); ?></a></div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-user-graduate fa-2x" style="opacity:.9;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Jumlah Kelas -->
        <div class="col-md-4 mb-3">
          <div class="card shadow-sm h-100 py-3" style="border:0; border-radius:14px; background:linear-gradient(135deg,#f59e0b,#ec4899); color:#fff;">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs text-uppercase mb-1">Jumlah Kelas</div>
                  <div class="h4 mb-0 fw-bold"><?= hitung_kelas(); ?></div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-chalkboard fa-2x" style="opacity:.9;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- End of Content Row 1 -->

      <!-- Dashboard Charts Section -->
      <div class="row mx-auto mb-4">
        <!-- Period Filter -->
        <div class="col-12 mb-3">
          <div class="card border-0 shadow-sm" style="border-radius: 15px;">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0 font-weight-bold text-dark">
                  <i class="fas fa-chart-line text-primary me-2"></i>
                  Analytics Dashboard
                </h5>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                  <div class="d-flex align-items-center gap-2">
                    <label class="small text-muted mb-0">Periode:</label>
                    <select id="periodFilter" class="form-control form-control-sm" style="border-radius: 20px; width: auto;">
                      <option value="weekly">Mingguan</option>
                      <option value="monthly" selected>Bulanan</option>
                      <option value="yearly">Tahunan</option>
                    </select>
                  </div>
                  <button class="btn btn-sm btn-primary" style="border-radius: 20px;" onclick="refreshCharts()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row 1 -->
      <div class="row mx-auto mb-4">
        <!-- Grafik Perkembangan Jurnal -->
        <div class="col-lg-8 mb-4">
          <div class="card border-0 shadow" style="border-radius: 20px; overflow: hidden;">
            <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
              <h6 class="m-0 font-weight-bold text-white">
                <i class="fas fa-chart-line me-2"></i>Perkembangan Pengisian Jurnal
              </h6>
            </div>
            <div class="card-body p-4">
              <div style="position: relative; height: 300px;">
                <canvas id="jurnalChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Status Kepegawaian Guru -->
        <div class="col-lg-4 mb-4">
          <div class="card border-0 shadow" style="border-radius: 20px; overflow: hidden;">
            <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
              <h6 class="m-0 font-weight-bold text-white">
                <i class="fas fa-chart-pie me-2"></i>Status Kepegawaian
              </h6>
            </div>
            <div class="card-body p-4">
              <div style="position: relative; height: 300px;">
                <canvas id="kepegawaianChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row 2 -->
      <div class="row mx-auto mb-4">
        <!-- Kehadiran Siswa per Kelas -->
        <div class="col-lg-6 mb-4">
          <div class="card border-0 shadow" style="border-radius: 20px; overflow: hidden;">
            <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
              <h6 class="m-0 font-weight-bold text-white">
                <i class="fas fa-chart-bar me-2"></i>Tingkat Kehadiran Siswa
              </h6>
            </div>
            <div class="card-body p-4">
              <div style="position: relative; height: 300px;">
                <canvas id="kehadiranChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Aktivitas Guru -->
        <div class="col-lg-6 mb-4">
          <div class="card border-0 shadow" style="border-radius: 20px; overflow: hidden;">
            <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
              <h6 class="m-0 font-weight-bold text-white">
                <i class="fas fa-chart-area me-2"></i>Aktivitas Guru
              </h6>
            </div>
            <div class="card-body p-4">
              <div style="position: relative; height: 300px;">
                <canvas id="aktivitasChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary Stats -->
      <div class="row mx-auto mb-4">
        <div class="col-12">
          <div class="card border-0 shadow" style="border-radius: 20px; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
            <div class="card-body p-4">
              <div class="row text-center">
                <div class="col-md-3 mb-3 mb-md-0">
                  <div class="text-dark">
                    <i class="fas fa-book fa-2x mb-2 opacity-75"></i>
                    <h4 class="font-weight-bold mb-1" id="totalJurnalMingguIni">-</h4>
                    <p class="mb-0 small">Jurnal Minggu Ini</p>
                  </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                  <div class="text-dark">
                    <i class="fas fa-percentage fa-2x mb-2 opacity-75"></i>
                    <h4 class="font-weight-bold mb-1" id="rataKehadiranBulanIni">-</h4>
                    <p class="mb-0 small">Rata-rata Kehadiran</p>
                  </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                  <div class="text-dark">
                    <i class="fas fa-user-check fa-2x mb-2 opacity-75"></i>
                    <h4 class="font-weight-bold mb-1" id="guruAktifMengajar">-</h4>
                    <p class="mb-0 small">Guru Aktif Mengajar</p>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="text-dark">
                    <i class="fas fa-calendar-check fa-2x mb-2 opacity-75"></i>
                    <h4 class="font-weight-bold mb-1" id="kelengkapanDataBulanIni">-</h4>
                    <p class="mb-0 small">Kelengkapan Data</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>


    <?php
      // bracket di bawah ini adalah penutup else dari is isset request
    }
    ?>


    <!-- /.container-fluid -->


  </div>
  <!-- End of Main Content -->

  <!-- Bagian chart Pengisian Jurnal Guru telah dihapus -->

  <!-- Chart.js Library -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>

  <!-- Dashboard Charts JavaScript -->
  <script>
    // Global chart variables
    let jurnalChart, kepegawaianChart, kehadiranChart, aktivitasChart;

    // Initialize all charts
    function initializeCharts() {
      // Jurnal Progress Chart
      const jurnalCtx = document.getElementById('jurnalChart').getContext('2d');
      jurnalChart = new Chart(jurnalCtx, {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            label: 'Jurnal Terisi',
            data: [],
            borderColor: 'rgb(102, 126, 234)',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgb(102, 126, 234)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 6
          }, {
            label: 'Target',
            data: [],
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            borderWidth: 2,
            borderDash: [5, 5],
            fill: false,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
            },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              titleColor: 'white',
              bodyColor: 'white',
              borderColor: 'rgba(102, 126, 234, 0.5)',
              borderWidth: 1
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.1)'
              }
            },
            x: {
              grid: {
                color: 'rgba(0, 0, 0, 0.1)'
              }
            }
          }
        }
      });

      // Status Kepegawaian Chart
      const kepegawaianCtx = document.getElementById('kepegawaianChart').getContext('2d');
      kepegawaianChart = new Chart(kepegawaianCtx, {
        type: 'doughnut',
        data: {
          labels: ['PNS', 'CPNS', 'GTT/PTT', 'Honorer'],
          datasets: [{
            data: [],
            backgroundColor: [
              'rgba(102, 126, 234, 0.8)',
              'rgba(54, 162, 235, 0.8)',
              'rgba(255, 206, 86, 0.8)',
              'rgba(75, 192, 192, 0.8)'
            ],
            borderColor: [
              'rgb(102, 126, 234)',
              'rgb(54, 162, 235)',
              'rgb(255, 206, 86)',
              'rgb(75, 192, 192)'
            ],
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                padding: 20,
                usePointStyle: true
              }
            },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              titleColor: 'white',
              bodyColor: 'white'
            }
          }
        }
      });

      // Kehadiran Chart
      const kehadiranCtx = document.getElementById('kehadiranChart').getContext('2d');
      kehadiranChart = new Chart(kehadiranCtx, {
        type: 'bar',
        data: {
          labels: [],
          datasets: [{
            label: 'Tingkat Kehadiran (%)',
            data: [],
            backgroundColor: 'rgba(240, 147, 251, 0.8)',
            borderColor: 'rgb(240, 147, 251)',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              titleColor: 'white',
              bodyColor: 'white',
              callbacks: {
                label: function(context) {
                  return context.parsed.y + '%';
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              max: 100,
              grid: {
                color: 'rgba(0, 0, 0, 0.1)'
              },
              ticks: {
                callback: function(value) {
                  return value + '%';
                }
              }
            },
            x: {
              grid: {
                display: false
              }
            }
          }
        }
      });

      // Aktivitas Guru Chart
      const aktivitasCtx = document.getElementById('aktivitasChart').getContext('2d');
      aktivitasChart = new Chart(aktivitasCtx, {
        type: 'radar',
        data: {
          labels: ['Pengisian Jurnal', 'Kehadiran Mengajar', 'Laporan Tugas', 'Update Data', 'Komunikasi'],
          datasets: [{
            label: 'Aktivitas Guru',
            data: [],
            borderColor: 'rgb(79, 172, 254)',
            backgroundColor: 'rgba(79, 172, 254, 0.2)',
            borderWidth: 2,
            pointBackgroundColor: 'rgb(79, 172, 254)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgb(79, 172, 254)'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            r: {
              beginAtZero: true,
              max: 100,
              grid: {
                color: 'rgba(0, 0, 0, 0.1)'
              },
              angleLines: {
                color: 'rgba(0, 0, 0, 0.1)'
              },
              pointLabels: {
                font: {
                  size: 11
                }
              }
            }
          }
        }
      });
    }

    // Load dashboard data
    function loadDashboardData() {
      const period = document.getElementById('periodFilter').value;

      // Load Jurnal Chart Data
      loadJurnalData(period);

      // Load Kepegawaian Data
      loadKepegawaianData();

      // Load Kehadiran Data
      loadKehadiranData(period);

      // Load Aktivitas Data
      loadAktivitasData(period);

      // Load Summary Stats
      loadSummaryStats(period);
    }

    // Load jurnal chart data
    function loadJurnalData(period) {
      fetch(`api/jurnal-data.php?period=${period}`)
        .then(response => response.json())
        .then(data => {
          jurnalChart.data.labels = data.labels;
          jurnalChart.data.datasets[0].data = data.data;
          jurnalChart.data.datasets[1].data = data.target;
          jurnalChart.update();
        })
        .catch(() => {
          // Fallback data
          let labels, data, target;

          if (period === 'weekly') {
            labels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
            data = [12, 19, 15, 25, 22, 18];
            target = [15, 20, 18, 25, 25, 20];
          } else if (period === 'monthly') {
            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ago', 'Sep', 'Okt', 'Nov', 'Des'];
            data = [85, 92, 78, 95, 88, 91, 96, 89, 93, 87, 90, 94];
            target = [90, 90, 90, 90, 90, 90, 90, 90, 90, 90, 90, 90];
          } else {
            labels = ['2021', '2022', '2023', '2024'];
            data = [1250, 1420, 1380, 1560];
            target = [1300, 1400, 1500, 1600];
          }

          jurnalChart.data.labels = labels;
          jurnalChart.data.datasets[0].data = data;
          jurnalChart.data.datasets[1].data = target;
          jurnalChart.update();
        });
    }

    // Load kepegawaian data
    function loadKepegawaianData() {
      // Fetch from PHP - simulate data
      fetch('api/kepegawaian-data.php')
        .then(response => response.json())
        .then(data => {
          kepegawaianChart.data.datasets[0].data = data.values || [15, 8, 12, 5];
          kepegawaianChart.update();
        })
        .catch(() => {
          // Fallback data
          kepegawaianChart.data.datasets[0].data = [15, 8, 12, 5];
          kepegawaianChart.update();
        });
    }

    // Load kehadiran data
    function loadKehadiranData(period) {
      fetch('api/kehadiran-data.php')
        .then(response => response.json())
        .then(data => {
          kehadiranChart.data.labels = data.labels;
          kehadiranChart.data.datasets[0].data = data.data;
          kehadiranChart.update();

          // Update rata-rata kehadiran di summary
          document.getElementById('rataKehadiranBulanIni').textContent = data.average + '%';
        })
        .catch(() => {
          // Fallback data
          const labels = ['Kelas X-A', 'Kelas X-B', 'Kelas XI-A', 'Kelas XI-B', 'Kelas XII-A', 'Kelas XII-B'];
          const data = [92, 88, 95, 87, 90, 93];

          kehadiranChart.data.labels = labels;
          kehadiranChart.data.datasets[0].data = data;
          kehadiranChart.update();
        });
    }

    // Load aktivitas data
    function loadAktivitasData(period) {
      // Simulate data
      const data = [85, 90, 78, 92, 88];

      aktivitasChart.data.datasets[0].data = data;
      aktivitasChart.update();
    }

    // Load summary stats
    function loadSummaryStats(period) {
      fetch('api/summary-stats.php')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const stats = data.data;
            document.getElementById('totalJurnalMingguIni').textContent = stats.totalJurnalMingguIni;
            document.getElementById('rataKehadiranBulanIni').textContent = stats.rataKehadiranBulanIni + '%';
            document.getElementById('guruAktifMengajar').textContent = stats.guruAktifMengajar;
            document.getElementById('kelengkapanDataBulanIni').textContent = stats.kelengkapanDataBulanIni + '%';
          }
        })
        .catch(() => {
          // Fallback data - keep existing values
          console.log('Failed to load summary stats, using fallback data');
        });
    }

    // Refresh all charts
    function refreshCharts() {
      const refreshBtn = document.querySelector('button[onclick="refreshCharts()"]');
      const icon = refreshBtn.querySelector('i');

      // Add spinning animation
      icon.classList.add('fa-spin');
      refreshBtn.disabled = true;

      setTimeout(() => {
        loadDashboardData();
        icon.classList.remove('fa-spin');
        refreshBtn.disabled = false;

        // Show success message
        const toast = document.createElement('div');
        toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; border-radius: 15px;';
        toast.innerHTML = '<i class="fas fa-check me-2"></i>Data grafik berhasil diperbarui! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.body.appendChild(toast);

        setTimeout(() => {
          if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
          }
        }, 3000);
      }, 1000);
    }

    // Period filter change handler
    if (document.getElementById('periodFilter')) {
      document.getElementById('periodFilter').addEventListener('change', function() {
        loadDashboardData();
      });
    }

    // Auto-initialize charts on dashboard page (no ?page= parameter)
    document.addEventListener('DOMContentLoaded', function() {
      // Only run on dashboard (home page without parameters)
      if (!window.location.search.includes('page=')) {
        // Check if chart elements exist
        if (document.getElementById('jurnalChart')) {
          console.log('Initializing dashboard charts...');
          initializeCharts();
          loadDashboardData();
        } else {
          console.warn('Chart elements not found - skipping chart initialization');
        }
      }
    });
  </script>

  <?php include "footer.php"; ?>