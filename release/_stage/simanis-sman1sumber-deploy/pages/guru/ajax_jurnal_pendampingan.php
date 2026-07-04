<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['no_induk']) || $_SESSION['hak_akses'] != 2) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once '../../koneksi.php';

function guru_is_wali_kelas(mysqli $conn, string $nipGuru, string $kelas): bool
{
    $nipEsc = mysqli_real_escape_string($conn, $nipGuru);
    $kelasEsc = mysqli_real_escape_string($conn, $kelas);

    $checkWali = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_wali_kelas'");
    if ($checkWali && mysqli_num_rows($checkWali) > 0) {
        $qWali = mysqli_query(
            $conn,
            "SELECT 1
             FROM tbl_wali_kelas wk
             JOIN tbl_kelas k ON k.id_kelas = wk.id_kelas
             WHERE wk.nip_wali = '$nipEsc' AND k.kelas = '$kelasEsc'
             LIMIT 1"
        );
        if ($qWali && mysqli_num_rows($qWali) > 0) {
            return true;
        }
    }

    $checkKelasCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_kelas LIKE 'nip_wali'");
    if ($checkKelasCol && mysqli_num_rows($checkKelasCol) > 0) {
        $qKelasWali = mysqli_query($conn, "SELECT 1 FROM tbl_kelas WHERE nip_wali='$nipEsc' AND kelas='$kelasEsc' LIMIT 1");
        if ($qKelasWali && mysqli_num_rows($qKelasWali) > 0) {
            return true;
        }
    }

    return false;
}

// Ensure table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_jurnal_pendampingan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    nis VARCHAR(50) NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    catatan TEXT,
    tindak_lanjut TEXT,
    status VARCHAR(20) DEFAULT 'Belum Selesai',
    nip_guru VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$nipGuru = $_SESSION['no_induk'];

header('Content-Type: application/json');

if ($action === 'save') {
    $nis = mysqli_real_escape_string($conn, $_POST['nis'] ?? '');
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas'] ?? '');
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');
    $tindak_lanjut = mysqli_real_escape_string($conn, $_POST['tindak_lanjut'] ?? '');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Belum Selesai');
    $tanggal = date('Y-m-d');

    if ($nis === '' || $kelas === '' || $catatan === '') {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    if (!guru_is_wali_kelas($conn, $nipGuru, $kelas)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Tindak lanjut hanya dapat dicatat oleh wali kelas.']);
        exit;
    }

    $q = mysqli_query($conn, "INSERT INTO tbl_jurnal_pendampingan (tanggal, nis, kelas, catatan, tindak_lanjut, status, nip_guru) 
                              VALUES ('$tanggal', '$nis', '$kelas', '$catatan', '$tindak_lanjut', '$status', '$nipGuru')");
    
    if ($q) {
        echo json_encode(['status' => 'success', 'message' => 'Jurnal berhasil disimpan']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action === 'get') {
    $kelas = mysqli_real_escape_string($conn, $_GET['kelas'] ?? '');
    $nis = mysqli_real_escape_string($conn, $_GET['nis'] ?? '');
    
    $whereParts = [];
    if ($kelas !== '') {
        $whereParts[] = "j.kelas='$kelas'";
    }
    if ($nis !== '') {
        $whereParts[] = "j.nis='$nis'";
    }
    
    $whereSql = !empty($whereParts) ? "WHERE " . implode(" AND ", $whereParts) : "";
    
    $q = mysqli_query($conn, "SELECT j.*, s.nama_siswa FROM tbl_jurnal_pendampingan j LEFT JOIN tbl_siswa s ON j.nis = s.no_induk $whereSql ORDER BY j.id DESC");
    $data = [];
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $data[] = $r;
        }
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
