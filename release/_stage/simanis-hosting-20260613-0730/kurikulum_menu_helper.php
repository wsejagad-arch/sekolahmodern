<?php

if (!function_exists('kurikulum_menu_ensure_table')) {
    function kurikulum_menu_ensure_table(mysqli $conn): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS tbl_kurikulum_menu (
            id_menu INT AUTO_INCREMENT PRIMARY KEY,
            menu_key VARCHAR(120) NOT NULL,
            menu_title VARCHAR(150) NOT NULL,
            menu_url VARCHAR(255) NOT NULL,
            menu_icon VARCHAR(60) NOT NULL DEFAULT 'bi-link-45deg',
            icon_type VARCHAR(20) NOT NULL DEFAULT 'bootstrap',
            icon_image_path VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            open_in_new_tab TINYINT(1) NOT NULL DEFAULT 1,
            created_by VARCHAR(50) NOT NULL,
            updated_by VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_menu_key (menu_key),
            INDEX idx_sort_active (is_active, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (mysqli_query($conn, $sql) !== true) {
            return false;
        }

        return kurikulum_menu_ensure_columns($conn);
    }
}

if (!function_exists('kurikulum_menu_ensure_columns')) {
    function kurikulum_menu_ensure_columns(mysqli $conn): bool
    {
        $needed = [
            'icon_type' => "ALTER TABLE tbl_kurikulum_menu ADD COLUMN icon_type VARCHAR(20) NOT NULL DEFAULT 'bootstrap' AFTER menu_icon",
            'icon_image_path' => "ALTER TABLE tbl_kurikulum_menu ADD COLUMN icon_image_path VARCHAR(255) NULL AFTER icon_type",
            'open_in_new_tab' => "ALTER TABLE tbl_kurikulum_menu ADD COLUMN open_in_new_tab TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active",
        ];

        foreach ($needed as $col => $alterSql) {
            $check = mysqli_query($conn, "SHOW COLUMNS FROM tbl_kurikulum_menu LIKE '" . mysqli_real_escape_string($conn, $col) . "'");
            if ($check && mysqli_num_rows($check) > 0) {
                continue;
            }

            if (mysqli_query($conn, $alterSql) !== true) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('kurikulum_menu_default_items')) {
    function kurikulum_menu_default_items(): array
    {
        return [
            ['key' => 'data-guru-wali', 'title' => 'Data Guru Wali', 'url' => '#', 'icon' => 'bi-people-fill', 'sort' => 10],
            ['key' => 'perangkat-kbm', 'title' => 'Perangkat KBM', 'url' => '#', 'icon' => 'bi-journal-bookmark-fill', 'sort' => 20],
            ['key' => 'daftar-nilai-leger', 'title' => 'Daftar Nilai dan Leger', 'url' => '#', 'icon' => 'bi-clipboard2-data-fill', 'sort' => 30],
            ['key' => 'supervisi-akademik', 'title' => 'Supervisi Akademik', 'url' => '#', 'icon' => 'bi-eye-fill', 'sort' => 40],
            ['key' => 'sertifikat-pelatihan', 'title' => 'Sertifikat Pelatihan Sekolah', 'url' => '#', 'icon' => 'bi-patch-check-fill', 'sort' => 50],
            ['key' => 'kegiatan-sekolah', 'title' => 'Kegiatan Sekolah dan Dokumentasi', 'url' => '#', 'icon' => 'bi-camera-fill', 'sort' => 60],
        ];
    }
}

if (!function_exists('kurikulum_menu_seed_defaults')) {
    function kurikulum_menu_seed_defaults(mysqli $conn, string $actor = 'system'): void
    {
        $check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tbl_kurikulum_menu");
        if (!$check) {
            return;
        }

        $row = mysqli_fetch_assoc($check);
        if ((int)($row['total'] ?? 0) > 0) {
            return;
        }

        foreach (kurikulum_menu_default_items() as $item) {
            $menuKey = mysqli_real_escape_string($conn, $item['key']);
            $menuTitle = mysqli_real_escape_string($conn, $item['title']);
            $menuUrl = mysqli_real_escape_string($conn, $item['url']);
            $menuIcon = mysqli_real_escape_string($conn, $item['icon']);
            $actorEsc = mysqli_real_escape_string($conn, $actor);
            $sort = (int)$item['sort'];

            mysqli_query(
                $conn,
                "INSERT INTO tbl_kurikulum_menu (menu_key, menu_title, menu_url, menu_icon, icon_type, icon_image_path, sort_order, is_active, open_in_new_tab, created_by, updated_by)
                 VALUES ('{$menuKey}', '{$menuTitle}', '{$menuUrl}', '{$menuIcon}', 'bootstrap', NULL, {$sort}, 1, 1, '{$actorEsc}', '{$actorEsc}')"
            );
        }
    }
}

if (!function_exists('kurikulum_menu_normalize_url')) {
    function kurikulum_menu_normalize_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '#';
        }
        if ($url === '#') {
            return '#';
        }

        return $url;
    }
}

if (!function_exists('kurikulum_menu_is_valid_url')) {
    function kurikulum_menu_is_valid_url(string $url): bool
    {
        $url = kurikulum_menu_normalize_url($url);
        if ($url === '#') {
            return true;
        }

        if (strpos($url, '/') === 0) {
            return true;
        }

        if (preg_match('/^[a-zA-Z0-9._\/-]+\.php(\?.*)?$/', $url)) {
            return true;
        }

        $valid = filter_var($url, FILTER_VALIDATE_URL);
        if ($valid === false) {
            return false;
        }

        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }
}

if (!function_exists('kurikulum_menu_slugify')) {
    function kurikulum_menu_slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim((string)$text, '-');
        return $text !== '' ? $text : 'menu';
    }
}

