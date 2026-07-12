<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['no_induk'])) {
  header("location:../../index.php?haruslogin");
  exit();
} elseif ((int)($_SESSION['hak_akses'] ?? 0) !== 3) {
?>
  <script>
    window.location = '../../404.html';
  </script>
<?php
  exit();
}

include "../../koneksi.php";
date_default_timezone_set('Asia/Jakarta');

$noInduk = $_SESSION['no_induk'];
$namaSiswa = $_SESSION['nama_siswa'] ?? '';
$kelas = $_SESSION['kelas'] ?? '';
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantSiswa = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_siswa', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$tenantPengaturan = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_pengaturan', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";

// Auto-migrate kolom profil siswa dan pengaturan global edit profil.
$cols = [
  'nisn' => "ALTER TABLE tbl_siswa ADD COLUMN nisn VARCHAR(20) DEFAULT NULL AFTER no_induk",
  'alamat' => "ALTER TABLE tbl_siswa ADD COLUMN alamat TEXT DEFAULT NULL",
  'lat' => "ALTER TABLE tbl_siswa ADD COLUMN lat VARCHAR(30) DEFAULT NULL",
  'lng' => "ALTER TABLE tbl_siswa ADD COLUMN lng VARCHAR(30) DEFAULT NULL",
  'no_wa' => "ALTER TABLE tbl_siswa ADD COLUMN no_wa VARCHAR(20) DEFAULT NULL",
  'no_darurat' => "ALTER TABLE tbl_siswa ADD COLUMN no_darurat VARCHAR(20) DEFAULT NULL",
  'nama_darurat' => "ALTER TABLE tbl_siswa ADD COLUMN nama_darurat VARCHAR(100) DEFAULT NULL",
  'rencana_setelah_lulus' => "ALTER TABLE tbl_siswa ADD COLUMN rencana_setelah_lulus VARCHAR(80) DEFAULT NULL",
  'rencana_detail' => "ALTER TABLE tbl_siswa ADD COLUMN rencana_detail TEXT DEFAULT NULL",
  'minat_jurusan' => "ALTER TABLE tbl_siswa ADD COLUMN minat_jurusan VARCHAR(160) DEFAULT NULL",
  'bakat_minat' => "ALTER TABLE tbl_siswa ADD COLUMN bakat_minat TEXT DEFAULT NULL",
  'dukungan_dibutuhkan' => "ALTER TABLE tbl_siswa ADD COLUMN dukungan_dibutuhkan TEXT DEFAULT NULL",
];

foreach ($cols as $col => $sqlAlter) {
  $chk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_siswa LIKE '$col'");
  if ($chk && mysqli_num_rows($chk) === 0) {
    @mysqli_query($conn, $sqlAlter);
  }
}

