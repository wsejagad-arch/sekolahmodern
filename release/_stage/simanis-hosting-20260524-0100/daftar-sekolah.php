<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/notification_helper.php';

$success = null;
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (!$conn instanceof mysqli) {
        $error = 'Database tidak tersambung.';
    } else {
        mt_ensure_schema($conn);

        $namaSekolah = trim((string)($_POST['nama_sekolah'] ?? ''));
        $alamat = trim((string)($_POST['alamat'] ?? ''));
        $namaAdmin = trim((string)($_POST['nama_admin'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $npsn = preg_replace('/[^0-9]/', '', (string)($_POST['npsn'] ?? ''));
        $namaAplikasi = trim((string)($_POST['nama_aplikasi'] ?? 'SIMANIS'));
        if ($namaAplikasi === '') $namaAplikasi = 'SIMANIS';
        $emailKontak = trim((string)($_POST['email_kontak'] ?? ''));
        $hpKontak = notif_normalize_phone((string)($_POST['hp_kontak'] ?? ''));
        $kodeSekolah = $npsn;

        if ($namaSekolah === '' || $namaAdmin === '' || $username === '' || strlen($password) < 6) {
            $error = 'Nama sekolah, nama admin, username, dan password minimal 6 karakter wajib diisi.';
        } elseif ($kodeSekolah === '' || strlen($kodeSekolah) < 8) {
            $error = 'NPSN sekolah wajib diisi dengan angka yang valid.';
        } elseif ($emailKontak === '' || !filter_var($emailKontak, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email kontak sekolah wajib diisi dengan format yang valid.';
        } elseif ($hpKontak === '' || strlen($hpKontak) < 10) {
            $error = 'Nomor HP/WhatsApp kontak sekolah wajib diisi dengan format yang valid.';
        } else {
            $kodeEsc = mysqli_real_escape_string($conn, $kodeSekolah);
            $cekKode = mysqli_query($conn, "SELECT id_sekolah FROM tbl_sekolah WHERE kode_sekolah='$kodeEsc' OR npsn='$kodeEsc' LIMIT 1");
            if ($cekKode && mysqli_num_rows($cekKode) > 0) {
                $error = 'NPSN sekolah sudah terdaftar.';
            } else {
                $namaEsc = mysqli_real_escape_string($conn, $namaSekolah);
                $aplikasiEsc = mysqli_real_escape_string($conn, $namaAplikasi);
                $alamatEsc = mysqli_real_escape_string($conn, $alamat);
                $adminEsc = mysqli_real_escape_string($conn, $namaAdmin);
                $userEsc = mysqli_real_escape_string($conn, $username);
                $emailEsc = mysqli_real_escape_string($conn, $emailKontak);
                $hpEsc = mysqli_real_escape_string($conn, $hpKontak);
                $hashEsc = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_DEFAULT));

                mysqli_begin_transaction($conn);
                $ok = mysqli_query(
                    $conn,
                    "INSERT INTO tbl_sekolah (kode_sekolah, npsn, nama_sekolah, alamat, email_kontak, hp_kontak, nama_pimpinan)
                     VALUES ('$kodeEsc', '$kodeEsc', '$namaEsc', '$alamatEsc', '$emailEsc', '$hpEsc', '$adminEsc')"
                );

                if ($ok) {
                    $idSekolah = (int)mysqli_insert_id($conn);
                    $nextSettingId = 1;
                    $qMax = mysqli_query($conn, "SELECT COALESCE(MAX(id),0)+1 AS next_id FROM tbl_setting");
                    if ($qMax && ($rowMax = mysqli_fetch_assoc($qMax))) {
                        $nextSettingId = (int)$rowMax['next_id'];
                    }

                    $settingSql = mt_column_exists($conn, 'tbl_setting', 'id_sekolah')
                        ? "INSERT INTO tbl_setting (id, nama_sekolah, nama_aplikasi, alamat, logo, nama_pimpinan, nip_pimpinan, id_sekolah)
                           VALUES ($nextSettingId, '$namaEsc', '$aplikasiEsc', '$alamatEsc', 'logo dash.png', '$adminEsc', '', $idSekolah)"
                        : "INSERT INTO tbl_setting (id, nama_sekolah, nama_aplikasi, alamat, logo, nama_pimpinan, nip_pimpinan)
                           VALUES ($nextSettingId, '$namaEsc', '$aplikasiEsc', '$alamatEsc', 'logo dash.png', '$adminEsc', '')";
                    $ok = mysqli_query($conn, $settingSql);
                }

                if ($ok) {
                    $userSql = mt_column_exists($conn, 'tbl_user', 'id_sekolah')
                        ? "INSERT INTO tbl_user (username, email, password, nama, hak_akses, id_sekolah)
                           VALUES ('$userEsc', '$emailEsc', '$hashEsc', '$adminEsc', '1', $idSekolah)"
                        : "INSERT INTO tbl_user (username, email, password, nama, hak_akses)
                           VALUES ('$userEsc', '$emailEsc', '$hashEsc', '$adminEsc', '1')";
                    $ok = mysqli_query($conn, $userSql);
                }

                if ($ok) {
                    notif_queue_school(
                        $conn,
                        $idSekolah,
                        'Registrasi SIMANIS berhasil',
                        "Ruang sekolah $namaSekolah sudah aktif.\nNPSN: $kodeSekolah\nUsername admin: $username\n\nAnda akan menerima notifikasi pembaruan dan informasi penting melalui kontak ini."
                    );
                    mysqli_commit($conn);
                    $success = [
                        'kode' => $kodeSekolah,
                        'username' => $username,
                        'email' => $emailKontak,
                        'hp' => $hpKontak,
                    ];
                } else {
                    mysqli_rollback($conn);
                    $error = 'Pendaftaran sekolah gagal: ' . mysqli_error($conn);
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Sekolah - SIMANIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #0f172a, #1d4ed8);
            font-family: "Poppins", "Segoe UI", sans-serif;
            color: #0f172a;
        }
        .register-shell {
            width: min(100%, 920px);
            margin: 0 auto;
            padding: 32px 16px;
        }
        .panel {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .26);
            overflow: hidden;
        }
        .panel-head {
            padding: 30px;
            background: #eef6ff;
            border-bottom: 1px solid #dbeafe;
        }
        .panel-body {
            padding: 30px;
        }
        .form-control {
            border-radius: 14px;
            padding: 12px 14px;
        }
        .btn-main {
            border: 0;
            border-radius: 14px;
            padding: 12px 18px;
            font-weight: 800;
            background: linear-gradient(135deg, #2563eb, #14b8a6);
            color: #ffffff;
        }
        .btn-check-npsn {
            border: 1px solid #dbeafe;
            background: #eef6ff;
            color: #2563eb;
            font-weight: 600;
            padding: 12px 18px;
            border-top-right-radius: 14px;
            border-bottom-right-radius: 14px;
            transition: all 0.2s ease-in-out;
        }
        .btn-check-npsn:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }
        .input-group .form-control {
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .spin {
            animation: spin 1s linear infinite;
            display: inline-block;
        }
    </style>
</head>
<body>
    <main class="register-shell">
        <section class="panel">
            <div class="panel-head">
                <a href="login.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke login</a>
                <h1 class="h3 fw-bold mt-3 mb-1">Daftarkan Sekolah</h1>
                <p class="text-muted mb-0">Setiap sekolah memakai NPSN sebagai identitas ruang data sendiri.</p>
            </div>
            <div class="panel-body">
                <?php if ($success) { ?>
                    <div class="alert alert-success">
                        <div class="fw-bold">Sekolah berhasil dibuat.</div>
                        NPSN sekolah: <strong><?= htmlspecialchars($success['kode']); ?></strong><br>
                        Username admin: <strong><?= htmlspecialchars($success['username']); ?></strong><br>
                        Kontak notifikasi: <strong><?= htmlspecialchars($success['email']); ?></strong> / <strong><?= htmlspecialchars($success['hp']); ?></strong>
                    </div>
                    <a class="btn btn-main" href="login.php?kode=<?= urlencode($success['kode']); ?>">Login ke sekolah</a>
                <?php } else { ?>
                    <?php if ($error) { ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                    <?php } ?>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()); ?>">
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold">NPSN Sekolah</label>
                            <div class="input-group">
                                <input class="form-control" name="npsn" id="npsn" inputmode="numeric" pattern="[0-9]{8,}" placeholder="Contoh: 202XXXXX" required autocomplete="off">
                                <button class="btn btn-check-npsn" type="button" id="btnCekNpsn">
                                    <span class="spinner-border spinner-border-sm d-none" id="npsnSpinner" role="status" aria-hidden="true"></span>
                                    <i class="bi bi-search" id="npsnSearchIcon"></i> Cari
                                </button>
                            </div>
                            <small id="npsnHelp" class="form-text mt-1 d-block"></small>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nama Sekolah</label>
                            <input class="form-control" name="nama_sekolah" id="nama_sekolah" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold">Nama Aplikasi</label>
                            <input class="form-control" name="nama_aplikasi" id="nama_aplikasi" placeholder="Misal: Jurnal Sekolah" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Alamat</label>
                            <input class="form-control" name="alamat" id="alamat">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Email Notifikasi</label>
                            <input type="email" class="form-control" name="email_kontak" placeholder="admin@sekolah.sch.id" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nomor HP/WhatsApp Notifikasi</label>
                            <input class="form-control" name="hp_kontak" inputmode="tel" placeholder="08xxxxxxxxxx" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Nama Admin</label>
                            <input class="form-control" name="nama_admin" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Username Admin</label>
                            <input class="form-control" name="username" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Password Admin</label>
                            <input type="password" class="form-control" name="password" minlength="6" required>
                        </div>
                        <div class="col-12">
                            <button class="btn-main" type="submit"><i class="bi bi-building-add me-1"></i> Daftar</button>
                        </div>
                    </form>
                <?php } ?>
            </div>
        </section>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const npsnInput = document.getElementById('npsn');
            const nmSekolahInput = document.getElementById('nama_sekolah');
            const alamatInput = document.getElementById('alamat');
            const btnCek = document.getElementById('btnCekNpsn');
            const spinner = document.getElementById('npsnSpinner');
            const searchIcon = document.getElementById('npsnSearchIcon');
            const npsnHelp = document.getElementById('npsnHelp');

            let searchTimeout = null;
            let lastSearchedNpsn = '';

            function setSearchingState(isSearching) {
                if (isSearching) {
                    spinner.classList.remove('d-none');
                    searchIcon.classList.add('d-none');
                    btnCek.disabled = true;
                    npsnInput.disabled = true;
                    npsnHelp.className = "form-text text-primary mt-1 d-block";
                    npsnHelp.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Mencari data sekolah...';
                } else {
                    spinner.classList.add('d-none');
                    searchIcon.classList.remove('d-none');
                    btnCek.disabled = false;
                    npsnInput.disabled = false;
                    npsnInput.focus();
                }
            }

            async function fetchSchoolData(npsn) {
                if (!npsn || npsn.length < 8) {
                    npsnHelp.className = "form-text text-danger mt-1 d-block";
                    npsnHelp.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> NPSN harus terdiri dari minimal 8 digit angka.';
                    return;
                }

                if (npsn === lastSearchedNpsn) return;
                lastSearchedNpsn = npsn;

                setSearchingState(true);

                try {
                    const response = await fetch(`https://api-sekolah-indonesia.vercel.app/sekolah?npsn=${npsn}`);
                    if (!response.ok) {
                        throw new Error('Gagal menghubungi server data sekolah.');
                    }
                    const result = await response.json();
                    
                    if (result.status === 'success' && result.dataSekolah && result.dataSekolah.length > 0) {
                        const data = result.dataSekolah[0];
                        
                        // Populate fields
                        nmSekolahInput.value = data.sekolah || '';
                        
                        // Construct full address if possible
                        let alamatFull = data.alamat_jalan || '';
                        const parts = [];
                        if (data.kecamatan) parts.push(data.kecamatan.trim());
                        if (data.kabupaten_kota) parts.push(data.kabupaten_kota.trim());
                        if (data.propinsi) parts.push(data.propinsi.trim());
                        
                        if (parts.length > 0) {
                            if (alamatFull) alamatFull += ', ';
                            alamatFull += parts.join(', ');
                        }
                        alamatInput.value = alamatFull;

                        npsnHelp.className = "form-text text-success mt-1 d-block";
                        npsnHelp.innerHTML = `<i class="bi bi-check-circle-fill"></i> Sekolah ditemukan: <strong>${data.sekolah}</strong>`;
                        
                        nmSekolahInput.dispatchEvent(new Event('input'));
                        alamatInput.dispatchEvent(new Event('input'));
                    } else {
                        npsnHelp.className = "form-text text-warning mt-1 d-block";
                        npsnHelp.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> NPSN tidak terdaftar. Silakan isi data secara manual.';
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    npsnHelp.className = "form-text text-danger mt-1 d-block";
                    npsnHelp.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Gagal memuat data otomatis. Silakan isi data secara manual.';
                } finally {
                    setSearchingState(false);
                }
            }

            // Input listener with debounce for auto search
            npsnInput.addEventListener('input', function(e) {
                // Keep only numeric
                let val = this.value.replace(/[^0-9]/g, '');
                if (this.value !== val) {
                    this.value = val;
                }

                // Clear previous timeout
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }

                if (val.length === 8) {
                    // Trigger immediately when 8 digits are entered
                    fetchSchoolData(val);
                } else if (val.length > 8) {
                    // Trigger with debounce if typing continues
                    searchTimeout = setTimeout(() => {
                        fetchSchoolData(val);
                    }, 600);
                } else {
                    npsnHelp.innerHTML = '';
                }
            });

            // Manual button click search
            btnCek.addEventListener('click', function() {
                const val = npsnInput.value.replace(/[^0-9]/g, '');
                fetchSchoolData(val);
            });

            // Support enter key in NPSN field
            npsnInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const val = this.value.replace(/[^0-9]/g, '');
                    fetchSchoolData(val);
                }
            });
        });
    </script>
</body>
</html>
