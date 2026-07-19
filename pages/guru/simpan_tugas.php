<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login sebagai guru.']);
    exit;
}

require_once __DIR__ . '/../../koneksi.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi database tidak tersedia.']);
    exit;
}

function tugas_json(bool $success, string $message, array $extra = []): void
{
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function tugas_column_exists(mysqli $conn, string $column): bool
{
    $columnEsc = mysqli_real_escape_string($conn, $column);
    $q = mysqli_query($conn, "SHOW COLUMNS FROM tbl_tugas LIKE '{$columnEsc}'");
    return (bool)($q && mysqli_num_rows($q) > 0);
}

function tugas_ensure_schema(mysqli $conn): void
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_tugas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tanggal DATE NOT NULL,
        id_mapel INT NOT NULL,
        kelas VARCHAR(50) NOT NULL,
        mapel VARCHAR(100) NOT NULL,
        no_induk_guru VARCHAR(50) NOT NULL,
        judul_tugas VARCHAR(255) NOT NULL,
        deskripsi TEXT,
        link_tugas TEXT NULL,
        file_tugas VARCHAR(255) NULL,
        tanggal_pengumpulan DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM('aktif','selesai','dihapus') DEFAULT 'aktif',
        INDEX (tanggal, id_mapel),
        INDEX (no_induk_guru)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if (!tugas_column_exists($conn, 'link_tugas')) {
        mysqli_query($conn, "ALTER TABLE tbl_tugas ADD COLUMN link_tugas TEXT NULL AFTER deskripsi");
    }
    if (!tugas_column_exists($conn, 'file_tugas')) {
        mysqli_query($conn, "ALTER TABLE tbl_tugas ADD COLUMN file_tugas VARCHAR(255) NULL AFTER link_tugas");
    }
    if (!tugas_column_exists($conn, 'updated_at')) {
        mysqli_query($conn, "ALTER TABLE tbl_tugas ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    }
    if (!tugas_column_exists($conn, 'tanggal_pengumpulan')) {
        // Coba rename batas_waktu jika ada, jika tidak, tambahkan baru
        if (tugas_column_exists($conn, 'batas_waktu')) {
            mysqli_query($conn, "ALTER TABLE tbl_tugas CHANGE batas_waktu tanggal_pengumpulan DATE NULL");
        } else {
            mysqli_query($conn, "ALTER TABLE tbl_tugas ADD COLUMN tanggal_pengumpulan DATE NULL AFTER file_tugas");
        }
    }
}

function tugas_store_file(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        tugas_json(false, 'Upload file gagal.');
    }
    if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
        tugas_json(false, 'Ukuran file maksimal 10MB.');
    }

    $allowed = ['pdf','doc','docx','ppt','pptx','xlsx','xls','jpg','jpeg','png','zip','rar'];
    $original = (string)($file['name'] ?? 'file');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        tugas_json(false, 'Jenis file tidak diizinkan.');
    }

    $uploadDir = __DIR__ . '/../../uploads/tugas';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $safeBase = preg_replace('/[^A-Za-z0-9_-]+/', '-', pathinfo($original, PATHINFO_FILENAME));
    $safeBase = trim($safeBase, '-') ?: 'tugas';
    $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '-' . $safeBase . '.' . $ext;
    $target = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
        tugas_json(false, 'Gagal menyimpan file upload.');
    }

    return '../../uploads/tugas/' . $fileName;
}

tugas_ensure_schema($conn);

$action = (string)($_POST['action'] ?? 'simpan');
$nipGuru = (string)$_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nipGuru);
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantTugas = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_tugas', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";
$tenantMapelAmpu = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_mapel_ampu', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";

if ($action === 'hapus') {
    $tugasId = (int)($_POST['tugas_id'] ?? 0);
    if ($tugasId <= 0) {
        tugas_json(false, 'ID tugas tidak valid.');
    }
    $ok = mysqli_query($conn, "UPDATE tbl_tugas SET status='dihapus' WHERE {$tenantTugas} AND id={$tugasId} AND no_induk_guru='{$nipEsc}'");
    tugas_json((bool)$ok, $ok ? 'Tugas berhasil dihapus.' : 'Gagal menghapus tugas.');
}

$idMapel = (int)($_POST['id_mapel'] ?? 0);
$kelas = trim((string)($_POST['kelas'] ?? ''));
$mapel = trim((string)($_POST['mapel'] ?? ''));
$tanggal = trim((string)($_POST['tanggal'] ?? date('Y-m-d')));
$judul = trim((string)($_POST['judul_tugas'] ?? ''));
$deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
$link = trim((string)($_POST['link_tugas'] ?? ''));
$deadline = trim((string)($_POST['tanggal_pengumpulan'] ?? ''));

if ($idMapel <= 0 || $kelas === '' || $mapel === '' || $judul === '' || $deskripsi === '') {
    tugas_json(false, 'Mapel, kelas, judul, dan deskripsi wajib diisi.');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    tugas_json(false, 'Tanggal tugas tidak valid.');
}
if ($deadline !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) {
    tugas_json(false, 'Tanggal pengumpulan tidak valid.');
}
if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
    tugas_json(false, 'Format link tugas tidak valid.');
}

$idMapelEsc = (string)$idMapel;
$kelasEsc = mysqli_real_escape_string($conn, $kelas);
$qMapel = mysqli_query($conn, "SELECT 1 FROM tbl_mapel_ampu WHERE {$tenantMapelAmpu} AND id_mapel={$idMapelEsc} AND no_induk='{$nipEsc}' AND kelas='{$kelasEsc}' LIMIT 1");
if (!$qMapel || mysqli_num_rows($qMapel) === 0) {
    tugas_json(false, 'Mata pelajaran tidak ditemukan atau bukan milik Anda.');
}

$filePath = isset($_FILES['file_tugas']) ? tugas_store_file($_FILES['file_tugas']) : null;

$stmt = mysqli_prepare($conn, "INSERT INTO tbl_tugas
    (tanggal, id_mapel, kelas, mapel, no_induk_guru, judul_tugas, deskripsi, link_tugas, file_tugas, tanggal_pengumpulan, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), 'aktif')");
if (!$stmt) {
    tugas_json(false, 'Gagal menyiapkan query: ' . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    'sissssssss',
    $tanggal,
    $idMapel,
    $kelas,
    $mapel,
    $nipGuru,
    $judul,
    $deskripsi,
    $link,
    $filePath,
    $deadline
);

if (!mysqli_stmt_execute($stmt)) {
    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    tugas_json(false, 'Gagal menyimpan tugas: ' . $error);
}

$insertId = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);
tugas_json(true, 'Tugas berhasil disimpan.', ['id' => $insertId]);