if (!function_exists('kurikulum_menu_can_manage')) {
    function kurikulum_menu_can_manage(mysqli $conn, int $hakAkses, string $noInduk = ''): bool
    {
        if ($hakAkses === 1) {
            return true;
        }

        if ($hakAkses !== 2 || $noInduk === '') {
            return false;
        }

        $roleColumns = ['jabatan', 'status_kepegawaian'];
        $availableColumns = [];
        foreach ($roleColumns as $col) {
            $colEsc = mysqli_real_escape_string($conn, $col);
            $check = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru LIKE '{$colEsc}'");
            if ($check && mysqli_num_rows($check) > 0) {
                $availableColumns[] = $col;
            }
        }

        if (empty($availableColumns)) {
            return false;
        }

        $selectCols = [];
        foreach ($availableColumns as $col) {
            $selectCols[] = "`{$col}`";
        }

        $noIndukEsc = mysqli_real_escape_string($conn, $noInduk);
        $q = mysqli_query($conn, "SELECT " . implode(', ', $selectCols) . " FROM tbl_guru WHERE no_induk='{$noIndukEsc}' LIMIT 1");
        if (!$q || mysqli_num_rows($q) === 0) {
            return false;
        }

        $row = mysqli_fetch_assoc($q);
        $roleText = '';
        foreach ($availableColumns as $col) {
            $roleText .= ' ' . strtolower((string)($row[$col] ?? ''));
        }

        return strpos($roleText, 'kurikulum') !== false
            || strpos($roleText, 'kesiswaan') !== false
            || strpos($roleText, 'humas') !== false
            || strpos($roleText, 'sarpras') !== false
            || strpos($roleText, 'sarana') !== false
            || strpos($roleText, 'prasarana') !== false;
    }
}

if (!function_exists('kurikulum_menu_get_items')) {
    function kurikulum_menu_get_items(mysqli $conn, bool $onlyActive = true): array
    {
        $where = $onlyActive ? 'WHERE is_active = 1' : '';
        $sql = "SELECT id_menu, menu_key, menu_title, menu_url, menu_icon, icon_type, icon_image_path,
                       sort_order, is_active, open_in_new_tab, updated_at
                FROM tbl_kurikulum_menu
                {$where}
                ORDER BY sort_order ASC, id_menu ASC";

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
