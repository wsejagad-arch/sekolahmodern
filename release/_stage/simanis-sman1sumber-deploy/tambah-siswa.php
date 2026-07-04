<?php
if (!isset($_SESSION['username'])) {
    header('location: index.php?haruslogin');
    exit;
} elseif ($hakakses != 1) { ?>
    <script>
        window.location = '404.html';
    </script>
    <?php
    exit;
}

include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$schemaColumns = [
    'nipd' => "ALTER TABLE tbl_siswa ADD COLUMN nipd VARCHAR(30) DEFAULT NULL AFTER no_induk",
    'jk' => "ALTER TABLE tbl_siswa ADD COLUMN jk VARCHAR(20) DEFAULT NULL AFTER nama_siswa",
    'tempat_lahir' => "ALTER TABLE tbl_siswa ADD COLUMN tempat_lahir VARCHAR(100) DEFAULT NULL",
    'tanggal_lahir' => "ALTER TABLE tbl_siswa ADD COLUMN tanggal_lahir DATE DEFAULT NULL",
    'nik' => "ALTER TABLE tbl_siswa ADD COLUMN nik VARCHAR(30) DEFAULT NULL",
    'agama' => "ALTER TABLE tbl_siswa ADD COLUMN agama VARCHAR(30) DEFAULT NULL",

    'rt' => "ALTER TABLE tbl_siswa ADD COLUMN rt VARCHAR(10) DEFAULT NULL",
    'rw' => "ALTER TABLE tbl_siswa ADD COLUMN rw VARCHAR(10) DEFAULT NULL",
    'dusun' => "ALTER TABLE tbl_siswa ADD COLUMN dusun VARCHAR(100) DEFAULT NULL",
    'kelurahan' => "ALTER TABLE tbl_siswa ADD COLUMN kelurahan VARCHAR(100) DEFAULT NULL",
    'kecamatan' => "ALTER TABLE tbl_siswa ADD COLUMN kecamatan VARCHAR(100) DEFAULT NULL",
    'kode_pos' => "ALTER TABLE tbl_siswa ADD COLUMN kode_pos VARCHAR(10) DEFAULT NULL",
    'jenis_tinggal' => "ALTER TABLE tbl_siswa ADD COLUMN jenis_tinggal VARCHAR(100) DEFAULT NULL",
    'alat_transportasi' => "ALTER TABLE tbl_siswa ADD COLUMN alat_transportasi VARCHAR(100) DEFAULT NULL",
    'telepon' => "ALTER TABLE tbl_siswa ADD COLUMN telepon VARCHAR(30) DEFAULT NULL",
    'hp' => "ALTER TABLE tbl_siswa ADD COLUMN hp VARCHAR(30) DEFAULT NULL",
    'email' => "ALTER TABLE tbl_siswa ADD COLUMN email VARCHAR(120) DEFAULT NULL",

    'skhun' => "ALTER TABLE tbl_siswa ADD COLUMN skhun VARCHAR(60) DEFAULT NULL",
    'rombel_saat_ini' => "ALTER TABLE tbl_siswa ADD COLUMN rombel_saat_ini VARCHAR(120) DEFAULT NULL",
    'no_peserta_un' => "ALTER TABLE tbl_siswa ADD COLUMN no_peserta_un VARCHAR(60) DEFAULT NULL",
    'no_seri_ijazah' => "ALTER TABLE tbl_siswa ADD COLUMN no_seri_ijazah VARCHAR(60) DEFAULT NULL",
    'sekolah_asal' => "ALTER TABLE tbl_siswa ADD COLUMN sekolah_asal VARCHAR(160) DEFAULT NULL",

    'penerima_kps' => "ALTER TABLE tbl_siswa ADD COLUMN penerima_kps TINYINT(1) NOT NULL DEFAULT 0",
    'no_kps' => "ALTER TABLE tbl_siswa ADD COLUMN no_kps VARCHAR(60) DEFAULT NULL",
    'penerima_kip' => "ALTER TABLE tbl_siswa ADD COLUMN penerima_kip TINYINT(1) NOT NULL DEFAULT 0",
    'nomor_kip' => "ALTER TABLE tbl_siswa ADD COLUMN nomor_kip VARCHAR(60) DEFAULT NULL",
    'nama_di_kip' => "ALTER TABLE tbl_siswa ADD COLUMN nama_di_kip VARCHAR(120) DEFAULT NULL",
    'nomor_kks' => "ALTER TABLE tbl_siswa ADD COLUMN nomor_kks VARCHAR(60) DEFAULT NULL",
    'bank' => "ALTER TABLE tbl_siswa ADD COLUMN bank VARCHAR(120) DEFAULT NULL",
    'nomor_rekening_bank' => "ALTER TABLE tbl_siswa ADD COLUMN nomor_rekening_bank VARCHAR(60) DEFAULT NULL",
    'rekening_atas_nama' => "ALTER TABLE tbl_siswa ADD COLUMN rekening_atas_nama VARCHAR(120) DEFAULT NULL",
    'layak_pip' => "ALTER TABLE tbl_siswa ADD COLUMN layak_pip TINYINT(1) NOT NULL DEFAULT 0",
    'alasan_layak_pip' => "ALTER TABLE tbl_siswa ADD COLUMN alasan_layak_pip TEXT DEFAULT NULL",

    'no_reg_akta_lahir' => "ALTER TABLE tbl_siswa ADD COLUMN no_reg_akta_lahir VARCHAR(60) DEFAULT NULL",
    'kebutuhan_khusus' => "ALTER TABLE tbl_siswa ADD COLUMN kebutuhan_khusus VARCHAR(120) DEFAULT NULL",
    'anak_ke' => "ALTER TABLE tbl_siswa ADD COLUMN anak_ke SMALLINT UNSIGNED DEFAULT NULL",
    'lintang' => "ALTER TABLE tbl_siswa ADD COLUMN lintang VARCHAR(40) DEFAULT NULL",
    'bujur' => "ALTER TABLE tbl_siswa ADD COLUMN bujur VARCHAR(40) DEFAULT NULL",
    'nama_kelas' => "ALTER TABLE tbl_siswa ADD COLUMN nama_kelas VARCHAR(120) DEFAULT NULL",
    'no_kk' => "ALTER TABLE tbl_siswa ADD COLUMN no_kk VARCHAR(30) DEFAULT NULL",
    'berat_badan' => "ALTER TABLE tbl_siswa ADD COLUMN berat_badan DECIMAL(5,2) DEFAULT NULL",
    'tinggi_badan' => "ALTER TABLE tbl_siswa ADD COLUMN tinggi_badan DECIMAL(5,2) DEFAULT NULL",
    'lingkar_kepala' => "ALTER TABLE tbl_siswa ADD COLUMN lingkar_kepala DECIMAL(5,2) DEFAULT NULL",
    'jumlah_saudara_kandung' => "ALTER TABLE tbl_siswa ADD COLUMN jumlah_saudara_kandung SMALLINT UNSIGNED DEFAULT NULL",
    'jarak_rumah_km' => "ALTER TABLE tbl_siswa ADD COLUMN jarak_rumah_km DECIMAL(5,2) DEFAULT NULL",

    'ayah_nama' => "ALTER TABLE tbl_siswa ADD COLUMN ayah_nama VARCHAR(120) DEFAULT NULL",
    'ayah_tahun_lahir' => "ALTER TABLE tbl_siswa ADD COLUMN ayah_tahun_lahir VARCHAR(4) DEFAULT NULL",
    'ayah_pendidikan' => "ALTER TABLE tbl_siswa ADD COLUMN ayah_pendidikan VARCHAR(100) DEFAULT NULL",
    'ayah_pekerjaan' => "ALTER TABLE tbl_siswa ADD COLUMN ayah_pekerjaan VARCHAR(100) DEFAULT NULL",
    'ayah_penghasilan' => "ALTER TABLE tbl_siswa ADD COLUMN ayah_penghasilan VARCHAR(100) DEFAULT NULL",
    'ayah_nik' => "ALTER TABLE tbl_siswa ADD COLUMN ayah_nik VARCHAR(30) DEFAULT NULL",

    'ibu_nama' => "ALTER TABLE tbl_siswa ADD COLUMN ibu_nama VARCHAR(120) DEFAULT NULL",
    'ibu_tahun_lahir' => "ALTER TABLE tbl_siswa ADD COLUMN ibu_tahun_lahir VARCHAR(4) DEFAULT NULL",
    'ibu_pendidikan' => "ALTER TABLE tbl_siswa ADD COLUMN ibu_pendidikan VARCHAR(100) DEFAULT NULL",
    'ibu_pekerjaan' => "ALTER TABLE tbl_siswa ADD COLUMN ibu_pekerjaan VARCHAR(100) DEFAULT NULL",
    'ibu_penghasilan' => "ALTER TABLE tbl_siswa ADD COLUMN ibu_penghasilan VARCHAR(100) DEFAULT NULL",
    'ibu_nik' => "ALTER TABLE tbl_siswa ADD COLUMN ibu_nik VARCHAR(30) DEFAULT NULL",

    'wali_nama' => "ALTER TABLE tbl_siswa ADD COLUMN wali_nama VARCHAR(120) DEFAULT NULL",
    'wali_tahun_lahir' => "ALTER TABLE tbl_siswa ADD COLUMN wali_tahun_lahir VARCHAR(4) DEFAULT NULL",
    'wali_pendidikan' => "ALTER TABLE tbl_siswa ADD COLUMN wali_pendidikan VARCHAR(100) DEFAULT NULL",
    'wali_pekerjaan' => "ALTER TABLE tbl_siswa ADD COLUMN wali_pekerjaan VARCHAR(100) DEFAULT NULL",
    'wali_penghasilan' => "ALTER TABLE tbl_siswa ADD COLUMN wali_penghasilan VARCHAR(100) DEFAULT NULL",
    'wali_nik' => "ALTER TABLE tbl_siswa ADD COLUMN wali_nik VARCHAR(30) DEFAULT NULL",
    'foto_depan_path' => "ALTER TABLE tbl_siswa ADD COLUMN foto_depan_path VARCHAR(255) DEFAULT NULL",
];

