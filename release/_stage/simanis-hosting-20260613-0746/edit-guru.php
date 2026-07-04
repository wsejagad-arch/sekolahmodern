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
    $status = mysqli_real_escape_string($conn, $_POST['status_keaktifan']);
    $fotolama = $_POST['foto'];
    $namafile = $_FILES['file']['name'];
    $ukuranFile = $_FILES['file']['size'];
    $error = $_FILES['file']['error'];
    $tmpName = $_FILES['file']['tmp_name'];
    $isilog = "$nama" . " mengubah data guru dengan NIP/NUPTK " . "$nip" . " kedalam sistem";

    if ($error != UPLOAD_ERR_NO_FILE) {
        $cekfoto = cek_foto($namafile);
        mysqli_query($conn, "UPDATE tbl_guru g LEFT JOIN tbl_mapel_ampu m ON g.no_induk=m.no_induk SET g.no_induk='$nip', g.nama_guru='$nami', g.no_wa='$no_wa', g.status_kepegawaian='$status_kepegawaian', g.jabatan='$jabatan', g.is_guru_bk=$is_guru_bk, g.foto='$cekfoto', g.status='$status', m.no_induk='$nip' WHERE g.id_guru='$idguru'");
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
        mysqli_query($conn, "UPDATE tbl_guru g LEFT JOIN tbl_mapel_ampu m ON g.no_induk=m.no_induk SET g.no_induk='$nip', g.nama_guru='$nami', g.no_wa='$no_wa', g.status_kepegawaian='$status_kepegawaian', g.jabatan='$jabatan', g.is_guru_bk=$is_guru_bk, g.status='$status', m.no_induk='$nip' WHERE g.id_guru='$idguru'");
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
        $guru = mysqli_query($conn, "SELECT * FROM tbl_guru WHERE id_guru='$idguru'");
        $data = mysqli_fetch_array($guru);
        ?>

        <div class="container rounded" style="background-color: #ffffff; outline: 1px solid lightgrey">
            <form method="POST" action="" class="needs-validation" enctype="multipart/form-data" novalidate>

                <!-- NIP -->
                <div class="form-group col-sm-4 pt-4">
                    <label for="nip">NIP/NUPTK:</label>
                    <input type="number" class="form-control" id="nip" name="nip" value="<?= $data['no_induk']; ?>" readonly>
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

                <!-- Guru BK -->
                <div class="form-group col-sm-4">
                    <label>Role Khusus:</label>
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="is_guru_bk" value="1" id="is_guru_bk"
                            <?= (!empty($data['is_guru_bk']) ? 'checked' : '') ?>>
                        <label class="form-check-label" for="is_guru_bk">
                            <strong>Guru BK</strong> <small class="text-muted">(Bimbingan Konseling — dapat memvalidasi izin siswa)</small>
                        </label>
                    </div>
                </div>
                <!-- Status Kepegawaian -->
                <div class="form-group col-sm-4">
                    <label for="status_kepegawaian">Status Kepegawaian:</label>
                    <select class="form-control" name="status_kepegawaian">
                        <?php if ($data['status_kepegawaian'] === "ASN") : ?>
                            <option value="ASN" selected>ASN</option>
                            <option value="Non-ASN">Non-ASN</option>
                            <!--	<option value="PPPK">PPPK</option> -->
                        <?php endif; ?>

                        <?php if ($data['status_kepegawaian'] === "Non-ASN") : ?>
                            <option value="ASN">ASN</option>
                            <option value="Non-ASN" selected>Non-ASN</option>
                            <!--	<option value="PPPK">PPPK</option>-->
                        <?php endif; ?>


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
                        <option value="WKS Humas" <?= (($data['jabatan'] ?? '') === 'WKS Humas' ? 'selected' : '') ?>>WKS Humas</option>
                        <option value="WKS Sarpras" <?= (($data['jabatan'] ?? '') === 'WKS Sarpras' ? 'selected' : '') ?>>WKS Sarpras</option>
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
                        <?php if ($data['status'] === "Aktif") : ?>
                            <option value="Aktif" selected>Aktif</option>
                            <option value="Non-Aktif">Non-Aktif</option>
                        <?php endif; ?>

                        <?php if ($data['status'] === "Non-Aktif") : ?>
                            <option value="Aktif">Aktif</option>
                            <option value="Non-Aktif" selected>Non-Aktif</option>
                        <?php endif; ?>
                    </select>
                    <div class="valid-feedback">Valid.</div>
                    <div class="invalid-feedback">Harap diisi kolom ini.</div>
                </div>
                <!-- Status Keaktifan -->


                <!-- Wali Kelas -->
                <div class="form-group col-sm-4">
                    <label for="wali_kelas">Wali Kelas:</label>
                    <select class="form-control" name="wali_kelas">
                        <option selected disabled>-- pilih --</option>
                        <?php
                        $kelasArray = array();
                        $sqlKelas = "SELECT DISTINCT kelas FROM tbl_mapel_ampu";
                        $resultKelas = mysqli_query($conn, $sqlKelas);
                        while ($dataKelas = mysqli_fetch_array($resultKelas)) {
                            $kelasArray[] = $dataKelas['kelas'];
                            $selected = (isset($_GET['kelas']) && $_GET['kelas'] == $dataKelas['kelas']) ? 'selected' : '';
                        ?>
                            <option value="<?= $dataKelas['kelas']; ?>" <?= $selected; ?>>
                                <?= $dataKelas['kelas']; ?>
                            </option>
                        <?php } ?>
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
