<?php
require_once __DIR__ . '/../../bootstrap.php';
require_login();

if (!is_guru()) {
    header('Location: ../../403.php');
    exit;
}

date_default_timezone_set('Asia/Jakarta');

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../../koneksi.php';
}

require_once __DIR__ . '/../../functions.php';

$no_induk = (string)($_SESSION['no_induk'] ?? '');
if ($no_induk === '') {
    header('Location: ../../index.php?haruslogin');
    exit;
}

@mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN IF NOT EXISTS no_wa VARCHAR(20) DEFAULT NULL AFTER nama_guru");
@mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN IF NOT EXISTS alamat TEXT DEFAULT NULL AFTER no_wa");
@mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN IF NOT EXISTS jabatan VARCHAR(100) DEFAULT NULL AFTER status_kepegawaian");
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pengaturan (
    kunci VARCHAR(60) PRIMARY KEY,
    nilai VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
@mysqli_query($conn, "INSERT IGNORE INTO tbl_pengaturan (kunci,nilai) VALUES ('izin_edit_profil_guru','0')");

$folderFoto = __DIR__ . '/../../foto/';
$urlFoto    = '../../foto/';
$pesan = '';
$tipePesan = 'success';

$noIndukEsc = mysqli_real_escape_string($conn, $no_induk);
$qIzinEdit = @mysqli_query($conn, "SELECT nilai FROM tbl_pengaturan WHERE kunci='izin_edit_profil_guru' LIMIT 1");
$izinEditProfilGuru = false;
if ($qIzinEdit && ($rIzinEdit = mysqli_fetch_assoc($qIzinEdit))) {
    $izinEditProfilGuru = ((string)($rIzinEdit['nilai'] ?? '0')) === '1';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$izinEditProfilGuru) {
        $pesan = 'Edit profil guru sedang dikunci oleh admin.';
        $tipePesan = 'danger';
    }

    $nama_guru = trim((string)($_POST['nama_guru'] ?? ''));
    $no_wa = trim((string)($_POST['no_wa'] ?? ''));
    $alamat = trim((string)($_POST['alamat'] ?? ''));
    $jabatan = trim((string)($_POST['jabatan'] ?? ''));
    $status_kepegawaian = trim((string)($_POST['status_kepegawaian'] ?? ''));
    $is_guru_bk = isset($_POST['is_guru_bk']) ? 1 : 0;
    $is_pendamping_literasi = isset($_POST['is_pendamping_literasi']) ? 1 : 0;
    $is_tim_aduan = isset($_POST['is_tim_aduan']) ? 1 : 0;
    $id_kelas_wali = isset($_POST['wali_kelas']) ? (int)$_POST['wali_kelas'] : 0;
    $walas_status = ($id_kelas_wali > 0) ? 'Ya' : 'Tidak';

    $qOld = mysqli_query($conn, "SELECT foto FROM tbl_guru WHERE no_induk='$noIndukEsc' LIMIT 1");
    $old = $qOld ? mysqli_fetch_assoc($qOld) : null;
    $fotoLama = (string)($old['foto'] ?? '');
    $fotoBaru = $fotoLama;

    if ($pesan === '' && $nama_guru === '') {
        $pesan = 'Nama guru wajib diisi.';
        $tipePesan = 'danger';
    }

    if ($pesan === '' && isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $namaFile = (string)$_FILES['foto']['name'];
            $tmpFile = (string)$_FILES['foto']['tmp_name'];
            $ukuran = (int)$_FILES['foto']['size'];
            $ext = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed, true)) {
                $pesan = 'Format foto harus JPG, JPEG, PNG, atau WEBP.';
                $tipePesan = 'danger';
            } elseif ($ukuran > 2 * 1024 * 1024) {
                $pesan = 'Ukuran foto maksimal 2MB.';
                $tipePesan = 'danger';
            } else {
                if (!is_dir($folderFoto)) {
                    mkdir($folderFoto, 0755, true);
                }

                $fotoBaru = 'guru_' . $no_induk . '_' . time() . '.' . $ext;

                if (!move_uploaded_file($tmpFile, $folderFoto . $fotoBaru)) {
                    $pesan = 'Gagal mengupload foto.';
                    $tipePesan = 'danger';
                    $fotoBaru = $fotoLama;
                } elseif ($fotoLama !== '' && $fotoLama !== $fotoBaru && file_exists($folderFoto . $fotoLama)) {
                    @unlink($folderFoto . $fotoLama);
                }
            }
        } else {
            $pesan = 'Terjadi kesalahan saat upload foto.';
            $tipePesan = 'danger';
        }
    }

    if ($pesan === '') {
        $namaEsc = mysqli_real_escape_string($conn, $nama_guru);
        $waEsc = mysqli_real_escape_string($conn, $no_wa);
        $alamatEsc = mysqli_real_escape_string($conn, $alamat);
        $jabatanEsc = mysqli_real_escape_string($conn, $jabatan);
        $statusKepEsc = mysqli_real_escape_string($conn, $status_kepegawaian);
        $fotoEsc = mysqli_real_escape_string($conn, $fotoBaru);

        $update = mysqli_query($conn, "
            UPDATE tbl_guru SET
                nama_guru='$namaEsc',
                no_wa='$waEsc',
                alamat='$alamatEsc',
                jabatan='$jabatanEsc',
                status_kepegawaian='$statusKepEsc',
                is_guru_bk=$is_guru_bk,
                is_pendamping_literasi=$is_pendamping_literasi,
                is_tim_aduan=$is_tim_aduan,
                walas='$walas_status',
                foto='$fotoEsc'
            WHERE no_induk='$noIndukEsc'
        ");

        if ($update) {
            // --- SINKRONISASI WALI KELAS ---
            $tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
            $old_wk_q = mysqli_query($conn, "SELECT id_kelas FROM tbl_wali_kelas WHERE nip_wali='$noIndukEsc' AND id_sekolah=$tenantId");
            while($row_old = mysqli_fetch_assoc($old_wk_q)) {
                $old_id = $row_old['id_kelas'];
                mysqli_query($conn, "UPDATE tbl_kelas SET wali_kelas=NULL, nip_wali=NULL WHERE id_kelas=$old_id AND id_sekolah=$tenantId");
            }
            mysqli_query($conn, "DELETE FROM tbl_wali_kelas WHERE nip_wali='$noIndukEsc' AND id_sekolah=$tenantId");

            if ($id_kelas_wali > 0) {
                mysqli_query($conn, "DELETE FROM tbl_wali_kelas WHERE id_kelas=$id_kelas_wali AND id_sekolah=$tenantId");
                $tgl_now = date('Y-m-d H:i:s');
                mysqli_query($conn, "INSERT INTO tbl_wali_kelas(id_kelas, nip_wali, nama_wali, id_sekolah, created_at, updated_at) VALUES($id_kelas_wali, '$noIndukEsc', '$namaEsc', $tenantId, '$tgl_now', '$tgl_now')");
                mysqli_query($conn, "UPDATE tbl_kelas SET wali_kelas='$namaEsc', nip_wali='$noIndukEsc' WHERE id_kelas=$id_kelas_wali AND id_sekolah=$tenantId");
            }
            // -------------------------------

            $_SESSION['nama_guru'] = $nama_guru;
            $pesan = 'Profil berhasil diperbarui.';
            $tipePesan = 'success';
        } else {
            $pesan = 'Profil gagal diperbarui: ' . mysqli_error($conn);
            $tipePesan = 'danger';
        }
    }
}

