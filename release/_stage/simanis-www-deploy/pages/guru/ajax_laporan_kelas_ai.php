<?php
// pages/guru/ajax_laporan_kelas_ai.php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_httponly', 1);
    @session_start();
}

require_once dirname(__DIR__, 2) . '/koneksi.php';

header('Content-Type: application/json');

// Cek login guru
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Silakan login sebagai guru.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$prompt = trim($_POST['prompt'] ?? '');
if ($prompt === '') {
    echo json_encode(['success' => false, 'message' => 'Prompt tidak boleh kosong.']);
    exit;
}

// Ambil API Key Gemini
$geminiApiKey = '';
$qSetting = mysqli_query($conn, "SELECT gemini_api_key FROM tbl_setting WHERE id=1 LIMIT 1");
if ($qSetting && $rowSetting = mysqli_fetch_assoc($qSetting)) {
    $geminiApiKey = trim((string)$rowSetting['gemini_api_key'] ?? '');
}
if ($geminiApiKey === '') {
    $geminiApiKey = 'AIzaSyC9zh6FHEnbqrW1MSlO4fVnSdu2L8SjSE8';
}

if ($geminiApiKey === '') {
    echo json_encode(['success' => false, 'message' => 'API Key Gemini belum diatur.']);
    exit;
}

// Gunakan model gemini-3-flash-preview atau gemini-2.5-flash
$model = 'gemini-3-flash-preview';

$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $geminiApiKey;

$payload = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ]
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    $errObj = json_decode($response, true);
    $errMsg = $errObj['error']['message'] ?? 'Unknown API error';
    echo json_encode(['success' => false, 'message' => "Gemini API error (Status $http_code): $errMsg"]);
    exit;
}

$resObj = json_decode($response, true);
$text = $resObj['candidates'][0]['content']['parts'][0]['text'] ?? '';

if ($text === '') {
    echo json_encode(['success' => false, 'message' => 'Tidak ada respon teks dari AI.']);
    exit;
}

echo json_encode(['success' => true, 'text' => $text]);
?>