foreach ($schemaColumns as $column => $ddl) {
    $check = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_siswa LIKE '{$column}'");
    if ($check && mysqli_num_rows($check) === 0) {
        @mysqli_query($conn, $ddl);
    }
}

if (!function_exists('ts_ensure_photo_dir')) {
    function ts_ensure_photo_dir()
    {
        $dir = __DIR__ . '/foto_siswa';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }
}

if (!function_exists('ts_create_image_from_path')) {
    function ts_create_image_from_path($tmpPath)
    {
        if (!extension_loaded('gd') || !is_file($tmpPath)) {
            return false;
        }
        $info = @getimagesize($tmpPath);
        if (!$info || empty($info['mime'])) {
            return false;
        }
        if ($info['mime'] === 'image/jpeg') {
            return @imagecreatefromjpeg($tmpPath);
        }
        if ($info['mime'] === 'image/png') {
            return @imagecreatefrompng($tmpPath);
        }
        if ($info['mime'] === 'image/webp' && function_exists('imagecreatefromwebp')) {
            return @imagecreatefromwebp($tmpPath);
        }
        return false;
    }
}

if (!function_exists('ts_crop_to_3x4')) {
    function ts_crop_to_3x4($src)
    {
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w <= 0 || $h <= 0) {
            return false;
        }

        $targetRatio = 3 / 4;
        $srcRatio = $w / $h;
        if ($srcRatio > $targetRatio) {
            $cropH = $h;
            $cropW = (int) round($h * $targetRatio);
            $srcX = (int) round(($w - $cropW) / 2);
            $srcY = 0;
        } else {
            $cropW = $w;
            $cropH = (int) round($w / $targetRatio);
            $srcX = 0;
            $srcY = (int) round(($h - $cropH) / 2);
        }

        $dstW = 360;
        $dstH = 480;
        $dst = imagecreatetruecolor($dstW, $dstH);
        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstW, $dstH, $cropW, $cropH);
        return $dst;
    }
}

if (!function_exists('ts_save_jpeg_under_100kb')) {
    function ts_save_jpeg_under_100kb($image, $targetPath)
    {
        $maxBytes = 100 * 1024;
        $best = null;
        $bestSize = PHP_INT_MAX;
        $quality = 88;

        while ($quality >= 30) {
            ob_start();
            imagejpeg($image, null, $quality);
            $data = ob_get_clean();
            if ($data === false) {
                break;
            }
            $size = strlen($data);
            if ($size < $bestSize) {
                $best = $data;
                $bestSize = $size;
            }
            if ($size <= $maxBytes) {
                file_put_contents($targetPath, $data);
                return true;
            }
            $quality -= 8;
        }

        if ($best !== null) {
            file_put_contents($targetPath, $best);
            return true;
        }
        return false;
    }
}

if (!function_exists('ts_save_student_photo')) {
    function ts_save_student_photo($noinduk)
    {
        $uploadDir = ts_ensure_photo_dir();
        $relative = null;
        $src = null;

        $fotoData = $_POST['foto_data'] ?? '';
        if (is_string($fotoData) && strpos($fotoData, 'data:image/') === 0) {
            $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $fotoData);
            $binary = base64_decode($base64, true);
            if ($binary !== false) {
                $src = @imagecreatefromstring($binary);
            }
        }

        if (!$src && isset($_FILES['foto_upload']) && !empty($_FILES['foto_upload']['tmp_name'])) {
            $src = ts_create_image_from_path($_FILES['foto_upload']['tmp_name']);
        }

        if (!$src) {
            return null;
        }

        $cropped = ts_crop_to_3x4($src);
        imagedestroy($src);
        if (!$cropped) {
            return null;
        }

        $fileName = 'siswa_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $noinduk) . '_' . time() . '.jpg';
        $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
        if (ts_save_jpeg_under_100kb($cropped, $fullPath)) {
            $relative = 'foto_siswa/' . $fileName;
        }
        imagedestroy($cropped);
        return $relative;
    }
}

