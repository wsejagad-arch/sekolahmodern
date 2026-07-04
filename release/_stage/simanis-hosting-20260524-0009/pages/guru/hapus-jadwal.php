<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
http_response_code(503);
echo json_encode([
    'success' => false,
    'message' => 'Fitur belum tersedia di salinan lokal.'
]);