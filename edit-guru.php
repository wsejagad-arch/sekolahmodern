<?php
if (!isset($_SESSION["username"])) {
    header("location: index.php?haruslogin");
    exit;
} else if ($hakakses != 1) { ?>
    <script>
        window.location = '404.html';
    </script>
    <?php }

include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');
$idguru = $_GET['id_guru'];

// Auto-migrate: tambah kolom no_wa jika belum ada
$_chkWa = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'no_wa'");
if ($_chkWa && mysqli_num_rows($_chkWa) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN no_wa VARCHAR(20) DEFAULT NULL AFTER nama_guru");
}
$_chkBK = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'is_guru_bk'");
if ($_chkBK && mysqli_num_rows($_chkBK) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN is_guru_bk TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
}
$_chkLit = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'is_pendamping_literasi'");
if ($_chkLit && mysqli_num_rows($_chkLit) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN is_pendamping_literasi TINYINT(1) NOT NULL DEFAULT 0 AFTER is_guru_bk");
}
$_chkAduan = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'is_tim_aduan'");
if ($_chkAduan && mysqli_num_rows($_chkAduan) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN is_tim_aduan TINYINT(1) NOT NULL DEFAULT 0 AFTER is_pendamping_literasi");
}
$_chkJabatan = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE 'jabatan'");
if ($_chkJabatan && mysqli_num_rows($_chkJabatan) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN jabatan VARCHAR(100) DEFAULT NULL AFTER status_kepegawaian");
}
$tglskr = date('Y-m-d H:i:s');
// Pemrosesan form
if (isset($_POST['submit'])) {
    //definisikan variabel dulu
    $nip = trim(mysqli_real_escape_string($conn, $_POST['nip']));
    $nami = mysqli_real_escape_string($conn, $_POST['nama']);
    $no_wa = mysqli_real_escape_string($conn, trim($_POST['no_wa'] ?? ''));
    $status_kepegawaian = mysqli_real_escape_string($conn, $_POST['status_kepegawaian']);
    $jabatan = mysqli_real_escape_string($conn, trim($_POST['jabatan'] ?? ''));
    $is_guru_bk = isset($_POST['is_guru_bk']) ? 1 : 0;
    $is_pendamping_literasi = isset($_POST['is_pendamping_literasi']) ? 1 : 0;
    $is_tim_aduan = isset($_POST['is_tim_aduan']) ? 1 : 0;
    $akses = isset($_POST['is_admin']) ? '1' : '2';
    $id_kelas_wali = isset($_POST['wali_kelas']) ? (int)$_POST['wali_kelas'] : 0;
    $walas_status = ($id_kelas_wali > 0) ? 'Ya' : 'Tidak';
    $status = mysqli_real_escape_string($conn, $_POST['status_keaktifan']);
    $fotolama = $_POST['foto'];
    $namafile = $_FILES['file']['name'];
    $ukuranFile = $_FILES['file']['size'];
    $error = $_FILES['file']['error'];
    $tmpName = $_FILES['file']['tmp_name'];
    $isilog = "$nama" . " mengubah data guru dengan NIP/NUPTK " . "$nip" . " kedalam sistem";

    if ($error != UPLOAD_ERR_NO_FILE) {
        $cekfoto = cek_foto($namafile);
        
        // Ambil NIP lama untuk update relasi jika NIP diubah
        $q_old = mysqli_query($conn, "SELECT no_induk FROM tbl_guru WHERE id_guru='$idguru'");
        $old_nip = ($r_old = mysqli_fetch_assoc($q_old)) ? $r_old['no_induk'] : $nip;
        $tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
        
        $upd1 = mysqli_query($conn, "UPDATE tbl_guru SET no_induk='$nip', nama_guru='$nami', no_wa='$no_wa', status_kepegawaian='$status_kepegawaian', jabatan='$jabatan', is_guru_bk=$is_guru_bk, is_pendamping_literasi=$is_pendamping_literasi, is_tim_aduan=$is_tim_aduan, walas='$walas_status', foto='$cekfoto', status='$status' WHERE id_guru='$idguru'");
        if (!$upd1) { die("Database Update Error (1): " . mysqli_error($conn)); }
        if ($old_nip !== $nip) {
            mysqli_query($conn, "UPDATE tbl_mapel_ampu SET no_induk='$nip' WHERE no_induk='$old_nip'");
        }
        mysqli_query($conn, "UPDATE tbl_pengguna SET hak_akses='$akses', no_induk='$nip' WHERE no_induk='$old_nip'");
        
        // --- SINKRONISASI WALI KELAS ---
        $old_wk_q = mysqli_query($conn, "SELECT id_kelas FROM tbl_wali_kelas WHERE nip_wali='$old_nip' AND id_sekolah=$tenantId");
        while($row_old = mysqli_fetch_assoc($old_wk_q)) {
            $old_id = $row_old['id_kelas'];
            mysqli_query($conn, "UPDATE tbl_kelas SET wali_kelas=NULL, nip_wali=NULL WHERE id_kelas=$old_id AND id_sekolah=$tenantId");
        }
        mysqli_query($conn, "DELETE FROM tbl_wali_kelas WHERE nip_wali='$old_nip' AND id_sekolah=$tenantId");

        if ($id_kelas_wali > 0) {
            mysqli_query($conn, "DELETE FROM tbl_wali_kelas WHERE id_kelas=$id_kelas_wali AND id_sekolah=$tenantId");
            $tgl_now = date('Y-m-d H:i:s');
            mysqli_query($conn, "INSERT INTO tbl_wali_kelas(id_kelas, nip_wali, nama_wali, id_sekolah, created_at, updated_at) VALUES($id_kelas_wali, '$nip', '$nami', $tenantId, '$tgl_now', '$tgl_now')");
            mysqli_query($conn, "UPDATE tbl_kelas SET wali_kelas='$nami', nip_wali='$nip' WHERE id_kelas=$id_kelas_wali AND id_sekolah=$tenantId");
        }
        // -------------------------------
        
        move_uploaded_file($tmpName, 'foto/' . $cekfoto);
        unlink('foto/' . $fotolama);
        mysqli_query($conn, "INSERT INTO tbl_log(waktu, isi_log) VALUES('$tglskr', '$isilog')");
    ?>
        <script>
            Swal.fire({
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil merubah data guru!',
                showConfirmButton: false,
                timer: 1500
            }).then(function() {
                window.location.href = "?page=data-guru";
            })
        </script>
    <?php } else if ($error === UPLOAD_ERR_NO_FILE) {
        // Ambil NIP lama untuk update relasi jika NIP diubah
        $q_old = mysqli_query($conn, "SELECT no_induk FROM tbl_guru WHERE id_guru='$idguru'");
        $old_nip = ($r_old = mysqli_fetch_assoc($q_old)) ? $r_old['no_induk'] : $nip;
        $tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
        
        $upd2 = mysqli_query($conn, "UPDATE tbl_guru SET no_induk='$nip', nama_guru='$nami', no_wa='$no_wa', status_kepegawaian='$status_kepegawaian', jabatan='$jabatan', is_guru_bk=$is_guru_bk, is_pendamping_literasi=$is_pendamping_literasi, is_tim_aduan=$is_tim_aduan, walas='$walas_status', status='$status' WHERE id_guru='$idguru'");
        if (!$upd2) { die("Database Update Error (2): " . mysqli_error($conn)); }
        if ($old_nip !== $nip) {
            mysqli_query($conn, "UPDATE tbl_mapel_ampu SET no_induk='$nip' WHERE no_induk='$old_nip'");
        }
        mysqli_query($conn, "UPDATE tbl_pengguna SET hak_akses='$akses', no_induk='$nip' WHERE no_induk='$old_nip'");
        
        // --- SINKRONISASI WALI KELAS ---
        $old_wk_q = mysqli_query($conn, "SELECT id_kelas FROM tbl_wali_kelas WHERE nip_wali='$old_nip' AND id_sekolah=$tenantId");
        while($row_old = mysqli_fetch_assoc($old_wk_q)) {
            $old_id = $row_old['id_kelas'];
            mysqli_query($conn, "UPDATE tbl_kelas SET wali_kelas=NULL, nip_wali=NULL WHERE id_kelas=$old_id AND id_sekolah=$tenantId");
        }
        mysqli_query($conn, "DELETE FROM tbl_wali_kelas WHERE nip_wali='$old_nip' AND id_sekolah=$tenantId");

        if ($id_kelas_wali > 0) {
            mysqli_query($conn, "DELETE FROM tbl_wali_kelas WHERE id_kelas=$id_kelas_wali AND id_sekolah=$tenantId");
            $tgl_now = date('Y-m-d H:i:s');
            mysqli_query($conn, "INSERT INTO tbl_wali_kelas(id_kelas, nip_wali, nama_wali, id_sekolah, created_at, updated_at) VALUES($id_kelas_wali, '$nip', '$nami', $tenantId, '$tgl_now', '$tgl_now')");
            mysqli_query($conn, "UPDATE tbl_kelas SET wali_kelas='$nami', nip_wali='$nip' WHERE id_kelas=$id_kelas_wali AND id_sekolah=$tenantId");
        }
        // -------------------------------
        
        mysqli_query($conn, "INSERT INTO tbl_log(waktu, isi_log) VALUES('$tglskr', '$isilog')"); ?>
        <script>
            Swal.fire({
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil merubah data guru!',
                showConfirmButton: false,
                timer: 1500
            }).then(function() {
                window.location.href = "?page=data-guru";
            })
        </script>
    <?php } else { ?>
        <script>
            Swal.fire('Gagal', 'merubah data guru!', 'error')
        </script>
<?php }
}
?>