$qGuru = mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$noIndukEsc' LIMIT 1");
$guru = $qGuru ? mysqli_fetch_assoc($qGuru) : null;

if (!$guru) {
    echo "<script>alert('Data guru tidak ditemukan'); window.location='../../home.php';</script>";
    exit;
}

$foto = !empty($guru['foto']) ? $urlFoto . rawurlencode($guru['foto']) : '';

$pageTitle = 'Profil Guru';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle); ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
* {
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    margin: 0;
    color: #0f172a;
    background: #f8fafc;
    font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.profile-wrapper {
    max-width: 1050px;
    margin: 0 auto;
    padding: 24px 14px 70px;
}

.profile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 22px;
}

.profile-title h1 {
    margin: 0;
    color: #0f172a;
    font-size: 30px;
    font-weight: 800;
}

.profile-title p {
    margin: 6px 0 0;
    color: #64748b;
}

.profile-card {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 20px;
}

.profile-left,
.profile-right {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, .08);
}

.profile-left {
    padding: 26px;
    text-align: center;
}

.photo-box {
    width: 160px;
    height: 160px;
    margin: 0 auto 16px;
    position: relative;
}

.photo-box img {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    object-fit: cover;
    border: 6px solid #ffffff;
    box-shadow: 0 12px 28px rgba(15, 23, 42, .18);
    background: #f1f5f9;
}

.photo-placeholder {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    overflow: hidden;
    border: 6px solid #ffffff;
    box-shadow: 0 12px 28px rgba(15, 23, 42, .18);
    background: #eff6ff;
}

.photo-placeholder svg {
    width: 100%;
    height: 100%;
    display: block;
}

