<?php

/**
 * Multi-school tenancy helpers.
 *
 * The app was originally built for one school. These helpers add a compatible
 * id_sekolah layer so new data can be separated without breaking old installs.
 */

function mt_table_exists(mysqli $conn, string $table): bool
{
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $q = @mysqli_query($conn, "SHOW TABLES LIKE '$tableEsc'");
    return (bool)($q && mysqli_num_rows($q) > 0);
}

function mt_column_exists(mysqli $conn, string $table, string $column): bool
{
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $columnEsc = mysqli_real_escape_string($conn, $column);
    $q = @mysqli_query($conn, "SHOW COLUMNS FROM `$tableEsc` LIKE '$columnEsc'");
    return (bool)($q && mysqli_num_rows($q) > 0);
}

function mt_add_school_column(mysqli $conn, string $table, string $after = '')
{
    if (!mt_table_exists($conn, $table) || mt_column_exists($conn, $table, 'id_sekolah')) {
        return;
    }

    $afterSql = $after !== '' && mt_column_exists($conn, $table, $after) ? " AFTER `$after`" : '';
    @mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN id_sekolah INT NOT NULL DEFAULT 1$afterSql");
    @mysqli_query($conn, "ALTER TABLE `$table` ADD INDEX idx_{$table}_id_sekolah (id_sekolah)");
}

function mt_trigger_exists(mysqli $conn, string $trigger): bool
{
    $triggerEsc = mysqli_real_escape_string($conn, $trigger);
    $q = @mysqli_query($conn, "SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME='$triggerEsc' LIMIT 1");
    return (bool)($q && mysqli_num_rows($q) > 0);
}

