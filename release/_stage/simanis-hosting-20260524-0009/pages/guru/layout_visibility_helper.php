<?php

/**
 * Helper preferensi visibilitas header/footer per user (session-based).
 */

if (!function_exists('guru_layout_available_pages')) {
    function guru_layout_available_pages()
    {
        return [
            'guru.php' => 'Dashboard',
            'guru_jurnal.php' => 'Jurnal Pembelajaran',
            'nilai.php' => 'Input Nilai',
            'presensi.php' => 'Rekap Presensi',
            'history-tugas.php' => 'Manajemen Tugas',
            'twibbon.php' => 'Twibbon',
            'validasi-izin.php' => 'Validasi Izin',
            'kalender.php' => 'Kalender',
            'pengaturan-layout.php' => 'Pengaturan Layout',
        ];
    }
}

if (!function_exists('guru_get_layout_preference')) {
    function guru_get_layout_preference()
    {
        $mode = $_SESSION['guru_layout_mode'] ?? 'all';
        if (!in_array($mode, ['all', 'selected'], true)) {
            $mode = 'all';
        }

        $selected = $_SESSION['guru_layout_selected_pages'] ?? [];
        if (!is_array($selected)) {
            $selected = [];
        }

        $allowedPages = array_keys(guru_layout_available_pages());
        $selected = array_values(array_intersect($selected, $allowedPages));

        return [
            'mode' => $mode,
            'selected' => $selected,
        ];
    }
}

if (!function_exists('guru_save_layout_preference_from_post')) {
    function guru_save_layout_preference_from_post()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['save_layout_visibility'])) {
            return false;
        }

        $mode = $_POST['layout_mode'] ?? 'all';
        if ($mode !== 'selected') {
            $mode = 'all';
        }

        $selected = $_POST['selected_pages'] ?? [];
        if (!is_array($selected)) {
            $selected = [];
        }

        $allowedPages = array_keys(guru_layout_available_pages());
        $selected = array_values(array_intersect($selected, $allowedPages));

        if ($mode === 'selected' && empty($selected)) {
            $selected = ['pengaturan-layout.php'];
        }

        $_SESSION['guru_layout_mode'] = $mode;
        $_SESSION['guru_layout_selected_pages'] = $selected;

        return true;
    }
}

if (!function_exists('guru_should_show_layout')) {
    function guru_should_show_layout($pageFile)
    {
        // Halaman pengaturan harus selalu bisa diakses agar user bisa mengubah pilihan.
        if ($pageFile === 'pengaturan-layout.php') {
            return true;
        }

        $pref = guru_get_layout_preference();
        if ($pref['mode'] === 'all') {
            return true;
        }

        return in_array($pageFile, $pref['selected'], true);
    }
}