$noIndukEsc = mysqli_real_escape_string($conn, $noInduk);

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pengaturan (
  kunci VARCHAR(60) PRIMARY KEY,
  nilai VARCHAR(255) DEFAULT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (function_exists('mt_add_school_column') && $conn instanceof mysqli) {
  mt_add_school_column($conn, 'tbl_pengaturan');
}
@mysqli_query($conn, "INSERT IGNORE INTO tbl_pengaturan (kunci,nilai" . (strpos($tenantPengaturan, 'id_sekolah=') === 0 ? ",id_sekolah" : "") . ") VALUES ('izin_edit_profil','0'" . (strpos($tenantPengaturan, 'id_sekolah=') === 0 ? ",{$tenantId}" : "") . ")");

$qIzinGlobal = @mysqli_query($conn, "SELECT nilai FROM tbl_pengaturan WHERE kunci='izin_edit_profil' AND {$tenantPengaturan} LIMIT 1");
$izinEdit = 0;
if ($qIzinGlobal && ($rIzinGlobal = mysqli_fetch_assoc($qIzinGlobal))) {
  $izinEdit = ((string)($rIzinGlobal['nilai'] ?? '0') === '1') ? 1 : 0;
}

$schemaCols = [];
$resCols = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_siswa');
if ($resCols) {
  while ($rowCol = mysqli_fetch_assoc($resCols)) {
    $schemaCols[] = $rowCol['Field'];
  }
}

$hasColumn = static function (string $column) use ($schemaCols): bool {
  return in_array($column, $schemaCols, true);
};

$profileLabelMap = [
  'no_induk' => 'No. Induk / NIS',
  'nipd' => 'Nipd',
  'nisn' => 'NISN',
  'nama_siswa' => 'Nama Siswa',
  'jk' => 'Jk',
  'kelas' => 'Kelas',
  'jabatan' => 'Jabatan',
  'status' => 'Status',
  'alamat' => 'Alamat',
  'lat' => 'Latitude',
  'lng' => 'Longitude',
  'no_wa' => 'No. WhatsApp',
  'no_darurat' => 'No. HP Keluarga',
  'nama_darurat' => 'Nama Keluarga Darurat',
  'tempat_lahir' => 'Tempat Lahir',
  'tanggal_lahir' => 'Tanggal Lahir',
  'nik' => 'Nik',
  'agama' => 'Agama',
  'rt' => 'Rt',
  'rw' => 'Rw',
  'dusun' => 'Dusun',
  'kelurahan' => 'Kelurahan',
  'kecamatan' => 'Kecamatan',
  'kode_pos' => 'Kode Pos',
  'jenis_tinggal' => 'Jenis Tinggal',
  'alat_transportasi' => 'Alat Transportasi',
  'telepon' => 'Telepon',
  'hp' => 'Hp',
  'email' => 'Email',
  'skhun' => 'Skhun',
  'rombel_saat_ini' => 'Rombel Saat Ini',
  'no_peserta_un' => 'No Peserta Un',
  'no_seri_ijazah' => 'No Seri Ijazah',
  'sekolah_asal' => 'Sekolah Asal',
  'penerima_kps' => 'Penerima Kps',
  'no_kps' => 'No Kps',
  'penerima_kip' => 'Penerima Kip',
  'nomor_kip' => 'Nomor Kip',
  'nama_di_kip' => 'Nama Di Kip',
  'nomor_kks' => 'Nomor Kks',
  'bank' => 'Bank',
  'nomor_rekening_bank' => 'Nomor Rekening Bank',
  'rekening_atas_nama' => 'Rekening Atas Nama',
  'layak_pip' => 'Layak Pip',
  'alasan_layak_pip' => 'Alasan Layak Pip',
  'no_reg_akta_lahir' => 'No Reg Akta Lahir',
  'kebutuhan_khusus' => 'Kebutuhan Khusus',
  'anak_ke' => 'Anak Ke',
  'bujur' => 'Bujur',
  'no_kk' => 'No Kk',
  'berat_badan' => 'Berat Badan',
  'tinggi_badan' => 'Tinggi Badan',
  'lingkar_kepala' => 'Lingkar Kepala',
  'jumlah_saudara_kandung' => 'Jumlah Saudara Kandung',
  'jarak_rumah_km' => 'Jarak Rumah Km',
  'ayah_nama' => 'Ayah Nama',
  'ayah_tahun_lahir' => 'Ayah Tahun Lahir',
  'ayah_pendidikan' => 'Ayah Pendidikan',
  'ayah_pekerjaan' => 'Ayah Pekerjaan',
  'ayah_penghasilan' => 'Ayah Penghasilan',
  'ayah_nik' => 'Ayah Nik',
  'ibu_nama' => 'Ibu Nama',
  'ibu_tahun_lahir' => 'Ibu Tahun Lahir',
  'ibu_pendidikan' => 'Ibu Pendidikan',
  'ibu_pekerjaan' => 'Ibu Pekerjaan',
  'ibu_penghasilan' => 'Ibu Penghasilan',
  'ibu_nik' => 'Ibu Nik',
  'wali_nama' => 'Wali Nama',
  'wali_tahun_lahir' => 'Wali Tahun Lahir',
  'wali_pendidikan' => 'Wali Pendidikan',
  'wali_pekerjaan' => 'Wali Pekerjaan',
  'wali_penghasilan' => 'Wali Penghasilan',
  'wali_nik' => 'Wali Nik',
  'lintang' => 'Lintang',
  'nama_kelas' => 'Nama Kelas',
  'foto_depan_path' => 'Foto Depan Path',
  'izin_edit_profil' => 'Izin Edit Profil',
  'rencana_setelah_lulus' => 'Rencana Setelah Lulus',
  'rencana_detail' => 'Detail Rencana',
  'minat_jurusan' => 'Minat Jurusan / Bidang',
  'bakat_minat' => 'Bakat dan Minat',
  'dukungan_dibutuhkan' => 'Dukungan yang Dibutuhkan',
];

$labelForColumn = static function (string $column) use ($profileLabelMap): string {
  return $profileLabelMap[$column] ?? ucwords(str_replace('_', ' ', $column));
};

$formatProfileValue = static function (string $column, $value): string {
  if ($value === null || $value === '') {
    return '—';
  }
  if ($column === 'alamat') {
    return nl2br(htmlspecialchars((string)$value));
  }
  return htmlspecialchars((string)$value);
};

$qSiswa = mysqli_query($conn, "SELECT * FROM tbl_siswa WHERE {$tenantSiswa} AND no_induk='$noIndukEsc' LIMIT 1");
$siswa = ($qSiswa ? mysqli_fetch_assoc($qSiswa) : []) ?: [];

$izinEditGlobal = ((int)$izinEdit === 1);
$izinEditSiswa = ((string)($siswa['izin_edit_profil'] ?? '0') === '1');
$izinEdit = ($izinEditGlobal || $izinEditSiswa);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['_simpan_profil'])) {
  if (!$izinEdit) {
    $_SESSION['_profil_msg'] = 'Profil sedang dikunci admin. Data tidak dapat diubah.';
    $_SESSION['_profil_msg_type'] = 'danger';
    header('Location: profil.php');
    exit;
  }

  $blockedColumns = ['password', 'token', 'remember_token'];
  $updateParts = [];
  foreach ($schemaCols as $field) {
    if (in_array($field, $blockedColumns, true)) {
      continue;
    }
    if (!array_key_exists($field, $_POST)) {
      continue;
    }
    $value = trim((string)$_POST[$field]);
    $safeValue = mysqli_real_escape_string($conn, $value);
    $updateParts[] = "{$field}='{$safeValue}'";
  }

  $newNoInduk = trim((string)($_POST['no_induk'] ?? ''));
  if ($newNoInduk !== '' && $newNoInduk !== $noInduk) {
      $cekKey = mysqli_query($conn, "SELECT no_induk FROM tbl_siswa WHERE no_induk='" . mysqli_real_escape_string($conn, $newNoInduk) . "' AND {$tenantSiswa}");
      if (mysqli_num_rows($cekKey) > 0) {
          $_SESSION['_profil_msg'] = 'Nomor Induk sudah digunakan oleh siswa lain.';
          $_SESSION['_profil_msg_type'] = 'danger';
          header('Location: profil.php');
          exit;
      }
      mysqli_query($conn, "UPDATE tbl_pengguna SET username='" . mysqli_real_escape_string($conn, $newNoInduk) . "', no_induk='" . mysqli_real_escape_string($conn, $newNoInduk) . "' WHERE no_induk='$noIndukEsc'");
  }

  if (!empty($updateParts) && @mysqli_query($conn, "UPDATE tbl_siswa SET " . implode(', ', $updateParts) . " WHERE {$tenantSiswa} AND no_induk='$noIndukEsc' LIMIT 1")) {
    if ($newNoInduk !== '' && $newNoInduk !== $noInduk) {
        $_SESSION['no_induk'] = $newNoInduk;
    }
    $_SESSION['_profil_msg'] = 'Profil berhasil diperbarui.';
    $_SESSION['_profil_msg_type'] = 'success';
  } else {
    $_SESSION['_profil_msg'] = 'Gagal memperbarui profil. Coba lagi.';
    $_SESSION['_profil_msg_type'] = 'danger';
  }

  header('Location: profil.php');
  exit;
}

$namaSiswa = $siswa['nama_siswa'] ?? $namaSiswa;
$kelas = $siswa['kelas'] ?? $kelas;

$displayColumns = [];
foreach ($siswa as $column => $value) {
  if (in_array($column, ['password', 'token', 'remember_token'], true)) {
    continue;
  }
  $displayColumns[] = $column;
}

$existingInputCols = [
  'nama_siswa',
  'nisn',
  'kelas',
  'status',
  'jabatan',
  'alamat',
  'lat',
  'lng',
  'no_wa',
  'nama_darurat',
  'no_darurat',
];
$additionalEditableColumns = [];
foreach ($displayColumns as $column) {
  if ($column === 'no_induk' || in_array($column, $existingInputCols, true)) {
    continue;
  }
  $additionalEditableColumns[] = $column;
}