function mt_ensure_insert_trigger(mysqli $conn, string $table)
{
    if (!mt_table_exists($conn, $table) || !mt_column_exists($conn, $table, 'id_sekolah')) {
        return;
    }

    $trigger = 'bi_' . $table . '_tenant';
    if (mt_trigger_exists($conn, $trigger)) {
        return;
    }

    @mysqli_query($conn, "CREATE TRIGGER `$trigger`
        BEFORE INSERT ON `$table`
        FOR EACH ROW
        SET NEW.id_sekolah = IF(
            NEW.id_sekolah IS NULL OR NEW.id_sekolah = 0 OR NEW.id_sekolah = 1,
            COALESCE(@simanis_id_sekolah, 1),
            NEW.id_sekolah
        )");
}

function mt_ensure_schema(mysqli $conn)
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    // Cache schema migration to avoid running 50+ queries on every page load.
    // Re-check schema only once per hour.
    $cacheFile = sys_get_temp_dir() . '/simanis_schema_' . md5(__DIR__) . '.cache';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
        return;
    }

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_sekolah (
        id_sekolah INT AUTO_INCREMENT PRIMARY KEY,
        kode_sekolah VARCHAR(40) NOT NULL UNIQUE,
        npsn VARCHAR(20) NULL UNIQUE,
        nama_sekolah VARCHAR(200) NOT NULL,
        alamat TEXT NULL,
        email_kontak VARCHAR(150) NULL,
        hp_kontak VARCHAR(30) NULL,
        nama_pimpinan VARCHAR(150) NULL,
        nip_pimpinan VARCHAR(50) NULL,
        logo VARCHAR(150) NULL DEFAULT 'logo dash.png',
        status ENUM('Aktif','Non-Aktif') NOT NULL DEFAULT 'Aktif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (!mt_column_exists($conn, 'tbl_sekolah', 'npsn')) {
        @mysqli_query($conn, "ALTER TABLE tbl_sekolah ADD COLUMN npsn VARCHAR(20) NULL UNIQUE AFTER kode_sekolah");
        @mysqli_query($conn, "UPDATE tbl_sekolah SET npsn=kode_sekolah WHERE kode_sekolah <> 'DEFAULT' AND (npsn IS NULL OR npsn='')");
    }
    if (!mt_column_exists($conn, 'tbl_sekolah', 'email_kontak')) {
        @mysqli_query($conn, "ALTER TABLE tbl_sekolah ADD COLUMN email_kontak VARCHAR(150) NULL AFTER alamat");
    }
    if (!mt_column_exists($conn, 'tbl_sekolah', 'hp_kontak')) {
        @mysqli_query($conn, "ALTER TABLE tbl_sekolah ADD COLUMN hp_kontak VARCHAR(30) NULL AFTER email_kontak");
    }

    $qSchool = @mysqli_query($conn, "SELECT id_sekolah FROM tbl_sekolah WHERE id_sekolah=1 LIMIT 1");
    if (!$qSchool || mysqli_num_rows($qSchool) === 0) {
        $setting = @mysqli_query($conn, "SELECT nama_sekolah, alamat, logo, nama_pimpinan, nip_pimpinan FROM tbl_setting LIMIT 1");
        $s = $setting ? mysqli_fetch_assoc($setting) : [];
        $nama = mysqli_real_escape_string($conn, (string)($s['nama_sekolah'] ?? 'Sekolah Default'));
        $alamat = mysqli_real_escape_string($conn, (string)($s['alamat'] ?? ''));
        $logo = mysqli_real_escape_string($conn, (string)($s['logo'] ?? 'logo dash.png'));
        $pimpinan = mysqli_real_escape_string($conn, (string)($s['nama_pimpinan'] ?? ''));
        $nip = mysqli_real_escape_string($conn, (string)($s['nip_pimpinan'] ?? ''));
        @mysqli_query($conn, "INSERT INTO tbl_sekolah (id_sekolah, kode_sekolah, npsn, nama_sekolah, alamat, logo, nama_pimpinan, nip_pimpinan)
                              VALUES (1, 'DEFAULT', NULL, '$nama', '$alamat', '$logo', '$pimpinan', '$nip')
                              ON DUPLICATE KEY UPDATE nama_sekolah=VALUES(nama_sekolah)");
    }

    $tables = [
        'tbl_setting' => 'id',
        'tbl_user' => 'id_user',
        'tbl_pengguna' => 'no_induk',
        'tbl_guru' => 'id_guru',
        'tbl_siswa' => 'no_induk',
        'tbl_kelas' => 'id_kelas',
        'tbl_mapel' => 'id_mapel',
        'tbl_mapel_ampu' => 'id_mapel',
        'tbl_materi' => 'id_materi',
        'tbl_bahan_ajar' => 'id',
        'tbl_kehadiran' => 'id',
        'tbl_absen' => 'id',
        'tbl_log' => 'id',
        'tbl_thn_ajaran' => 'id',
        'tbl_penilaian_item' => 'id',
        'tbl_nilai_item' => 'id',
        'tbl_tugas' => 'id',
        'tbl_pelanggaran_siswa' => 'id',
        'tbl_leger_nilai_raport_siswa' => 'id',
        'tbl_presensi_setting' => 'id',
        'tbl_agenda_sekolah' => 'id',
    ];

    foreach ($tables as $table => $after) {
        mt_add_school_column($conn, $table, $after);
        if (mt_table_exists($conn, $table) && mt_column_exists($conn, $table, 'id_sekolah')) {
            @mysqli_query($conn, "UPDATE `$table` SET id_sekolah=1 WHERE id_sekolah IS NULL OR id_sekolah=0");
            mt_ensure_insert_trigger($conn, $table);
        }
    }

    if (mt_table_exists($conn, 'tbl_user') && !mt_column_exists($conn, 'tbl_user', 'email')) {
        @mysqli_query($conn, "ALTER TABLE tbl_user ADD COLUMN email VARCHAR(150) NULL AFTER username");
        @mysqli_query($conn, "ALTER TABLE tbl_user ADD INDEX idx_tbl_user_email (email)");
    }
    if (mt_table_exists($conn, 'tbl_guru') && !mt_column_exists($conn, 'tbl_guru', 'email')) {
        @mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN email VARCHAR(150) NULL AFTER nama_guru");
        @mysqli_query($conn, "ALTER TABLE tbl_guru ADD INDEX idx_tbl_guru_email (email)");
    }
    if (mt_table_exists($conn, 'tbl_siswa') && !mt_column_exists($conn, 'tbl_siswa', 'email')) {
        @mysqli_query($conn, "ALTER TABLE tbl_siswa ADD COLUMN email VARCHAR(150) NULL AFTER nama_siswa");
        @mysqli_query($conn, "ALTER TABLE tbl_siswa ADD INDEX idx_tbl_siswa_email (email)");
    }

    $qAllTables = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl\\_%'");
    while ($qAllTables && ($rowTable = mysqli_fetch_array($qAllTables))) {
        $table = (string)$rowTable[0];
        if (in_array($table, ['tbl_sekolah'], true)) {
            continue;
        }
        mt_add_school_column($conn, $table);
        if (mt_column_exists($conn, $table, 'id_sekolah')) {
            @mysqli_query($conn, "UPDATE `$table` SET id_sekolah=1 WHERE id_sekolah IS NULL OR id_sekolah=0");
            mt_ensure_insert_trigger($conn, $table);
        }
    }

    // Mark schema as up-to-date
    @file_put_contents($cacheFile, date('Y-m-d H:i:s'), LOCK_EX);
}

