<?php
require_once __DIR__ . '/google_auth.php';

$schoolCode = strtoupper(trim((string)($_GET['kode'] ?? $_POST['kode'] ?? 'DEFAULT')));
if ($schoolCode === '') {
    $schoolCode = 'DEFAULT';
}

google_oauth_start($schoolCode);