if (isset($_POST['submit'])) {
    $esc = function ($key) use ($conn) {
        return mysqli_real_escape_string($conn, trim($_POST[$key] ?? ''));
    };

    $noinduk = $esc('noinduk');
    $defaultPassword = '12345';
    $hashnoinduk = md5($defaultPassword);
    $akses = (int) ($_POST['hak_akses'] ?? 3);
    $status = $esc('status');
    $tglskr = date('Y-m-d H:i:s');
    $fotoDepanPath = ts_save_student_photo($noinduk);

    $insertData = [
        'no_induk' => $noinduk,
        'nipd' => $esc('nipd') ?: $noinduk,
        'nama_siswa' => $esc('nama'),
        'kelas' => $esc('kelas'),
        'nama_kelas' => $esc('nama_kelas'),
        'status' => $status,
        'foto_depan_path' => $fotoDepanPath ?: '',
        'nisn' => $esc('nisn'),
        'jk' => $esc('jk'),
        'tempat_lahir' => $esc('tempat_lahir'),
        'tanggal_lahir' => $esc('tanggal_lahir'),
        'nik' => $esc('nik'),
        'agama' => $esc('agama'),

        'alamat' => $esc('alamat'),
        'rt' => $esc('rt'),
        'rw' => $esc('rw'),
        'dusun' => $esc('dusun'),
        'kelurahan' => $esc('kelurahan'),
        'kecamatan' => $esc('kecamatan'),
        'kode_pos' => $esc('kode_pos'),
        'jenis_tinggal' => $esc('jenis_tinggal'),
        'alat_transportasi' => $esc('alat_transportasi'),
        'telepon' => $esc('telepon'),
        'hp' => $esc('hp'),
        'no_wa' => $esc('hp'),
        'email' => $esc('email'),

        'skhun' => $esc('skhun'),
        'rombel_saat_ini' => $esc('rombel_saat_ini'),
        'no_peserta_un' => $esc('no_peserta_un'),
        'no_seri_ijazah' => $esc('no_seri_ijazah'),
        'sekolah_asal' => $esc('sekolah_asal'),

        'penerima_kps' => (int) ($_POST['penerima_kps'] ?? 0),
        'no_kps' => $esc('no_kps'),
        'penerima_kip' => (int) ($_POST['penerima_kip'] ?? 0),
        'nomor_kip' => $esc('nomor_kip'),
        'nama_di_kip' => $esc('nama_di_kip'),
        'nomor_kks' => $esc('nomor_kks'),
        'bank' => $esc('bank'),
        'nomor_rekening_bank' => $esc('nomor_rekening_bank'),
        'rekening_atas_nama' => $esc('rekening_atas_nama'),
        'layak_pip' => (int) ($_POST['layak_pip'] ?? 0),
        'alasan_layak_pip' => $esc('alasan_layak_pip'),

        'no_reg_akta_lahir' => $esc('no_reg_akta_lahir'),
        'kebutuhan_khusus' => $esc('kebutuhan_khusus'),
        'anak_ke' => $esc('anak_ke'),
        'lintang' => $esc('lintang'),
        'bujur' => $esc('bujur'),
        'no_kk' => $esc('no_kk'),
        'berat_badan' => $esc('berat_badan'),
        'tinggi_badan' => $esc('tinggi_badan'),
        'lingkar_kepala' => $esc('lingkar_kepala'),
        'jumlah_saudara_kandung' => $esc('jumlah_saudara_kandung'),
        'jarak_rumah_km' => $esc('jarak_rumah_km'),

        'ayah_nama' => $esc('ayah_nama'),
        'ayah_tahun_lahir' => $esc('ayah_tahun_lahir'),
        'ayah_pendidikan' => $esc('ayah_pendidikan'),
        'ayah_pekerjaan' => $esc('ayah_pekerjaan'),
        'ayah_penghasilan' => $esc('ayah_penghasilan'),
        'ayah_nik' => $esc('ayah_nik'),

        'ibu_nama' => $esc('ibu_nama'),
        'ibu_tahun_lahir' => $esc('ibu_tahun_lahir'),
        'ibu_pendidikan' => $esc('ibu_pendidikan'),
        'ibu_pekerjaan' => $esc('ibu_pekerjaan'),
        'ibu_penghasilan' => $esc('ibu_penghasilan'),
        'ibu_nik' => $esc('ibu_nik'),

        'wali_nama' => $esc('wali_nama'),
        'wali_tahun_lahir' => $esc('wali_tahun_lahir'),
        'wali_pendidikan' => $esc('wali_pendidikan'),
        'wali_pekerjaan' => $esc('wali_pekerjaan'),
        'wali_penghasilan' => $esc('wali_penghasilan'),
        'wali_nik' => $esc('wali_nik'),
    ];

    $isilog = $nama . ' menambahkan data siswa dengan NIS ' . $noinduk . ' ke dalam sistem';

    $cek = cek_siswa($noinduk);
    if ($cek == true) {
        $columns = implode(', ', array_keys($insertData));
        $values = implode(", ", array_map(function ($value) {
            return "'" . $value . "'";
        }, array_values($insertData)));

        mysqli_query($conn, "INSERT INTO tbl_siswa({$columns}) VALUES({$values})");
        mysqli_query($conn, "INSERT INTO tbl_pengguna(no_induk, password, hak_akses) VALUES('{$noinduk}', '{$hashnoinduk}', '{$akses}')");
        mysqli_query($conn, "INSERT INTO tbl_log(waktu, isi_log) VALUES('{$tglskr}', '{$isilog}')");
    ?>
        <script>
            Swal.fire({
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil menambah data siswa!',
                showConfirmButton: false,
                timer: 1500
            }).then(function() {
                window.location.href = '?page=data-siswa';
            });
        </script>
    <?php } else { ?>
        <script>
            Swal.fire('Gagal', 'Siswa dengan NIS ini sudah ada di dalam daftar!', 'error')
        </script>
<?php }
}
?>

<style>
    .student-form-shell {
        max-width: 1200px;
    }

    .student-form-shell .page-intro {
        border: 0;
        border-radius: 18px;
        background: linear-gradient(135deg, #0f172a, #1d4ed8);
        color: #ffffff;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.16);
    }

    .student-form-shell .page-intro h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: 0.01em;
    }

    .student-form-shell .page-intro .small {
        color: rgba(255, 255, 255, 0.82) !important;
    }

    .student-form-shell .form-wrapper {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 20px;
        background: #ffffff;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
    }

    .student-form-shell .form-section {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
    }

    .student-form-shell .section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 800;
        letter-spacing: 0.01em;
        padding: 12px 16px;
        border: 0;
    }

    .student-form-shell .section-header .section-number {
        display: inline-flex;
        width: 26px;
        height: 26px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.25);
        font-size: 0.82rem;
        font-weight: 700;
    }

    .student-form-shell .form-group {
        margin-bottom: 0.95rem;
    }

    .student-form-shell .form-group label {
        margin-bottom: 0.35rem;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 700;
        color: #334155;
    }

    .student-form-shell .form-control,
    .student-form-shell .form-select,
    .student-form-shell select.form-control {
        border-radius: 12px;
        border: 1px solid #cbd5e1;
        min-height: 42px;
        box-shadow: none;
    }

    .student-form-shell .form-control:focus,
    .student-form-shell .form-select:focus,
    .student-form-shell select.form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 0.18rem rgba(37, 99, 235, 0.15);
    }

    .student-form-shell textarea.form-control {
        min-height: 86px;
    }

    .student-form-shell .btn-submit {
        border-radius: 12px;
        padding: 10px 18px;
        font-weight: 700;
    }

    .photo-box {
        border: 1px dashed #94a3b8;
        border-radius: 14px;
        padding: 12px;
        background: #f8fafc;
    }

    .photo-preview-wrap {
        width: 180px;
        height: 240px;
        border-radius: 12px;
        overflow: hidden;
        border: 2px solid #cbd5e1;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .photo-preview-wrap img,
    .photo-preview-wrap video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .photo-preview-wrap canvas.face-overlay {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        display: none;
    }

    .photo-preview-wrap .countdown-badge {
        position: absolute;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.28);
        color: #ffffff;
        font-weight: 700;
        font-size: 68px;
        line-height: 1;
        letter-spacing: 1px;
        text-shadow: 0 3px 10px rgba(0, 0, 0, 0.45);
        pointer-events: none;
    }

    .photo-hint {
        font-size: 0.82rem;
        color: #475569;
    }