function mt_bootstrap($conn)
{
    if (!$conn instanceof mysqli) {
        return;
    }
    mt_ensure_schema($conn);
    mt_refresh_session_school($conn);
    mt_apply_connection_school($conn);
}

function mt_current_school_id(): int
{
    return max(1, (int)($_SESSION['id_sekolah'] ?? 1));
}

function mt_apply_connection_school(mysqli $conn)
{
    $idSekolah = mt_current_school_id();
    @mysqli_query($conn, "SET @simanis_id_sekolah := " . (int)$idSekolah);
}

function mt_resolve_school_id(mysqli $conn, string $code): int
{
    mt_ensure_schema($conn);
    $code = trim($code);
    if ($code === '') {
        return 0;
    }
    $codeEsc = mysqli_real_escape_string($conn, strtoupper($code));
    $q = @mysqli_query($conn, "SELECT id_sekolah FROM tbl_sekolah WHERE (UPPER(kode_sekolah)='$codeEsc' OR UPPER(COALESCE(npsn,''))='$codeEsc') AND status='Aktif' LIMIT 1");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    return $row ? (int)$row['id_sekolah'] : 0;
}

function mt_refresh_session_school(mysqli $conn)
{
    if (isset($_SESSION['id_sekolah']) && (int)$_SESSION['id_sekolah'] > 0) {
        return;
    }

    if (isset($_SESSION['id_user']) && mt_table_exists($conn, 'tbl_user') && mt_column_exists($conn, 'tbl_user', 'id_sekolah')) {
        $id = (int)$_SESSION['id_user'];
        $q = @mysqli_query($conn, "SELECT id_sekolah FROM tbl_user WHERE id_user=$id LIMIT 1");
        $row = $q ? mysqli_fetch_assoc($q) : null;
        if ($row) {
            $_SESSION['id_sekolah'] = (int)$row['id_sekolah'];
            return;
        }
    }

    if (isset($_SESSION['no_induk'])) {
        $no = mysqli_real_escape_string($conn, (string)$_SESSION['no_induk']);
        foreach (['tbl_guru', 'tbl_siswa', 'tbl_pengguna'] as $table) {
            if (!mt_table_exists($conn, $table) || !mt_column_exists($conn, $table, 'id_sekolah')) {
                continue;
            }
            $q = @mysqli_query($conn, "SELECT id_sekolah FROM `$table` WHERE no_induk='$no' LIMIT 1");
            $row = $q ? mysqli_fetch_assoc($q) : null;
            if ($row) {
                $_SESSION['id_sekolah'] = (int)$row['id_sekolah'];
                return;
            }
        }
    }
}

function mt_school_condition(mysqli $conn, string $alias = ''): string
{
    $prefix = $alias !== '' ? "`$alias`." : '';
    return $prefix . "id_sekolah=" . mt_current_school_id();
}

function mt_create_school_code(string $name): string
{
    $base = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $name));
    $base = substr($base !== '' ? $base : 'SEKOLAH', 0, 12);
    return $base . random_int(100, 999);
}

// Lightweight morning trigger check to ensure background process runs for morning reminders
$mt_current_hour = date('H');
if ($mt_current_hour === '06' || $mt_current_hour === '07') {
    $mt_trigger_lock = sys_get_temp_dir() . '/simanis_morning_trigger_' . date('YmdH') . '.lock';
    if (!file_exists($mt_trigger_lock)) {
        @touch($mt_trigger_lock);
        if (!function_exists('notif_trigger_background_process')) {
            $nh_path = __DIR__ . '/notification_helper.php';
            if (file_exists($nh_path)) {
                require_once $nh_path;
            }
        }
        if (function_exists('notif_trigger_background_process')) {
            notif_trigger_background_process();
        }
    }
}
