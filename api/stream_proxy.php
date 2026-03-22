<?php
/**
 * FinnTV HLS CORS / Mixed-Content Proxy - UNIVERSAL STABLE VERSION
 * Routes M3U8 manifests and TS chunks through Vercel to completely bypass 
 * browser security sandbox limitations (CORS and HTTP-in-HTTPS blocks).
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: *");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if (!isset($_GET['url'])) {
    http_response_code(400);
    die("Error: No URL provided");
}

// FIX: PHP converts '+' to ' ' in GET parameters, breaking Base64.
$raw_url = str_replace(' ', '+', $_GET['url']);
$url = base64_decode($raw_url);

if (!$url || filter_var($url, FILTER_VALIDATE_URL) === false) {
    http_response_code(400);
    die("Error: Invalid URL decoded from Base64: " . htmlspecialchars($url));
}

// Function to resolve relative URLs
function resolve_url($base, $rel) {
    if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
    if ($rel[0] == '#' || $rel[0] == '?') return $base.$rel;
    $parse = parse_url($base);
    $hostname = $parse['scheme'] . '://' . (isset($parse['host']) ? $parse['host'] : '') . (isset($parse['port']) ? ':' . $parse['port'] : '');
    $path = isset($parse['path']) ? $parse['path'] : '/';
    if ($rel[0] == '/') return $hostname . $rel;
    $path = dirname($path);
    if ($path == '/' || $path == '\\') $path = '';
    $abs = $hostname . $path . '/' . $rel;
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}
    return $abs;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, "VLC/3.0.16 LibVLC/3.0.16"); // Spoof VLC
curl_setopt($ch, CURLOPT_TIMEOUT, 9); // Stay under Vercel's 10s limit
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

if ($http_code !== 200) {
    http_response_code(502);
    die("IPTV Server error (HTTP $http_code)");
}

header("Content-Type: $content_type");

$is_playlist = (strpos(strtolower($content_type), 'mpegurl') !== false || strpos(trim($response), '#EXTM3U') === 0);

if ($is_playlist) {
    $lines = explode("\n", $response);
    $output = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        if (strpos($line, '#') === 0) {
            // Rewrite keys
            if (strpos($line, '#EXT-X-KEY') !== false && preg_match('/URI="([^"]+)"/', $line, $matches)) {
                $absUri = resolve_url($final_url, $matches[1]);
                $proxyUri = '/api/stream_proxy.php?url=' . urlencode(base64_encode($absUri));
                $line = str_replace($matches[1], $proxyUri, $line);
            }
            $output[] = $line;
        } else {
            $absUrl = resolve_url($final_url, $line);
            $output[] = '/api/stream_proxy.php?url=' . urlencode(base64_encode($absUrl));
        }
    }
    echo implode("\n", $output);
} else {
    header("Cache-Control: public, max-age=3600");
    echo $response;
}
?>
