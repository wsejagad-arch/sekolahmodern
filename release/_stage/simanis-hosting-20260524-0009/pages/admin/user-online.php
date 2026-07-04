<?php
if (!isset($_SESSION['username'])) {
    header('location: index.php?haruslogin');
    exit;
} else if (current_role() != 1) {
    echo '<script>window.location = "404.html";</script>';
    exit;
}

if (function_exists('online_status_ensure_table') && isset($conn)) {
    online_status_ensure_table($conn);
}

if (!function_exists('format_user_online_last_active')) {
    function format_user_online_last_active($datetime)
    {
        if (empty($datetime)) {
            return 'Belum terdeteksi aktivitas';
        }

        $lastTs = strtotime($datetime);
        if (!$lastTs) {
            return 'Waktu aktivitas tidak valid';
        }

        $diff = time() - $lastTs;
        if ($diff < 60) {
            return 'aktif beberapa detik lalu';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' menit lalu';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' jam lalu';
        }
        return floor($diff / 86400) . ' hari lalu';
    }
}

function user_online_state_from_activity($lastActivity)
{
    if (empty($lastActivity)) {
        return ['state' => 'offline', 'label' => 'Belum terdeteksi aktivitas', 'timeLabel' => 'Belum terdeteksi'];
    }

    $lastTs = strtotime($lastActivity);
    if (!$lastTs) {
        return ['state' => 'offline', 'label' => 'Waktu aktivitas tidak valid', 'timeLabel' => 'Tidak valid'];
    }

    $diff = time() - $lastTs;

    // Simplified status: online if activity < 5 minutes, offline otherwise
    if ($diff < 300) {
        // Online: show time difference
        if ($diff < 60) {
            $timeLabel = 'login beberapa detik lalu';
        } else {
            $minutes = floor($diff / 60);
            $timeLabel = 'login ' . $minutes . ' menit lalu';
        }
        return ['state' => 'online', 'label' => 'Online', 'timeLabel' => $timeLabel];
    }

    // Offline: show time difference
    $timeLabel = format_user_online_last_active($lastActivity);
    return ['state' => 'offline', 'label' => 'Offline', 'timeLabel' => $timeLabel];
}

$summary = [
    'total' => 0,
    'online' => 0,
    'offline' => 0,
];
$rows = [];

if (isset($conn)) {
    $sql = mysqli_query($conn, "SELECT user_key, user_type, user_ref, display_name, last_activity, is_online, ip_address, user_agent, latitude, longitude FROM tbl_user_online ORDER BY last_activity DESC, display_name ASC");
    if ($sql) {
        while ($row = mysqli_fetch_assoc($sql)) {
            $stateInfo = user_online_state_from_activity($row['last_activity'] ?? null);
            $state = $stateInfo['state'];
            $summary['total']++;
            if ($state === 'online') {
                $summary['online']++;
            } else {
                $summary['offline']++;
            }

            $rows[] = [
                'user_key' => $row['user_key'] ?? '',
                'user_type' => $row['user_type'] ?? '',
                'user_ref' => $row['user_ref'] ?? '',
                'display_name' => $row['display_name'] ?? '-',
                'last_activity' => $row['last_activity'] ?? null,
                'state' => $state,
                'label' => $stateInfo['label'],
                'timeLabel' => $stateInfo['timeLabel'] ?? '',
                'ip_address' => $row['ip_address'] ?? '-',
                'user_agent' => $row['user_agent'] ?? '-',
                'latitude' => $row['latitude'] ?? null,
                'longitude' => $row['longitude'] ?? null,
            ];
        }
    }
}
?>

