<?php
/**
 * API: Konfirmasi Kehadiran Guru oleh Ketua Kelas
 * POST /api/konfirmasi_kehadiran_guru.php
 *
 * Body (JSON or form):
 *   id_mapel  - INT, ID jadwal mapel
 *   tanggal   - DATE Y-m-d (opsional, default hari ini)
 *   status    - string: Hadir | Telat | Izin | Tidak Hadir Tanpa Tugas | Tidak Hadir Ada Tugas
 *   catatan   - string (opsional)
 *   hapus     - "1" (opsional) → hapus konfirmasi (undo)
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// ── Auth: harus login sebagai siswa ──────────────────────────────────────────
if (!isset($_SESSION['no_induk']) || ($_SESSION['hak_akses'] ?? 0) != 3) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Akses ditolak. Harus login sebagai siswa.']);
    exit;
}

include __DIR__ . '/../koneksi.php';

$nis       = $_SESSION['no_induk'];
$kelas     = $_SESSION['kelas'];
$namaKetua = $_SESSION['nama_siswa'] ?? '';

// ── Cek apakah siswa adalah ketua kelas (dari DB, toleran jika kolom belum ada) ──
$_jabColChk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_siswa LIKE 'jabatan'");
if (!$_jabColChk || mysqli_num_rows($_jabColChk) === 0) {
    // Belum ada kolom jabatan – bukan ketua kelas
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Fitur ketua kelas belum diaktifkan.']);
    exit;
}
$nisEsc = mysqli_real_escape_string($conn, $nis);
$_jabQ = mysqli_query($conn, "SELECT jabatan FROM tbl_siswa WHERE no_induk='$nisEsc' LIMIT 1");
$_jabRow = $_jabQ ? mysqli_fetch_assoc($_jabQ) : null;
if (!$_jabRow || $_jabRow['jabatan'] !== 'Ketua Kelas') {
    // Refresh session jabatan jika beda
    if ($_jabRow) $_SESSION['jabatan'] = $_jabRow['jabatan'];
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Hanya ketua kelas yang dapat melakukan konfirmasi.']);
    exit;
}
$_SESSION['jabatan'] = 'Ketua Kelas';

// ── Buat tabel jika belum ada ────────────────────────────────────────────────
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_konfirmasi_kehadiran_guru (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  id_mapel INT NOT NULL,
  kelas VARCHAR(100) NOT NULL,
  no_induk_guru VARCHAR(25) NOT NULL,
  nama_guru VARCHAR(150) DEFAULT '',
  nama_mapel VARCHAR(100) DEFAULT '',
  no_induk_ketua VARCHAR(25) NOT NULL,
  nama_ketua VARCHAR(150) NOT NULL,
  status ENUM('Hadir','Telat','Izin','Tidak Hadir Tanpa Tugas','Tidak Hadir Ada Tugas') NOT NULL,
  catatan TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_konfirm (tanggal, id_mapel, kelas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Ambil input ──────────────────────────────────────────────────────────────
$rawInput = json_decode(file_get_contents('php://input'), true);
$input    = $rawInput ?? $_POST;

$idMapel = (int)($input['id_mapel'] ?? 0);
$tanggal = trim($input['tanggal'] ?? date('Y-m-d'));
$status  = trim($input['status'] ?? '');
$catatan = trim($input['catatan'] ?? '');
$hapus   = ($input['hapus'] ?? '0') === '1';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    $tanggal = date('Y-m-d');
}

// ── Validasi id_mapel milik kelas ini ────────────────────────────────────────
$idMapelEsc = (int)$idMapel;
$kelasEsc   = mysqli_real_escape_string($conn, $kelas);
$qMapel = mysqli_query($conn,
    "SELECT ma.id_mapel, ma.no_induk AS no_induk_guru, ma.nama_mapel, g.nama_guru
     FROM tbl_mapel_ampu ma
     LEFT JOIN tbl_guru g ON ma.no_induk = g.no_induk
     WHERE ma.id_mapel = $idMapelEsc AND ma.kelas = '$kelasEsc'
     LIMIT 1"
);
if (!$qMapel || mysqli_num_rows($qMapel) === 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Jadwal tidak ditemukan untuk kelas Anda.']);
    exit;
}
$mapelRow    = mysqli_fetch_assoc($qMapel);
$noIndukGuru = $mapelRow['no_induk_guru'];
$namaGuru    = $mapelRow['nama_guru'];
$namaMapel   = $mapelRow['nama_mapel'];

// ── HAPUS konfirmasi (undo) ─────────────────────────────────────────────────
if ($hapus) {
    $tanggalEsc = mysqli_real_escape_string($conn, $tanggal);
    $ok = mysqli_query($conn,
        "DELETE FROM tbl_konfirmasi_kehadiran_guru
         WHERE tanggal='$tanggalEsc' AND id_mapel=$idMapelEsc AND kelas='$kelasEsc'"
    );
    echo json_encode([
        'ok'  => (bool)$ok,
        'msg' => $ok ? 'Konfirmasi berhasil dihapus.' : mysqli_error($conn)
    ]);
    exit;
}

// ── Validasi status ───────────────────────────────────────────────────────────
$validStatus = ['Hadir','Telat','Izin','Tidak Hadir Tanpa Tugas','Tidak Hadir Ada Tugas'];
if (!in_array($status, $validStatus)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Status tidak valid.', 'valid' => $validStatus]);
    exit;
}

// ── Simpan (INSERT or UPDATE) ─────────────────────────────────────────────────
$tanggalEsc      = mysqli_real_escape_string($conn, $tanggal);
$noIndukGuruEsc  = mysqli_real_escape_string($conn, $noIndukGuru);
$namaGuruEsc     = mysqli_real_escape_string($conn, $namaGuru);
$namaMapelEsc    = mysqli_real_escape_string($conn, $namaMapel);
$noIndukKetuaEsc = mysqli_real_escape_string($conn, $nis);
$namaKetuaEsc    = mysqli_real_escape_string($conn, $namaKetua);
$statusEsc       = mysqli_real_escape_string($conn, $status);
$catatanEsc      = mysqli_real_escape_string($conn, $catatan);

$sql = "INSERT INTO tbl_konfirmasi_kehadiran_guru
    (tanggal, id_mapel, kelas, no_induk_guru, nama_guru, nama_mapel,
     no_induk_ketua, nama_ketua, status, catatan)
VALUES
    ('$tanggalEsc', $idMapelEsc, '$kelasEsc', '$noIndukGuruEsc', '$namaGuruEsc', '$namaMapelEsc',
     '$noIndukKetuaEsc', '$namaKetuaEsc', '$statusEsc', '$catatanEsc')
ON DUPLICATE KEY UPDATE
    status       = VALUES(status),
    catatan      = VALUES(catatan),
    no_induk_ketua = VALUES(no_induk_ketua),
    nama_ketua   = VALUES(nama_ketua),
    updated_at   = NOW()";

$ok = mysqli_query($conn, $sql);

echo json_encode([
    'ok'     => (bool)$ok,
    'msg'    => $ok ? 'Konfirmasi berhasil disimpan.' : mysqli_error($conn),
    'status' => $status,
    'guru'   => $namaGuru,
    'mapel'  => $namaMapel
]);
