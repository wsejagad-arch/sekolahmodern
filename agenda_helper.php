<?php

if (!function_exists('agenda_ensure_table')) {
    function agenda_ensure_table(mysqli $conn): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS tbl_agenda_sekolah (
            id_agenda INT AUTO_INCREMENT PRIMARY KEY,
            judul VARCHAR(180) NOT NULL,
            deskripsi TEXT NULL,
            agenda_date DATE NOT NULL,
            jam_mulai TIME NOT NULL,
            jam_selesai TIME NOT NULL,
            dibuat_unit VARCHAR(40) NOT NULL DEFAULT 'Kurikulum',
            dibuat_oleh_role VARCHAR(30) NOT NULL,
            dibuat_oleh_id VARCHAR(50) NOT NULL,
            dibuat_oleh_nama VARCHAR(140) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_agenda_waktu (agenda_date, jam_selesai),
            INDEX idx_agenda_status (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        return mysqli_query($conn, $sql) === true;
    }
}

if (!function_exists('agenda_get_guru_profile')) {
    function agenda_get_guru_profile(mysqli $conn, string $noInduk): ?array
    {
        if ($noInduk === '') {
            return null;
        }

        $noIndukEsc = mysqli_real_escape_string($conn, $noInduk);
        $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
        $q = mysqli_query($conn, "SELECT no_induk, nama_guru, jabatan FROM tbl_guru WHERE no_induk='" . $noIndukEsc . "' AND id_sekolah = " . $idSekolah . " LIMIT 1");
        if (!$q || mysqli_num_rows($q) === 0) {
            return null;
        }

        return mysqli_fetch_assoc($q);
    }
}

if (!function_exists('agenda_normalize_text')) {
    function agenda_normalize_text(?string $value): string
    {
        $value = (string)$value;
        return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }
}

if (!function_exists('agenda_unit_from_jabatan')) {
    function agenda_unit_from_jabatan(?string $jabatan): string
    {
        $jabatanNorm = agenda_normalize_text($jabatan);

        if ($jabatanNorm === '') {
            return '';
        }

        if (strpos($jabatanNorm, 'kurikulum') !== false) {
            return 'Kurikulum';
        }

        if (strpos($jabatanNorm, 'kesiswaan') !== false) {
            return 'Kesiswaan';
        }

        if (strpos($jabatanNorm, 'humas') !== false) {
            return 'Humas';
        }

        if (strpos($jabatanNorm, 'sarpras') !== false || strpos($jabatanNorm, 'sarana') !== false || strpos($jabatanNorm, 'prasarana') !== false) {
            return 'Sarpras';
        }

        return '';
    }
}

if (!function_exists('agenda_can_manage_user')) {
    function agenda_can_manage_user(mysqli $conn, int $hakAkses, string $noInduk = '', ?array $guruProfile = null, ?string &$unit = null): bool
    {
        $unit = '';

        if ($hakAkses === 1) {
            $unit = 'Admin';
            return true;
        }

        if ($hakAkses !== 2) {
            return false;
        }

        if (!$guruProfile) {
            $guruProfile = agenda_get_guru_profile($conn, $noInduk);
        }

        $unit = agenda_unit_from_jabatan($guruProfile['jabatan'] ?? '');
        return $unit !== '';
    }
}

if (!function_exists('agenda_get_active')) {
    function agenda_get_active(mysqli $conn, int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));

        $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;

        $sql = "SELECT id_agenda, judul, deskripsi, agenda_date, jam_mulai, jam_selesai,
                       dibuat_unit, dibuat_oleh_nama,
                       TIMESTAMP(agenda_date, jam_selesai) AS selesai_at
                FROM tbl_agenda_sekolah
                WHERE is_active = 1 AND id_sekolah = " . $idSekolah . "
                                    AND agenda_date >= CURDATE()
                                ORDER BY (TIMESTAMP(agenda_date, jam_selesai) < NOW()) ASC,
                                                 agenda_date ASC,
                                                 jam_mulai ASC
                LIMIT " . (int)$limit;

        $res = mysqli_query($conn, $sql);
        if (!$res) {
            return [];
        }

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = $row;
        }

        return $items;
    }
}

