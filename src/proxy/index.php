<?php

// Let's avoid CORS with this one :D

$allowedBaseUrls = [
    'https://s3.amazonaws.com/setup.gametest2.robloxlabs.com'
];

$path = filter_input(INPUT_GET, 'path', FILTER_SANITIZE_URL);
if (!$path) {
    http_response_code(400);
    echo 'Invalid path parameter';
    exit;
}

$targetUrl = $allowedBaseUrls[0] . '/' . ltrim($path, '/');
if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo 'Invalid target URL';
    exit;
}

$queryString = http_build_query(array_diff_key($_GET, ['path' => '']));
$finalUrl = $queryString ? "{$targetUrl}?{$queryString}" : $targetUrl;

$ch = curl_init($finalUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 100);

$response = curl_exec($ch);

if ($response === false) {
    http_response_code(504);
    echo 'Error: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
curl_close($ch);

foreach (explode("\r\n", $header) as $headerLine) {
    if (!preg_match('/^(Transfer-Encoding|Content-Encoding):/i', $headerLine)) {
        header($headerLine);
    }
}

header('Access-Control-Allow-Origin: *');
header('Content-Security-Policy: default-src \'self\'; script-src \'none\'; connect-src \'self\'');
header('X-Content-Type-Options: nosniff');

echo $body;
?>