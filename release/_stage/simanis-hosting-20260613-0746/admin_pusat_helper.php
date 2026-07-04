<?php

function pusat_admin_ensure_schema(mysqli $conn): void
{
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_admin_pusat (
        id_admin_pusat INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(80) NOT NULL UNIQUE,
        nama VARCHAR(150) NOT NULL,
        email VARCHAR(150) NULL,
        password VARCHAR(255) NOT NULL,
        status ENUM('Aktif','Non-Aktif') NOT NULL DEFAULT 'Aktif',
        last_login_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function pusat_admin_count(mysqli $conn): int
{
    pusat_admin_ensure_schema($conn);
    $q = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM tbl_admin_pusat");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    return (int)($row['total'] ?? 0);
}

function pusat_admin_create(mysqli $conn, string $username, string $nama, string $email, string $password): bool
{
    pusat_admin_ensure_schema($conn);
    $userEsc = mysqli_real_escape_string($conn, $username);
    $namaEsc = mysqli_real_escape_string($conn, $nama);
    $emailEsc = mysqli_real_escape_string($conn, $email);
    $hashEsc = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_DEFAULT));

    return (bool)@mysqli_query(
        $conn,
        "INSERT INTO tbl_admin_pusat (username, nama, email, password, status)
         VALUES ('$userEsc', '$namaEsc', '$emailEsc', '$hashEsc', 'Aktif')"
    );
}

function pusat_admin_find(mysqli $conn, string $username): ?array
{
    pusat_admin_ensure_schema($conn);
    $userEsc = mysqli_real_escape_string($conn, $username);
    $q = @mysqli_query($conn, "SELECT * FROM tbl_admin_pusat WHERE username='$userEsc' LIMIT 1");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    return $row ?: null;
}

function pusat_admin_mark_login(mysqli $conn, int $idAdmin): void
{
    @mysqli_query($conn, "UPDATE tbl_admin_pusat SET last_login_at=NOW() WHERE id_admin_pusat=" . (int)$idAdmin);
}

function pusat_mask_secret(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '-';
    }
    if (strlen($value) <= 14) {
        return substr($value, 0, 4) . '...' . substr($value, -3);
    }
    return substr($value, 0, 8) . '...' . substr($value, -6);
}

function pusat_google_oauth_status(mysqli $conn): array
{
    if (!function_exists('google_oauth_credentials')) {
        require_once __DIR__ . '/google_auth.php';
    }

    $cfg = google_oauth_credentials();
    return [
        'configured' => $cfg['client_id'] !== '' && $cfg['client_secret'] !== '',
        'client_id' => pusat_mask_secret((string)$cfg['client_id']),
        'redirect_uri' => (string)$cfg['redirect_uri'],
    ];
}

function pusat_count_table(mysqli $conn, string $table): int
{
    if (!function_exists('mt_table_exists') || !mt_table_exists($conn, $table)) {
        return 0;
    }
    $q = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM `$table`");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    return (int)($row['total'] ?? 0);
}

function pusat_count_by_school_expr(mysqli $conn, string $table, string $aliasName): string
{
    if (!function_exists('mt_table_exists') || !function_exists('mt_column_exists') || !mt_table_exists($conn, $table) || !mt_column_exists($conn, $table, 'id_sekolah')) {
        return "0 AS `$aliasName`";
    }

    return "(SELECT COUNT(*) FROM `$table` x WHERE x.id_sekolah=s.id_sekolah) AS `$aliasName`";
}

function pusat_dashboard_stats(mysqli $conn): array
{
    $stats = [
        'schools' => 0,
        'active_schools' => 0,
        'inactive_schools' => 0,
        'admins' => pusat_count_table($conn, 'tbl_user'),
        'teachers' => pusat_count_table($conn, 'tbl_guru'),
        'students' => pusat_count_table($conn, 'tbl_siswa'),
    ];

    if (!function_exists('mt_table_exists') || !mt_table_exists($conn, 'tbl_sekolah')) {
        return $stats;
    }

    $q = @mysqli_query($conn, "SELECT
        COUNT(*) AS schools,
        SUM(CASE WHEN status='Aktif' THEN 1 ELSE 0 END) AS active_schools,
        SUM(CASE WHEN status<>'Aktif' THEN 1 ELSE 0 END) AS inactive_schools
        FROM tbl_sekolah");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    if ($row) {
        $stats['schools'] = (int)$row['schools'];
        $stats['active_schools'] = (int)$row['active_schools'];
        $stats['inactive_schools'] = (int)$row['inactive_schools'];
    }

    return $stats;
}

