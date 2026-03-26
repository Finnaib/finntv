<?php
/**
 * FinnTV High-Performance Streaming Proxy
 * Optimized for Vercel: Uses streaming pass-through to avoid memory limits and timeouts.
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: *");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

if (!isset($_GET['url'])) {
    http_response_code(400);
    die("Error: No URL");
}

$raw_url = str_replace(' ', '+', $_GET['url']);
$url = base64_decode($raw_url);

if (!$url || filter_var($url, FILTER_VALIDATE_URL) === false) {
    http_response_code(400);
    die("Error: Invalid URL");
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
    if ($path === '/' || $path === '\\') $path = '';
    $abs = $hostname . $path . '/' . $rel;
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}
    return $abs;
}

$is_ts = (strpos($url, '.ts') !== false);

// If it's a TS chunk, we stream it directly to avoid memory/timeout issues
if ($is_ts) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Stream directly
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, "VLC/3.0.16 LibVLC/3.0.16");
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Higher timeout for chunks
    header("Content-Type: video/mp2t");
    header("Cache-Control: public, max-age=3600");
    curl_exec($ch);
    curl_close($ch);
    exit;
}

// If it's a playlist, we need to buffer and rewrite
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, "VLC/3.0.16 LibVLC/3.0.16");
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

if (!$response) {
    http_response_code(504);
    die("IPTV Server Timeout");
}

$is_playlist = (strpos(strtolower($content_type), 'mpegurl') !== false || strpos(trim($response), '#EXTM3U') === 0);
$is_vita = (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Vita') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'PlayStation') !== false));

if ($is_playlist) {
    header("Content-Type: application/vnd.apple.mpegurl");
} else if (strpos($url, '.ts') !== false) {
    header("Content-Type: video/mp2t");
} else {
    header("Content-Type: $content_type");
}
header("X-Content-Type-Options: nosniff");

if ($is_playlist) {
    $lines = explode("\n", $response);
    $output = [];
    
    $skip_next = false;
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        if ($is_vita && strpos($line, '#EXT-X-STREAM-INF') === 0) {
            // PS Vita Optimization: Skip 1080p, 4K, or high bitrates if detectable in the line
            if (preg_match('/RESOLUTION=(1920|3840)/i', $line) || strpos($line, '1080') !== false || strpos($line, '4K') !== false) {
                $skip_next = true;
                continue;
            }
        }
        
        if ($skip_next && strpos($line, '#') !== 0) {
            $skip_next = false;
            continue;
        }
        $skip_next = false;

        if (strpos($line, '#') === 0) {
            if (strpos($line, 'URI=') !== false && preg_match('/URI="([^"]+)"/', $line, $matches)) {
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
    echo $response;
}
?>
