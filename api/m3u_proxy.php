<?php
/**
 * M3U Playlist Proxy
 * Fetches and proxies external M3U playlists to bypass CORS and Mixed Content issues.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$url = isset($_GET['url']) ? $_GET['url'] : '';

if (empty($url)) {
    http_response_code(400);
    die("Error: No URL provided.");
}

// Decode if base64 (to stay consistent with stream_proxy)
if (preg_match('/^[a-zA-Z0-9\/\+=]+$/', $url) && (strlen($url) % 4 == 0)) {
    $decoded = base64_decode($url);
    if ($decoded !== false && (strpos($decoded, 'http') === 0)) {
        $url = $decoded;
    }
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

$content = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($content === false || $http_code >= 400) {
    http_response_code(502);
    die("Error reaching the playlist source (HTTP $http_code).");
}

curl_close($ch);
echo $content;
