<?php
// Set session configuration untuk compatibility
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', 1);
session_start();

require_once 'koneksi.php';
require_once 'functions.php';
require_once 'notification_helper.php';

header('Content-Type: application/json');

// Cek koneksi database
if ($conn === null || !($conn instanceof mysqli)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Koneksi database gagal. Hubungi administrator.'
    ]);
    exit;
}

// Cek login - sesuaikan dengan session guru atau siswa
if (!isset($_SESSION['no_induk']) || !in_array((int)($_SESSION['hak_akses'] ?? 0), [2, 3], true)) {
    // Log untuk debugging
    error_log('Simpan pelanggaran - Unauthorized. Session: ' . print_r($_SESSION, true));
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized - Silakan login terlebih dahulu sebagai guru atau siswa',
        'debug' => [
            'session_exists' => isset($_SESSION['no_induk']),
            'hak_akses' => $_SESSION['hak_akses'] ?? 'not set',
            'session_id' => session_id()
        ]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Validasi input berdasarkan role (Siswa vs Guru)
    $is_siswa = ((int)$_SESSION['hak_akses'] === 3);
    
    if ($is_siswa) {
        $required_fields = ['kategori_pelanggaran', 'jenis_pelanggaran'];
    } else {
        $required_fields = ['kelas', 'no_induk', 'kategori_pelanggaran', 'jenis_pelanggaran'];
    }
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field $field harus diisi"]);
            exit;
        }
    }
    
    $kategori_pelanggaran = trim($_POST['kategori_pelanggaran']);
    $jenis_pelanggaran = trim($_POST['jenis_pelanggaran']);
    if ($jenis_pelanggaran === 'Lainnya' && !empty($_POST['jenis_pelanggaran_kustom'])) {
        $jenis_pelanggaran = trim($_POST['jenis_pelanggaran_kustom']);
    }
    $deskripsi_pelanggaran = trim($_POST['deskripsi_pelanggaran'] ?? '');
    $status_pelanggaran = trim($_POST['status_pelanggaran'] ?? 'Aktif');
    $tanggal_pelanggaran = date('Y-m-d'); // otomatis hari ini sesuai input
    
    // Validasi kategori pelanggaran
    $valid_categories = ['Ringan', 'Sedang', 'Berat'];
    if (!in_array($kategori_pelanggaran, $valid_categories)) {
        echo json_encode(['success' => false, 'message' => 'Kategori pelanggaran tidak valid']);
        exit;
    }
    
    // Validasi status pelanggaran
    $valid_status = ['Aktif', 'Diselesaikan', 'Follow Up'];
    if (!in_array($status_pelanggaran, $valid_status)) {
        echo json_encode(['success' => false, 'message' => 'Status pelanggaran tidak valid']);
        exit;
    }

    if ($is_siswa) {
        $no_induk = $_SESSION['no_induk'];
        // Ambil info nama & kelas dari tbl_siswa untuk keamanan
        $q_siswa_info = mysqli_query($conn, "SELECT nama_siswa, kelas FROM tbl_siswa WHERE no_induk = '" . mysqli_real_escape_string($conn, $no_induk) . "' LIMIT 1");
        if ($q_siswa_info && $row_info = mysqli_fetch_assoc($q_siswa_info)) {
            $nama_siswa = $row_info['nama_siswa'];
            $kelas = $row_info['kelas'];
        } else {
            $nama_siswa = $_SESSION['nama'] ?? 'Siswa';
            $kelas = $_SESSION['kelas'] ?? '';
        }
        
        $tindakan_guru = 'Laporan Mandiri (Siswa)';
        $id_guru = $no_induk;
        $nama_guru = 'Mandiri (Siswa)';
    } else {
        $kelas = trim($_POST['kelas']);
        $no_induk = trim($_POST['no_induk']);
        $tindakan_guru = trim($_POST['tindakan_guru'] ?? '');
        $id_guru = $_SESSION['no_induk'];
        
        // Cek apakah siswa ada di kelas tersebut (PHP 7.0 compatible)
        $check_siswa = "SELECT nama_siswa AS nama FROM tbl_siswa WHERE no_induk = ? AND kelas = ?";
        $stmt_check = mysqli_prepare($conn, $check_siswa);
        mysqli_stmt_bind_param($stmt_check, "ss", $no_induk, $kelas);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_bind_result($stmt_check, $nama_siswa);
        
        if (!mysqli_stmt_fetch($stmt_check)) {
            mysqli_stmt_close($stmt_check);
            
            // Log untuk debugging
            error_log("Siswa tidak ditemukan - no_induk: $no_induk, kelas: $kelas");
            
            // Cek apakah siswa ada tapi di kelas berbeda
            $check_any = mysqli_query($conn, "SELECT kelas FROM tbl_siswa WHERE no_induk = '$no_induk'");
            if ($check_any && mysqli_num_rows($check_any) > 0) {
                $siswa_kelas = mysqli_fetch_assoc($check_any);
                echo json_encode([
                    'success' => false, 
                    'message' => "Siswa ditemukan di kelas " . $siswa_kelas['kelas'] . ", bukan di kelas $kelas. Pastikan kelas yang dipilih sudah benar."
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => "Siswa dengan No. Induk $no_induk tidak ditemukan di database. Pastikan data siswa sudah benar."
                ]);
            }
            exit;
        }
        mysqli_stmt_close($stmt_check);
        
        // Get guru name
        $guru_query = "SELECT nama_guru FROM tbl_guru WHERE no_induk = ?";
        $guru_stmt = mysqli_prepare($conn, $guru_query);
        mysqli_stmt_bind_param($guru_stmt, "s", $id_guru);
        mysqli_stmt_execute($guru_stmt);
        mysqli_stmt_bind_result($guru_stmt, $nama_guru);
        if (!mysqli_stmt_fetch($guru_stmt)) {
            $nama_guru = 'Unknown';
        }
        mysqli_stmt_close($guru_stmt);
    }
    
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pelanggaran_siswa (
        id_pelanggaran int(11) NOT NULL AUTO_INCREMENT,
        no_induk varchar(25) NOT NULL,
        nama_siswa varchar(150) NOT NULL,
        kelas varchar(50) NOT NULL,
        tanggal_pelanggaran date NOT NULL,
        kategori_pelanggaran enum('Berat','Sedang','Ringan') NOT NULL,
        jenis_pelanggaran varchar(100) NOT NULL,
        deskripsi_pelanggaran text,
        tindakan_yang_diambil text,
        no_induk_guru varchar(25) NOT NULL,
        nama_guru varchar(150) NOT NULL,
        status_pelanggaran enum('Aktif','Diselesaikan','Follow Up') DEFAULT 'Aktif',
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (id_pelanggaran),
        KEY idx_siswa (no_induk),
        KEY idx_tanggal (tanggal_pelanggaran),
        KEY idx_kategori (kategori_pelanggaran),
        KEY idx_guru (no_induk_guru)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pelanggaran_siswa'");
    if (!$check_table || mysqli_num_rows($check_table) === 0) {
        echo json_encode(['success' => false, 'message' => 'Tabel pelanggaran_siswa gagal dibuat. Hubungi administrator.']);
        exit;
    }

    $query = "INSERT INTO tbl_pelanggaran_siswa (
        no_induk, nama_siswa, kelas, kategori_pelanggaran, jenis_pelanggaran,
        deskripsi_pelanggaran, tindakan_yang_diambil, tanggal_pelanggaran,
        status_pelanggaran, no_induk_guru, nama_guru, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "sssssssssss", 
        $no_induk, $nama_siswa, $kelas, $kategori_pelanggaran, $jenis_pelanggaran,
        $deskripsi_pelanggaran, $tindakan_guru, $tanggal_pelanggaran,
        $status_pelanggaran, $id_guru, $nama_guru
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $insert_id = mysqli_insert_id($conn);
        
        $log_message = "Pelanggaran disimpan - ID: {$insert_id}, Siswa: {$nama_siswa} ({$no_induk}), Kategori: {$kategori_pelanggaran}, Jenis: {$jenis_pelanggaran}";
        error_log($log_message);
        
        // ─── Notifikasi WhatsApp ke orang tua ───────────────────────
        $q_siswa = @mysqli_query($conn,
            "SELECT no_wa, no_darurat, nama_darurat, ayah_nama, ibu_nama
               FROM tbl_siswa WHERE no_induk = '" . mysqli_real_escape_string($conn, $no_induk) . "' LIMIT 1"
        );
        if ($q_siswa && $row_siswa = mysqli_fetch_assoc($q_siswa)) {
            $hp_ortu = trim($row_siswa['no_wa'] ?? $row_siswa['no_darurat'] ?? '');
            $nama_ortu = trim($row_siswa['nama_darurat'] ?? $row_siswa['ayah_nama'] ?? 'Orang Tua');

            if ($hp_ortu !== '') {
                $tgl_fmt   = date('d/m/Y', strtotime($tanggal_pelanggaran));
                $judul_wa  = "📢 Pemberitahuan Pelanggaran Siswa";
                $pesan_wa  = "Yth. {$nama_ortu},\n\n"
                           . "Kami informasikan bahwa putra/putri Anda:\n"
                           . "*Nama*  : {$nama_siswa}\n"
                           . "*Kelas* : {$kelas}\n"
                           . "*Tanggal* : {$tgl_fmt}\n\n"
                           . "Telah tercatat melakukan pelanggaran:\n"
                           . "*Kategori* : {$kategori_pelanggaran}\n"
                           . "*Jenis*    : {$jenis_pelanggaran}\n";
                if ($deskripsi_pelanggaran !== '') {
                    $pesan_wa .= "*Keterangan* : {$deskripsi_pelanggaran}\n";
                }
                if ($tindakan_guru !== '') {
                    $pesan_wa .= "*Tindakan*   : {$tindakan_guru}\n";
                }
                $pesan_wa .= "\nMohon kerja sama Bapak/Ibu untuk memberikan pembinaan.\n"
                           . "— SIMANIS SMAN 1 Sumber";

                // Langsung kirim (non-blocking jika WASENDER aktif)
                notif_send_whatsapp($hp_ortu, $judul_wa, $pesan_wa, $conn);
            }
        }
        // ────────────────────────────────────────────────────────────

        mysqli_stmt_close($stmt);
        
        echo json_encode([
            'success' => true,
            'message' => 'Catatan pelanggaran berhasil disimpan',
            'data' => [
                'id'          => $insert_id,
                'nama_siswa'  => $nama_siswa,
                'kelas'       => $kelas,
                'kategori'    => $kategori_pelanggaran,
                'jenis'       => $jenis_pelanggaran,
                'tanggal'     => $tanggal_pelanggaran
            ]
        ]);
        
    } else {
        mysqli_stmt_close($stmt);
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt) . " - " . mysqli_error($conn));
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
