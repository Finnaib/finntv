<?php
/**
 * FinnTV Xtream API Proxy
 * Forwards Xtream Codes API requests and returns JSON response.
 * Separate from stream_proxy.php which handles video streams.
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: *");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

if (!isset($_GET['url'])) {
    http_response_code(400);
    die(json_encode(["error" => "No URL provided"]));
}

$raw_url = str_replace(' ', '+', $_GET['url']);
$url = base64_decode($raw_url);

if (!$url || filter_var($url, FILTER_VALIDATE_URL) === false) {
    http_response_code(400);
    die(json_encode(["error" => "Invalid URL: " . htmlspecialchars($url)]));
}

// Only allow player_api.php and panel_api.php URLs for security
if (stripos($url, 'player_api.php') === false && stripos($url, 'panel_api.php') === false) {
    http_response_code(403);
    die(json_encode(["error" => "Only Xtream API URLs are allowed"]));
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36",
]);

$response = curl_exec($ch);
$info     = curl_getinfo($ch);
$err      = curl_error($ch);
curl_close($ch);

if ($err || !$response) {
    http_response_code(502);
    die(json_encode(["error" => "Upstream connection failed: " . $err]));
}

if ($info['http_code'] !== 200) {
    http_response_code($info['http_code']);
    die(json_encode(["error" => "Upstream returned HTTP " . $info['http_code']]));
}

// Try to parse and return JSON
$decoded = json_decode($response, true);
if ($decoded === null) {
    http_response_code(502);
    die(json_encode(["error" => "Upstream did not return valid JSON", "raw" => substr($response, 0, 200)]));
}

header("Content-Type: application/json");
echo $response;