function pusat_school_rows(mysqli $conn, string $search = '', string $status = ''): array
{
    if (!function_exists('mt_ensure_schema')) {
        require_once __DIR__ . '/multi_tenant.php';
    }
    mt_ensure_schema($conn);

    $where = [];
    if ($search !== '') {
        $qEsc = mysqli_real_escape_string($conn, '%' . $search . '%');
        $where[] = "(s.kode_sekolah LIKE '$qEsc' OR COALESCE(s.npsn,'') LIKE '$qEsc' OR s.nama_sekolah LIKE '$qEsc' OR COALESCE(s.email_kontak,'') LIKE '$qEsc' OR COALESCE(s.hp_kontak,'') LIKE '$qEsc')";
    }
    if (in_array($status, ['Aktif', 'Non-Aktif'], true)) {
        $statusEsc = mysqli_real_escape_string($conn, $status);
        $where[] = "s.status='$statusEsc'";
    }

    $adminCount = pusat_count_by_school_expr($conn, 'tbl_user', 'total_admin');
    $guruCount = pusat_count_by_school_expr($conn, 'tbl_guru', 'total_guru');
    $siswaCount = pusat_count_by_school_expr($conn, 'tbl_siswa', 'total_siswa');
    $kelasCount = pusat_count_by_school_expr($conn, 'tbl_kelas', 'total_kelas');
    $mapelCount = pusat_count_by_school_expr($conn, 'tbl_mapel', 'total_mapel');
    $onlineExpr = function_exists('mt_table_exists') && mt_table_exists($conn, 'tbl_user_online')
        ? "(SELECT COUNT(*) FROM tbl_user_online uo WHERE uo.is_online=1 AND uo.user_key LIKE CONCAT('school:', s.id_sekolah, ':%')) AS total_online"
        : "0 AS total_online";
    $lastExpr = function_exists('mt_table_exists') && mt_table_exists($conn, 'tbl_user_online')
        ? "(SELECT MAX(uo.last_activity) FROM tbl_user_online uo WHERE uo.user_key LIKE CONCAT('school:', s.id_sekolah, ':%')) AS last_activity"
        : "NULL AS last_activity";

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT s.*, $adminCount, $guruCount, $siswaCount, $kelasCount, $mapelCount, $onlineExpr, $lastExpr
            FROM tbl_sekolah s
            $whereSql
            ORDER BY s.created_at DESC, s.id_sekolah DESC";

    $rows = [];
    $q = @mysqli_query($conn, $sql);
    while ($q && ($row = mysqli_fetch_assoc($q))) {
        $rows[] = $row;
    }
    return $rows;
}

function pusat_update_school_status(mysqli $conn, int $idSekolah, string $status): bool
{
    if (!in_array($status, ['Aktif', 'Non-Aktif'], true)) {
        return false;
    }
    $statusEsc = mysqli_real_escape_string($conn, $status);
    return (bool)@mysqli_query($conn, "UPDATE tbl_sekolah SET status='$statusEsc' WHERE id_sekolah=" . (int)$idSekolah);
}

function pusat_delete_school(mysqli $conn, int $idSekolah): bool
{
    if ($idSekolah <= 0) {
        return false;
    }

    // Get school info first
    $q = @mysqli_query($conn, "SELECT id_sekolah, kode_sekolah FROM tbl_sekolah WHERE id_sekolah=" . (int)$idSekolah);
    if (!$q || !($school = mysqli_fetch_assoc($q))) {
        return false;
    }

    // Delete school record
    $deleted = (bool)@mysqli_query($conn, "DELETE FROM tbl_sekolah WHERE id_sekolah=" . (int)$idSekolah);

    if ($deleted) {
        // Log deletion
        @error_log("[Admin Pusat] School deleted: ID=$idSekolah, Code=" . $school['kode_sekolah']);
    }

    return $deleted;
}