$editableGroupsMap = [];
foreach ($additionalEditableColumns as $column) {
  $groupName = 'Lainnya';

  if (strpos($column, 'ayah_') === 0 || strpos($column, 'ibu_') === 0) {
    $groupName = 'Data Orang Tua';
  } elseif (strpos($column, 'wali_') === 0) {
    $groupName = 'Data Wali';
  } elseif (in_array($column, ['penerima_kps', 'no_kps', 'penerima_kip', 'nomor_kip', 'nama_di_kip', 'nomor_kks', 'bank', 'nomor_rekening_bank', 'rekening_atas_nama', 'layak_pip', 'alasan_layak_pip'], true)) {
    $groupName = 'Program Bantuan';
  } elseif (in_array($column, ['alamat', 'rt', 'rw', 'dusun', 'kelurahan', 'kecamatan', 'kode_pos', 'jenis_tinggal', 'alat_transportasi', 'telepon', 'hp', 'email', 'lat', 'lng', 'lintang', 'bujur', 'no_wa', 'no_kk'], true)) {
    $groupName = 'Domisili dan Kontak';
  } elseif (in_array($column, ['rencana_setelah_lulus', 'rencana_detail', 'minat_jurusan', 'bakat_minat', 'dukungan_dibutuhkan'], true)) {
    $groupName = 'Arah Minat dan Rencana Masa Depan';
  } elseif (in_array($column, ['nipd', 'jk', 'tempat_lahir', 'tanggal_lahir', 'nik', 'agama', 'skhun', 'rombel_saat_ini', 'no_peserta_un', 'no_seri_ijazah', 'sekolah_asal', 'no_reg_akta_lahir', 'kebutuhan_khusus', 'anak_ke', 'berat_badan', 'tinggi_badan', 'lingkar_kepala', 'jumlah_saudara_kandung', 'jarak_rumah_km', 'nama_kelas', 'foto_depan_path'], true)) {
    $groupName = 'Identitas Siswa';
  }

  if (!isset($editableGroupsMap[$groupName])) {
    $editableGroupsMap[$groupName] = [];
  }
  $editableGroupsMap[$groupName][] = $column;
}

$groupOrder = ['Arah Minat dan Rencana Masa Depan', 'Identitas Siswa', 'Domisili dan Kontak', 'Program Bantuan', 'Data Orang Tua', 'Data Wali', 'Lainnya'];
$editableGroups = [];
foreach ($groupOrder as $groupName) {
  if (!empty($editableGroupsMap[$groupName])) {
    $editableGroups[$groupName] = $editableGroupsMap[$groupName];
  }
}

$notifMsg = $_SESSION['_profil_msg'] ?? '';
$notifType = $_SESSION['_profil_msg_type'] ?? 'info';
unset($_SESSION['_profil_msg'], $_SESSION['_profil_msg_type']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
  <title>Profil Siswa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary-color: #4361ee;
      --secondary-color: #3f37c9;
      --bg-light: #f1f5f9;
      --text-main: #1e293b;
      --card-radius: 1.25rem;
    }

    body {
      background-color: var(--bg-light);
      font-family: 'Inter', sans-serif;
      color: var(--text-main);
      padding-bottom: 80px;
      /* Space for bottom nav */
    }

    /* Modern Top Bar */
    .app-header {
      background: white;
      border-bottom: 1px solid #e2e8f0;
      position: sticky;
      top: 0;
      z-index: 1020;
    }

    /* Profile Header Section */
    .profile-hero {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      padding: 2rem 1rem 4rem 1rem;
      color: white;
      text-align: center;
      border-radius: 0 0 2rem 2rem;
      margin-bottom: -2.5rem;
    }

    .avatar-wrapper {
      position: relative;
      display: inline-block;
      margin-bottom: 0.5rem;
    }

    .avatar-circle {
      width: 85px;
      height: 85px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
      border: 3px solid rgba(255, 255, 255, 0.8);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.25rem;
      font-weight: 700;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    /* Card Styling */
    .card-app {
      background: white;
      border: none;
      border-radius: var(--card-radius);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
      margin-bottom: 1rem;
    }

    .profile-form-wrap {
      max-width: 980px;
      margin: 0 auto;
      margin-top: 1rem;
    }

    .section-intro-text {
      margin-bottom: 0.85rem;
    }

    .profile-accordion-item {
      border: 1px solid #e2e8f0;
      border-radius: 0.9rem;
      overflow: hidden;
      margin-bottom: 0.65rem;
    }

    .profile-accordion-item .accordion-button {
      font-weight: 700;
      background: #f8fafc;
      color: #0f172a;
      box-shadow: none;
    }

    .profile-accordion-item .accordion-button:not(.collapsed) {
      background: #eef2ff;
      color: #1e1b4b;
    }

    .profile-accordion-item .accordion-button:focus {
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.12);
    }

    .accordion-field-count {
      margin-left: 0.65rem;
      font-size: 0.72rem;
      font-weight: 700;
    }

    .form-label-custom {
      font-size: 0.75rem;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.025em;
      margin-bottom: 0.4rem;
    }

    .form-control-app {
      border: 1.5px solid #e2e8f0;
      border-radius: 0.75rem;
      padding: 0.6rem 1rem;
      transition: all 0.2s;
    }

    .form-control-app:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
    }

    /* Bottom Navigation */
    .bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: white;
      height: 70px;
      display: flex;
      justify-content: space-around;
      align-items: center;
      border-top: 1px solid #e2e8f0;
      z-index: 1030;
      box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.05);
    }

    .nav-item-app {
      text-decoration: none;
      color: #94a3b8;
      display: flex;
      flex-direction: column;
      align-items: center;
      font-size: 0.7rem;
      font-weight: 600;
    }

    .nav-item-app.active {
      color: var(--primary-color);
    }

    .nav-item-app i {
      font-size: 1.35rem;
      margin-bottom: 2px;
    }

    /* Bottom Save Button */
    .fab-save {
      width: 100%;
      height: auto;
      border-radius: 0.9rem;
      background: #22c55e;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.85rem 1rem;
      font-weight: 700;
      box-shadow: 0 8px 14px -4px rgba(34, 197, 94, 0.35);
      border: none;
      margin-bottom: 1rem;
    }

    .map-container-app {
      height: 250px;
      border-radius: 1rem;
      overflow: hidden;
      margin-top: 10px;
      border: 1px solid #e2e8f0;
    }

    /* Minimalist Progress Bar */
    .progress-container {
      background: white;
      border-radius: var(--card-radius);
      padding: 1.5rem;
      margin-bottom: 1rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
      border: 1px solid #f1f5f9;
    }

    .progress-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1rem;
    }

    .progress-title {
      font-size: 1rem;
      font-weight: 700;
      color: var(--text-main);
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .progress-percentage {
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--primary-color);
      margin: 0;
      text-shadow: 0 1px 2px rgba(67, 97, 238, 0.2);
    }

    .progress-bar-wrapper {
      position: relative;
      width: 100%;
      height: 8px;
      background: linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 100%);
      border-radius: 4px;
      overflow: hidden;
      box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #f5576c 75%, #4facfe 100%);
      background-size: 200% 100%;
      border-radius: 4px;
      transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      box-shadow: 0 0 10px rgba(102, 126, 234, 0.3);
    }

    .progress-fill::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
      animation: progressShine 2s ease-in-out infinite;
    }

    @keyframes progressShine {
      0% {
        left: -100%;
      }

      100% {
        left: 100%;
      }
    }

    .progress-stats {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 0.75rem;
      font-size: 0.75rem;
      color: #64748b;
      font-weight: 600;
    }

    .progress-stat {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.125rem;
    }

    .progress-stat-number {
      font-size: 0.875rem;
      font-weight: 800;
      color: var(--text-main);
    }

    .progress-stat-label {
      font-size: 0.625rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      opacity: 0.8;
    }

    .edit-status-banner {
      display: flex;
      align-items: flex-start;
      gap: 0.85rem;
      border-radius: 1rem;
      padding: 0.95rem 1rem;
    }

    .edit-status-banner .status-icon {
      width: 2.35rem;
      height: 2.35rem;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 auto;
    }

    .edit-status-banner .status-copy {
      flex: 1;
      min-width: 0;
    }

    .edit-status-banner .status-title {
      font-weight: 800;
      margin: 0 0 0.15rem 0;
      line-height: 1.25;
    }

    .edit-status-banner .status-text {
      margin: 0;
      font-size: 0.9rem;
      line-height: 1.45;
    }

    .profile-mobile-note {
      margin-top: 0.5rem;
      color: #64748b;
      font-size: 0.82rem;
    }

    .location-search-row {
      display: flex;
      gap: 0.5rem;
      align-items: center;
    }

    .location-search-row .btn {
      min-width: 3rem;
      flex: 0 0 auto;
    }

    @media (max-width: 480px) {
      body {
        padding-bottom: 88px;
      }

      .container {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
      }

      .profile-form-wrap {
        margin-top: 0.75rem;
      }

      .profile-hero {
        padding: 1.65rem 1rem 3.35rem 1rem;
        border-radius: 0 0 1.5rem 1.5rem;
      }

      .avatar-circle {
        width: 74px;
        height: 74px;
        font-size: 2rem;
      }

      .card-app {
        border-radius: 1rem;
      }

      .progress-container {
        padding: 1.1rem;
      }

      .progress-header {
        align-items: flex-start;
      }

      .progress-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.5rem;
      }

      .progress-stat {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.85rem;
        padding: 0.55rem 0.35rem;
      }

      .edit-status-banner {
        padding: 0.85rem;
      }

      .edit-status-banner .status-title {
        font-size: 0.95rem;
      }

      .edit-status-banner .status-text {
        font-size: 0.85rem;
      }

      .location-search-row {
        flex-direction: column;
      }

      .location-search-row .btn {
        width: 100%;
      }
    }
  </style>
