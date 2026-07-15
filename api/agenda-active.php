<?php
header('Content-Type: application/json');

session_start();
session_write_close();
include '../koneksi.php';
require_once __DIR__ . '/../agenda_helper.php';

date_default_timezone_set('Asia/Jakarta');
agenda_ensure_table($conn);

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 4;
$items = agenda_get_active($conn, $limit);

$payload = [];
foreach ($items as $item) {
    $palette = agenda_unit_palette((string)($item['dibuat_unit'] ?? ''));
    $payload[] = [
        'id_agenda' => (int)($item['id_agenda'] ?? 0),
        'judul' => (string)($item['judul'] ?? ''),
        'deskripsi' => (string)($item['deskripsi'] ?? ''),
        'agenda_date' => (string)($item['agenda_date'] ?? ''),
        'agenda_date_label' => date('d M Y', strtotime((string)($item['agenda_date'] ?? 'now'))),
        'jam_mulai' => substr((string)($item['jam_mulai'] ?? ''), 0, 5),
        'jam_selesai' => substr((string)($item['jam_selesai'] ?? ''), 0, 5),
        'dibuat_unit' => (string)($item['dibuat_unit'] ?? ''),
        'target_at' => agenda_format_datetime_local((string)($item['agenda_date'] ?? ''), (string)($item['jam_selesai'] ?? '')),
        'unit_palette' => $palette,
    ];
}

echo json_encode([
    'success' => true,
    'count' => count($payload),
    'data' => $payload,
]);