.photo-button {
    position: absolute;
    right: 4px;
    bottom: 8px;
    width: 44px;
    height: 44px;
    border: 0;
    border-radius: 50%;
    background: #2563eb;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 10px 22px rgba(37, 99, 235, .35);
}

.photo-button:hover {
    background: #1d4ed8;
}

.profile-name {
    margin: 10px 0 4px;
    color: #0f172a;
    font-size: 21px;
    font-weight: 800;
}

.profile-nip {
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
    word-break: break-word;
}

.profile-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-top: 16px;
    padding: 8px 12px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 800;
    font-size: 13px;
}

.profile-right {
    padding: 26px;
}

.form-title {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 20px;
    color: #0f172a;
    font-size: 17px;
    font-weight: 800;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-label {
    display: block;
    margin-bottom: 7px;
    color: #475569;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.form-control-profile {
    width: 100%;
    min-height: 46px;
    border: 1px solid #dbe4ef;
    border-radius: 12px;
    padding: 11px 13px;
    color: #0f172a;
    background: #ffffff;
    font-size: 14px;
    font-weight: 600;
    outline: none;
    transition: .18s ease;
}

.form-control-profile:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
}

textarea.form-control-profile {
    min-height: 105px;
    resize: vertical;
}

.form-control-profile[readonly] {
    background: #f8fafc;
    color: #64748b;
}

.alert-profile {
    margin-bottom: 18px;
    padding: 13px 15px;
    border-radius: 13px;
    font-weight: 700;
}

