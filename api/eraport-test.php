<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_admin_ajax();
require_once __DIR__ . '/../eraport_helper.php';

$endpoint = trim((string)($_GET['endpoint'] ?? $_POST['endpoint'] ?? ''));
$method = strtoupper(trim((string)($_GET['method'] ?? $_POST['method'] ?? 'GET')));

if ($endpoint === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter endpoint wajib diisi. Contoh: login atau login/cekuser',
    ]);
    exit;
}

$payloadRaw = (string)($_POST['payload_json'] ?? $_GET['payload_json'] ?? '');
$payload = [];
if ($payloadRaw !== '') {
    $decoded = json_decode($payloadRaw, true);
    if (!is_array($decoded)) {
        echo json_encode([
            'success' => false,
            'message' => 'payload_json harus berupa JSON object yang valid.',
        ]);
        exit;
    }
    $payload = $decoded;
}

$tokenOverride = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$result = eraport_request($endpoint, $method, $payload, $tokenOverride !== '' ? $tokenOverride : null);

echo json_encode([
    'success' => $result['success'],
    'request' => [
        'endpoint' => $endpoint,
        'method' => $method,
        'payload' => $payload,
        'token_override_used' => $tokenOverride !== '',
    ],
    'result' => [
        'status_code' => $result['status_code'],
        'url' => $result['url'],
        'has_token' => $result['has_token'],
        'error' => $result['error'],
        'json' => $result['json'],
        'body_preview' => mb_substr((string)$result['body'], 0, 4000),
    ],
], JSON_UNESCAPED_UNICODE);