<div class="container-fluid">
    <div class="container">
        <div class="alert" style="background-color: #ffffff; outline: 1px solid lightgrey">
            <h4>Edit Data Guru</h4>
        </div>

        <?php
        // Ambil dulu data guru
        $guru = mysqli_query($conn, "SELECT g.*, p.hak_akses FROM tbl_guru g LEFT JOIN tbl_pengguna p ON g.no_induk = p.no_induk WHERE g.id_guru='$idguru'");
        $data = mysqli_fetch_array($guru);
        ?>

        <div class="container rounded" style="background-color: #ffffff; outline: 1px solid lightgrey">
            <form method="POST" action="" class="needs-validation" enctype="multipart/form-data" novalidate>

                <!-- NIP -->
                <div class="form-group col-sm-4 pt-4">
                    <label for="nip">NIP/NUPTK:</label>
                    <input type="number" class="form-control" id="nip" name="nip" value="<?= $data['no_induk']; ?>" required>
                    <div class="valid-feedback">Valid.</div>
                    <div class="invalid-feedback">Harap diisi kolom ini.</div>
                </div>
                <!-- NIP -->

                <!-- Nama Guru -->
                <div class="form-group col-sm-4">
                    <label for="nama">Nama Guru:</label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?= $data['nama_guru']; ?>" required>
                    <div class="valid-feedback">Valid.</div>
                    <div class="invalid-feedback">Harap diisi kolom ini.</div>
                </div>
                <!-- Nama Guru -->

                <!-- No. WA -->
                <div class="form-group col-sm-4">
                    <label for="no_wa">No. WhatsApp Aktif:</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                        </div>
                        <input type="text" class="form-control" id="no_wa" name="no_wa"
                            value="<?= htmlspecialchars($data['no_wa'] ?? ''); ?>"
                            placeholder="Contoh: 08123456789"
                            inputmode="numeric" maxlength="16">
                    </div>
                    <small class="text-muted">Nomor diawali 08 atau 628. Kosongkan jika tidak ada.</small>
                </div>
                <!-- No. WA -->

                <!-- Role Khusus -->
                <div class="form-group col-sm-4">
                    <label>Role Khusus:</label>
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="is_guru_bk" value="1" id="is_guru_bk"
                            <?= (!empty($data['is_guru_bk']) ? 'checked' : '') ?>>
                        <label class="form-check-label" for="is_guru_bk">
                            <strong>Guru BK</strong> <small class="text-muted">(Bimbingan Konseling — dapat memvalidasi izin siswa)</small>
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="is_pendamping_literasi" value="1" id="is_pendamping_literasi"
                            <?= (!empty($data['is_pendamping_literasi']) ? 'checked' : '') ?>>
                        <label class="form-check-label" for="is_pendamping_literasi">
                            <strong>Pendamping Literasi</strong> <small class="text-muted">(Dapat mengatur kelas & memberi tugas literasi)</small>
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="is_tim_aduan" value="1" id="is_tim_aduan"
                            <?= (!empty($data['is_tim_aduan']) ? 'checked' : '') ?>>
                        <label class="form-check-label" for="is_tim_aduan">
                            <strong>Tim Aduan</strong> <small class="text-muted">(Koordinasi aduan — menerima notifikasi aduan siswa)</small>
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="is_admin" value="1" id="is_admin"
                            <?= ((string)($data['hak_akses'] ?? '') === '1' ? 'checked' : '') ?>>
                        <label class="form-check-label" for="is_admin">
                            <strong>Admin</strong> <small class="text-muted">(Jadikan Admin — memiliki akses penuh ke dashboard admin)</small>
                        </label>
                    </div>
                </div>
                <!-- Role Khusus -->

                <!-- Status Kepegawaian -->
                <div class="form-group col-sm-4">
                    <label for="status_kepegawaian">Status Kepegawaian:</label>
                    <?php $skNow = $data['status_kepegawaian'] ?? ''; ?>
                    <select class="form-control" name="status_kepegawaian">
                        <option value="ASN"       <?= ($skNow === 'ASN'       ? 'selected' : '') ?>>ASN</option>
                        <option value="Non-ASN"   <?= ($skNow === 'Non-ASN'   ? 'selected' : '') ?>>Non-ASN</option>
                        <option value="PNS"       <?= ($skNow === 'PNS'       ? 'selected' : '') ?>>PNS</option>
                        <option value="CPNS"      <?= ($skNow === 'CPNS'      ? 'selected' : '') ?>>CPNS</option>
                        <option value="GTT/PTT"   <?= ($skNow === 'GTT/PTT'   ? 'selected' : '') ?>>GTT/PTT</option>
                        <option value="Honorer"   <?= ($skNow === 'Honorer'   ? 'selected' : '') ?>>Honorer</option>
                    </select>
                    <div class="valid-feedback">Valid.</div>
                    <div class="invalid-feedback">Harap diisi kolom ini.</div>
                </div>
                <!-- Status Kepegawaian -->

                <!-- Jabatan WKS -->
                <div class="form-group col-sm-4">
                    <label for="jabatan">Jabatan WKS:</label>
                    <select class="form-control" name="jabatan" id="jabatan">
                        <option value="" <?= (($data['jabatan'] ?? '') === '' ? 'selected' : '') ?>>-- Guru Biasa --</option>
                        <option value="WKS Kurikulum" <?= (($data['jabatan'] ?? '') === 'WKS Kurikulum' ? 'selected' : '') ?>>WKS Kurikulum</option>
                        <option value="Tim WKS Kurikulum" <?= (($data['jabatan'] ?? '') === 'Tim WKS Kurikulum' ? 'selected' : '') ?>>Tim WKS Kurikulum</option>
                        <option value="WKS Kesiswaan" <?= (($data['jabatan'] ?? '') === 'WKS Kesiswaan' ? 'selected' : '') ?>>WKS Kesiswaan</option>
                        <option value="Tim WKS Kesiswaan" <?= (($data['jabatan'] ?? '') === 'Tim WKS Kesiswaan' ? 'selected' : '') ?>>Tim WKS Kesiswaan</option>
                        <option value="WKS Humas" <?= (($data['jabatan'] ?? '') === 'WKS Humas' ? 'selected' : '') ?>>WKS Humas</option>
                        <option value="Tim WKS Humas" <?= (($data['jabatan'] ?? '') === 'Tim WKS Humas' ? 'selected' : '') ?>>Tim WKS Humas</option>
                        <option value="WKS Sarpras" <?= (($data['jabatan'] ?? '') === 'WKS Sarpras' ? 'selected' : '') ?>>WKS Sarpras</option>
                        <option value="Tim WKS Sarpras" <?= (($data['jabatan'] ?? '') === 'Tim WKS Sarpras' ? 'selected' : '') ?>>Tim WKS Sarpras</option>
                        <option value="STPKS" <?= (($data['jabatan'] ?? '') === 'STPKS' ? 'selected' : '') ?>>STPKS</option>
                        <option value="Kepala Sekolah" <?= (($data['jabatan'] ?? '') === 'Kepala Sekolah' ? 'selected' : '') ?>>Kepala Sekolah</option>
                    </select>
                    <small class="text-muted">WKS Kurikulum dan Tim WKS Kurikulum dapat mengelola microsite WKS.</small>
                </div>
                <!-- Jabatan WKS -->

                <!-- Status Keaktifan -->
                <div class="form-group col-sm-4">
                    <label for="status_keaktifan">Status Keaktifan:</label>
                    <select class="form-control" name="status_keaktifan">
                        <option value="Aktif"     <?= ($data['status'] === 'Aktif'     ? 'selected' : '') ?>>Aktif</option>
                        <option value="Non-Aktif" <?= ($data['status'] === 'Non-Aktif' ? 'selected' : '') ?>>Non-Aktif</option>
                    </select>
                    <div class="valid-feedback">Valid.</div>
                    <div class="invalid-feedback">Harap diisi kolom ini.</div>
                </div>
                <!-- Status Keaktifan -->


                <!-- Wali Kelas -->
                <div class="form-group col-sm-4">
                    <label for="wali_kelas">Wali Kelas:</label>
                    <?php
                        $q_wk = mysqli_query($conn, "SELECT id_kelas FROM tbl_wali_kelas WHERE nip_wali='{$data['no_induk']}' LIMIT 1");
                        $current_kelas_id = ($r_wk = mysqli_fetch_assoc($q_wk)) ? $r_wk['id_kelas'] : 0;
                    ?>
                    <select class="form-control" name="wali_kelas">
                        <option value="">-- Tidak Menjabat --</option>
                        <?php
                        $sqlKelas = "SELECT id_kelas, kelas FROM tbl_kelas ORDER BY kelas ASC";
                        $resultKelas = mysqli_query($conn, $sqlKelas);
                        while ($dataKelas = mysqli_fetch_array($resultKelas)) {
                            $selected = ($dataKelas['id_kelas'] == $current_kelas_id) ? 'selected' : '';
                        ?>
                            <option value="<?= $dataKelas['id_kelas']; ?>" <?= $selected; ?>>
                                <?= htmlspecialchars($dataKelas['kelas']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <!-- Wali Kelas -->



                <!-- Upload file -->
                <div class="form-group col-sm-6">
                    <label for="file">Foto Guru:</label>
                    <input type="file" class="form-control" id="file" name="file">
                    <small>File yang diizinkan berekstensi .jpg maksimal size 500 KB.</small>
                    <div class="valid-feedback">Valid.</div>
                    <div class="invalid-feedback">Silahkan pilih file!</div>
                </div>
                <!-- end of upload file -->

                <!-- Foto Lama -->
                <input type="text" class="form-control" id="foto" name="foto" value="<?= $data['foto']; ?>" hidden>
                <!-- Foto Lama -->

                <!-- Tombol Submit dan cancel -->
                <div class="form-group col-sm-2 pb-4">
                    <table style="border: none;">
                        <tr>
                            <td><input type="submit" onclick="return confirm('Apakah yakin mau merubah data ini?');" class="btn btn-success" id="submit" name="submit" value="Simpan"></td>
                            <td><a class="btn btn-warning" href="?page=data-guru">Cancel</a></td>
                        </tr>
                    </table>
                </div>
                <!-- end of submit dan cancel -->

            </form>
        </div>
    </div>
</div>

<!-- Script handling upload file -->
<script type="text/javascript">
    // ini untuk batasi ukuran
    var uploadField = document.getElementById("file");
    uploadField.onchange = function() {
        if (this.files[0].size > 512000) {
            alert("Ukuran file maksimal 512 KB!");
            this.value = "";
        } else if (this.files[0].type != "image/jpeg") {
            alert("File yang diizinkan hanya bertipe JPG!");
            this.value = "";
        };
    };
</script>
<!-- End of script handling upload file -->
