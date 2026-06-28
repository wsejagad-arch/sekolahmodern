<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_admin_ajax();

if (function_exists('online_status_ensure_table')) {
    online_status_ensure_table($conn);
}

function format_last_active_api($datetime)
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

$data = [];
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantGuru = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_guru', 'id_sekolah') ? "WHERE id_sekolah={$tenantId}" : "";
$tenantOnline = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_user_online', 'id_sekolah') ? "AND id_sekolah={$tenantId}" : "";
$sql = mysqli_query($conn, "SELECT no_induk FROM tbl_guru {$tenantGuru}");
if ($sql) {
    while ($g = mysqli_fetch_assoc($sql)) {
        $nip = (string)$g['no_induk'];
        $data[$nip] = [
            'status' => 'Offline',
            'label' => 'Belum terdeteksi aktivitas'
        ];
    }
}

$q = mysqli_query($conn, "SELECT user_ref, last_activity FROM tbl_user_online WHERE user_type='guru' {$tenantOnline}");
if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        $nip = (string)$row['user_ref'];
        $lastActivity = $row['last_activity'] ?? null;
        $lastTs = !empty($lastActivity) ? strtotime($lastActivity) : false;
        $diff = $lastTs ? (time() - $lastTs) : PHP_INT_MAX;
        $isOnline = $lastTs && ($diff < 300);
        $state = 'offline';
        if ($isOnline && $diff < 60) {
            $state = 'fresh';
        } else if ($isOnline) {
            $state = 'online';
        }
        $data[$nip] = [
            'status' => $isOnline ? 'Online' : 'Offline',
            'state' => $state,
            'label' => $state === 'fresh'
                ? 'baru aktif kurang dari 1 menit'
                : ($isOnline ? 'aktif sekarang' : format_last_active_api($lastActivity))
        ];
    }
}

echo json_encode([
    'success' => true,
    'data' => $data,
    'timestamp' => date('Y-m-d H:i:s')
]);