.alert-profile.success {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.alert-profile.danger {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.alert-profile.warning {
    background: #fffbeb;
    color: #92400e;
    border: 1px solid #fde68a;
}

.action-row {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
}

.btn-profile {
    min-height: 43px;
    padding: 10px 16px;
    border-radius: 12px;
    border: 1px solid transparent;
    font-weight: 800;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
}

.btn-light-profile {
    background: #ffffff;
    border-color: #dbe4ef;
    color: #334155;
}

.btn-primary-profile {
    background: #2563eb;
    color: #ffffff;
    box-shadow: 0 10px 22px rgba(37, 99, 235, .22);
}

.btn-primary-profile:hover {
    background: #1d4ed8;
}

.help-text {
    margin-top: 8px;
    color: #64748b;
    font-size: 12px;
}

@media (max-width: 900px) {
    .profile-card {
        grid-template-columns: 1fr;
    }

    .profile-header {
        align-items: flex-start;
        flex-direction: column;
    }
}

@media (max-width: 620px) {
    .profile-wrapper {
        padding-top: 12px;
    }

    .profile-title h1 {
        font-size: 25px;
    }

    .profile-left,
    .profile-right {
        padding: 20px;
        border-radius: 16px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .action-row {
        flex-direction: column;
    }

    .btn-profile {
        width: 100%;
    }
}
</style>
<link rel="stylesheet" href="css/guru-desktop.css?v=<?= time() ?>">
</head>
<body style="background: #ebf1f6;">
<?php include 'guru_sidebar_shared.php'; ?>

<div class="app-shell" style="grid-template-columns: 1fr; padding-right: 24px;">
    <div class="desktop-center-column">
        <!-- Welcome Banner -->
        <div class="welcome-banner-premium mb-4">
            <div class="banner-content">
                <div class="banner-text">
                    <h2 class="animate-fade-in" style="font-size:2.2rem;font-weight:800;margin-bottom:12px;letter-spacing:-0.5px;">Profil Saya 👤</h2>
                    <p class="banner-subtitle" style="font-size:1.05rem;opacity:0.9;">Kelola identitas dan foto profil Anda.</p>
                </div>
                <div class="banner-actions">
                    <a href="../../home.php" class="btn-premium-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>
                </div>
            </div>
            <div class="banner-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>
        <div class="profile-wrapper" style="padding-top:0;">

    <?php if ($pesan !== ''): ?>
        <div class="alert-profile <?= htmlspecialchars($tipePesan); ?>">
            <?= htmlspecialchars($pesan); ?>
        </div>
    <?php endif; ?>

    <?php if (!$izinEditProfilGuru): ?>
        <div class="alert-profile warning">
            <i class="bi bi-lock-fill"></i>
            Edit profil sedang dikunci oleh admin. Anda hanya dapat melihat data profil.
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="profile-card">
        <div class="profile-left">
            <div class="photo-box">
                <?php if ($foto !== ''): ?>
                    <img src="<?= htmlspecialchars($foto); ?>" id="previewFoto" alt="Foto Profil">
                <?php else: ?>
                    <div class="photo-placeholder" id="previewPlaceholder">
                        <?= get_guru_avatar_svg(get_guru_gender((string)($guru['no_induk'] ?? $no_induk), (string)($guru['nama_guru'] ?? 'Guru'))); ?>
                    </div>
                    <img src="" id="previewFoto" alt="Foto Profil" style="display:none;">
                <?php endif; ?>
                <label for="fotoInput" class="photo-button" title="Edit Foto Profil">
                    <i class="bi bi-camera-fill"></i>
                </label>
                <input type="file" id="fotoInput" name="foto" accept=".jpg,.jpeg,.png,.webp" <?= !$izinEditProfilGuru ? 'disabled' : '' ?> hidden>
            </div>

            <h2 class="profile-name"><?= htmlspecialchars($guru['nama_guru'] ?? 'Guru'); ?></h2>
            <div class="profile-nip"><?= htmlspecialchars($guru['no_induk'] ?? $no_induk); ?></div>

            <div class="profile-badge">
                <i class="bi bi-person-badge"></i>
                <?= htmlspecialchars($guru['jabatan'] ?? 'Guru'); ?>
            </div>

            <p class="help-text">Klik ikon kamera untuk mengganti foto profil. Maksimal 2MB.</p>
        </div>

        <div class="profile-right">
            <div class="form-title">
                <i class="bi bi-person-lines-fill"></i>
                Identitas Guru
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">No Induk / NIP</label>
                    <input type="text" class="form-control-profile" value="<?= htmlspecialchars($guru['no_induk'] ?? $no_induk); ?>" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">Jabatan</label>
                    <?php if (!$izinEditProfilGuru): ?>
                        <input type="text" name="jabatan" class="form-control-profile" value="<?= htmlspecialchars($guru['jabatan'] ?? ''); ?>" readonly>
                    <?php else: ?>
                        <select name="jabatan" class="form-control-profile">
                            <?php $currJabatan = $guru['jabatan'] ?? ''; ?>
                            <option value="" <?= $currJabatan === '' ? 'selected' : '' ?>>-- Guru Biasa --</option>
                            <option value="WKS Kurikulum" <?= $currJabatan === 'WKS Kurikulum' ? 'selected' : '' ?>>WKS Kurikulum</option>
                            <option value="Tim WKS Kurikulum" <?= $currJabatan === 'Tim WKS Kurikulum' ? 'selected' : '' ?>>Tim WKS Kurikulum</option>
                            <option value="WKS Kesiswaan" <?= $currJabatan === 'WKS Kesiswaan' ? 'selected' : '' ?>>WKS Kesiswaan</option>
                            <option value="Tim WKS Kesiswaan" <?= $currJabatan === 'Tim WKS Kesiswaan' ? 'selected' : '' ?>>Tim WKS Kesiswaan</option>
                            <option value="WKS Humas" <?= $currJabatan === 'WKS Humas' ? 'selected' : '' ?>>WKS Humas</option>
                            <option value="Tim WKS Humas" <?= $currJabatan === 'Tim WKS Humas' ? 'selected' : '' ?>>Tim WKS Humas</option>
                            <option value="WKS Sarpras" <?= $currJabatan === 'WKS Sarpras' ? 'selected' : '' ?>>WKS Sarpras</option>
                            <option value="Tim WKS Sarpras" <?= $currJabatan === 'Tim WKS Sarpras' ? 'selected' : '' ?>>Tim WKS Sarpras</option>
                            <option value="Kepala Sekolah" <?= $currJabatan === 'Kepala Sekolah' ? 'selected' : '' ?>>Kepala Sekolah</option>
                            <option value="STPKS" <?= $currJabatan === 'STPKS' ? 'selected' : '' ?>>STPKS</option>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="form-group full">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama_guru" class="form-control-profile" value="<?= htmlspecialchars($guru['nama_guru'] ?? ''); ?>" required <?= !$izinEditProfilGuru ? 'readonly' : '' ?>>
                </div>

                <div class="form-group">
                    <label class="form-label">No WhatsApp</label>
                    <input type="text" name="no_wa" class="form-control-profile" value="<?= htmlspecialchars($guru['no_wa'] ?? ''); ?>" placeholder="08xxxxxxxxxx" <?= !$izinEditProfilGuru ? 'readonly' : '' ?>>
                </div>

                <div class="form-group">
                    <label class="form-label">Status Kepegawaian</label>
                    <?php if (!$izinEditProfilGuru): ?>
                        <input type="text" class="form-control-profile" value="<?= htmlspecialchars($guru['status_kepegawaian'] ?? ''); ?>" readonly>
                    <?php else: ?>
                        <select name="status_kepegawaian" class="form-control-profile">
                            <?php $currStatus = $guru['status_kepegawaian'] ?? ''; ?>
                            <option value="ASN" <?= $currStatus === 'ASN' ? 'selected' : '' ?>>ASN</option>
                            <option value="Non-ASN" <?= $currStatus === 'Non-ASN' ? 'selected' : '' ?>>Non-ASN</option>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Wali Kelas</label>
                    <?php if (!$izinEditProfilGuru): ?>
                        <input type="text" class="form-control-profile" value="<?= htmlspecialchars($guru['walas'] === 'Ya' ? 'Ya' : 'Tidak Menjabat'); ?>" readonly>
                    <?php else: ?>
                        <?php
                            $tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
                            $q_wk = mysqli_query($conn, "SELECT id_kelas FROM tbl_wali_kelas WHERE nip_wali='{$guru['no_induk']}' AND id_sekolah=$tenantId LIMIT 1");
                            $current_kelas_id = ($r_wk = mysqli_fetch_assoc($q_wk)) ? $r_wk['id_kelas'] : 0;
                        ?>
                        <select name="wali_kelas" class="form-control-profile">
                            <option value="0">-- Tidak Menjabat --</option>
                            <?php
                            $q_kelas = mysqli_query($conn, "SELECT id_kelas, nama_kelas, nip_wali FROM tbl_kelas WHERE id_sekolah=$tenantId ORDER BY nama_kelas ASC");
                            while ($k = mysqli_fetch_assoc($q_kelas)) {
                                $selected = ($k['id_kelas'] == $current_kelas_id) ? "selected" : "";
                                $keterangan = "";
                                if ($k['nip_wali'] && $k['nip_wali'] != $guru['no_induk']) {
                                    $keterangan = " (Sudah ada wali)";
                                }
                                echo "<option value='{$k['id_kelas']}' $selected>{$k['nama_kelas']} $keterangan</option>";
                            }
                            ?>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="form-group full">
                    <label class="form-label">Tugas Tambahan</label>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <label style="display:flex; align-items:center; gap:8px; font-size:14px; font-weight:600; color:#334155;">
                            <input type="checkbox" name="is_guru_bk" value="1" <?= (!empty($guru['is_guru_bk']) ? 'checked' : '') ?> <?= !$izinEditProfilGuru ? 'disabled' : '' ?> style="width:18px;height:18px;">
                            Guru BK <span style="font-size:12px; font-weight:400; color:#64748b;">(Dapat memvalidasi izin siswa)</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:14px; font-weight:600; color:#334155;">
                            <input type="checkbox" name="is_pendamping_literasi" value="1" <?= (!empty($guru['is_pendamping_literasi']) ? 'checked' : '') ?> <?= !$izinEditProfilGuru ? 'disabled' : '' ?> style="width:18px;height:18px;">
                            Pendamping Literasi <span style="font-size:12px; font-weight:400; color:#64748b;">(Mengatur tugas literasi)</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:14px; font-weight:600; color:#334155;">
                            <input type="checkbox" name="is_tim_aduan" value="1" <?= (!empty($guru['is_tim_aduan']) ? 'checked' : '') ?> <?= !$izinEditProfilGuru ? 'disabled' : '' ?> style="width:18px;height:18px;">
                            Tim Aduan <span style="font-size:12px; font-weight:400; color:#64748b;">(Menerima notifikasi aduan)</span>
                        </label>
                    </div>
                </div>

                <div class="form-group full">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-control-profile" placeholder="Masukkan alamat lengkap" <?= !$izinEditProfilGuru ? 'readonly' : '' ?>><?= htmlspecialchars($guru['alamat'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="action-row">
                <a href="../../home.php" class="btn-profile btn-light-profile">
                    <i class="bi bi-x-lg"></i> Batal
                </a>
                <button type="submit" class="btn-profile btn-primary-profile" <?= !$izinEditProfilGuru ? 'disabled style="opacity:.55;cursor:not-allowed;"' : '' ?>>
                    <i class="bi bi-save2"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('fotoInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('Format foto harus JPG, PNG, atau WEBP.');
        this.value = '';
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        alert('Ukuran foto maksimal 2MB.');
        this.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        const placeholder = document.getElementById('previewPlaceholder');
        const preview = document.getElementById('previewFoto');
        if (placeholder) {
            placeholder.style.display = 'none';
        }
        preview.style.display = 'block';
        preview.src = e.target.result;
    };
    reader.readAsDataURL(file);
});
</script>
    </div>
    </div>
</div>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
