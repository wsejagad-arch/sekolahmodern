<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_admin_ajax();

if (function_exists('online_status_ensure_table') && isset($conn)) {
    online_status_ensure_table($conn);
}

function format_user_online_ts($datetime)
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
        return 'login beberapa detik lalu';
    }
    if ($diff < 3600) {
        return 'login ' . floor($diff / 60) . ' menit lalu';
    }
    if ($diff < 86400) {
        return 'login ' . floor($diff / 3600) . ' jam lalu';
    }
    return 'login ' . floor($diff / 86400) . ' hari lalu';
}

function get_user_state($lastActivity)
{
    if (empty($lastActivity)) {
        return ['state' => 'offline', 'label' => 'Offline', 'timeLabel' => 'Belum terdeteksi'];
    }

    $lastTs = strtotime($lastActivity);
    if (!$lastTs) {
        return ['state' => 'offline', 'label' => 'Offline', 'timeLabel' => 'Tidak valid'];
    }

    $diff = time() - $lastTs;

    // Simplified: online if activity < 5 minutes, offline otherwise
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
    $timeLabel = format_user_online_ts($lastActivity);
    return ['state' => 'offline', 'label' => 'Offline', 'timeLabel' => $timeLabel];
}

$summary = [
    'total' => 0,
    'online' => 0,
    'offline' => 0,
];
$rows = [];

if (isset($conn)) {
    $tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
    $tenantOnline = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_user_online', 'id_sekolah') ? "WHERE id_sekolah={$tenantId}" : "";
    $sql = mysqli_query($conn, "SELECT user_key, user_type, user_ref, display_name, last_activity, is_online, ip_address, latitude, longitude FROM tbl_user_online {$tenantOnline} ORDER BY last_activity DESC, display_name ASC");
    if ($sql) {
        while ($row = mysqli_fetch_assoc($sql)) {
            $stateInfo = get_user_state($row['last_activity'] ?? null);
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
                'latitude' => $row['latitude'] ?? null,
                'longitude' => $row['longitude'] ?? null,
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'summary' => $summary,
    'data' => $rows,
    'timestamp' => date('Y-m-d H:i:s')
]);
