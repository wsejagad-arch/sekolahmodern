<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_admin_ajax();

if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database tidak tersedia.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_ekskul_siswa_discovery_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    run_id VARCHAR(40) NOT NULL,
    endpoint VARCHAR(200) NOT NULL,
    method VARCHAR(10) DEFAULT NULL,
    status_code INT DEFAULT NULL,
    has_keyword TINYINT(1) NOT NULL DEFAULT 0,
    relations_found INT NOT NULL DEFAULT 0,
    preview_text TEXT,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_run_id (run_id),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$runId = trim((string)($_GET['run_id'] ?? $_POST['run_id'] ?? ''));
$limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 200);
if ($limit < 1) {
    $limit = 50;
}
if ($limit > 500) {
    $limit = 500;
}

$runs = [];
$qRuns = mysqli_query($conn, "SELECT run_id, COUNT(*) AS total_rows, MAX(created_at) AS created_at
    FROM tbl_ekskul_siswa_discovery_log
    GROUP BY run_id
    ORDER BY MAX(created_at) DESC
    LIMIT 20");
if ($qRuns) {
    while ($r = mysqli_fetch_assoc($qRuns)) {
        $runs[] = $r;
    }
}

if ($runId === '' && !empty($runs)) {
    $runId = (string)$runs[0]['run_id'];
}

$rows = [];
if ($runId !== '') {
    $runIdEsc = mysqli_real_escape_string($conn, $runId);
    $qRows = mysqli_query($conn, "SELECT run_id, endpoint, method, status_code, has_keyword, relations_found, preview_text, created_at
        FROM tbl_ekskul_siswa_discovery_log
        WHERE run_id='{$runIdEsc}'
        ORDER BY id ASC
        LIMIT {$limit}");
    if ($qRows) {
        while ($r = mysqli_fetch_assoc($qRows)) {
            $rows[] = $r;
        }
    }
}

echo json_encode([
    'success' => true,
    'selected_run_id' => $runId,
    'runs' => $runs,
    'rows' => $rows,
], JSON_UNESCAPED_UNICODE);
