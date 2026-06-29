<?php
if (!isset($guruLayoutVisible)) {
    require_once __DIR__ . '/layout_visibility_helper.php';
    $currentGuruPage = basename($_SERVER['PHP_SELF'] ?? '');
    $guruLayoutVisible = guru_should_show_layout($currentGuruPage);
}

if (!function_exists('guru_nav_url')) {
    function guru_nav_url(string $page): string
    {
        $safe = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $page));
        return php_sapi_name() === 'cli-server' ? $safe . '.php' : $safe;
    }
}
?>

        </main> <!-- end main-content -->
    </div> <!-- end app-container -->

<?php if ($guruLayoutVisible): ?>
    <!-- Mobile Bottom Nav (Standard Style) -->
    <nav class="bottom-nav d-lg-none">
        <a href="<?= guru_nav_url('guru_2026'); ?>" class="nav-item">
            <i class="bi bi-house-door"></i>
            <span>Beranda</span>
        </a>
        <a href="<?= guru_nav_url('validasi-izin'); ?>" class="nav-item">
            <i class="bi bi-patch-check"></i>
            <span>Validasi</span>
        </a>
        <a href="<?= guru_nav_url('nilai'); ?>" class="nav-item">
            <i class="bi bi-pencil-square"></i>
            <span>Nilai</span>
        </a>
        <a href="<?= guru_nav_url('cetak-jurnal-guru'); ?>" class="nav-item">
            <i class="bi bi-printer"></i>
            <span>Cetak</span>
        </a>
        <a href="<?= guru_nav_url('apresiasi-guru'); ?>" class="nav-item">
            <i class="bi bi-award"></i>
            <span>Apresiasi</span>
        </a>
    </nav>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleNotifDropdown() {
        const dropdown = document.getElementById('notifDropdown');
        if (dropdown) {
            const isHidden = dropdown.style.display === 'none';
            dropdown.style.display = isHidden ? 'block' : 'none';
        }
    }

    // Close notification dropdown when clicking outside
    document.addEventListener('click', (e) => {
        const notifWrapper = document.querySelector('.hdr-btn-notif');
        const dropdown = document.getElementById('notifDropdown');
        if (notifWrapper && !notifWrapper.contains(e.target) && dropdown && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Set active nav item for mobile bottom nav
    function setActiveNavItem() {
        const currentPage = window.location.pathname.split('/').pop() || 'guru_2026';
        document.querySelectorAll('.bottom-nav .nav-item').forEach(item => {
            item.classList.remove('active');
            const href = item.getAttribute('href');
            if (href && href.includes(currentPage)) {
                item.classList.add('active');
            }
        });
    }
    setActiveNavItem();
</script>

</body>
</html>