</style>

<div class="container-fluid">
    <div class="container student-form-shell">
        <div class="alert page-intro mb-4">
            <h4>Tambah Data Siswa</h4>
            <div class="small">Form lengkap Data Utama Peserta Didik dan Data Orang Tua/Wali.</div>
        </div>

        <div class="form-wrapper">
            <form method="POST" action="" class="needs-validation" enctype="multipart/form-data" novalidate>

                <div class="form-section card mb-3">
                    <div class="section-header bg-primary text-white"><span class="section-number">1</span> Data Utama Peserta Didik - Identitas</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label>No Induk (NIS)</label>
                                <input type="number" class="form-control" name="noinduk" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label>NIPD</label>
                                <input type="text" class="form-control" name="nipd" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Nama</label>
                                <input type="text" class="form-control" name="nama" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label>JK (Jenis Kelamin)</label>
                                <select class="form-control" name="jk">
                                    <option value="">-- pilih --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>NISN</label>
                                <input type="text" class="form-control" name="nisn">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Tempat Lahir</label>
                                <input type="text" class="form-control" name="tempat_lahir">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Tanggal Lahir</label>
                                <input type="date" class="form-control" name="tanggal_lahir">
                            </div>
                            <div class="form-group col-md-4">
                                <label>NIK</label>
                                <input type="text" class="form-control" name="nik">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Agama</label>
                                <input type="text" class="form-control" name="agama">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Kelas / Rombel Saat Ini</label>
                                <select class="form-control" id="kelasSelect" name="kelas" required>
                                    <option selected disabled value="">-- pilih --</option>
                                    <?php
                                    $kelasData = mysqli_query($conn, 'SELECT * FROM tbl_kelas ORDER BY id_kelas ASC');
                                    while ($dkelas = mysqli_fetch_array($kelasData)) { ?>
                                        <option value="<?= htmlspecialchars($dkelas['kelas']); ?>"><?= htmlspecialchars($dkelas['kelas']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Nama Kelas</label>
                                <input type="text" class="form-control" id="namaKelas" name="nama_kelas" placeholder="Otomatis dari kelas terpilih" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section card mb-3">
                    <div class="section-header bg-indigo text-white" style="background:#4338ca;"><span class="section-number">2</span> Foto Diri Tampak Depan (3x4)</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="photo-box mb-3">
                                    <label class="mb-2">Upload Foto</label>
                                    <input type="file" class="form-control" id="fotoUpload" name="foto_upload" accept="image/*">
                                    <div class="photo-hint mt-2">Ukuran berapa pun akan diproses otomatis menjadi foto 3x4 dan dikompres maksimal 100KB.</div>
                                </div>

                                <div class="photo-box">
                                    <label class="mb-2">Kamera (Realtime)</label>
                                    <div class="d-flex gap-2 flex-wrap mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-submit" id="btnStartCamera">Aktifkan Kamera</button>
                                        <button type="button" class="btn btn-success btn-submit" id="btnCapture" disabled>Ambil Foto Depan</button>
                                        <button type="button" class="btn btn-outline-secondary btn-submit" id="btnStopCamera" disabled>Matikan Kamera</button>
                                    </div>
                                    <div class="photo-hint" id="faceStatus">Pastikan wajah tampak depan, satu orang, dan pencahayaan cukup.</div>
                                </div>
                            </div>

                            <div class="col-md-4 d-flex flex-column align-items-center gap-2">
                                <div class="photo-preview-wrap">
                                    <video id="cameraPreview" autoplay playsinline muted style="display:none;"></video>
                                    <img id="photoPreview" alt="Preview foto siswa" style="display:none;">
                                    <canvas id="faceOverlay" class="face-overlay"></canvas>
                                    <div id="autoCountdown" class="countdown-badge" aria-live="polite"></div>
                                    <span id="photoPlaceholder" class="text-muted small">Preview 3x4</span>
                                </div>
                                <small class="text-muted" id="photoMeta">Belum ada foto.</small>
                            </div>
                        </div>
                        <input type="hidden" name="foto_data" id="fotoData">
                    </div>
                </div>

                <div class="form-section card mb-3">
                    <div class="section-header bg-info text-white"><span class="section-number">3</span> Alamat & Kontak</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-6"><label>Alamat</label><textarea class="form-control" name="alamat" rows="2"></textarea></div>
                            <div class="form-group col-md-2"><label>RT</label><input type="text" class="form-control" name="rt"></div>
                            <div class="form-group col-md-2"><label>RW</label><input type="text" class="form-control" name="rw"></div>
                            <div class="form-group col-md-2"><label>Kode Pos</label><input type="text" class="form-control" name="kode_pos"></div>
                            <div class="form-group col-md-4"><label>Dusun</label><input type="text" class="form-control" name="dusun"></div>
                            <div class="form-group col-md-4"><label>Kelurahan</label><input type="text" class="form-control" name="kelurahan"></div>
                            <div class="form-group col-md-4"><label>Kecamatan</label><input type="text" class="form-control" name="kecamatan"></div>
                            <div class="form-group col-md-4"><label>Jenis Tinggal</label><input type="text" class="form-control" name="jenis_tinggal"></div>
                            <div class="form-group col-md-4"><label>Alat Transportasi</label><input type="text" class="form-control" name="alat_transportasi"></div>
                            <div class="form-group col-md-4"><label>Telepon</label><input type="text" class="form-control" name="telepon"></div>
                            <div class="form-group col-md-6"><label>HP</label><input type="text" class="form-control" name="hp"></div>
                            <div class="form-group col-md-6"><label>E-Mail</label><input type="email" class="form-control" name="email"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section card mb-3">
                    <div class="section-header bg-secondary text-white"><span class="section-number">4</span> Akademik</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-4"><label>SKHUN</label><input type="text" class="form-control" name="skhun"></div>
                            <div class="form-group col-md-4"><label>Rombel Saat Ini</label><input type="text" class="form-control" name="rombel_saat_ini"></div>
                            <div class="form-group col-md-4"><label>No Peserta Ujian Nasional</label><input type="text" class="form-control" name="no_peserta_un"></div>
                            <div class="form-group col-md-6"><label>No Seri Ijazah</label><input type="text" class="form-control" name="no_seri_ijazah"></div>
                            <div class="form-group col-md-6"><label>Sekolah Asal</label><input type="text" class="form-control" name="sekolah_asal"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section card mb-3">
                    <div class="section-header bg-warning text-dark"><span class="section-number">5</span> Bantuan / Beasiswa</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-2"><label>Penerima KPS</label><select class="form-control" name="penerima_kps">
                                    <option value="0">Tidak</option>
                                    <option value="1">Ya</option>
                                </select></div>
                            <div class="form-group col-md-4"><label>No. KPS</label><input type="text" class="form-control" name="no_kps"></div>
                            <div class="form-group col-md-2"><label>Penerima KIP</label><select class="form-control" name="penerima_kip">
                                    <option value="0">Tidak</option>
                                    <option value="1">Ya</option>
                                </select></div>
                            <div class="form-group col-md-4"><label>Nomor KIP</label><input type="text" class="form-control" name="nomor_kip"></div>
                            <div class="form-group col-md-4"><label>Nama di KIP</label><input type="text" class="form-control" name="nama_di_kip"></div>
                            <div class="form-group col-md-4"><label>Nomor KKS</label><input type="text" class="form-control" name="nomor_kks"></div>
                            <div class="form-group col-md-4"><label>Bank</label><input type="text" class="form-control" name="bank"></div>
                            <div class="form-group col-md-4"><label>Nomor Rekening Bank</label><input type="text" class="form-control" name="nomor_rekening_bank"></div>
                            <div class="form-group col-md-4"><label>Rekening Atas Nama</label><input type="text" class="form-control" name="rekening_atas_nama"></div>
                            <div class="form-group col-md-4"><label>Layak PIP</label><select class="form-control" name="layak_pip">
                                    <option value="0">Tidak</option>
                                    <option value="1">Ya</option>
                                </select></div>
                            <div class="form-group col-md-8"><label>Alasan Layak PIP</label><textarea class="form-control" name="alasan_layak_pip" rows="2"></textarea></div>
                        </div>
                    </div>
                </div>

                <div class="form-section card mb-3">
                    <div class="section-header bg-dark text-white"><span class="section-number">6</span> Data Fisik & Lainnya</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-4"><label>No Registrasi Akta Lahir</label><input type="text" class="form-control" name="no_reg_akta_lahir"></div>
                            <div class="form-group col-md-4"><label>Kebutuhan Khusus</label><input type="text" class="form-control" name="kebutuhan_khusus"></div>
                            <div class="form-group col-md-4"><label>Anak ke-berapa</label><input type="number" class="form-control" name="anak_ke"></div>
                            <div class="form-group col-md-3"><label>Lintang</label><input type="text" class="form-control" id="lintang" name="lintang"></div>
                            <div class="form-group col-md-3"><label>Bujur</label><input type="text" class="form-control" id="bujur" name="bujur"></div>
                            <div class="form-group col-md-6">
                                <label>Lokasi Rumah Realtime</label>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-outline-primary btn-submit" id="btnAmbilKoordinat">
                                        <i class="fas fa-map-marker-alt"></i> Ambil Titik Koordinat Rumah
                                    </button>
                                    <small class="text-muted align-self-center" id="gpsStatus">Belum mengambil titik koordinat.</small>
                                </div>
                            </div>
                            <div class="form-group col-md-3"><label>No KK</label><input type="text" class="form-control" name="no_kk"></div>
                            <div class="form-group col-md-3"><label>Jarak Rumah ke Sekolah (KM)</label><input type="number" step="0.01" class="form-control" name="jarak_rumah_km"></div>
                            <div class="form-group col-md-3"><label>Berat Badan</label><input type="number" step="0.01" class="form-control" name="berat_badan"></div>
                            <div class="form-group col-md-3"><label>Tinggi Badan</label><input type="number" step="0.01" class="form-control" name="tinggi_badan"></div>
                            <div class="form-group col-md-3"><label>Lingkar Kepala</label><input type="number" step="0.01" class="form-control" name="lingkar_kepala"></div>
                            <div class="form-group col-md-3"><label>Jml. Saudara Kandung</label><input type="number" class="form-control" name="jumlah_saudara_kandung"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section card mb-3">
                    <div class="section-header bg-success text-white"><span class="section-number">7</span> Data Ayah</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-4"><label>Nama</label><input type="text" class="form-control" name="ayah_nama"></div>
                            <div class="form-group col-md-2"><label>Tahun Lahir</label><input type="text" class="form-control" name="ayah_tahun_lahir"></div>
                            <div class="form-group col-md-2"><label>Jenjang Pendidikan</label><input type="text" class="form-control" name="ayah_pendidikan"></div>
                            <div class="form-group col-md-2"><label>Pekerjaan</label><input type="text" class="form-control" name="ayah_pekerjaan"></div>
                            <div class="form-group col-md-2"><label>Penghasilan</label><input type="text" class="form-control" name="ayah_penghasilan"></div>
                            <div class="form-group col-md-4"><label>NIK</label><input type="text" class="form-control" name="ayah_nik"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section card mb-3">
                    <div class="section-header bg-danger text-white"><span class="section-number">8</span> Data Ibu</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-4"><label>Nama</label><input type="text" class="form-control" name="ibu_nama"></div>
                            <div class="form-group col-md-2"><label>Tahun Lahir</label><input type="text" class="form-control" name="ibu_tahun_lahir"></div>
                            <div class="form-group col-md-2"><label>Jenjang Pendidikan</label><input type="text" class="form-control" name="ibu_pendidikan"></div>
                            <div class="form-group col-md-2"><label>Pekerjaan</label><input type="text" class="form-control" name="ibu_pekerjaan"></div>
                            <div class="form-group col-md-2"><label>Penghasilan</label><input type="text" class="form-control" name="ibu_penghasilan"></div>
                            <div class="form-group col-md-4"><label>NIK</label><input type="text" class="form-control" name="ibu_nik"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section card mb-3">
                    <div class="section-header bg-primary text-white"><span class="section-number">9</span> Data Wali</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-4"><label>Nama</label><input type="text" class="form-control" name="wali_nama"></div>
                            <div class="form-group col-md-2"><label>Tahun Lahir</label><input type="text" class="form-control" name="wali_tahun_lahir"></div>
                            <div class="form-group col-md-2"><label>Jenjang Pendidikan</label><input type="text" class="form-control" name="wali_pendidikan"></div>
                            <div class="form-group col-md-2"><label>Pekerjaan</label><input type="text" class="form-control" name="wali_pekerjaan"></div>
                            <div class="form-group col-md-2"><label>Penghasilan</label><input type="text" class="form-control" name="wali_penghasilan"></div>
                            <div class="form-group col-md-4"><label>NIK</label><input type="text" class="form-control" name="wali_nik"></div>
                        </div>
                    </div>
                </div>

                <input type="text" class="form-control" name="hak_akses" value="3" hidden>
                <input type="text" class="form-control" name="status" value="Aktif" hidden>

                <div class="form-group pb-2 d-flex gap-2 flex-wrap">
                    <button type="submit" onclick="return confirm('Pastikan data siswa sudah benar sebelum disimpan.');" class="btn btn-success btn-submit" name="submit">Simpan</button>
                    <a class="btn btn-warning btn-submit" href="?page=data-siswa">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function() {
        const kelasSelect = document.getElementById('kelasSelect');
        const namaKelas = document.getElementById('namaKelas');
        if (kelasSelect && namaKelas) {
            const syncNamaKelas = function() {
                const selected = kelasSelect.options[kelasSelect.selectedIndex];
                if (!selected || !selected.value) {
                    namaKelas.value = '';
                    return;
                }
                namaKelas.value = selected.text.trim();
            };
            kelasSelect.addEventListener('change', syncNamaKelas);
            syncNamaKelas();
        }

        const fotoUpload = document.getElementById('fotoUpload');
        const fotoData = document.getElementById('fotoData');
        const cameraPreview = document.getElementById('cameraPreview');
        const faceOverlay = document.getElementById('faceOverlay');
        const autoCountdown = document.getElementById('autoCountdown');
        const photoPreview = document.getElementById('photoPreview');
        const photoPlaceholder = document.getElementById('photoPlaceholder');
        const photoMeta = document.getElementById('photoMeta');
        const faceStatus = document.getElementById('faceStatus');
        const btnStartCamera = document.getElementById('btnStartCamera');
        const btnCapture = document.getElementById('btnCapture');
        const btnStopCamera = document.getElementById('btnStopCamera');
        const supportsFaceDetector = ('FaceDetector' in window);
        const FALLBACK_MIN_BRIGHTNESS = 26;
        const FALLBACK_MOTION_THRESHOLD = 6.2;
        const FALLBACK_STABLE_TARGET = 3;
        const NOSE_CENTER_X_TOLERANCE = 0.07;
        const NOSE_CENTER_Y_TOLERANCE = 0.10;
        let mediaStream = null;
        let autoCaptureTimer = null;
        let fallbackAutoTimer = null;
        let fallbackLastSignature = null;
        let fallbackStableFrames = 0;
        let autoStableCount = 0;
        let autoCaptureTriggered = false;
        let autoCountdownActive = false;
        let countdownToken = 0;

        function waitMs(ms) {
            return new Promise(function(resolve) {
                setTimeout(resolve, ms);
            });
        }

        function hideCountdown() {
            if (!autoCountdown) return;
            autoCountdown.textContent = '';
            autoCountdown.style.display = 'none';
        }

        function showCountdown(value) {
            if (!autoCountdown) return;
            autoCountdown.textContent = String(value);
            autoCountdown.style.display = 'flex';
        }

        function cancelAutoCountdown() {
            countdownToken += 1;
            autoCountdownActive = false;
            hideCountdown();
        }

        function syncOverlaySize() {
            if (!faceOverlay || !cameraPreview) return;
            const w = cameraPreview.videoWidth || 360;
            const h = cameraPreview.videoHeight || 480;
            if (faceOverlay.width !== w) faceOverlay.width = w;
            if (faceOverlay.height !== h) faceOverlay.height = h;
        }

        function clearOverlay() {
            if (!faceOverlay) return;
            const octx = faceOverlay.getContext('2d');
            if (!octx) return;
            octx.clearRect(0, 0, faceOverlay.width, faceOverlay.height);
        }

        function stopFallbackAutoLoop() {
            if (fallbackAutoTimer) {
                clearInterval(fallbackAutoTimer);
                fallbackAutoTimer = null;
            }
            fallbackLastSignature = null;
            fallbackStableFrames = 0;
        }

        function drawOverlayGuide(face, isGood) {
            if (!faceOverlay || !cameraPreview) return;
            syncOverlaySize();

            const octx = faceOverlay.getContext('2d');
            if (!octx) return;

            const w = faceOverlay.width;
            const h = faceOverlay.height;

            octx.clearRect(0, 0, w, h);
            octx.strokeStyle = 'rgba(255,255,255,0.45)';
            octx.lineWidth = 2;
            octx.setLineDash([8, 6]);

            const guideW = Math.round(w * 0.52);
            const guideH = Math.round((guideW * 4) / 3);
            const gx = Math.round((w - guideW) / 2);
            const gy = Math.round((h - guideH) / 2);
            octx.strokeRect(gx, gy, guideW, guideH);

            // Titik tengah untuk panduan posisi hidung.
            const cx = Math.round(w / 2);
            const cy = Math.round(h / 2);
            octx.setLineDash([]);
            octx.beginPath();
            octx.arc(cx, cy, 8, 0, Math.PI * 2);
            octx.strokeStyle = 'rgba(255,255,255,0.8)';
            octx.lineWidth = 2;
            octx.stroke();
            octx.beginPath();
            octx.moveTo(cx - 12, cy);
            octx.lineTo(cx + 12, cy);
            octx.moveTo(cx, cy - 12);
            octx.lineTo(cx, cy + 12);
            octx.strokeStyle = 'rgba(255,255,255,0.65)';
            octx.lineWidth = 1.5;
            octx.stroke();

            if (face && face.boundingBox) {
                const box = face.boundingBox;
                octx.setLineDash([]);
                octx.lineWidth = 3;
                octx.strokeStyle = isGood ? 'rgba(16,185,129,0.95)' : 'rgba(245,158,11,0.95)';
                octx.strokeRect(box.x, box.y, box.width, box.height);
            }
        }

        async function detectSingleFace(bitmap) {
            if (!supportsFaceDetector) {
                return {
                    ok: true,
                    note: 'Browser belum mendukung deteksi wajah otomatis, foto tetap diproses.'
                };
            }
            try {
                const detector = new FaceDetector({
                    maxDetectedFaces: 2,
                    fastMode: true
                });
                const faces = await detector.detect(bitmap);
                if (faces.length !== 1) {
                    return {
                        ok: false,
                        note: 'Pastikan hanya 1 wajah tampak depan.'
                    };
                }
                return {
                    ok: true,
                    note: 'Wajah terdeteksi.'
                };
            } catch (e) {
                return {
                    ok: true,
                    note: 'Deteksi wajah gagal, cek manual.'
                };
            }
        }

        function evaluateFrontalFace(face, frameW, frameH) {
            const box = face && face.boundingBox ? face.boundingBox : null;
            if (!box || !box.width || !box.height) {
                return {
                    ok: false,
                    note: 'Wajah belum terbaca jelas.'
                };
            }

            const centerX = box.x + (box.width / 2);
            const centerY = box.y + (box.height / 2);
            const offsetX = Math.abs((centerX - (frameW / 2)) / frameW);
            const offsetY = Math.abs((centerY - (frameH / 2)) / frameH);
            const areaRatio = (box.width * box.height) / (frameW * frameH);

            if (areaRatio < 0.12) {
                return {
                    ok: false,
                    note: 'Mendekat ke kamera sedikit.'
                };
            }
            if (areaRatio > 0.62) {
                return {
                    ok: false,
                    note: 'Wajah terlalu dekat, mundur sedikit.'
                };
            }
            if (offsetX > 0.12 || offsetY > 0.16) {
                return {
                    ok: false,
                    note: 'Posisikan wajah di tengah frame.'
                };
            }

            const landmarks = Array.isArray(face.landmarks) ? face.landmarks : [];
            const leftEye = landmarks.find(function(item) {
                return item.type === 'leftEye';
            });
            const rightEye = landmarks.find(function(item) {
                return item.type === 'rightEye';
            });
            const nose = landmarks.find(function(item) {
                return item.type === 'nose';
            });

            if (leftEye && rightEye && nose && leftEye.locations && rightEye.locations && nose.locations) {
                const lp = leftEye.locations[0];
                const rp = rightEye.locations[0];
                const np = nose.locations[0];
                if (lp && rp && np) {
                    const eyeDist = Math.abs(rp.x - lp.x);
                    const eyeTilt = Math.abs(lp.y - rp.y);
                    const eyeMidX = (lp.x + rp.x) / 2;
                    const noseOffset = Math.abs(np.x - eyeMidX);
                    if (eyeDist > 0) {
                        if ((eyeTilt / eyeDist) > 0.10) {
                            return {
                                ok: false,
                                note: 'Kepala miring, luruskan wajah.'
                            };
                        }
                        if ((noseOffset / eyeDist) > 0.22) {
                            return {
                                ok: false,
                                note: 'Hadapkan wajah lurus ke depan.'
                            };
                        }

                        const noseCenterOffsetX = Math.abs(np.x - (frameW / 2)) / frameW;
                        const noseCenterOffsetY = Math.abs(np.y - (frameH / 2)) / frameH;
                        if (noseCenterOffsetX > NOSE_CENTER_X_TOLERANCE || noseCenterOffsetY > NOSE_CENTER_Y_TOLERANCE) {
                            return {
                                ok: false,
                                note: 'Posisikan hidung tepat di titik tengah frame.'
                            };
                        }
                    }
                }
            } else {
                return {
                    ok: false,
                    note: 'Landmark hidung belum terbaca, tahan posisi wajah di tengah.'
                };
            }

            return {
                ok: true,
                note: 'Wajah presisi, tahan posisi...'
            };
        }

        function stopAutoFaceLoop() {
            if (autoCaptureTimer) {
                clearInterval(autoCaptureTimer);
                autoCaptureTimer = null;
            }
            stopFallbackAutoLoop();
            autoStableCount = 0;
            clearOverlay();
        }

        function frameSignature(canvas) {
            const probe = document.createElement('canvas');
            probe.width = 48;
            probe.height = 64;
            const pctx = probe.getContext('2d');

            const srcW = canvas.width;
            const srcH = canvas.height;
            const cropW = Math.round(srcW * 0.52);
            const cropH = Math.round((cropW * 4) / 3);
            const sx = Math.max(0, Math.round((srcW - cropW) / 2));
            const sy = Math.max(0, Math.round((srcH - cropH) / 2));
            const safeW = Math.max(1, Math.min(cropW, srcW - sx));
            const safeH = Math.max(1, Math.min(cropH, srcH - sy));

            pctx.drawImage(canvas, sx, sy, safeW, safeH, 0, 0, probe.width, probe.height);
            const imgData = pctx.getImageData(0, 0, probe.width, probe.height).data;

            let sum = 0;
            let count = 0;
            for (let i = 0; i < imgData.length; i += 16) {
                sum += (imgData[i] + imgData[i + 1] + imgData[i + 2]) / 3;
                count += 1;
            }
            return {
                avg: count ? (sum / count) : 0,
                sample: imgData
            };
        }

        function motionDelta(sigA, sigB) {
            if (!sigA || !sigB || !sigA.sample || !sigB.sample) return 999;
            const len = Math.min(sigA.sample.length, sigB.sample.length);
            let diff = 0;
            let count = 0;
            for (let i = 0; i < len; i += 16) {
                diff += Math.abs(sigA.sample[i] - sigB.sample[i]);
                count += 1;
            }
            return count ? (diff / count) : 999;
        }

        function startFallbackAutoLoop() {
            stopFallbackAutoLoop();
            drawOverlayGuide(null, false);

            fallbackAutoTimer = setInterval(async function() {
                if (!mediaStream || autoCaptureTriggered || autoCountdownActive || cameraPreview.readyState < 2) {
                    return;
                }

                const c = document.createElement('canvas');
                c.width = cameraPreview.videoWidth || 0;
                c.height = cameraPreview.videoHeight || 0;
                if (!c.width || !c.height) {
                    return;
                }

                const ctx = c.getContext('2d');
                ctx.drawImage(cameraPreview, 0, 0, c.width, c.height);
                drawOverlayGuide(null, fallbackStableFrames >= (FALLBACK_STABLE_TARGET - 1));

                const sig = frameSignature(c);
                if (sig.avg < FALLBACK_MIN_BRIGHTNESS) {
                    fallbackStableFrames = 0;
                    fallbackLastSignature = sig;
                    faceStatus.textContent = 'Pencahayaan kurang. Tambah cahaya agar foto jelas.';
                    faceStatus.className = 'photo-hint text-warning';
                    return;
                }

                const delta = motionDelta(fallbackLastSignature, sig);
                fallbackLastSignature = sig;

                if (delta < FALLBACK_MOTION_THRESHOLD) {
                    fallbackStableFrames += 1;
                    faceStatus.textContent = 'Mode kompatibel aktif. Arahkan hidung ke titik tengah lalu tahan posisi... (' + fallbackStableFrames + '/' + FALLBACK_STABLE_TARGET + ')';
                    faceStatus.className = 'photo-hint text-success';
                } else {
                    fallbackStableFrames = 0;
                    faceStatus.textContent = 'Mode kompatibel aktif. Kurangi gerakan dan arahkan hidung ke titik tengah.';
                    faceStatus.className = 'photo-hint text-primary';
                    return;
                }

                if (fallbackStableFrames >= FALLBACK_STABLE_TARGET) {
                    autoCaptureTriggered = true;
                    stopAutoFaceLoop();
                    const captured = await runCountdownAndCapture();
                    if (captured) {
                        faceStatus.textContent = 'Foto otomatis berhasil diambil (mode kompatibel).';
                        faceStatus.className = 'photo-hint text-success';
                    } else {
                        autoCaptureTriggered = false;
                        faceStatus.textContent = 'Auto foto dibatalkan. Coba posisi ulang.';
                        faceStatus.className = 'photo-hint text-warning';
                        if (mediaStream) {
                            startFallbackAutoLoop();
                        }
                    }
                }
            }, 550);
        }

        async function runCountdownAndCapture() {
            if (autoCountdownActive) {
                return false;
            }

            autoCountdownActive = true;
            const token = ++countdownToken;

            for (let i = 3; i >= 1; i -= 1) {
                if (!mediaStream || token !== countdownToken) {
                    autoCountdownActive = false;
                    hideCountdown();
                    return false;
                }

                showCountdown(i);
                faceStatus.textContent = 'Wajah presisi. Foto otomatis dalam ' + i + '...';
                faceStatus.className = 'photo-hint text-success';
                await waitMs(600);
            }

            hideCountdown();
            if (!mediaStream || token !== countdownToken) {
                autoCountdownActive = false;
                return false;
            }

            const captured = await processImageElement(cameraPreview);
            autoCountdownActive = false;
            return captured;
        }

        function startAutoFaceLoop() {
            stopAutoFaceLoop();
            autoCaptureTriggered = false;

            if (!supportsFaceDetector) {
                faceStatus.textContent = 'Mode kompatibel aktif: tahan posisi agar foto otomatis diambil.';
                faceStatus.className = 'photo-hint text-primary';
                startFallbackAutoLoop();
                return;
            }

            const detector = new FaceDetector({
                maxDetectedFaces: 2,
                fastMode: false
            });

            autoCaptureTimer = setInterval(async function() {
                if (!mediaStream || autoCaptureTriggered || cameraPreview.readyState < 2) {
                    return;
                }

                const c = document.createElement('canvas');
                c.width = cameraPreview.videoWidth || 0;
                c.height = cameraPreview.videoHeight || 0;
                if (!c.width || !c.height) {
                    return;
                }

                const ctx = c.getContext('2d');
                ctx.drawImage(cameraPreview, 0, 0, c.width, c.height);

                try {
                    const bitmap = await createImageBitmap(c);
                    const faces = await detector.detect(bitmap);
                    if (faces.length !== 1) {
                        autoStableCount = 0;
                        clearOverlay();
                        faceStatus.textContent = faces.length === 0 ? 'Wajah belum terdeteksi. Arahkan wajah ke kamera.' : 'Terdeteksi lebih dari 1 wajah. Pastikan hanya 1 orang.';
                        faceStatus.className = 'photo-hint text-danger';
                        return;
                    }

                    const quality = evaluateFrontalFace(faces[0], c.width, c.height);
                    drawOverlayGuide(faces[0], quality.ok);
                    if (!quality.ok) {
                        autoStableCount = 0;
                        faceStatus.textContent = quality.note;
                        faceStatus.className = 'photo-hint text-warning';
                        return;
                    }

                    autoStableCount += 1;
                    faceStatus.textContent = quality.note + ' (' + autoStableCount + '/3)';
                    faceStatus.className = 'photo-hint text-success';

                    if (autoStableCount >= 3) {
                        autoCaptureTriggered = true;
                        stopAutoFaceLoop();
                        const captured = await runCountdownAndCapture();
                        if (captured) {
                            faceStatus.textContent = 'Foto otomatis berhasil diambil.';
                            faceStatus.className = 'photo-hint text-success';
                        } else {
                            autoCaptureTriggered = false;
                            faceStatus.textContent = 'Auto foto dibatalkan. Arahkan wajah dan coba lagi.';
                            faceStatus.className = 'photo-hint text-warning';
                            if (mediaStream) {
                                startAutoFaceLoop();
                            }
                        }
                        stopAutoFaceLoop();
                    }
                } catch (err) {
                    autoStableCount = 0;
                    clearOverlay();
                    faceStatus.textContent = 'Deteksi otomatis gagal. Coba posisi ulang atau ambil manual.';
                    faceStatus.className = 'photo-hint text-danger';
                }
            }, 700);
        }

        function cropTo3x4(sourceCanvas) {
            const sw = sourceCanvas.width;
            const sh = sourceCanvas.height;
            const targetRatio = 3 / 4;
            const srcRatio = sw / sh;

            let cropW = sw;
            let cropH = sh;
            let sx = 0;
            let sy = 0;

            if (srcRatio > targetRatio) {
                cropW = Math.round(sh * targetRatio);
                sx = Math.round((sw - cropW) / 2);
            } else {
                cropH = Math.round(sw / targetRatio);
                sy = Math.round((sh - cropH) / 2);
            }

            const out = document.createElement('canvas');
            out.width = 360;
            out.height = 480;
            const ctx = out.getContext('2d');
            ctx.drawImage(sourceCanvas, sx, sy, cropW, cropH, 0, 0, out.width, out.height);
            return out;
        }

        function compressUnder100KB(canvas) {
            let q = 0.9;
            let dataUrl = canvas.toDataURL('image/jpeg', q);
            while (dataUrl.length * 0.75 > 102400 && q > 0.35) {
                q -= 0.08;
                dataUrl = canvas.toDataURL('image/jpeg', q);
            }
            return dataUrl;
        }

        function setPreview(dataUrl) {
            photoPreview.src = dataUrl;
            photoPreview.style.display = 'block';
            cameraPreview.style.display = 'none';
            if (faceOverlay) faceOverlay.style.display = 'none';
            hideCountdown();
            photoPlaceholder.style.display = 'none';
            fotoData.value = dataUrl;
            const approxKb = Math.round((dataUrl.length * 0.75) / 1024);
            photoMeta.textContent = 'Siap kirim: ~' + approxKb + ' KB (3x4)';
        }

        async function processImageElement(imageEl) {
            const tmpCanvas = document.createElement('canvas');
            tmpCanvas.width = imageEl.naturalWidth || imageEl.videoWidth;
            tmpCanvas.height = imageEl.naturalHeight || imageEl.videoHeight;
            const tmpCtx = tmpCanvas.getContext('2d');
            tmpCtx.drawImage(imageEl, 0, 0, tmpCanvas.width, tmpCanvas.height);

            try {
                const bitmap = await createImageBitmap(tmpCanvas);
                const faceResult = await detectSingleFace(bitmap);
                if (!faceResult.ok) {
                    faceStatus.textContent = faceResult.note;
                    faceStatus.className = 'photo-hint text-danger';
                    return false;
                }
                faceStatus.textContent = faceResult.note;
                faceStatus.className = 'photo-hint text-success';
            } catch (e) {
                faceStatus.textContent = 'Deteksi wajah tidak tersedia, lanjut dengan pemeriksaan manual.';
                faceStatus.className = 'photo-hint text-warning';
            }

            const cropped = cropTo3x4(tmpCanvas);
            const compressed = compressUnder100KB(cropped);
            setPreview(compressed);
            return true;
        }

        if (fotoUpload) {
            fotoUpload.addEventListener('change', function() {
                const file = this.files && this.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        processImageElement(img);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }

        if (btnStartCamera && btnCapture && btnStopCamera && cameraPreview) {
            btnStartCamera.addEventListener('click', async function() {
                try {
                    mediaStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: 'user'
                        },
                        audio: false
                    });
                    cameraPreview.srcObject = mediaStream;
                    cameraPreview.style.display = 'block';
                    if (faceOverlay) {
                        faceOverlay.style.display = 'block';
                        syncOverlaySize();
                    }
                    photoPreview.style.display = 'none';
                    photoPlaceholder.style.display = 'none';
                    cancelAutoCountdown();
                    autoStableCount = 0;
                    autoCaptureTriggered = false;
                    btnCapture.disabled = false;
                    btnStopCamera.disabled = false;
                    faceStatus.textContent = 'Kamera aktif. Saat wajah tampak depan dan sejajar, foto otomatis diambil.';
                    faceStatus.className = 'photo-hint text-primary';
                    startAutoFaceLoop();
                } catch (err) {
                    faceStatus.textContent = 'Kamera gagal diakses. Pastikan izin kamera diberikan.';
                    faceStatus.className = 'photo-hint text-danger';
                }
            });

            btnCapture.addEventListener('click', async function() {
                if (!mediaStream) return;
                cancelAutoCountdown();
                stopAutoFaceLoop();
                autoCaptureTriggered = true;
                await processImageElement(cameraPreview);
            });

            btnStopCamera.addEventListener('click', function() {
                cancelAutoCountdown();
                stopAutoFaceLoop();
                if (mediaStream) {
                    mediaStream.getTracks().forEach(function(track) {
                        track.stop();
                    });
                    mediaStream = null;
                }
                cameraPreview.style.display = 'none';
                if (faceOverlay) {
                    faceOverlay.style.display = 'none';
                }
                if (!photoPreview.src) {
                    photoPlaceholder.style.display = 'block';
                }
                btnCapture.disabled = true;
                btnStopCamera.disabled = true;
            });
        }

        const btnGps = document.getElementById('btnAmbilKoordinat');
        const lintang = document.getElementById('lintang');
        const bujur = document.getElementById('bujur');
        const gpsStatus = document.getElementById('gpsStatus');

        if (btnGps && lintang && bujur && gpsStatus) {
            btnGps.addEventListener('click', function() {
                if (!navigator.geolocation) {
                    gpsStatus.textContent = 'Browser tidak mendukung GPS.';
                    gpsStatus.className = 'text-danger align-self-center';
                    return;
                }

                gpsStatus.textContent = 'Sedang mengambil titik koordinat...';
                gpsStatus.className = 'text-primary align-self-center';
                btnGps.disabled = true;

                navigator.geolocation.getCurrentPosition(function(position) {
                    lintang.value = position.coords.latitude.toFixed(7);
                    bujur.value = position.coords.longitude.toFixed(7);
                    gpsStatus.textContent = 'Koordinat berhasil diambil.';
                    gpsStatus.className = 'text-success align-self-center';
                    btnGps.disabled = false;
                }, function(error) {
                    let message = 'Gagal mengambil koordinat.';
                    if (error.code === 1) message = 'Akses lokasi ditolak.';
                    if (error.code === 2) message = 'Lokasi tidak tersedia.';
                    if (error.code === 3) message = 'Permintaan lokasi timeout.';
                    gpsStatus.textContent = message;
                    gpsStatus.className = 'text-danger align-self-center';
                    btnGps.disabled = false;
                }, {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 0
                });
            });
        }
    })();
</script>