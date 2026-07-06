<?php
if (!function_exists('guru_common_footer_url')) {
    function guru_common_footer_url(string $page, array $params = []): string
    {
        // Dynamically find base path by removing known entry points
        $script = $_SERVER['SCRIPT_NAME'];
        $base = '';
        if (($pos = strpos($script, '/home.php')) !== false) {
            $base = substr($script, 0, $pos);
        } elseif (($pos = strpos($script, '/pages/')) !== false) {
            $base = substr($script, 0, $pos);
        } else {
            $base = rtrim(dirname($script), '/\\');
            if ($base === '/' || $base === '\\') $base = '';
        }

        $safe = preg_replace('/[^a-z0-9_\-\.\/]/i', '', $page);
        
        // If it's the home page
        if ($safe === '../../home.php' || $safe === 'home') {
            return $base . '/home.php?page=dashboard';
        }

        // For all other guru pages, use the direct path to pages/guru/
        if (!preg_match('/\.php$/', $safe)) {
            $safe .= '.php';
        }
        
        $url = $base . '/pages/guru/' . $safe;

        if (!empty($params)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }
        return $url;
    }
}

$currentGuruFooterPage = strtolower((string)($_GET['page'] ?? basename($_SERVER['PHP_SELF'] ?? '')));
$currentGuruFooterPage = preg_replace('/\.php$/', '', $currentGuruFooterPage);
$footerItems = [
    'home' => ['page' => '../../home.php', 'label' => 'Beranda', 'icon' => 'bi-house-door-fill', 'aliases' => ['guru', '../../home.php', 'guru_mobile_app', 'guru_mobile_app_with_external_css', '../../home.php']],
    'kelas' => ['page' => 'data-siswa', 'label' => 'Kelas', 'icon' => 'bi-grid-fill', 'aliases' => ['data-siswa', 'walikelas', 'guru-wali-siswa', 'laporan-kelas', 'rekap-kehadiran', 'leger']],
    'tugas' => ['page' => 'history-tugas', 'label' => 'Tugas', 'icon' => 'bi-clipboard-check-fill', 'aliases' => ['history-tugas', 'history-tugas-simple', 'history-tugas-content', 'inputtugas', 'task-detail']],
    'profil' => ['page' => 'profil-guru', 'label' => 'Profil', 'icon' => 'bi-person-fill', 'aliases' => ['profil-guru']],
];

