<?php
$url = 'http://localhost:8000/jurnal/pages/guru/nilai';
$options = ['http' => ['method' => 'GET', 'ignore_errors' => true, 'timeout' => 5]];
$ctx = stream_context_create($options);
echo "Requesting: $url\n";
$body = @file_get_contents($url, false, $ctx);
if ($body === false) {
    echo "Request failed.\n";
}
if (isset($http_response_header)) {
    echo "Response Headers:\n";
    foreach ($http_response_header as $h) echo $h . "\n";
}
if ($body !== false) {
    echo "\nBody sample:\n" . substr($body, 0, 500) . "\n";
}