<div class="container-fluid">
    <div class="mb-4">
        <div class="card border-0 shadow-sm" style="border-radius: 20px; background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 50%, #22c55e 100%); overflow: hidden;">
            <div class="card-body p-4 text-white">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h1 class="h4 mb-2 font-weight-bold"><i class="fas fa-signal me-2"></i>User Online</h1>
                        <p class="mb-0 opacity-75">Daftar sesi aktif yang terakhir terdeteksi di sistem.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge badge-light px-3 py-2" style="border-radius: 999px; font-size: .85rem;">
                            Total <span id="summaryTotal"><?= (int)$summary['total']; ?></span>
                        </span>
                        <span class="badge px-3 py-2" style="border-radius: 999px; font-size: .85rem; background: rgba(16,185,129,.18); color: #d1fae5; border: 1px solid rgba(16,185,129,.25);">
                            Online <span id="summaryOnline"><?= (int)$summary['online']; ?></span>
                        </span>
                        <span class="badge px-3 py-2" style="border-radius: 999px; font-size: .85rem; background: rgba(107,114,128,.18); color: #f3f4f6; border: 1px solid rgba(107,114,128,.25);">
                            Offline <span id="summaryOffline"><?= (int)$summary['offline']; ?></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px;">
        <div class="card-body p-4">
            <div class="row g-3 align-items-center mb-2">
                <div class="col-md-4">
                    <input type="text" id="searchOnlineUser" class="form-control" style="border-radius: 999px;" placeholder="Cari nama, NIP, atau username...">
                </div>
                <div class="col-md-3">
                    <select id="filterOnlineType" class="form-control" style="border-radius: 999px;">
                        <option value="">Semua Tipe</option>
                        <option value="admin">Admin</option>
                        <option value="guru">Guru</option>
                        <option value="siswa">Siswa</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterOnlineState" class="form-control" style="border-radius: 999px;">
                        <option value="">Semua Status</option>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                    </select>
                </div>
                <div class="col-md-2 text-md-end">
                    <a href="home.php?page=data-guru" class="btn btn-outline-primary btn-block" style="border-radius: 999px;">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Data Guru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" id="userOnlineTable">
                    <thead style="background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%); color: white;">
                        <tr>
                            <th class="border-0 py-3 px-4">No</th>
                            <th class="border-0 py-3 px-4">Nama</th>
                            <th class="border-0 py-3 px-4">Tipe</th>
                            <th class="border-0 py-3 px-4">Ref</th>
                            <th class="border-0 py-3 px-4">Status</th>
                            <th class="border-0 py-3 px-4">Aktivitas Terakhir</th>
                            <th class="border-0 py-3 px-4">IP</th>
                            <th class="border-0 py-3 px-4">Lokasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rows)): ?>
                            <?php $no = 1;
                            foreach ($rows as $row): ?>
                                <tr class="online-row" data-name="<?= htmlspecialchars(strtolower(($row['display_name'] ?? '') . ' ' . ($row['user_ref'] ?? '') . ' ' . ($row['user_key'] ?? '')), ENT_QUOTES); ?>" data-type="<?= htmlspecialchars($row['user_type'] ?? '', ENT_QUOTES); ?>" data-state="<?= htmlspecialchars($row['state'] ?? '', ENT_QUOTES); ?>">
                                    <td class="px-4 py-3">
                                        <span class="badge badge-light" style="border-radius: 999px; padding: 6px 12px; font-weight: 600;"><?= $no++; ?></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-weight-semibold"><?= htmlspecialchars($row['display_name'] ?? '-'); ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($row['user_key'] ?? '-'); ?></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge" style="border-radius: 999px; padding: 8px 12px; background: #eef2ff; color: #4338ca; text-transform: uppercase; letter-spacing: .5px;">
                                            <?= htmlspecialchars($row['user_type'] ?? '-'); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-muted small"><?= htmlspecialchars($row['user_ref'] ?? '-'); ?></td>
                                    <td class="px-4 py-3">
                                        <div class="status-label">
                                            <?php if (($row['state'] ?? '') === 'online'): ?>
                                                <span class="indicator-online"></span>
                                                <span>Online</span>
                                            <?php else: ?>
                                                <span class="indicator-offline"></span>
                                                <span>Offline</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="time-label"><?= htmlspecialchars($row['timeLabel'] ?? '-'); ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-muted small"><?= htmlspecialchars($row['last_activity'] ?? '-'); ?></td>
                                    <td class="px-4 py-3 text-muted small"><?= htmlspecialchars($row['ip_address'] ?? '-'); ?></td>
                                    <td class="px-4 py-3 text-muted small">
                                        <?php if (!empty($row['latitude']) && !empty($row['longitude'])): ?>
                                            <a href="https://www.google.com/maps?q=<?= htmlspecialchars($row['latitude']); ?>,<?= htmlspecialchars($row['longitude']); ?>" target="_blank" rel="noopener" style="color: #2563eb; text-decoration: none; cursor: pointer; font-weight: 500;">
                                                <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($row['latitude']); ?>, <?= htmlspecialchars($row['longitude']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-signal fa-2x mb-3"></i>
                                        <div class="font-weight-semibold">Belum ada data user online</div>
                                        <div class="small">Status akan muncul saat pengguna aktif di aplikasi.</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .online-row:hover {
        background: #f8fafc !important;
    }

    /* Blinking indicator animation */
    @keyframes blink {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.3;
        }
    }

    .indicator-online {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #10b981;
        margin-right: 6px;
        animation: blink 1s infinite;
        vertical-align: middle;
    }

    .indicator-offline {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #6b7280;
        margin-right: 6px;
        vertical-align: middle;
    }

    .status-label {
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .time-label {
        font-size: 13px;
        color: #6b7280;
        margin-top: 6px;
        font-weight: 500;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchOnlineUser');
        const typeFilter = document.getElementById('filterOnlineType');
        const stateFilter = document.getElementById('filterOnlineState');
        const tableBody = document.querySelector('#userOnlineTable tbody');

        function renderUserRow(row, idx) {
            const isOnline = row.state === 'online';
            const timeLabel = row.timeLabel || row.label || 'Tidak terdeteksi';

            const indicatorHTML = isOnline ?
                '<span class="indicator-online"></span>' :
                '<span class="indicator-offline"></span>';

            const statusText = isOnline ? 'Online' : 'Offline';

            const locationHTML = (row.latitude && row.longitude) ?
                `<a href="https://www.google.com/maps?q=${row.latitude},${row.longitude}" target="_blank" rel="noopener" style="color: #2563eb; text-decoration: none; cursor: pointer; font-weight: 500;">
                    <i class="fas fa-map-marker-alt me-1"></i>${row.latitude}, ${row.longitude}
                  </a>` :
                '<span class="text-muted">-</span>';

            return `<tr class="online-row" data-name="${row.display_name.toLowerCase()} ${row.user_ref} ${row.user_key}" data-type="${row.user_type}" data-state="${row.state}">
                <td class="px-4 py-3"><span class="badge badge-light" style="border-radius: 999px; padding: 6px 12px; font-weight: 600;">${idx}</span></td>
                <td class="px-4 py-3">
                    <div class="font-weight-semibold">${row.display_name}</div>
                    <div class="small text-muted">${row.user_key}</div>
                </td>
                <td class="px-4 py-3">
                    <span class="badge" style="border-radius: 999px; padding: 8px 12px; background: #eef2ff; color: #4338ca; text-transform: uppercase; letter-spacing: .5px;">
                        ${row.user_type}
                    </span>
                </td>
                <td class="px-4 py-3 text-muted small">${row.user_ref}</td>
                <td class="px-4 py-3">
                    <div class="status-label">
                        ${indicatorHTML}
                        <span>${statusText}</span>
                    </div>
                    <div class="time-label">${timeLabel}</div>
                </td>
                <td class="px-4 py-3 text-muted small">${row.last_activity || '-'}</td>
                <td class="px-4 py-3 text-muted small">${row.ip_address}</td>
                <td class="px-4 py-3 text-muted small">${locationHTML}</td>
            </tr>`;
        }

        function applyFilters() {
            const rows = document.querySelectorAll('.online-row');
            const searchTerm = (searchInput ? searchInput.value : '').toLowerCase().trim();
            const selectedType = typeFilter ? typeFilter.value : '';
            const selectedState = stateFilter ? stateFilter.value : '';

            let visibleCount = 0;
            let onlineCount = 0;
            let offlineCount = 0;

            rows.forEach(row => {
                const name = row.dataset.name || '';
                const type = row.dataset.type || '';
                const state = row.dataset.state || '';

                const matchSearch = !searchTerm || name.includes(searchTerm);
                const matchType = !selectedType || type === selectedType;
                const matchState = !selectedState || state === selectedState;

                const shouldShow = matchSearch && matchType && matchState;
                row.style.display = shouldShow ? '' : 'none';

                if (shouldShow) {
                    visibleCount++;
                    if (state === 'online') {
                        onlineCount++;
                    } else if (state === 'offline') {
                        offlineCount++;
                    }
                }
            });

            const totalEl = document.getElementById('summaryTotal');
            const onlineEl = document.getElementById('summaryOnline');
            const offlineEl = document.getElementById('summaryOffline');

            if (totalEl) totalEl.textContent = visibleCount;
            if (onlineEl) onlineEl.textContent = onlineCount;
            if (offlineEl) offlineEl.textContent = offlineCount;
        }

        function refreshRealtimeData() {
            fetch('api/user-online-data.php', {
                    method: 'GET',
                    cache: 'no-store'
                })
                .then(response => response.json())
                .then(payload => {
                    if (!payload || !payload.success || !payload.data) return;

                    tableBody.innerHTML = '';
                    if (payload.data.length === 0) {
                        tableBody.innerHTML = `<tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-signal fa-2x mb-3"></i>
                                    <div class="font-weight-semibold">Belum ada data user online</div>
                                    <div class="small">Status akan muncul saat pengguna aktif di aplikasi.</div>
                                </div>
                            </td>
                        </tr>`;
                    } else {
                        payload.data.forEach((row, idx) => {
                            tableBody.insertAdjacentHTML('beforeend', renderUserRow(row, idx + 1));
                        });
                    }

                    applyFilters();
                })
                .catch(err => {
                    console.log('Realtime fetch failed:', err);
                });
        }

        if (searchInput) searchInput.addEventListener('input', applyFilters);
        if (typeFilter) typeFilter.addEventListener('change', applyFilters);
        if (stateFilter) stateFilter.addEventListener('change', applyFilters);

        applyFilters();

        setInterval(refreshRealtimeData, 30000);
    });
</script>