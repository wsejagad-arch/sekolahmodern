<?php
// Initialize session and bootstrap BEFORE any HTML output
require_once __DIR__ . '/bootstrap.php';
require_login();

if (function_exists('is_admin_pusat') && is_admin_pusat()) {
  redirect('admin-pusat.php');
}

if (isset($_GET['page']) && $_GET['page'] === 'beranda') {
  header('Location: home.php');
  exit;
}

// Get user info
$id_user = $_SESSION['id_user'] ?? null;
$username = $_SESSION['username'] ?? '';
$nama = $_SESSION['nama'] ?? '';
$hakakses = current_role();
$lembaga = data_lembaga();

include "header.php";
if ((int)($_SESSION['hak_akses'] ?? 0) !== 2) {
  include "sidebar.php";
}
?>


<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">

  <!-- Main Content -->
  <div id="content">

    <?php
    // Ini bagian top bar (judul dan navigasi user)
    if ((int)($_SESSION['hak_akses'] ?? 0) !== 2) {
      include "topbar.php";
    }
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
        case 'clear-cache':
          include "pages/admin/clear-cache.php";
          break;
        case 'broadcast-wa':
          include "pages/admin/broadcast-wa.php";
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
        case 'hapus-kelas-simple':
          include "hapus-kelas-simple.php";
          break;
        case 'cek-nilai':
          include "pages/admin/cek-nilai.php";
          break;
        case 'nilai-perkembangan':
          include "pages/admin/nilai-perkembangan.php";
          break;
        case 'cari_siswa':
          include "cari_siswa.php";
          break;
        case 'jurnal-cetak':
          include "jurnal-cetak.php";
          break;
        case 'lihatuser':
          include "user.php";
          break;
        case 'user-online':
          include "pages/admin/user-online.php";
          break;
        case 'tambahuser':
          include "tambah-user.php";
          break;
        case 'edit-user':
          include "edit-user.php";
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
        case 'ketua-kelas':
          include "pages/admin/ketua-kelas.php";
          break;
        case 'kenaikan-kelas':
          include "pages/admin/kenaikan-kelas.php";
          break;
        case 'data-alumni':
          include "pages/admin/data-alumni.php";
          break;
        case 'pengumuman':
          include "pages/admin/pengumuman.php";
          break;
        case 'lihat-pengumuman':
          include "pages/view_pengumuman.php";
          break;
        case 'lacak-siswa':
          include "pages/admin/lacak-siswa.php";
          break;
        case 'cetak-kehadiran-siswa':
          include "pages/admin/cetak-kehadiran-siswa.php";
          break;
        case 'kelola-twibbon':
          include "pages/guru/twibbon-kelola.php";
          break;
        case 'monitoring-izin':
          include "pages/admin/monitoring-izin.php";
          break;
        case 'aduan-siswa':
          include "pages/admin/aduan-siswa.php";
          break;
        case 'agenda-sekolah':
          include "pages/admin/agenda-sekolah.php";
          break;
        case 'validasi-profil-guru':
          include "pages/admin/validasi-profil-guru.php";
          break;
        case 'sync-eraport-ekskul':
          include "pages/admin/sync-eraport-ekskul.php";
          break;
        case 'ekskul':
          include "pages/admin/ekskul.php";
          break;
        case 'literasi-admin':
          include "pages/admin/literasi_mapping.php";
          break;
        case 'kurikulum-microsite':
          echo "<script>window.location='kurikulum-microsite.php';</script>";
          break;
        case 'reset-semester':
          include "reset-semester.php";
          break;
        default:
          echo "<h4 class=\"pl-4 font-weight-bold\">Halaman tidak ditemukan!</h4>";
          exit();
          break;
      } // penutup switch
      // dibawah komen ini penutup if request
    } else {
      $agendaItemsHome = [];
      $agendaCanManageHome = false;
      $posterBulanNama = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
      $posterPeriode = $posterBulanNama[(int)date('n')] . ' ' . date('Y');
      $posterSiswaRajin = [
        'no_induk' => '-',
        'nama_siswa' => 'Belum ada data absensi',
        'kelas' => '-',
        'hadir_count' => 0,
        'alpha_count' => 0,
        'telat_count' => 0,
        'izin_count' => 0,
        'sakit_count' => 0,
        'total_records' => 0,
        'attendance_rate' => 0.0,
      ];
      $posterApresiasiTanpaAlpha = [];
      $posterApresiasiTersehat = [];

      if ($conn) {
        $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
        $posterQuery = mysqli_query($conn, "SELECT
            a.no_induk,
            COALESCE(NULLIF(TRIM(s.nama_siswa), ''), a.no_induk) AS nama_siswa,
            COALESCE(NULLIF(TRIM(s.kelas), ''), a.kelas) AS kelas,
            SUM(CASE WHEN LOWER(a.status) = 'hadir' THEN 1 ELSE 0 END) AS hadir_count,
            SUM(CASE WHEN LOWER(a.status) = 'alpha' THEN 1 ELSE 0 END) AS alpha_count,
            SUM(CASE WHEN LOWER(a.status) = 'telat' THEN 1 ELSE 0 END) AS telat_count,
            SUM(CASE WHEN LOWER(a.status) IN ('ijin','izin') THEN 1 ELSE 0 END) AS izin_count,
            SUM(CASE WHEN LOWER(a.status) = 'sakit' THEN 1 ELSE 0 END) AS sakit_count,
            COUNT(*) AS total_records
          FROM tbl_absen a
          LEFT JOIN tbl_siswa s ON s.no_induk = a.no_induk AND s.id_sekolah = a.id_sekolah
          WHERE a.id_sekolah = $idSekolah AND DATE_FORMAT(a.tanggal, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
          GROUP BY a.no_induk
          ORDER BY a.no_induk");

        $posterRows = [];
        if ($posterQuery) {
          while ($posterRow = mysqli_fetch_assoc($posterQuery)) {
            $totalPoster = (int)($posterRow['total_records'] ?? 0);
            $hadirPoster = (int)($posterRow['hadir_count'] ?? 0);
            $posterRows[] = [
              'no_induk' => (string)($posterRow['no_induk'] ?? '-'),
              'nama_siswa' => (string)($posterRow['nama_siswa'] ?? 'Belum ada data absensi'),
              'kelas' => (string)($posterRow['kelas'] ?? '-'),
              'hadir_count' => $hadirPoster,
              'alpha_count' => (int)($posterRow['alpha_count'] ?? 0),
              'telat_count' => (int)($posterRow['telat_count'] ?? 0),
              'izin_count' => (int)($posterRow['izin_count'] ?? 0),
              'sakit_count' => (int)($posterRow['sakit_count'] ?? 0),
              'total_records' => $totalPoster,
              'attendance_rate' => $totalPoster > 0 ? round(($hadirPoster / $totalPoster) * 100, 1) : 0,
            ];
          }
        }

        if (!empty($posterRows)) {
          $posterTopSort = $posterRows;
          usort($posterTopSort, static function (array $left, array $right): int {
            if ($left['attendance_rate'] === $right['attendance_rate']) {
              if ($left['alpha_count'] === $right['alpha_count']) {
                if ($left['telat_count'] === $right['telat_count']) {
                  if ($left['total_records'] === $right['total_records']) {
                    return strcmp($left['nama_siswa'], $right['nama_siswa']);
                  }

                  return $right['total_records'] <=> $left['total_records'];
                }

                return $left['telat_count'] <=> $right['telat_count'];
              }

              return $left['alpha_count'] <=> $right['alpha_count'];
            }

            return $right['attendance_rate'] <=> $left['attendance_rate'];
          });

          $posterSiswaRajin = $posterTopSort[0];

          $tanpaAlphaPool = array_values(array_filter($posterRows, static function (array $item): bool {
            return (int)$item['alpha_count'] === 0 && (int)$item['izin_count'] === 0;
          }));
          usort($tanpaAlphaPool, static function (array $left, array $right): int {
            if ($left['attendance_rate'] === $right['attendance_rate']) {
              if ($left['hadir_count'] === $right['hadir_count']) {
                if ($left['telat_count'] === $right['telat_count']) {
                  return strcmp($left['nama_siswa'], $right['nama_siswa']);
                }

                return $left['telat_count'] <=> $right['telat_count'];
              }

              return $right['hadir_count'] <=> $left['hadir_count'];
            }

            return $right['attendance_rate'] <=> $left['attendance_rate'];
          });
          $posterApresiasiTanpaAlpha = array_slice($tanpaAlphaPool, 0, 3);

          $tanpaAlphaIds = [];
          foreach ($posterApresiasiTanpaAlpha as $item) {
            $tanpaAlphaIds[$item['no_induk']] = true;
          }

          $tersehatPool = array_values(array_filter($posterRows, static function (array $item): bool {
            return (int)$item['sakit_count'] === 0;
          }));
          usort($tersehatPool, static function (array $left, array $right): int {
            if ($left['attendance_rate'] === $right['attendance_rate']) {
              if ($left['sakit_count'] === $right['sakit_count']) {
                if ($left['alpha_count'] === $right['alpha_count']) {
                  if ($left['izin_count'] === $right['izin_count']) {
                    return strcmp($left['nama_siswa'], $right['nama_siswa']);
                  }

                  return $left['izin_count'] <=> $right['izin_count'];
                }

                return $left['alpha_count'] <=> $right['alpha_count'];
              }

              return $left['sakit_count'] <=> $right['sakit_count'];
            }

            return $right['attendance_rate'] <=> $left['attendance_rate'];
          });

          $posterApresiasiTersehat = [];
          foreach ($tersehatPool as $item) {
            if (isset($tanpaAlphaIds[$item['no_induk']])) {
              continue;
            }

            $posterApresiasiTersehat[] = $item;
            if (count($posterApresiasiTersehat) >= 3) {
              break;
            }
          }

          if (count($posterApresiasiTersehat) < 3) {
            foreach ($tersehatPool as $item) {
              if (count($posterApresiasiTersehat) >= 3) {
                break;
              }

              $posterApresiasiTersehat[] = $item;
            }
          }
        }
      }

      $agendaHelperPathHome = __DIR__ . '/agenda_helper.php';
      if (file_exists($agendaHelperPathHome)) {
        require_once $agendaHelperPathHome;

        if (function_exists('agenda_ensure_table')) {
          agenda_ensure_table($conn);
        }

        if (function_exists('agenda_get_active')) {
          $agendaItemsHome = agenda_get_active($conn, 6);
        }

        if (function_exists('agenda_can_manage_user')) {
          $hakAksesHome = (int)($_SESSION['hak_akses'] ?? 0);
          $noIndukHome = (string)($_SESSION['no_induk'] ?? '');
          $agendaCanManageHome = agenda_can_manage_user($conn, $hakAksesHome, $noIndukHome);
        }
      }

      $aduanDashboardRows = [];
      $aduanDashboardCount = 0;
      $aduanDashboardOpen = 0;
      if ($conn && (int)($_SESSION['hak_akses'] ?? 0) === 1) {
        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_aduan_siswa (
            id_aduan INT UNSIGNED NOT NULL AUTO_INCREMENT,
            kode_aduan VARCHAR(30) NOT NULL,
            no_induk_pelapor VARCHAR(50) NOT NULL,
            nama_pelapor VARCHAR(150) NOT NULL DEFAULT '',
            kelas_pelapor VARCHAR(80) NOT NULL DEFAULT '',
            kategori VARCHAR(80) NOT NULL,
            judul VARCHAR(180) NOT NULL,
            isi_laporan TEXT NOT NULL,
            lokasi VARCHAR(180) DEFAULT NULL,
            tanggal_kejadian DATE DEFAULT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'baru',
            tahap_aktif VARCHAR(40) NOT NULL DEFAULT 'stpks',
            prioritas VARCHAR(20) NOT NULL DEFAULT 'normal',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            closed_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id_aduan),
            UNIQUE KEY uniq_kode_aduan (kode_aduan),
            KEY idx_status_tahap (status, tahap_aktif),
            KEY idx_pelapor (no_induk_pelapor),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
        $qAduanDashCount = @mysqli_query($conn, "SELECT COUNT(*) AS total, SUM(status <> 'selesai') AS open_total FROM tbl_aduan_siswa WHERE id_sekolah = $idSekolah");
        if ($qAduanDashCount && ($rowAduanDashCount = mysqli_fetch_assoc($qAduanDashCount))) {
          $aduanDashboardCount = (int)($rowAduanDashCount['total'] ?? 0);
          $aduanDashboardOpen = (int)($rowAduanDashCount['open_total'] ?? 0);
        }
        $qAduanDash = @mysqli_query($conn, "SELECT kode_aduan, nama_pelapor, kelas_pelapor, kategori, judul, status, tahap_aktif, prioritas, created_at FROM tbl_aduan_siswa WHERE id_sekolah = $idSekolah ORDER BY created_at DESC LIMIT 5");
        while ($qAduanDash && ($rowAduanDash = mysqli_fetch_assoc($qAduanDash))) {
          $aduanDashboardRows[] = $rowAduanDash;
        }
      }
    ?>
      <!-- End of konten include -->
      <?php if ((int)($_SESSION['hak_akses'] ?? 0) === 2): ?>
        <?php include "pages/guru/dashboard_guru.php"; ?>
      <?php else: ?>
      <!-- Content Row -->
      <div class="row mx-auto">

        <!-- Kartu ucapan selamat datang -->
        <div class="col-md-12 mb-4">
          <div class="card border-0 shadow-sm" style="border-radius:18px; background:#fff; overflow:hidden;">
            <div style="height:5px; background:linear-gradient(90deg,#1a3c6e,#0ea5e9,#8b5cf6,#ec4899);"></div>
            <div class="card-body py-4 px-4">
              <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;">
                <div>
                  <div style="font-size:13px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">
                    <i class="fas fa-hand-wave mr-1" style="color:#f0b429;"></i>
                    <?php
                    $jam = (int)date('H');
                    if ($jam >= 5 && $jam < 12) echo 'Selamat Pagi';
                    elseif ($jam >= 12 && $jam < 15) echo 'Selamat Siang';
                    elseif ($jam >= 15 && $jam < 18) echo 'Selamat Sore';
                    else echo 'Selamat Malam';
                    ?>
                  </div>
                  <h4 style="font-size:22px; font-weight:800; color:#1e293b; margin-bottom:4px;">
                    <?php echo htmlspecialchars($_SESSION['nama']); ?>
                    <span style="font-size:14px; font-weight:500; color:#64748b; margin-left:8px;">
                      <?php
                      $hak = isset($_SESSION['hak_akses']) ? (int)$_SESSION['hak_akses'] : 0;
                      if ($hak === 1) {
                        echo '<span style="background:#dbeafe; color:#1d4ed8; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:700;">Super Admin</span>';
                      } elseif ($hak === 2) {
                        echo '<span style="background:#dcfce7; color:#15803d; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:700;">Guru</span>';
                      } elseif ($hak === 3) {
                        echo '<span style="background:#fef9c3; color:#92400e; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:700;">Siswa</span>';
                      }
                      ?>
                    </span>
                  </h4>
                  <p style="font-size:13.5px; color:#64748b; margin:0;">
                    <i class="fas fa-calendar-alt mr-1" style="color:#0ea5e9;"></i>
                    <?php
                    $hari = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
                    $bulan = array('', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
                    echo $hari[date('w')] . ', ' . date('j') . ' ' . $bulan[(int)date('n')] . ' ' . date('Y');
                    ?>
                    &nbsp;&bull;&nbsp;Berikut ringkasan data sistem.
                  </p>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                  <div style="width:56px; height:56px; border-radius:50%; background:linear-gradient(135deg,#1a3c6e,#0ea5e9); display:flex; align-items:center; justify-content:center; box-shadow:0 4px 14px rgba(14,165,233,0.3);">
                    <i class="fas fa-school" style="font-size:24px; color:#fff;"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <?php if ((int)($_SESSION['hak_akses'] ?? 0) === 2): ?>
        <div class="col-md-12 mb-4">
          <div class="card border-0 shadow-sm" style="border-radius:18px; background:#fff; overflow:hidden;">
            <div style="height:5px; background:linear-gradient(90deg,#0f766e,#14b8a6,#22c55e);"></div>
            <div class="card-body py-3 px-4">
              <div style="font-size:12px; font-weight:800; color:#0f766e; letter-spacing:0.8px; text-transform:uppercase; margin-bottom:10px;">Aksi Cepat Dasbor Guru</div>
              <div class="d-flex flex-wrap" style="gap:10px;">
                <a href="pages/guru/ekinerja.php" class="btn btn-outline-success" style="border-radius:12px; font-weight:600; padding:10px 16px;">
                  <i class="fas fa-file-pdf mr-2"></i> File E-Kinerja
                </a>
                <?php
                // Cek apakah guru adalah pembina literasi
                $nipHome = $_SESSION['no_induk'] ?? '';
                $nipEscHome = mysqli_real_escape_string($conn, $nipHome);
                $idSekolahHome = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
                $qHomeLiterasi = @mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_literasi_ampuh WHERE no_induk_guru='$nipEscHome' AND id_sekolah=$idSekolahHome");
                $isPembinaLiterasiHome = false;
                if ($qHomeLiterasi) {
                    $rowHomeLiterasi = mysqli_fetch_assoc($qHomeLiterasi);
                    $isPembinaLiterasiHome = (int)($rowHomeLiterasi['total'] ?? 0) > 0;
                }
                if ($isPembinaLiterasiHome):
                ?>
                <a href="pages/guru/literasi.php" class="btn btn-outline-info" style="border-radius:12px; font-weight:600; padding:10px 16px; border-color: #0ea5e9; color: #0ea5e9;">
                  <i class="fas fa-book-reader mr-2"></i> LENTERA Literasi
                </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if ((int)($_SESSION['hak_akses'] ?? 0) === 1): ?>
        <div class="col-md-12 mb-4">
          <div class="card border-0 shadow-sm" style="border-radius:18px; background:#fff; overflow:hidden;">
            <div style="height:5px; background:linear-gradient(90deg,#be123c,#ef4444,#f59e0b);"></div>
            <div class="card-body py-3 px-4">
              <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
                <div>
                  <div style="font-size:12px; font-weight:800; color:#be123c; letter-spacing:0.8px; text-transform:uppercase;">Histori Aduan Siswa</div>
                  <h5 style="margin:0; color:#0f172a; font-weight:800;">Aduan terbaru dan status alur</h5>
                  <div style="font-size:12px;color:#64748b;margin-top:4px;">Total <?= (int)$aduanDashboardCount; ?> laporan, <?= (int)$aduanDashboardOpen; ?> masih aktif.</div>
                </div>
                <a href="home.php?page=aduan-siswa" class="btn btn-sm btn-danger" style="border-radius:999px; font-weight:700;">
                  <i class="fas fa-shield-heart mr-1"></i>Kelola Aduan
                </a>
              </div>
              <div class="row mt-3">
                <?php if (empty($aduanDashboardRows)): ?>
                  <div class="col-12"><div class="p-3" style="border:1px dashed #fecdd3;border-radius:14px;color:#64748b;">Belum ada aduan siswa.</div></div>
                <?php endif; ?>
                <?php foreach ($aduanDashboardRows as $aduanDash): ?>
                  <div class="col-lg-6 mb-3">
                    <div class="p-3" style="border:1px solid #fee2e2; border-radius:14px; background:linear-gradient(135deg,#fff1f2,#ffffff); height:100%;">
                      <div class="d-flex justify-content-between align-items-start" style="gap:10px;">
                        <div>
                          <div style="font-size:11px;color:#be123c;font-weight:800;"><?= htmlspecialchars($aduanDash['kode_aduan']); ?> • <?= htmlspecialchars(strtoupper($aduanDash['prioritas'])); ?></div>
                          <div style="font-size:14px;font-weight:800;color:#0f172a;"><?= htmlspecialchars($aduanDash['judul']); ?></div>
                          <div style="font-size:12px;color:#64748b;margin-top:3px;">
                            <?= htmlspecialchars($aduanDash['nama_pelapor']); ?> - <?= htmlspecialchars($aduanDash['kelas_pelapor']); ?>
                          </div>
                        </div>
                        <span class="badge badge-<?= $aduanDash['status'] === 'selesai' ? 'success' : 'warning'; ?>"><?= htmlspecialchars($aduanDash['status']); ?></span>
                      </div>
                      <div style="font-size:12px;color:#334155;margin-top:8px;">
                        <?= htmlspecialchars($aduanDash['kategori']); ?> • Tahap <?= htmlspecialchars(strtoupper($aduanDash['tahap_aktif'])); ?> • <?= date('d M Y H:i', strtotime($aduanDash['created_at'])); ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div class="col-md-12 mb-4">
          <div class="card border-0 shadow-sm" style="border-radius:18px; background:#fff; overflow:hidden;">
            <div style="height:5px; background:linear-gradient(90deg,#0f766e,#14b8a6,#22c55e);"></div>
            <div class="card-body py-3 px-4">
              <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
                <div>
                  <div style="font-size:12px; font-weight:800; color:#0f766e; letter-spacing:0.8px; text-transform:uppercase;">Agenda Ringkas</div>
                  <h5 style="margin:0; color:#0f172a; font-weight:800;">Papan Jadwal Sekolah</h5>
                </div>
                <?php if ($agendaCanManageHome): ?>
                  <a href="home.php?page=agenda-sekolah" class="btn btn-sm btn-success" style="border-radius:999px; font-weight:700;">
                    <i class="fas fa-calendar-plus mr-1"></i>Kelola Agenda
                  </a>
                <?php endif; ?>
              </div>

              <div class="row mt-2" id="agendaHomeList" style="<?= empty($agendaItemsHome) ? 'display:none;' : ''; ?>">
                <?php foreach ($agendaItemsHome as $indexAgendaHome => $agendaItem): ?>
                  <?php
                  $targetAt = agenda_format_datetime_local((string)$agendaItem['agenda_date'], (string)$agendaItem['jam_selesai']);
                  $unitPalette = agenda_unit_palette((string)$agendaItem['dibuat_unit']);
                  ?>
                  <div class="col-lg-6 mb-3" style="opacity:0; transform:translateY(8px); animation:agendaHomeCardIn .45s ease forwards; animation-delay:<?= (int)$indexAgendaHome * 70; ?>ms;">
                    <div class="p-3" style="border:1px solid #ccfbf1; border-radius:14px; background:linear-gradient(135deg,#f0fdfa,#ffffff); height:100%;">
                      <div class="d-flex justify-content-between align-items-start" style="gap:10px;">
                        <div>
                          <div style="font-size:13px; font-weight:800; color:#0f172a;"><?= htmlspecialchars($agendaItem['judul']); ?></div>
                          <div style="font-size:11px; margin-top:2px;">
                            <span class="badge" style="background:<?= htmlspecialchars($unitPalette['bg']); ?>; color:<?= htmlspecialchars($unitPalette['text']); ?>; border:1px solid <?= htmlspecialchars($unitPalette['border']); ?>;">
                              <?= htmlspecialchars($agendaItem['dibuat_unit']); ?>
                            </span>
                          </div>
                        </div>
                      </div>

                      <?php if (!empty($agendaItem['deskripsi'])): ?>
                        <div style="font-size:12px; color:#475569; margin-top:8px;"><?= nl2br(htmlspecialchars($agendaItem['deskripsi'])); ?></div>
                      <?php endif; ?>

                      <div style="font-size:12px; color:#334155; margin-top:10px; font-weight:600;">
                        <i class="fas fa-clock mr-1"></i>
                        <?= date('d M Y', strtotime($agendaItem['agenda_date'])); ?>
                        &nbsp;<?= substr((string)$agendaItem['jam_mulai'], 0, 5); ?> - <?= substr((string)$agendaItem['jam_selesai'], 0, 5); ?>
                      </div>
                      <div class="agenda-countdown" data-target="<?= htmlspecialchars($targetAt); ?>" style="margin-top:8px; font-size:12px; color:#0f766e; font-weight:800;">
                        Menghitung waktu...
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              <div id="agendaHomeEmpty" class="mt-3 p-3" style="border:1px solid #99f6e4; border-radius:14px; color:#475569; background:linear-gradient(135deg,#ffffff,#f0fdfa); box-shadow:0 10px 24px -18px rgba(15,118,110,.7); <?= empty($agendaItemsHome) ? '' : 'display:none;'; ?>">
                <div class="d-flex align-items-start" style="gap:12px;">
                  <div style="width:42px; height:42px; border-radius:12px; background:linear-gradient(135deg,#14b8a6,#0ea5e9); color:#fff; display:flex; align-items:center; justify-content:center; box-shadow:0 10px 20px -15px rgba(14,165,233,.9); flex-shrink:0;">
                    <i class="fas fa-calendar-day"></i>
                  </div>
                  <div>
                    <div style="font-size:14px; font-weight:800; color:#0f172a;">Belum Ada Jadwal Aktif</div>
                    <div style="font-size:12px; color:#475569; margin-top:2px;">Area ini akan terisi otomatis saat tim sekolah menambahkan agenda baru.</div>
                  </div>
                </div>
                <div class="d-flex flex-wrap align-items-center mt-2" style="gap:6px;">
                  <span class="badge" style="background:#ecfdf5; color:#047857; border:1px dashed #86efac;">Akademik</span>
                  <span class="badge" style="background:#eff6ff; color:#1d4ed8; border:1px dashed #93c5fd;">Siswa</span>
                  <span class="badge" style="background:#fff7ed; color:#c2410c; border:1px dashed #fdba74;">Publikasi</span>
                  <span class="badge" style="background:#f5f3ff; color:#6d28d9; border:1px dashed #c4b5fd;">Sarana</span>
                  <span class="badge" style="background:#f0fdfa; color:#0f766e; border:1px solid #99f6e4;"><i class="fas fa-sync-alt mr-1"></i>Sinkron otomatis</span>
                </div>
                <div class="mt-2">
                  <?php if ($agendaCanManageHome): ?>
                    <a href="home.php?page=agenda-sekolah" class="btn btn-sm btn-outline-success" style="border-radius:999px; font-weight:700;">
                      <i class="fas fa-plus-circle mr-1"></i>Tambah Jadwal
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row mx-auto mb-4">
          <div class="col-12">
            <div class="card border-0 shadow-sm position-relative" style="border-radius:24px; overflow:hidden; background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 52%,#7c3aed 100%); color:#fff;">
              <div style="position:absolute; inset:0; background:
                radial-gradient(circle at 12% 18%, rgba(255,255,255,.15), transparent 22%),
                radial-gradient(circle at 88% 24%, rgba(255,255,255,.18), transparent 18%),
                radial-gradient(circle at 72% 88%, rgba(255,255,255,.10), transparent 20%);"></div>
              <div class="card-body p-4 p-lg-5 position-relative">
                <div class="d-flex flex-column flex-lg-row align-items-stretch" style="gap:18px;">
                  <div class="flex-grow-1" style="min-width:0;">
                    <div class="d-inline-flex align-items-center mb-3" style="gap:8px; padding:6px 12px; border-radius:999px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18); backdrop-filter:blur(6px); font-size:12px; font-weight:800; letter-spacing:.8px; text-transform:uppercase;">
                      <span style="width:26px; height:26px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.18);">
                        <i class="fas fa-award" style="font-size:12px;"></i>
                      </span>
                      Sertifikat Kehadiran Bulan Ini
                    </div>
                    <h2 style="font-size:clamp(28px,4vw,44px); line-height:1.05; font-weight:900; margin:0 0 10px; letter-spacing:-.03em;">
                      Siswa Paling Rajin
                    </h2>
                    <p style="max-width:640px; color:rgba(255,255,255,.88); font-size:15px; line-height:1.65; margin:0 0 16px;">
                      Poster ini diambil dari rekap daftar hadir siswa bulan ini. Cocok untuk apresiasi internal, tayangan dashboard, atau bahan cetak cepat.
                    </p>

                    <div class="d-flex flex-wrap" style="gap:10px; margin-bottom:18px;">
                      <div style="padding:10px 14px; border-radius:16px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);">
                        <div style="font-size:11px; opacity:.8; text-transform:uppercase; letter-spacing:.8px;">Periode</div>
                        <div style="font-size:15px; font-weight:800;"><?= htmlspecialchars($posterPeriode); ?></div>
                      </div>
                      <div style="padding:10px 14px; border-radius:16px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);">
                        <div style="font-size:11px; opacity:.8; text-transform:uppercase; letter-spacing:.8px;">Tingkat Hadir</div>
                        <div style="font-size:15px; font-weight:800;"><?= number_format((float)$posterSiswaRajin['attendance_rate'], 1); ?>%</div>
                      </div>
                      <div style="padding:10px 14px; border-radius:16px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);">
                        <div style="font-size:11px; opacity:.8; text-transform:uppercase; letter-spacing:.8px;">Total Rekap</div>
                        <div style="font-size:15px; font-weight:800;"><?= (int)$posterSiswaRajin['total_records']; ?> data</div>
                      </div>
                    </div>

                    <a href="?page=rekap_absen_siswa" class="btn btn-light btn-sm" style="border-radius:999px; font-weight:800; color:#1d4ed8; padding:10px 16px;">
                      <i class="fas fa-search mr-1"></i>Lihat Rekap Absensi
                    </a>
                  </div>

                  <div style="flex:0 0 360px; max-width:100%;">
                    <div style="height:100%; border-radius:22px; background:rgba(255,255,255,.96); color:#0f172a; box-shadow:0 22px 50px rgba(15,23,42,.22); overflow:hidden; position:relative;">
                      <div style="height:10px; background:linear-gradient(90deg,#f59e0b,#ef4444,#8b5cf6);"></div>
                      <div style="padding:24px 22px 22px; position:relative;">
                        <div style="position:absolute; top:-24px; right:-24px; width:120px; height:120px; border-radius:50%; background:radial-gradient(circle, rgba(30,64,175,.12), rgba(30,64,175,0) 65%);"></div>
                        <div style="text-align:center; padding-bottom:14px; border-bottom:1px solid #e2e8f0; margin-bottom:14px;">
                          <div style="font-size:11px; font-weight:900; letter-spacing:1.8px; color:#1d4ed8; text-transform:uppercase;">SERTIFIKAT KEHADIRAN</div>
                          <div style="font-size:14px; color:#64748b; margin-top:3px;">Apresiasi Siswa Teladan</div>
                        </div>

                        <div style="text-align:center; margin-bottom:16px;">
                          <div style="width:74px; height:74px; margin:0 auto 12px; border-radius:22px; background:#f8fafc; border:1px solid #dbeafe; display:flex; align-items:center; justify-content:center; box-shadow:0 10px 24px -18px rgba(79,70,229,.55);">
                            <i class="fas fa-user-graduate" style="font-size:26px; color:#4f46e5;"></i>
                          </div>
                          <div style="font-size:12px; color:#64748b; font-weight:800; text-transform:uppercase; letter-spacing:1px;">Nama Siswa</div>
                          <div style="font-size:26px; line-height:1.15; font-weight:900; margin-top:4px; color:#111827; text-transform:uppercase;"><?= htmlspecialchars($posterSiswaRajin['nama_siswa']); ?></div>
                          <div style="font-size:14px; color:#475569; margin-top:6px; font-weight:700;">Kelas <?= htmlspecialchars($posterSiswaRajin['kelas']); ?> · NIS <?= htmlspecialchars($posterSiswaRajin['no_induk']); ?></div>
                          <div style="display:inline-flex; align-items:center; gap:8px; margin-top:10px; padding:7px 12px; border-radius:999px; background:#f8fafc; border:1px solid #dbeafe; color:#1d4ed8; font-size:12px; font-weight:800;">
                            <i class="fas fa-at" style="font-size:11px;"></i>
                            @sman1sumber.rembang
                          </div>
                        </div>

                        <div class="d-flex" style="gap:10px; margin-bottom:14px;">
                          <div style="flex:1; padding:12px; border-radius:16px; background:#f8fafc; border:1px solid #e2e8f0; text-align:center;">
                            <div style="width:32px; height:32px; margin:0 auto 8px; border-radius:10px; background:#ecfdf5; border:1px solid #bbf7d0; display:flex; align-items:center; justify-content:center; color:#16a34a;">
                              <i class="fas fa-user-check" style="font-size:14px;"></i>
                            </div>
                            <div style="font-size:11px; color:#64748b; font-weight:800; text-transform:uppercase; letter-spacing:.8px;">Hadir</div>
                            <div style="font-size:20px; font-weight:900; color:#16a34a;"><?= (int)$posterSiswaRajin['hadir_count']; ?></div>
                          </div>
                          <div style="flex:1; padding:12px; border-radius:16px; background:#f8fafc; border:1px solid #e2e8f0; text-align:center;">
                            <div style="width:32px; height:32px; margin:0 auto 8px; border-radius:10px; background:#eff6ff; border:1px solid #bfdbfe; display:flex; align-items:center; justify-content:center; color:#1d4ed8;">
                              <i class="fas fa-percentage" style="font-size:14px;"></i>
                            </div>
                            <div style="font-size:11px; color:#64748b; font-weight:800; text-transform:uppercase; letter-spacing:.8px;">Persentase</div>
                            <div style="font-size:20px; font-weight:900; color:#1d4ed8;"><?= number_format((float)$posterSiswaRajin['attendance_rate'], 1); ?>%</div>
                          </div>
                          <div style="flex:1; padding:12px; border-radius:16px; background:#f8fafc; border:1px solid #e2e8f0; text-align:center;">
                            <div style="width:32px; height:32px; margin:0 auto 8px; border-radius:10px; background:#fef2f2; border:1px solid #fecaca; display:flex; align-items:center; justify-content:center; color:#dc2626;">
                              <i class="fas fa-shield-alt" style="font-size:14px;"></i>
                            </div>
                            <div style="font-size:11px; color:#64748b; font-weight:800; text-transform:uppercase; letter-spacing:.8px;">Tanpa Alpha</div>
                            <div style="font-size:20px; font-weight:900; color:#dc2626;"><?= (int)$posterSiswaRajin['alpha_count']; ?></div>
                          </div>
                        </div>

                        <div style="padding:12px 14px; border-radius:16px; background:#f8fafc; border:1px solid #dbeafe; font-size:13px; color:#334155; line-height:1.55;">
                          <?= htmlspecialchars($lembaga['nmsekolah'] ?? 'Sekolah'); ?> menampilkan poster ringkas ini sebagai apresiasi kehadiran siswa paling disiplin pada periode berjalan.
                          <div style="margin-top:8px; display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; background:linear-gradient(135deg,#eff6ff,#f8fafc); border:1px solid #bfdbfe; color:#1d4ed8; font-size:12px; font-weight:800;">
                            <i class="fas fa-check-circle" style="font-size:11px;"></i>
                            @sman1sumber.rembang
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row mx-auto mb-4">
          <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:24px; background:linear-gradient(135deg,#ffffff 0%,#f8fafc 100%); overflow:hidden;">
              <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-column flex-lg-row align-items-start justify-content-between mb-4" style="gap:12px;">
                  <div>
                    <div class="d-inline-flex align-items-center mb-2" style="gap:8px; padding:6px 12px; border-radius:999px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-size:12px; font-weight:800; letter-spacing:.8px; text-transform:uppercase;">
                      <i class="fas fa-medal" style="font-size:12px;"></i>
                      Apresiasi Bulan Ini
                    </div>
                    <h3 style="font-size:clamp(22px,3vw,32px); line-height:1.15; font-weight:900; color:#0f172a; margin:0; letter-spacing:-.02em;">6 Siswa Terpilih Berdasarkan Kehadiran</h3>
                    <p style="margin:8px 0 0; color:#64748b; font-size:14px; line-height:1.6; max-width:760px;">
                      Terdiri dari 3 siswa tanpa alpha dan tanpa izin, serta 3 siswa kategori tersehat yang tidak pernah sakit pada periode bulan ini.
                    </p>
                  </div>
                  <div style="display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:16px; background:#f8fafc; border:1px solid #e2e8f0; color:#334155; font-size:12px; font-weight:800;">
                    <i class="fas fa-calendar-check" style="color:#0ea5e9;"></i>
                    <?= htmlspecialchars($posterPeriode); ?>
                  </div>
                </div>

                <div class="row">
                  <div class="col-lg-6 mb-4 mb-lg-0">
                    <div style="height:100%; padding:18px; border-radius:22px; background:linear-gradient(135deg,#f0fdf4,#ffffff); border:1px solid #bbf7d0;">
                      <div class="d-flex align-items-center mb-3" style="gap:10px;">
                        <div style="width:42px; height:42px; border-radius:14px; background:#ecfdf5; border:1px solid #bbf7d0; display:flex; align-items:center; justify-content:center; color:#16a34a; flex-shrink:0;">
                          <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                          <div style="font-size:12px; font-weight:800; letter-spacing:.8px; text-transform:uppercase; color:#16a34a;">Kategori 1</div>
                          <div style="font-size:18px; font-weight:900; color:#0f172a; line-height:1.2;">Tanpa Alpha &amp; Tanpa Izin</div>
                        </div>
                      </div>

                      <?php if (!empty($posterApresiasiTanpaAlpha)): ?>
                        <?php foreach ($posterApresiasiTanpaAlpha as $index => $item): ?>
                          <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; border-radius:16px; background:#ffffff; border:1px solid #dcfce7; margin-bottom:10px; box-shadow:0 8px 20px -16px rgba(22,163,74,.4);">
                            <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                              <div style="width:42px; height:42px; border-radius:14px; background:#ecfdf5; border:1px solid #bbf7d0; display:flex; align-items:center; justify-content:center; color:#16a34a; font-size:15px; font-weight:900; flex-shrink:0;">
                                <?= $index + 1; ?>
                              </div>
                              <div style="min-width:0;">
                                <div style="font-size:15px; font-weight:900; color:#0f172a; line-height:1.25;"><?= htmlspecialchars($item['nama_siswa']); ?></div>
                                <div style="font-size:12px; color:#64748b; font-weight:600; margin-top:3px;">Kelas <?= htmlspecialchars($item['kelas']); ?> · NIS <?= htmlspecialchars($item['no_induk']); ?></div>
                              </div>
                            </div>
                            <div style="text-align:right; font-size:12px; color:#475569; line-height:1.5; flex-shrink:0;">
                              <div><span style="font-weight:800; color:#16a34a;">Hadir</span> <?= (int)$item['hadir_count']; ?></div>
                              <div><span style="font-weight:800; color:#dc2626;">Alpha</span> <?= (int)$item['alpha_count']; ?></div>
                              <div><span style="font-weight:800; color:#f97316;">Izin</span> <?= (int)$item['izin_count']; ?></div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <div style="padding:16px; border-radius:16px; background:#ffffff; border:1px dashed #86efac; color:#475569; font-size:13px; line-height:1.6;">
                          Belum ada siswa yang memenuhi kriteria tanpa alpha dan tanpa izin pada periode ini.
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="col-lg-6">
                    <div style="height:100%; padding:18px; border-radius:22px; background:linear-gradient(135deg,#eff6ff,#ffffff); border:1px solid #bfdbfe;">
                      <div class="d-flex align-items-center mb-3" style="gap:10px;">
                        <div style="width:42px; height:42px; border-radius:14px; background:#eff6ff; border:1px solid #bfdbfe; display:flex; align-items:center; justify-content:center; color:#1d4ed8; flex-shrink:0;">
                          <i class="fas fa-heartbeat"></i>
                        </div>
                        <div>
                          <div style="font-size:12px; font-weight:800; letter-spacing:.8px; text-transform:uppercase; color:#1d4ed8;">Kategori 2</div>
                          <div style="font-size:18px; font-weight:900; color:#0f172a; line-height:1.2;">Tersehat, Tidak Pernah Sakit</div>
                        </div>
                      </div>

                      <?php if (!empty($posterApresiasiTersehat)): ?>
                        <?php foreach ($posterApresiasiTersehat as $index => $item): ?>
                          <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; border-radius:16px; background:#ffffff; border:1px solid #dbeafe; margin-bottom:10px; box-shadow:0 8px 20px -16px rgba(29,78,216,.38);">
                            <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                              <div style="width:42px; height:42px; border-radius:14px; background:#eff6ff; border:1px solid #bfdbfe; display:flex; align-items:center; justify-content:center; color:#1d4ed8; font-size:15px; font-weight:900; flex-shrink:0;">
                                <?= $index + 1; ?>
                              </div>
                              <div style="min-width:0;">
                                <div style="font-size:15px; font-weight:900; color:#0f172a; line-height:1.25;"><?= htmlspecialchars($item['nama_siswa']); ?></div>
                                <div style="font-size:12px; color:#64748b; font-weight:600; margin-top:3px;">Kelas <?= htmlspecialchars($item['kelas']); ?> · NIS <?= htmlspecialchars($item['no_induk']); ?></div>
                              </div>
                            </div>
                            <div style="text-align:right; font-size:12px; color:#475569; line-height:1.5; flex-shrink:0;">
                              <div><span style="font-weight:800; color:#1d4ed8;">Hadir</span> <?= (int)$item['hadir_count']; ?></div>
                              <div><span style="font-weight:800; color:#dc2626;">Sakit</span> <?= (int)$item['sakit_count']; ?></div>
                              <div><span style="font-weight:800; color:#64748b;">Persentase</span> <?= number_format((float)$item['attendance_rate'], 1); ?>%</div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <div style="padding:16px; border-radius:16px; background:#ffffff; border:1px dashed #93c5fd; color:#475569; font-size:13px; line-height:1.6;">
                          Belum ada siswa yang memenuhi kriteria tersehat pada periode ini.
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Jumlah Guru -->
        <div class="col-md-4 col-sm-6 mb-3">
          <div class="card border-0 shadow-sm h-100" style="border-radius:14px; background:#fff; border-left:4px solid #3b82f6 !important;">
            <div class="card-body py-3 px-4">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:6px;">Jumlah Guru</div>
                  <div style="font-size:28px; font-weight:800; color:#1e293b; line-height:1;">
                    <a href="?page=data-guru" style="color:#1e293b; text-decoration:none;"><?php echo hitung_guru(); ?></a>
                  </div>
                  <div style="font-size:11px; color:#94a3b8; margin-top:4px;">Tenaga pengajar terdaftar</div>
                </div>
                <div style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg,#dbeafe,#bfdbfe); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                  <i class="fas fa-chalkboard-teacher" style="font-size:20px; color:#3b82f6;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Jumlah Mapel -->
        <div class="col-md-4 col-sm-6 mb-3">
          <div class="card border-0 shadow-sm h-100" style="border-radius:14px; background:#fff; border-left:4px solid #10b981 !important;">
            <div class="card-body py-3 px-4">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:6px;">Jumlah Mata Pelajaran</div>
                  <div style="font-size:28px; font-weight:800; color:#1e293b; line-height:1;">
                    <a href="?page=tambah-data-mapel" style="color:#1e293b; text-decoration:none;"><?php echo hitung_mapel(); ?></a>
                  </div>
                  <div style="font-size:11px; color:#94a3b8; margin-top:4px;">Mata pelajaran aktif</div>
                </div>
                <div style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg,#d1fae5,#a7f3d0); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                  <i class="fas fa-book-open" style="font-size:20px; color:#10b981;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Jumlah User -->
        <div class="col-md-4 col-sm-6 mb-3">
          <div class="card border-0 shadow-sm h-100" style="border-radius:14px; background:#fff; border-left:4px solid #ef4444 !important;">
            <div class="card-body py-3 px-4">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:6px;">Jumlah User</div>
                  <div style="font-size:28px; font-weight:800; color:#1e293b; line-height:1;">
                    <?php echo hitung_user(); ?>
                  </div>
                  <div style="font-size:11px; color:#94a3b8; margin-top:4px;">Akun admin aktif</div>
                </div>
                <div style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg,#fee2e2,#fecaca); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                  <i class="fas fa-users" style="font-size:20px; color:#ef4444;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Jumlah Siswa -->
        <div class="col-md-4 col-sm-6 mb-3">
          <div class="card border-0 shadow-sm h-100" style="border-radius:14px; background:#fff; border-left:4px solid #6366f1 !important;">
            <div class="card-body py-3 px-4">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:6px;">Jumlah Siswa</div>
                  <div style="font-size:28px; font-weight:800; color:#1e293b; line-height:1;">
                    <a href="?page=data-siswa" style="color:#1e293b; text-decoration:none;"><?php echo hitung_siswa(); ?></a>
                  </div>
                  <div style="font-size:11px; color:#94a3b8; margin-top:4px;">Peserta didik terdaftar</div>
                </div>
                <div style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg,#ede9fe,#ddd6fe); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                  <i class="fas fa-user-graduate" style="font-size:20px; color:#6366f1;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Jumlah Kelas -->
        <div class="col-md-4 col-sm-6 mb-3">
          <div class="card border-0 shadow-sm h-100" style="border-radius:14px; background:#fff; border-left:4px solid #f59e0b !important;">
            <div class="card-body py-3 px-4">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:6px;">Jumlah Kelas</div>
                  <div style="font-size:28px; font-weight:800; color:#1e293b; line-height:1;">
                    <?php echo hitung_kelas(); ?>
                  </div>
                  <div style="font-size:11px; color:#94a3b8; margin-top:4px;">Rombongan belajar aktif</div>
                </div>
                <div style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg,#fef3c7,#fde68a); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                  <i class="fas fa-chalkboard" style="font-size:20px; color:#f59e0b;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- End of Content Row 1 -->

      <!-- Pengumuman Section -->
      <div class="row mx-auto mb-4">
        <div class="col-12">
          <div class="card shadow" style="border-radius: 15px;">
            <div class="card-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-bullhorn"></i> Pengumuman</h5>
                <a href="?page=lihat-pengumuman" class="btn btn-light btn-sm">Lihat Semua</a>
              </div>
            </div>
            <div class="card-body">
              <?php
              $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
              $pengumuman_query = mysqli_query($conn, "SELECT * FROM tbl_pengumuman WHERE status = 'aktif' AND id_sekolah = $idSekolah ORDER BY created_at DESC LIMIT 5");
              if ($pengumuman_query && mysqli_num_rows($pengumuman_query) > 0) {
                while ($p = mysqli_fetch_assoc($pengumuman_query)) {
                  echo '<div class="alert alert-info" role="alert">';
                  echo '<h6 class="alert-heading">' . htmlspecialchars($p['judul']) . '</h6>';
                  echo '<p>' . nl2br(htmlspecialchars(substr($p['isi'], 0, 200))) . '...</p>';
                  echo '<small class="text-muted">Dibuat: ' . date('d-m-Y H:i', strtotime($p['created_at'])) . '</small>';
                  echo '</div>';
                }
              } else {
                echo '<p class="text-muted">Tidak ada pengumuman aktif saat ini.</p>';
              }
              ?>
            </div>
          </div>
        </div>
      </div>
      <!-- End Pengumuman Section -->

      <!-- Dashboard Charts Section -->
      <div class="row mx-auto mb-4">
        <!-- Period Filter -->
        <div class="col-12 mb-3">
          <div class="card border-0 shadow-sm" style="border-radius: 15px;">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0 font-weight-bold" style="color:#1a202c;">
                  <i class="fas fa-chart-line me-2" style="color:#4e73df;"></i>
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


    <?php endif; ?>
    <?php
        // bracket di bawah ini adalah penutup else dari is isset request
      }
    ?>


    <!-- /.container-fluid -->


  </div>
  <!-- End of Main Content -->

  <?php include "footer.php"; ?><!-- End of Main Content -->

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

    function escapeHtml(value) {
      const div = document.createElement('div');
      div.textContent = value == null ? '' : String(value);
      return div.innerHTML;
    }

    function agendaUnitPalette(unitName) {
      const val = (unitName || '').toLowerCase();
      if (val.includes('kurikulum')) {
        return {
          bg: '#eff6ff',
          text: '#1d4ed8',
          border: '#bfdbfe'
        };
      }
      if (val.includes('kesiswaan')) {
        return {
          bg: '#ecfdf5',
          text: '#047857',
          border: '#a7f3d0'
        };
      }
      if (val.includes('humas')) {
        return {
          bg: '#fff7ed',
          text: '#c2410c',
          border: '#fdba74'
        };
      }
      if (val.includes('sarpras') || val.includes('sarana') || val.includes('prasarana')) {
        return {
          bg: '#f5f3ff',
          text: '#6d28d9',
          border: '#c4b5fd'
        };
      }
      return {
        bg: '#f8fafc',
        text: '#334155',
        border: '#cbd5e1'
      };
    }

    function formatAgendaCountdown(targetAt) {
      if (!targetAt) return 'Waktu agenda tidak valid';

      const target = new Date(targetAt.replace(' ', 'T'));
      if (Number.isNaN(target.getTime())) {
        return 'Waktu agenda tidak valid';
      }

      const diffMs = target.getTime() - Date.now();
      if (diffMs <= 0) {
        return 'Agenda selesai';
      }

      const totalSeconds = Math.floor(diffMs / 1000);
      const days = Math.floor(totalSeconds / 86400);
      const hours = Math.floor((totalSeconds % 86400) / 3600);
      const minutes = Math.floor((totalSeconds % 3600) / 60);
      const seconds = totalSeconds % 60;

      if (days > 0) {
        return `${days}h ${hours}j ${minutes}m ${seconds}d lagi`;
      }
      if (hours > 0) {
        return `${hours}j ${minutes}m ${seconds}d lagi`;
      }
      if (minutes > 0) {
        return `${minutes}m ${seconds}d lagi`;
      }

      return `${seconds} detik lagi`;
    }

    function updateAgendaHomeCountdowns() {
      const countdownEls = document.querySelectorAll('.agenda-countdown');
      countdownEls.forEach((el) => {
        const targetAt = el.getAttribute('data-target') || '';
        el.textContent = formatAgendaCountdown(targetAt);
      });
    }

    function renderAgendaHome(items) {
      const listEl = document.getElementById('agendaHomeList');
      const emptyEl = document.getElementById('agendaHomeEmpty');
      if (!listEl || !emptyEl) return;

      if (!Array.isArray(items) || items.length === 0) {
        listEl.style.display = 'none';
        listEl.innerHTML = '';
        emptyEl.style.display = '';
        return;
      }

      const html = items.map((item, idx) => {
        const palette = agendaUnitPalette(item.dibuat_unit || '');
        const title = escapeHtml(item.judul || 'Agenda');
        const unit = escapeHtml(item.dibuat_unit || 'Umum');
        const descRaw = String(item.deskripsi || '');
        const desc = escapeHtml(descRaw).replace(/\n/g, '<br>');
        const agendaDateLabel = escapeHtml(item.agenda_date_label || '');
        const jamMulai = escapeHtml(item.jam_mulai || '');
        const jamSelesai = escapeHtml(item.jam_selesai || '');
        const targetAt = escapeHtml(item.target_at || '');

        return `<div class="col-lg-6 mb-3" style="opacity:0; transform:translateY(8px); animation:agendaHomeCardIn .45s ease forwards; animation-delay:${idx * 70}ms;">
                  <div class="p-3" style="border:1px solid #ccfbf1; border-radius:14px; background:linear-gradient(135deg,#f0fdfa,#ffffff); height:100%;">
                    <div class="d-flex justify-content-between align-items-start" style="gap:10px;">
                      <div>
                        <div style="font-size:13px; font-weight:800; color:#0f172a;">${title}</div>
                        <div style="font-size:11px; margin-top:2px;">
                          <span class="badge" style="background:${palette.bg}; color:${palette.text}; border:1px solid ${palette.border};">${unit}</span>
                        </div>
                      </div>
                    </div>
                    ${descRaw ? `<div style="font-size:12px; color:#475569; margin-top:8px;">${desc}</div>` : ''}
                    <div style="font-size:12px; color:#334155; margin-top:10px; font-weight:600;">
                      <i class="fas fa-clock mr-1"></i>
                      ${agendaDateLabel} &nbsp;${jamMulai} - ${jamSelesai}
                    </div>
                    <div class="agenda-countdown" data-target="${targetAt}" style="margin-top:8px; font-size:12px; color:#0f766e; font-weight:800;">Menghitung waktu...</div>
                  </div>
                </div>`;
      }).join('');

      listEl.innerHTML = html;
      listEl.style.display = '';
      emptyEl.style.display = 'none';
      updateAgendaHomeCountdowns();
    }

    function refreshAgendaHome() {
      fetch('api/agenda-active.php?limit=6')
        .then((response) => response.json())
        .then((res) => {
          if (!res || res.success !== true || !Array.isArray(res.data)) {
            return;
          }
          renderAgendaHome(res.data);
        })
        .catch(() => {
          // Keep existing rendered server-side agenda when API fails.
        });
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
        // Intercept back button to stay on beranda
        if (window.history && window.history.pushState) {
          window.history.pushState('forward', null, window.location.href);
          window.addEventListener('popstate', function() {
            window.history.pushState('forward', null, window.location.href);
          });
        }

        updateAgendaHomeCountdowns();
        setInterval(updateAgendaHomeCountdowns, 1000);
        refreshAgendaHome();
        setInterval(refreshAgendaHome, 60000);

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