$isActiveFooterItem = static function (array $item) use ($currentGuruFooterPage): bool {
    return in_array($currentGuruFooterPage, $item['aliases'], true) || $currentGuruFooterPage === $item['page'];
};
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    body.guru-common-footer-active {
        padding-bottom: max(112px, env(safe-area-inset-bottom, 0px) + 96px) !important;
    }
    body.guru-common-footer-active .bottom-nav-wrap:not(.guru-common-footer-wrap),
    body.guru-common-footer-active nav.bottom-nav:not(.guru-common-footer-nav),
    body.guru-common-footer-active .footer-nav,
    body.guru-common-footer-active .app-bottom-nav {
        display: none !important;
    }
    .guru-common-footer-wrap {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 2140;
        padding: 12px 16px calc(18px + env(safe-area-inset-bottom, 0px));
        pointer-events: none;
        font-family: "Poppins", sans-serif !important;
    }
    .guru-common-footer-nav {
        max-width: 440px;
        min-height: 72px;
        margin: 0 auto;
        padding: 10px 12px;
        display: grid;
        grid-template-columns: 1fr 1fr 78px 1fr 1fr;
        align-items: center;
        gap: 4px;
        background: #f8fafc;
        border: 2px solid #ffffff;
        border-radius: 35px;
        box-shadow: 
            6px 6px 16px rgba(148, 163, 184, 0.3), 
            -6px -6px 16px rgba(255, 255, 255, 0.8),
            inset 2px 2px 4px rgba(255, 255, 255, 0.8);
        backdrop-filter: none;
        pointer-events: auto;
        font-family: "Poppins", sans-serif !important;
    }
    .guru-common-footer-nav *,
    .guru-common-footer-wrap *,
    body.guru-common-footer-active .bottom-nav,
    body.guru-common-footer-active .bottom-nav *,
    body.guru-common-footer-active .footer-nav,
    body.guru-common-footer-active .footer-nav *,
    body.guru-common-footer-active .app-bottom-nav,
    body.guru-common-footer-active .app-bottom-nav * {
        font-family: "Poppins", sans-serif !important;
    }
    .guru-common-footer-item {
        min-width: 0;
        color: #94a3b8;
        text-decoration: none;
        font-size: 10px;
        font-weight: 700;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        line-height: 1.1;
    }
    .guru-common-footer-item i {
        font-size: 20px;
        line-height: 1;
    }
    .guru-common-footer-item.is-active {
        color: #059669;
    }
    .guru-common-footer-center {
        width: 68px;
        height: 68px;
        margin: -42px auto 0;
        border-radius: 50%;
        display: grid;
        place-items: center;
        color: #fff;
        text-decoration: none;
        font-size: 38px;
        background: #10b981;
        box-shadow: 
            4px 4px 10px rgba(16, 185, 129, 0.4),
            -4px -4px 10px rgba(255, 255, 255, 0.9),
            inset 2px 2px 5px rgba(255, 255, 255, 0.3),
            inset -2px -2px 5px rgba(4, 120, 87, 0.3);
        border: 4px solid #f8fafc;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .guru-common-footer-center:active {
        transform: scale(0.95);
        box-shadow: 
            inset 4px 4px 10px rgba(4, 120, 87, 0.4),
            inset -4px -4px 10px rgba(255, 255, 255, 0.3);
    }
    .guru-common-footer-center:hover,
    .guru-common-footer-item:hover {
        color: #047857;
    }
    @media (min-width: 992px) {
        .guru-common-footer-wrap {
            display: none;
        }
        body.guru-common-footer-active {
            padding-bottom: 0 !important;
        }
    }
    @media print {
        .guru-common-footer-wrap {
            display: none !important;
        }
        body.guru-common-footer-active {
            padding-bottom: 0 !important;
        }
    }
</style>

<div class="bottom-nav-wrap guru-common-footer-wrap">
    <nav class="bottom-nav guru-common-footer-nav" aria-label="Navigasi guru">
        <a href="<?= htmlspecialchars(guru_common_footer_url($footerItems['home']['page']), ENT_QUOTES, 'UTF-8'); ?>" class="guru-common-footer-item <?= $isActiveFooterItem($footerItems['home']) ? 'is-active' : ''; ?>">
            <i class="bi <?= htmlspecialchars($footerItems['home']['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
            <span><?= htmlspecialchars($footerItems['home']['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <a href="<?= htmlspecialchars(guru_common_footer_url($footerItems['kelas']['page']), ENT_QUOTES, 'UTF-8'); ?>" class="guru-common-footer-item <?= $isActiveFooterItem($footerItems['kelas']) ? 'is-active' : ''; ?>">
            <i class="bi <?= htmlspecialchars($footerItems['kelas']['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
            <span><?= htmlspecialchars($footerItems['kelas']['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <a href="<?= htmlspecialchars(guru_common_footer_url('../../home.php', ['open_jurnal' => '1']), ENT_QUOTES, 'UTF-8'); ?>" class="guru-common-footer-center" aria-label="Input jurnal">
            <i class="bi bi-fingerprint"></i>
        </a>
        <a href="<?= htmlspecialchars(guru_common_footer_url($footerItems['tugas']['page']), ENT_QUOTES, 'UTF-8'); ?>" class="guru-common-footer-item <?= $isActiveFooterItem($footerItems['tugas']) ? 'is-active' : ''; ?>">
            <i class="bi <?= htmlspecialchars($footerItems['tugas']['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
            <span><?= htmlspecialchars($footerItems['tugas']['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <a href="<?= htmlspecialchars(guru_common_footer_url($footerItems['profil']['page']), ENT_QUOTES, 'UTF-8'); ?>" class="guru-common-footer-item <?= $isActiveFooterItem($footerItems['profil']) ? 'is-active' : ''; ?>">
            <i class="bi <?= htmlspecialchars($footerItems['profil']['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
            <span><?= htmlspecialchars($footerItems['profil']['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
    </nav>
</div>

<script>
    document.body.classList.add('guru-common-footer-active');
</script>
