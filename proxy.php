<?php
// proxy.php - Robust PHP Proxy for IPTV HLS Streams
// This solves CORS, Mixed Content (HTTP/HTTPS), and Header restrictions.

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: *");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$url = isset($_GET['url']) ? $_GET['url'] : '';

if (empty($url)) {
    header("HTTP/1.1 400 Bad Request");
    echo "Error: Missing URL parameter.";
    exit;
}

// Decode if needed
$url = urldecode($url);

// Security: Optional whitelist check could go here.

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36');

// Some IPTV providers require a Referer
curl_setopt($ch, CURLOPT_REFERER, $url);

$response = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "Proxy Error: " . $error;
    exit;
}

// Pass through the Content-Type
if (isset($info['content_type'])) {
    header("Content-Type: " . $info['content_type']);
}

echo $response;
?>
