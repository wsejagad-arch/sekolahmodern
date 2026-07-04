<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sesi guru tidak valid.'
    ]);
    exit;
}

require_once __DIR__ . '/../../koneksi.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database tidak tersedia.'
    ]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

function guru_api_table_exists(mysqli $conn, string $table): bool
{
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $q = @mysqli_query($conn, "SHOW TABLES LIKE '$tableEsc'");
    return (bool)($q && mysqli_num_rows($q) > 0);
}

function guru_api_column_exists(mysqli $conn, string $table, string $column): bool
{
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $columnEsc = mysqli_real_escape_string($conn, $column);
    $q = @mysqli_query($conn, "SHOW COLUMNS FROM `$tableEsc` LIKE '$columnEsc'");
    return (bool)($q && mysqli_num_rows($q) > 0);
}

function guru_api_grade_from_class(string $kelas): string
{
    if (preg_match('/\d+/', $kelas, $match)) {
        return $match[0];
    }
    return $kelas;
}

function guru_api_json_error(string $message, int $status = 500): void
{
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

if (!guru_api_table_exists($conn, 'tbl_pengumuman')) {
    echo json_encode([
        'success' => true,
        'items' => [],
        'unread_count' => 0
    ]);
    exit;
}

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_pengumuman_read (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengumuman_id INT NOT NULL,
    no_induk VARCHAR(50) NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_read (pengumuman_id, no_induk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$requiredColumns = ['id', 'judul', 'isi', 'penting', 'mulai', 'selesai'];
foreach ($requiredColumns as $column) {
    if (!guru_api_column_exists($conn, 'tbl_pengumuman', $column)) {
        guru_api_json_error('Struktur tabel pengumuman belum lengkap.');
    }
}

$hasTargetScope = guru_api_column_exists($conn, 'tbl_pengumuman', 'target_scope');
$hasTargetValue = guru_api_column_exists($conn, 'tbl_pengumuman', 'target_value');
$hasLampiran = guru_api_column_exists($conn, 'tbl_pengumuman', 'lampiran');
$hasUpdatedAt = guru_api_column_exists($conn, 'tbl_pengumuman', 'updated_at');

$noInduk = (string)$_SESSION['no_induk'];
$noIndukEsc = mysqli_real_escape_string($conn, $noInduk);
$today = date('Y-m-d');

$kelas = [];
$qKelas = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='$noIndukEsc' AND kelas <> ''");
if ($qKelas) {
    while ($row = mysqli_fetch_assoc($qKelas)) {
        $kelas[] = (string)$row['kelas'];
    }
}

$targetWhere = '1=1';
if ($hasTargetScope && $hasTargetValue) {
    $targetParts = ["p.target_scope IS NULL", "p.target_scope=''", "p.target_scope='SEMUA'"];
    $targetParts[] = "(p.target_scope='GURU' AND (p.target_value IS NULL OR p.target_value='' OR p.target_value='$noIndukEsc'))";

    if ($kelas) {
        $kelasEsc = array_map(static function ($item) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $item) . "'";
        }, $kelas);
        $targetParts[] = "(p.target_scope='KELAS' AND p.target_value IN (" . implode(',', $kelasEsc) . "))";

        $tingkat = array_values(array_unique(array_filter(array_map('guru_api_grade_from_class', $kelas))));
        if ($tingkat) {
            $tingkatEsc = array_map(static function ($item) use ($conn) {
                return "'" . mysqli_real_escape_string($conn, $item) . "'";
            }, $tingkat);
            $targetParts[] = "(p.target_scope='TINGKAT' AND p.target_value IN (" . implode(',', $tingkatEsc) . "))";
        }
    }

    $targetWhere = '(' . implode(' OR ', $targetParts) . ')';
}

$lampiranSelect = $hasLampiran ? 'p.lampiran' : "NULL AS lampiran";
$updatedSelect = $hasUpdatedAt ? 'p.updated_at' : 'p.created_at AS updated_at';

$sql = "SELECT
            p.id,
            p.judul,
            p.isi,
            p.penting,
            p.mulai,
            p.selesai,
            $lampiranSelect,
            p.created_at,
            $updatedSelect,
            CASE WHEN r.id IS NULL THEN 0 ELSE 1 END AS is_read
        FROM tbl_pengumuman p
        LEFT JOIN tbl_pengumuman_read r
          ON r.pengumuman_id = p.id AND r.no_induk = '$noIndukEsc'
        WHERE p.mulai <= '$today'
          AND p.selesai >= '$today'
          AND $targetWhere
        ORDER BY p.penting DESC, p.updated_at DESC, p.id DESC
        LIMIT 20";

if (!$hasUpdatedAt) {
    $sql = str_replace('p.updated_at DESC, ', 'p.created_at DESC, ', $sql);
}

$items = [];
$unreadCount = 0;
$q = @mysqli_query($conn, $sql);
if (!$q) {
    guru_api_json_error('Gagal memuat pengumuman: ' . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($q)) {
    $isRead = (int)($row['is_read'] ?? 0) === 1;
    if (!$isRead) {
        $unreadCount++;
    }

    $items[] = [
        'id' => (int)$row['id'],
        'judul' => (string)$row['judul'],
        'isi' => (string)$row['isi'],
        'penting' => (int)$row['penting'],
        'mulai' => (string)$row['mulai'],
        'selesai' => (string)$row['selesai'],
        'lampiran' => $row['lampiran'] !== null ? (string)$row['lampiran'] : null,
        'created_at' => (string)$row['created_at'],
        'updated_at' => (string)$row['updated_at'],
        'read' => $isRead
    ];
}

echo json_encode([
    'success' => true,
    'items' => $items,
    'unread_count' => $unreadCount
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