if (!function_exists('agenda_get_all_for_manage')) {
    function agenda_get_all_for_manage(mysqli $conn, int $limit = 100, string $filter = 'all'): array
    {
        $limit = max(10, min(300, $limit));
        $filter = strtolower(trim($filter));

        $idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
        $where = 'WHERE id_sekolah = ' . $idSekolah;
        if ($filter === 'today') {
            $where .= ' AND agenda_date = CURDATE()';
        } elseif ($filter === 'week') {
            $where .= ' AND agenda_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)';
        } elseif ($filter === 'active') {
            $where .= ' AND is_active = 1 AND TIMESTAMP(agenda_date, jam_selesai) >= NOW()';
        } elseif ($filter === 'passed') {
            $where .= ' AND (TIMESTAMP(agenda_date, jam_selesai) < NOW() OR is_active = 0)';
        }

        $sql = "SELECT id_agenda, judul, deskripsi, agenda_date, jam_mulai, jam_selesai,
                       dibuat_unit, dibuat_oleh_nama, dibuat_oleh_role, dibuat_oleh_id,
                       is_active, created_at, updated_at,
                       TIMESTAMP(agenda_date, jam_selesai) AS selesai_at
                FROM tbl_agenda_sekolah
                " . $where . "
                ORDER BY agenda_date DESC, jam_mulai DESC
                LIMIT " . (int)$limit;

        $res = mysqli_query($conn, $sql);
        if (!$res) {
            return [];
        }

        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = $row;
        }

        return $items;
    }
}

if (!function_exists('agenda_allowed_units')) {
    function agenda_allowed_units(): array
    {
        return ['Kurikulum', 'Kesiswaan', 'Humas', 'Sarpras'];
    }
}

if (!function_exists('agenda_clean_unit')) {
    function agenda_clean_unit(string $unit, bool $isAdmin, string $guruUnit = ''): string
    {
        if (!$isAdmin) {
            return $guruUnit;
        }

        $unit = trim($unit);
        if (in_array($unit, agenda_allowed_units(), true)) {
            return $unit;
        }

        return 'Kurikulum';
    }
}

if (!function_exists('agenda_format_datetime_local')) {
    function agenda_format_datetime_local(string $date, string $time): string
    {
        $ts = strtotime($date . ' ' . $time);
        if ($ts === false) {
            return '';
        }

        return date('Y-m-d H:i:s', $ts);
    }
}

if (!function_exists('agenda_unit_key')) {
    function agenda_unit_key(?string $unit): string
    {
        $key = agenda_normalize_text((string)$unit);
        if (strpos($key, 'kurikulum') !== false) {
            return 'kurikulum';
        }
        if (strpos($key, 'kesiswaan') !== false) {
            return 'kesiswaan';
        }
        if (strpos($key, 'humas') !== false) {
            return 'humas';
        }
        if (strpos($key, 'sarpras') !== false || strpos($key, 'sarana') !== false || strpos($key, 'prasarana') !== false) {
            return 'sarpras';
        }

        return 'default';
    }
}

if (!function_exists('agenda_unit_palette')) {
    function agenda_unit_palette(?string $unit): array
    {
        $key = agenda_unit_key($unit);

        if ($key === 'kurikulum') {
            return ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'border' => '#bfdbfe'];
        }
        if ($key === 'kesiswaan') {
            return ['bg' => '#ecfdf5', 'text' => '#047857', 'border' => '#a7f3d0'];
        }
        if ($key === 'humas') {
            return ['bg' => '#fff7ed', 'text' => '#c2410c', 'border' => '#fdba74'];
        }
        if ($key === 'sarpras') {
            return ['bg' => '#f5f3ff', 'text' => '#6d28d9', 'border' => '#c4b5fd'];
        }

        return ['bg' => '#f8fafc', 'text' => '#334155', 'border' => '#cbd5e1'];
    }
}