</head>

<body>

  <header class="app-header px-3 py-2">
    <div class="d-flex align-items-center justify-content-between">
      <a href="siswa.php" class="text-dark"><i class="fas fa-chevron-left fa-lg"></i></a>
      <h6 class="mb-0 fw-bold">Profil Akun</h6>
      <a href="../../logout.php" class="text-danger"><i class="fas fa-power-off"></i></a>
    </div>
  </header>

  <section class="profile-hero">
    <div class="avatar-wrapper">
      <div class="avatar-circle">
        <?= mb_strtoupper(mb_substr($namaSiswa, 0, 1)) ?>
      </div>
    </div>
    <h5 class="fw-bold mb-0"><?= htmlspecialchars($namaSiswa) ?></h5>
    <p class="small opacity-75 mb-0"><?= htmlspecialchars($kelas) ?></p>
  </section>

  <div class="container px-3 px-md-4 profile-form-wrap">
    <form method="POST" action="" id="formProfil">
      <input type="hidden" name="_simpan_profil" value="1">

      <div class="alert <?= $izinEdit ? 'alert-info' : 'alert-warning' ?> border-0 shadow-sm mb-4 edit-status-banner">
        <div class="status-icon bg-white text-<?= $izinEdit ? 'primary' : 'warning' ?>">
          <i class="fas <?= $izinEdit ? 'fa-unlock' : 'fa-lock' ?>"></i>
        </div>
        <div class="status-copy">
          <div class="status-title"><?= $izinEdit ? 'Mode edit aktif' : 'Profil sedang dikunci' ?></div>
          <p class="status-text">
            <?= $izinEdit ? 'Pastikan alamat, koordinat, dan nomor kontak sudah akurat sebelum disimpan.' : 'Admin sedang menutup akses edit profil. Data tetap bisa dilihat, tetapi tidak bisa diubah.' ?>
          </p>
        </div>
      </div>

      <?php if ($notifMsg !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($notifType) ?> border-0 shadow-sm" style="border-radius:1rem;">
          <?= htmlspecialchars($notifMsg) ?>
        </div>
      <?php endif; ?>

      <!-- Minimalist Progress Bar -->
      <div class="progress-container">
        <div class="progress-header">
          <h6 class="progress-title">
            <i class="fas fa-chart-line text-primary"></i>
            Kelengkapan Profil
          </h6>
          <span class="progress-percentage" id="completionPercentage">0%</span>
        </div>

        <div class="progress-bar-wrapper">
          <div class="progress-fill" id="progressFill" style="width: 0%"></div>
        </div>

        <div class="progress-stats">
          <div class="progress-stat">
            <span class="progress-stat-number" id="filledFields">0</span>
            <span class="progress-stat-label">Terisi</span>
          </div>
          <div class="progress-stat">
            <span class="progress-stat-number" id="totalFields">0</span>
            <span class="progress-stat-label">Total</span>
          </div>

          <div class="progress-stat">
            <span class="progress-stat-number" id="emptyFields">0</span>
            <span class="progress-stat-label">Kosong</span>
          </div>
        </div>
      </div>

      <div class="card-app p-4">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label-custom">Nomor Induk Siswa</label>
            <input type="text" name="no_induk" class="form-control form-control-app <?= $izinEdit ? '' : 'bg-light' ?>" value="<?= htmlspecialchars($noInduk) ?>" <?= $izinEdit ? '' : 'readonly' ?>>
          </div>
          <?php if ($hasColumn('nama_siswa')): ?>
            <div class="col-12">
              <label class="form-label-custom">Nama Siswa</label>
              <input type="text" name="nama_siswa" class="form-control form-control-app" value="<?= htmlspecialchars($siswa['nama_siswa'] ?? '') ?>" <?= $izinEdit ? '' : 'readonly' ?> placeholder="Masukkan nama siswa">
            </div>
          <?php endif; ?>
          <div class="col-12">
            <label class="form-label-custom">Nomor Induk Siswa Nasional (NISN)</label>
            <input type="text" name="nisn" class="form-control form-control-app" value="<?= htmlspecialchars($siswa['nisn'] ?? '') ?>" <?= $izinEdit ? '' : 'readonly' ?> placeholder="Input 10 digit NISN">
          </div>
          <?php if ($hasColumn('kelas')): ?>
            <div class="col-md-6">
              <label class="form-label-custom">Kelas</label>
              <?php if ($izinEdit): ?>
                <?php
                $qKelas = mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE {$tenantSiswa} ORDER BY kelas ASC");
                ?>
                <select name="kelas" class="form-select form-control-app">
                  <option value="">Pilih Kelas</option>
                  <?php while ($rowKelas = mysqli_fetch_assoc($qKelas)): ?>
                    <option value="<?= htmlspecialchars($rowKelas['kelas']) ?>" <?= ($siswa['kelas'] ?? '') === $rowKelas['kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($rowKelas['kelas']) ?></option>
                  <?php endwhile; ?>
                </select>
              <?php else: ?>
                <input type="text" name="kelas" class="form-control form-control-app" value="<?= htmlspecialchars($siswa['kelas'] ?? '') ?>" readonly placeholder="Contoh: X IPA 1">
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ($hasColumn('status')): ?>
            <div class="col-md-6">
              <label class="form-label-custom">Status</label>
              <input type="text" class="form-control form-control-app bg-light" value="<?= htmlspecialchars(ucwords((string)($siswa['status'] ?? ''))) ?>" readonly>
              <input type="hidden" name="status" value="<?= htmlspecialchars((string)($siswa['status'] ?? '')) ?>">
            </div>
          <?php endif; ?>
          <?php if ($hasColumn('jabatan')): ?>
            <div class="col-12">
              <label class="form-label-custom">Jabatan</label>
              <input type="text" name="jabatan" class="form-control form-control-app" value="<?= htmlspecialchars($siswa['jabatan'] ?? '') ?>" <?= $izinEdit ? '' : 'readonly' ?> placeholder="Contoh: Ketua Kelas">
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card-app p-4">
        <div class="d-flex align-items-center mb-3">
          <i class="fas fa-map-marker-alt text-primary me-2"></i>
          <h6 class="mb-0 fw-bold">Lokasi Rumah</h6>
        </div>

        <div class="mb-3">
          <label class="form-label-custom">Alamat Lengkap</label>
          <textarea name="alamat" id="alamat" class="form-control form-control-app" rows="3" <?= $izinEdit ? '' : 'readonly' ?> placeholder="Jl. Nama Jalan, No. Rumah..."><?= htmlspecialchars($siswa['alamat'] ?? '') ?></textarea>
        </div>

        <?php if ($izinEdit): ?>
          <div class="mb-3">
            <div class="location-search-row">
              <input type="text" id="searchAlamat" class="form-control form-control-app flex-grow-1" placeholder="Cari desa/lokasi...">
              <button type="button" id="btnSearchAlamat" class="btn btn-primary rounded-3"><i class="fas fa-search"></i></button>
            </div>
            <div class="profile-mobile-note">Pencarian lokasi lebih nyaman dipakai di layar besar, tetapi tetap bisa dijalankan di HP.</div>
          </div>

          <button type="button" id="btnGps" class="btn btn-outline-primary w-100 mb-3 py-2 rounded-3 fw-bold">
            <i class="fas fa-crosshairs me-2" id="gpsIcon"></i> <span id="gpsLabel">Ambil Posisi Sekarang</span>
          </button>
        <?php endif; ?>

        <div class="row g-2 mb-3">
          <div class="col-6">
            <input type="text" name="lat" id="lat" class="form-control form-control-app small bg-light" value="<?= htmlspecialchars($siswa['lat'] ?? '') ?>" placeholder="Lat" readonly>
          </div>
          <div class="col-6">
            <input type="text" name="lng" id="lng" class="form-control form-control-app small bg-light" value="<?= htmlspecialchars($siswa['lng'] ?? '') ?>" placeholder="Lng" readonly>
          </div>
        </div>

        <button type="button" onclick="toggleMapPanel()" class="btn btn-dark w-100 rounded-3 py-2 fw-bold">
          <i class="fas fa-map me-2"></i> <span id="mapBtnLabel">Buka Panel Peta</span>
        </button>

        <div id="mapPanel" class="d-none mt-3">
          <div class="map-container-app">
            <iframe id="mapIframe" style="width:100%;height:100%;border:none;"></iframe>
          </div>
        </div>
      </div>

      <div class="card-app p-4">
        <div class="d-flex align-items-center mb-3">
          <i class="fab fa-whatsapp text-success me-2"></i>
          <h6 class="mb-0 fw-bold">Informasi Kontak</h6>
        </div>
        <div class="mb-3">
          <label class="form-label-custom">WhatsApp Siswa</label>
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0" style="border-radius: 0.75rem 0 0 0.75rem;">+62</span>
            <input type="tel" name="no_wa" class="form-control form-control-app border-start-0" value="<?= htmlspecialchars($siswa['no_wa'] ?? '') ?>" <?= $izinEdit ? '' : 'readonly' ?>>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label-custom">Nama Wali (Darurat)</label>
          <input type="text" name="nama_darurat" class="form-control form-control-app" value="<?= htmlspecialchars($siswa['nama_darurat'] ?? '') ?>" <?= $izinEdit ? '' : 'readonly' ?>>
        </div>
        <div class="mb-0">
          <label class="form-label-custom">No. HP Wali</label>
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0" style="border-radius: 0.75rem 0 0 0.75rem;">+62</span>
            <input type="tel" name="no_darurat" class="form-control form-control-app border-start-0" value="<?= htmlspecialchars($siswa['no_darurat'] ?? '') ?>" <?= $izinEdit ? '' : 'readonly' ?>>
          </div>
        </div>
      </div>

      <div class="card-app p-4">
        <div class="d-flex align-items-center mb-3">
          <i class="fas fa-layer-group text-primary me-2"></i>
          <h6 class="mb-0 fw-bold">Input Tambahan Data Siswa</h6>
        </div>
        <p class="small text-muted section-intro-text">Field dasar tetap ada di bagian atas. Bagian ini menampilkan kolom tambahan dari tabel siswa.</p>
        <?php if (empty($editableGroups)): ?>
          <div class="text-muted small">Tidak ada field tambahan yang perlu ditampilkan.</div>
        <?php else: ?>
          <div class="accordion" id="accordionInputTambahan">
            <?php $groupIndex = 0; ?>
            <?php foreach ($editableGroups as $groupName => $columns): ?>
              <?php
              $groupIndex++;
              $headingId = 'headingInputTambahan' . $groupIndex;
              $collapseId = 'collapseInputTambahan' . $groupIndex;
              $showGroup = ($groupIndex === 1);
              ?>
              <div class="accordion-item profile-accordion-item">
                <h2 class="accordion-header" id="<?= $headingId ?>">
                  <button class="accordion-button <?= $showGroup ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="<?= $showGroup ? 'true' : 'false' ?>" aria-controls="<?= $collapseId ?>">
                    <?= htmlspecialchars($groupName) ?>
                    <span class="badge text-bg-light border accordion-field-count"><?= count($columns) ?> field</span>
                  </button>
                </h2>
                <div id="<?= $collapseId ?>" class="accordion-collapse collapse <?= $showGroup ? 'show' : '' ?>" aria-labelledby="<?= $headingId ?>" data-bs-parent="#accordionInputTambahan">
                  <div class="accordion-body pt-3">
                    <div class="row g-3">
                      <?php foreach ($columns as $column): ?>
                        <div class="col-12 col-md-6">
                          <label class="form-label-custom"><?= htmlspecialchars($labelForColumn($column)) ?></label>
                          <?php if ($column === 'tanggal_lahir'): ?>
                            <input type="date" name="<?= htmlspecialchars($column) ?>" class="form-control form-control-app" value="<?= htmlspecialchars((string)($siswa[$column] ?? '')) ?>" <?= $izinEdit ? '' : 'readonly' ?>>
                          <?php elseif ($column === 'agama'): ?>
                            <?php
                            $currentAgama = trim((string)($siswa[$column] ?? ''));
                            $agamaOptions = ['Islam', 'Hindu', 'Buddha', 'Kristen', 'Katolik', 'Lainnya'];
                            $foundAgama = false;
                            foreach ($agamaOptions as $opt) {
                                if (strcasecmp($currentAgama, $opt) === 0) {
                                    $foundAgama = true;
                                    break;
                                }
                            }
                            if ($currentAgama !== '' && !$foundAgama) {
                                $agamaOptions[] = ucfirst(strtolower($currentAgama));
                            }
                            ?>
                            <select name="<?= htmlspecialchars($column) ?>" class="form-select form-control-app" <?= $izinEdit ? '' : 'disabled' ?>>
                              <option value="">Pilih agama...</option>
                              <?php foreach ($agamaOptions as $opt): ?>
                                <option value="<?= htmlspecialchars($opt) ?>" <?= strcasecmp($currentAgama, $opt) === 0 ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                              <?php endforeach; ?>
                            </select>
                            <?php if (!$izinEdit): ?>
                              <input type="hidden" name="<?= htmlspecialchars($column) ?>" value="<?= htmlspecialchars($currentAgama) ?>">
                            <?php endif; ?>
                          <?php elseif ($column === 'rencana_setelah_lulus'): ?>
                            <?php
                            $currentPlan = (string)($siswa[$column] ?? '');
                            $planOptions = ['Kuliah', 'Kerja', 'Wirausaha', 'Kursus/Sertifikasi', 'Kedinasan/TNI/Polri', 'Belum Menentukan', 'Lainnya'];
                            ?>
                            <select name="<?= htmlspecialchars($column) ?>" class="form-select form-control-app" <?= $izinEdit ? '' : 'disabled' ?>>
                              <option value="">Pilih rencana</option>
                              <?php foreach ($planOptions as $planOption): ?>
                                <option value="<?= htmlspecialchars($planOption) ?>" <?= $currentPlan === $planOption ? 'selected' : '' ?>><?= htmlspecialchars($planOption) ?></option>
                              <?php endforeach; ?>
                            </select>
                            <?php if (!$izinEdit): ?>
                              <input type="hidden" name="<?= htmlspecialchars($column) ?>" value="<?= htmlspecialchars($currentPlan) ?>">
                            <?php endif; ?>
                          <?php elseif ($column === 'alamat'): ?>
                            <textarea name="<?= htmlspecialchars($column) ?>" class="form-control form-control-app" rows="3" <?= $izinEdit ? '' : 'readonly' ?>><?= htmlspecialchars((string)($siswa[$column] ?? '')) ?></textarea>
                          <?php elseif (in_array($column, ['rencana_detail', 'bakat_minat', 'dukungan_dibutuhkan'], true)): ?>
                            <textarea name="<?= htmlspecialchars($column) ?>" class="form-control form-control-app" rows="3" <?= $izinEdit ? '' : 'readonly' ?> placeholder="Tuliskan dengan singkat dan jelas"><?= htmlspecialchars((string)($siswa[$column] ?? '')) ?></textarea>
                          <?php elseif (in_array($column, ['penerima_kps', 'penerima_kip', 'layak_pip', 'izin_edit_profil'], true)): ?>
                            <select name="<?= htmlspecialchars($column) ?>" class="form-select form-control-app" <?= $izinEdit ? '' : 'disabled' ?>>
                              <?php $boolValue = (string)($siswa[$column] ?? '0'); ?>
                              <option value="0" <?= $boolValue === '0' ? 'selected' : '' ?>>0</option>
                              <option value="1" <?= $boolValue === '1' ? 'selected' : '' ?>>1</option>
                            </select>
                            <?php if (!$izinEdit): ?>
                              <input type="hidden" name="<?= htmlspecialchars($column) ?>" value="<?= htmlspecialchars($boolValue) ?>">
                            <?php endif; ?>
                          <?php else: ?>
                            <input type="text" name="<?= htmlspecialchars($column) ?>" class="form-control form-control-app" value="<?= htmlspecialchars((string)($siswa[$column] ?? '')) ?>" <?= $izinEdit ? '' : 'readonly' ?>>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card-app p-4 mb-4">
        <h6 class="mb-3 fw-bold">Ringkasan Lokasi dan Kontak</h6>
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <div class="border rounded-3 p-3 h-100">
              <div class="form-label-custom mb-2">Alamat Lengkap</div>
              <div><?= $formatProfileValue('alamat', $siswa['alamat'] ?? '') ?></div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="border rounded-3 p-3 h-100">
              <div class="form-label-custom mb-2">Koordinat</div>
              <div><?= htmlspecialchars((string)($siswa['lat'] ?? '')) ?>, <?= htmlspecialchars((string)($siswa['lng'] ?? '')) ?></div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="border rounded-3 p-3 h-100">
              <div class="form-label-custom mb-2">WhatsApp Siswa</div>
              <div><?= $formatProfileValue('no_wa', $siswa['no_wa'] ?? '') ?></div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="border rounded-3 p-3 h-100">
              <div class="form-label-custom mb-2">Kontak Darurat</div>
              <div><?= $formatProfileValue('nama_darurat', $siswa['nama_darurat'] ?? '') ?></div>
              <div class="small text-muted"><?= $formatProfileValue('no_darurat', $siswa['no_darurat'] ?? '') ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="card-app p-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
          <div>
            <h6 class="mb-1 fw-bold">Ubah Password Akun</h6>
            <p class="small text-muted mb-0">Kelola password login siswa dari halaman terpisah yang aman.</p>
          </div>
          <a href="../../ubah-password.php" class="btn btn-outline-primary rounded-3 fw-bold">Buka Halaman Password</a>
        </div>
      </div>

      <div class="card-app p-4 mb-4" style="border: 1px solid #fecaca; background-color: #fef2f2;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
          <div>
            <h6 class="mb-1 fw-bold text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Hapus Data Akun</h6>
            <p class="small text-danger mb-0">Ajukan penghapusan akun dan seluruh data profil Anda secara permanen dari sistem sesuai kebijakan Google Play.</p>
          </div>
          <a href="hapus-data.php" class="btn btn-danger rounded-3 fw-bold" onclick="return confirm('Apakah Anda yakin ingin mengajukan penghapusan seluruh data profil dan akun Anda? Tindakan ini tidak dapat dibatalkan.')">Hapus Data</a>
        </div>
      </div>

      <?php if ($izinEdit): ?>
        <button type="submit" class="fab-save">
          <i class="fas fa-save"></i>
          <span>Simpan Perubahan Profil</span>
        </button>
      <?php endif; ?>

    </form>
  </div>

  <?php 
  $lembagaQuery = mysqli_query($conn, "SELECT nmsekolah FROM tbl_lembaga WHERE id_sekolah = " . (int)($tenantId ?? 1));
  $lembagaRow = mysqli_fetch_assoc($lembagaQuery);
  $nmsekolah = $lembagaRow['nmsekolah'] ?? 'Portal Siswa';
  ?>
  <!-- Footer info -->
  <p style="text-align:center;font-size:0.7rem;color:var(--muted);padding: 20px 0 30px;">
    &copy; <?= date('Y') ?> <?= htmlspecialchars($nmsekolah) ?>
  </p>

  <?php include 'siswa_footer.php'; ?>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Copy logika JavaScript lama Anda di sini (btnGps, reverseGeocode, toggleMapPanel, dll)
    // Pastikan ID elemen (lat, lng, alamat, dll) tetap sama agar sinkron.

    // Progress Bar Calculation and Animation
    function calculateProfileCompletion() {
      const form = document.getElementById('formProfil');
      if (!form) {
        return {
          percentage: 0,
          filled: 0,
          total: 0,
          empty: 0
        };
      }

      const candidates = Array.from(form.querySelectorAll('input[name], textarea[name], select[name]'));
      const ignoredInputTypes = ['hidden', 'button', 'submit', 'reset', 'file', 'image'];
      const fields = candidates.filter(field => {
        if (field.tagName !== 'INPUT') {
          return true;
        }
        return !ignoredInputTypes.includes((field.type || '').toLowerCase());
      });

      let filledCount = 0;
      const totalFields = fields.length;

      fields.forEach(field => {
        const tag = (field.tagName || '').toUpperCase();
        const type = (field.type || '').toLowerCase();

        if (tag === 'INPUT' && (type === 'checkbox' || type === 'radio')) {
          if (field.checked) {
            filledCount++;
          }
          return;
        }

        const value = (field.value || '').trim();
        if (value !== '') {
          filledCount++;
        }
      });

      const percentage = totalFields > 0 ? Math.round((filledCount / totalFields) * 100) : 0;
      const emptyCount = totalFields - filledCount;

      return {
        percentage: percentage,
        filled: filledCount,
        total: totalFields,
        empty: emptyCount
      };
    }

    function animateProgressBar(targetPercentage, stats) {
      const progressFill = document.getElementById('progressFill');
      const percentageElement = document.getElementById('completionPercentage');
      const filledElement = document.getElementById('filledFields');
      const totalElement = document.getElementById('totalFields');
      const emptyElement = document.getElementById('emptyFields');

      // Animate progress bar
      progressFill.style.width = '0%';
      setTimeout(() => {
        progressFill.style.transition = 'width 1.5s cubic-bezier(0.4, 0, 0.2, 1)';
        progressFill.style.width = targetPercentage + '%';
      }, 200);

      // Animate numbers with counter effect
      animateCounter(percentageElement, 0, targetPercentage, 1000, '%');
      animateCounter(filledElement, 0, stats.filled, 800);
      animateCounter(totalElement, 0, stats.total, 800);
      animateCounter(emptyElement, 0, stats.empty, 800);
    }

    function animateCounter(element, start, end, duration, suffix = '') {
      const startTime = performance.now();
      const difference = end - start;

      function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        const currentValue = Math.floor(start + difference * progress);
        element.textContent = currentValue + suffix;

        if (progress < 1) {
          requestAnimationFrame(updateCounter);
        }
      }

      requestAnimationFrame(updateCounter);
    }

    function refreshProfileCompletion() {
      const stats = calculateProfileCompletion();
      animateProgressBar(stats.percentage, stats);
    }

    // Initialize progress bar on page load
    document.addEventListener('DOMContentLoaded', function() {
      refreshProfileCompletion();

      // Update progress on form input changes
      const formFields = document.querySelectorAll('#formProfil input[name], #formProfil textarea[name], #formProfil select[name]');
      formFields.forEach(field => {
        field.addEventListener('input', function() {
          setTimeout(refreshProfileCompletion, 150);
        });
        field.addEventListener('change', refreshProfileCompletion);
      });

      const latInput = document.getElementById('lat');
      const lngInput = document.getElementById('lng');
      const alamatInput = document.getElementById('alamat');
      const btnGps = document.getElementById('btnGps');
      const gpsLabel = document.getElementById('gpsLabel');
      const gpsIcon = document.getElementById('gpsIcon');
      const searchAlamatInput = document.getElementById('searchAlamat');
      const btnSearchAlamat = document.getElementById('btnSearchAlamat');
      let hasAutoRequestedLocation = false;

      function setGpsBusy(isBusy, labelText) {
        if (!btnGps || !gpsLabel || !gpsIcon) {
          return;
        }
        btnGps.disabled = isBusy;
        gpsLabel.textContent = labelText;
        gpsIcon.classList.toggle('fa-crosshairs', !isBusy);
        gpsIcon.classList.toggle('fa-spinner', isBusy);
        gpsIcon.classList.toggle('fa-spin', isBusy);
      }

      function setCoordinate(lat, lng) {
        if (!latInput || !lngInput) {
          return;
        }
        latInput.value = Number(lat).toFixed(6);
        lngInput.value = Number(lng).toFixed(6);
        refreshProfileCompletion();
        if (typeof loadMap === 'function') {
          loadMap(latInput.value, lngInput.value);
        }
      }

      async function reverseGeocode(lat, lng) {
        if (!alamatInput) {
          return;
        }
        try {
          const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=18&addressdetails=1`;
          const response = await fetch(url, {
            headers: {
              'Accept': 'application/json'
            }
          });
          if (!response.ok) {
            return;
          }
          const data = await response.json();
          if (data && data.display_name) {
            alamatInput.value = data.display_name;
            refreshProfileCompletion();
          }
        } catch (error) {
          // Keep silent: coordinate capture should still succeed even if reverse geocode fails.
        }
      }

      async function searchAlamat() {
        if (!searchAlamatInput) {
          return;
        }
        const keyword = (searchAlamatInput.value || '').trim();
        if (keyword === '') {
          return;
        }

        if (btnSearchAlamat) {
          btnSearchAlamat.disabled = true;
        }

        try {
          const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(keyword)}`;
          const response = await fetch(url, {
            headers: {
              'Accept': 'application/json'
            }
          });
          if (!response.ok) {
            alert('Gagal mencari lokasi. Coba lagi.');
            return;
          }
          const results = await response.json();
          if (!Array.isArray(results) || results.length === 0) {
            alert('Lokasi tidak ditemukan.');
            return;
          }

          const first = results[0];
          setCoordinate(first.lat, first.lon);
          if (alamatInput && first.display_name) {
            alamatInput.value = first.display_name;
          }
          searchAlamatInput.value = first.display_name || keyword;
          refreshProfileCompletion();
        } catch (error) {
          alert('Terjadi kesalahan saat mencari lokasi.');
        } finally {
          if (btnSearchAlamat) {
            btnSearchAlamat.disabled = false;
          }
        }
      }

      function openBrowserLocationSettings() {
        const ua = navigator.userAgent || '';
        let settingsUrl = '';

        if (ua.includes('Edg/')) {
          settingsUrl = 'edge://settings/content/location';
        } else if (ua.includes('Chrome/')) {
          settingsUrl = 'chrome://settings/content/location';
        } else if (ua.includes('Firefox/')) {
          settingsUrl = 'about:preferences#privacy';
        }

        if (settingsUrl) {
          try {
            window.open(settingsUrl, '_blank');
          } catch (error) {
            // Ignore if browser blocks opening internal settings URLs.
          }
        }

        alert('Izin lokasi masih ditolak. Silakan aktifkan izin lokasi untuk situs ini dari pengaturan browser (ikon gembok di address bar), lalu coba lagi.');
      }

      function requestCurrentLocation() {
        if (!navigator.geolocation) {
          alert('Browser tidak mendukung geolocation.');
          return;
        }

        setGpsBusy(true, 'Mengambil lokasi...');
        navigator.geolocation.getCurrentPosition(async function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            setCoordinate(lat, lng);
            await reverseGeocode(lat, lng);
            setGpsBusy(false, 'Ambil Posisi Sekarang');
          },
          function(error) {
            setGpsBusy(false, 'Ambil Posisi Sekarang');

            if (error && error.code === 1) {
              openBrowserLocationSettings();
              return;
            }
            if (error && error.code === 2) {
              alert('Lokasi tidak tersedia.');
              return;
            }
            if (error && error.code === 3) {
              alert('Permintaan lokasi timeout.');
              return;
            }
            alert('Gagal mengambil lokasi.');
          }, {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
          });
      }

      async function setupGeolocationPermissionFlow() {
        if (!btnGps || !navigator.geolocation) {
          return;
        }

        if (!navigator.permissions || !navigator.permissions.query) {
          return;
        }

        try {
          const permission = await navigator.permissions.query({
            name: 'geolocation'
          });

          const updateByPermission = function(state) {
            if (!gpsLabel) {
              return;
            }
            if (state === 'denied') {
              gpsLabel.textContent = 'Izin Ditolak, Buka Pengaturan';
              return;
            }
            gpsLabel.textContent = 'Ambil Posisi Sekarang';
          };

          updateByPermission(permission.state);
          permission.onchange = function() {
            updateByPermission(permission.state);
          };

          if (permission.state === 'prompt' && !hasAutoRequestedLocation) {
            hasAutoRequestedLocation = true;
            requestCurrentLocation();
          }
        } catch (error) {
          // Skip permission precheck when not supported by browser.
        }
      }

      if (btnGps) {
        btnGps.addEventListener('click', function() {
          if (gpsLabel && gpsLabel.textContent.includes('Buka Pengaturan')) {
            openBrowserLocationSettings();
            return;
          }
          requestCurrentLocation();
        });
      }

      setupGeolocationPermissionFlow();

      if (btnSearchAlamat) {
        btnSearchAlamat.addEventListener('click', searchAlamat);
      }

      if (searchAlamatInput) {
        searchAlamatInput.addEventListener('keydown', function(event) {
          if (event.key === 'Enter') {
            event.preventDefault();
            searchAlamat();
          }
        });
      }
    });

    // Logika tambahan untuk animasi smooth
    function toggleMapPanel() {
      const panel = document.getElementById('mapPanel');
      const label = document.getElementById('mapBtnLabel');
      panel.classList.toggle('d-none');
      if (!panel.classList.contains('d-none')) {
        label.innerText = "Tutup Peta";
        loadMap(document.getElementById('lat').value, document.getElementById('lng').value);
      } else {
        label.innerText = "Buka Panel Peta";
      }
    }

    function loadMap(lat, lng) {
      const iframe = document.getElementById('mapIframe');
      if (!lat || !lng) return;
      const osmEmbed = `https://www.openstreetmap.org/export/embed.html?bbox=${parseFloat(lng)-0.003},${parseFloat(lat)-0.003},${parseFloat(lng)+0.003},${parseFloat(lat)+0.003}&layer=mapnik&marker=${lat},${lng}`;
      iframe.src = osmEmbed;
    }
  </script>
</body>

</html>
