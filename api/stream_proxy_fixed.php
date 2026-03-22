<?php
/**
 * FinnTV HLS CORS / Mixed-Content Proxy 
 * Routes M3U8 manifests and TS chunks through Vercel to completely bypass 
 * browser security sandbox limitations (CORS and HTTP-in-HTTPS blocks).
 */

// Allow all origins to bypass CORS locally
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if (!isset($_GET['url'])) {
    http_response_code(400);
    die("Error: No URL provided");
}

// Repair base64 + characters that may have been parsed as spaces by PHP's GET logic
$raw_url = str_replace(' ', '+', $_GET['url']);
$url = base64_decode($raw_url);

if (!$url || filter_var($url, FILTER_VALIDATE_URL) === false) {
    http_response_code(400);
    die("Error: Invalid URL");
}

// Function to resolve relative URLs against a base URL
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
    
    // Replace '//' or '/./' or '/foo/../'
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}
    return $abs;
}

// Setup cURL to fetch the target stream/manifest
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, "VLC/3.0.16 LibVLC/3.0.16"); // Spoof VLC
curl_setopt($ch, CURLOPT_TIMEOUT, 8); // Vercel timeout protection

// Execute request
$response = curl_exec($ch);
$err = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); // in case of redirects
curl_close($ch);

if ($http_code !== 200) {
    http_response_code(502);
    die("Target server returned HTTP $http_code. $err");
}

// Pass Content-Type downstream
if ($content_type) {
    header("Content-Type: $content_type");
}

// If it's a playlist, rewrite the chunk URLs!
if (strpos(strtolower($content_type), 'mpegurl') !== false || strpos(trim($response), '#EXTM3U') === 0) {
    $lines = explode("\n", $response);
    $output = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        if (strpos($line, '#') === 0) {
            // Handle EXT-X-KEY if it contains a URI
            if (strpos($line, '#EXT-X-KEY:METHOD') === 0 && preg_match('/URI="([^"]+)"/', $line, $matches)) {
                $keyUrl = resolve_url($final_url, $matches[1]);
                $proxyKeyUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/api/stream_proxy.php?url=' . urlencode(base64_encode($keyUrl));
                $line = str_replace($matches[1], $proxyKeyUrl, $line);
            }
            $output[] = $line;
        } else {
            // This is a chunk or sub-playlist URL
            $fullUrl = resolve_url($final_url, $line);
            // Generate proxy URL safely urlencoded
            $proxyUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/api/stream_proxy.php?url=' . urlencode(base64_encode($fullUrl));
            $output[] = $proxyUrl;
        }
    }
    echo implode("\n", $output);
} else {
    // It's a raw video chunk (.ts) or encryption key, just output it directly!
    // Set headers for caching video chunks
    header("Cache-Control: public, max-age=86400");
    echo $response;
}
?>

