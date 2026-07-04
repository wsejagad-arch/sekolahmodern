<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'message' => 'Sesi guru tidak valid.'
    ]);
    exit;
}

require_once __DIR__ . '/../../koneksi.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Koneksi database tidak tersedia.'
    ]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'ID pengumuman tidak valid.'
    ]);
    exit;
}

$noInduk = mysqli_real_escape_string($conn, (string)$_SESSION['no_induk']);

$exists = @mysqli_query($conn, "SELECT id FROM tbl_pengumuman WHERE id=$id LIMIT 1");
if (!$exists || mysqli_num_rows($exists) === 0) {
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'message' => 'Pengumuman tidak ditemukan.'
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

$ok = @mysqli_query(
    $conn,
    "INSERT INTO tbl_pengumuman_read (pengumuman_id, no_induk)
     VALUES ($id, '$noInduk')
     ON DUPLICATE KEY UPDATE read_at=CURRENT_TIMESTAMP"
);

if (!$ok) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Gagal menandai pengumuman: ' . mysqli_error($conn)
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'id' => $id
]);
