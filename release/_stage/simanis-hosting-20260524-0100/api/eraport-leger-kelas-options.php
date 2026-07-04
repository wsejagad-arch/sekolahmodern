<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_admin_ajax();
require_once __DIR__ . '/../eraport_helper.php';

$options = eraport_login_and_get_leger_class_options();
if (empty($options['success'])) {
    echo json_encode([
        'success' => false,
        'message' => (string)($options['message'] ?? 'Gagal memuat kelas e-Raport.'),
        'classes' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Daftar kelas e-Raport berhasil dimuat.',
    'classes' => array_values($options['classes'] ?? []),
], JSON_UNESCAPED_UNICODE);
