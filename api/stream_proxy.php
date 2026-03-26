<?php
/**
 * FinnTV High-Performance Streaming Proxy
 * Optimized for Vercel & PS Vita
 */

// Global CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: *");
header("X-Content-Type-Options: nosniff");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

if (!isset($_GET['url'])) {
    http_response_code(400);
    die("Error: No URL provided");
}

$raw_url = str_replace(' ', '+', $_GET['url']);
$url = base64_decode($raw_url);

if (!$url || filter_var($url, FILTER_VALIDATE_URL) === false) {
    http_response_code(400);
    die("Error: Invalid URL");
}

/**
 * Resolve relative URLs against a base URL
 */
function resolve_url($base, $rel) {
    if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
    if (empty($rel)) return $base;
    if ($rel[0] == '#' || $rel[0] == '?') return $base.$rel;
    
    $parse = parse_url($base);
    $scheme = isset($parse['scheme']) ? $parse['scheme'] : 'http';
    $host = isset($parse['host']) ? $parse['host'] : '';
    $port = isset($parse['port']) ? ':' . $parse['port'] : '';
    $path = isset($parse['path']) ? $parse['path'] : '/';
    
    $hostname = $scheme . '://' . $host . $port;
    
    if ($rel[0] == '/') return $hostname . $rel;
    
    $dir = dirname($path);
    if ($dir === '/' || $dir === '\\') $dir = '';
    
    $abs = $hostname . $dir . '/' . $rel;
    
    // Cleanup path
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}
    return $abs;
}

$is_ts = (stripos($url, '.ts') !== false || stripos($url, '.m2t') !== false);
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$is_vita = (stripos($ua, 'Vita') !== false || stripos($ua, 'PlayStation') !== false);

// Setup cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36");
curl_setopt($ch, CURLOPT_REFERER, $url);
curl_setopt($ch, CURLOPT_TIMEOUT, 20); // Longer timeout to prevent "No Video" on slow sources

if ($is_ts) {
    // Stream segments directly for memory efficiency
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    header("Content-Type: video/mp2t");
    header("Cache-Control: public, max-age=3600");
    curl_exec($ch);
    curl_close($ch);
    exit;
}

// Buffer playlists for rewriting
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
$content_type = strtolower($info['content_type']);
$final_url = $info['url'];
curl_close($ch);

if (!$response) {
    http_response_code(504);
    die("IPTV Server Timeout or Unavailable");
}

// Robust M3U8 Detection
$is_playlist = (stripos($content_type, 'mpegurl') !== false || stripos($content_type, 'm3u8') !== false || stripos(trim($response), '#EXTM3U') === 0);

if ($is_playlist) {
    header("Content-Type: application/vnd.apple.mpegurl");
    header("Cache-Control: no-cache, no-store, must-revalidate");
    
    $lines = explode("\n", $response);
    $output = [];
    $host_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    
    $skip_next = false;
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // PS Vita Optimization: Avoid 1080p and higher to prevent C0-14350-3 memory crash
        if ($is_vita && stripos($line, '#EXT-X-STREAM-INF') === 0) {
            if (preg_match('/RESOLUTION=(\d+x\d+)/i', $line, $matches)) {
                 $res = explode('x', strtolower($matches[1]));
                 if (isset($res[1]) && intval($res[1]) > 720) {
                     $skip_next = true;
                     continue;
                 }
            }
            if (stripos($line, '1080') !== false || stripos($line, '4K') !== false || stripos($line, 'UHD') !== false) {
                $skip_next = true;
                continue;
            }
        }
        
        if ($skip_next && $line[0] !== '#') {
            $skip_next = false;
            continue;
        }
        $skip_next = false;

        if ($line[0] === '#') {
            // Rewrite URI in attributes (like EXT-X-KEY or sub-playlists)
            if (stripos($line, 'URI=') !== false && preg_match('/URI="([^"]+)"/', $line, $matches)) {
                $absUri = resolve_url($final_url, $matches[1]);
                $proxyUri = $host_url . '/api/stream_proxy.php?url=' . urlencode(base64_encode($absUri));
                $line = str_replace($matches[1], $proxyUri, $line);
            }
            $output[] = $line;
        } else {
            // Rewrite content URLs
            $absUrl = resolve_url($final_url, $line);
            $output[] = $host_url . '/api/stream_proxy.php?url=' . urlencode(base64_encode($absUrl));
        }
    }
    
    // Fallback if filtering removed all streams
    if (count($output) <= 1 && $is_vita) {
         // If we only have #EXTM3U, we failed. Let's return original (unfiltered) or just the first stream.
         // Actually, let's just output the original lines if the filtered list is empty.
         echo $response;
    } else {
         echo implode("\n", $output);
    }
} else {
    // Normal file or chunk
    header("Content-Type: " . ($content_type ?: "video/mp4"));
    echo $response;
}
?>
