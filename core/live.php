<?php
/**
 * FinnTV Xtream Server V2 - Stream Redirector
 * Handles /live, /movie, /series requests
 */

require_once __DIR__ . '/../config.php';

// No Output buffering needed if we are clean, but good safety
ob_start();

// 1. Context Analysis
header("Access-Control-Allow-Origin: *");
$uri = $_SERVER['REQUEST_URI'];
$parts = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));

// Detect Type from Query Param OR URI path
$type = $_GET['type'] ?? "live"; // Priority to query param from vercel.json
if ($type === "live") {
    if (in_array('movie', $parts))
        $type = "movie";
    if (in_array('series', $parts))
        $type = "series";
}

$id_part = end($parts); // "1055.ts" or "1055.ts?token=..."
// Robust ID extraction: get everything before the first dot or question mark
$id = 0;
// Strip extension and query if present in the last part
$leaf = explode('?', $id_part)[0];
$leaf = explode('.', $leaf)[0];

if (preg_match('/^(\d+)/', $leaf, $matches)) {
    $id = $matches[1];
}

$ext = pathinfo(parse_url($id_part, PHP_URL_PATH), PATHINFO_EXTENSION);
$map_key = $type . "_" . $id;

// 2. Search for Stream (Optimized)
$target_url = "";

// file_put_contents(__DIR__ . '/../debug_live_log.txt', "Request: ID=$id, EXT=$ext\n", FILE_APPEND);

// Try to load optimized ID Map (Fastest)
$map_file = __DIR__ . '/../data/id_map.json';
if (file_exists($map_file)) {
    $id_map = json_decode(file_get_contents($map_file), true);
    if (isset($id_map[$map_key])) {
        $target_url = $id_map[$map_key];
    } else {
        // Legacy Fallback: Try without prefix if prefixed lookup fails
        if (isset($id_map[$id])) {
            $target_url = $id_map[$id];
        }
    }
}

// 2.a Fallback Constructor for Movies and Series (Smart Constructor)
// If not found in map, we build it directly using upstream host and credentials
if (!$target_url && ($type === 'movie' || $type === 'series')) {
    global $provider_config;
    if (!empty($provider_config['host'])) {
        $u_host = $provider_config['host'];
        $u_user = $provider_config['username'];
        $u_pass = $provider_config['password'];

        $v_ext = $ext ? $ext : "mp4";
        if ($type === 'movie') {
            $target_url = "{$u_host}/movie/{$u_user}/{$u_pass}/{$id}.{$v_ext}";
        } else {
            $target_url = "{$u_host}/series/{$u_user}/{$u_pass}/{$id}.{$v_ext}";
        }
    }
}

// Fallback to legacy linear search (Slowest - only for Live if map fails)
if (!$target_url && $type === 'live') {
    foreach ($data['live_streams'] as $s) {
        if ($s['num'] == $id) {
            $target_url = $s['direct_source'];
            break;
        }
    }
}

// 404
if (!$target_url) {
    ob_clean();
    http_response_code(404);
    die("Stream not found.");
}

// 3. Smart Redirect Logic
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_smart_app = false;

// Check for Smarters, TiviMate, etc.
if (
    stripos($ua, 'Smarters') !== false ||
    stripos($ua, 'TiviMate') !== false ||
    stripos($ua, 'HLS') !== false
) {
    $is_smart_app = true;
}

// --- 3. Stream Handoff Logic ---

/*
 * MODE: PROXY (Secure)
 * - Hides the provider URL.
 * - Fixes Mixed Content (HTTP source on HTTPS server).
 * - Uses Server CPU/Bandwidth.
 */
if (isset($server_config['stream_mode']) && $server_config['stream_mode'] === 'proxy') {
    // Universal Logic: Rewrite HLS to TS if requested (and it looks like HLS)
    // Universal Logic: Rewrite HLS to TS if requested (and it looks like HLS)
    // DISABLED: Upstream does not support .ts
    // if ($ext === 'ts' && stripos($target_url, '.m3u8') !== false) {
    //     $target_url = str_replace('.m3u8', '.ts', $target_url);
    // }

    // Open Connection to Provider with User-Agent to bypass blocks
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: VLC/3.0.16 LibVLC/3.0.16\r\n" .
                "Accept: */*\r\n" .
                "Connection: close\r\n" .
                "Icy-MetaData: 1\r\n" // Request metadata if supported
        ]
    ];
    $context = stream_context_create($opts);

    // Suppress warnings with @ and handle manually
    $fp = @fopen($target_url, 'rb', false, $context);

    if (!$fp) {
        $error = error_get_last();
        // Log error for debugging (optional)
        // file_put_contents(__DIR__ . '/../debug_error.log', "Stream Fail: " . $error['message'] . "\n", FILE_APPEND);

        http_response_code(502); // Bad Gateway
        die("Error: Could not connect to stream provider. Provider might be blocking or offline.");
    }

    // Forward Headers (Respected)
    // We already set Access-Control-Allow-Origin globally, but good to reinforce
    if (!headers_sent()) {
        header("Content-Type: video/mp2t"); // Force TS content type for best compatibility
        header("Access-Control-Allow-Origin: *");
    }

    // Pump Data (Optimized Chunk Size)
    // 8KB is standard, but 64KB might be better for high bitrate streams on VPS
    $chunk_size = 8192;

    while (!feof($fp)) {
        echo fread($fp, $chunk_size);
        // Flush buffer to prevent lag
        if (ob_get_level() > 0)
            ob_flush();
        flush();
    }
    fclose($fp);
    exit;
}

/*
 * MODE: REDIRECT (Legacy/Fast)
 * - Exposes provider URL.
 * - Zero CPU usage.
 * - Mixed Content Warnings.
 */

// Universal Logic: Rewrite HLS to TS if requested (and it looks like HLS)
// if ($ext === 'ts' && stripos($target_url, '.m3u8') !== false) {
// Safest bet for generic upstream: Try replacing extension
// $target_url = str_replace('.m3u8', '.ts', $target_url);
// }

// Enhanced Headers for IPTV Pro and Smarters Compatibility
// These players are strict about redirects and need proper headers

// 1. Clear any buffered output
if (ob_get_length())
    ob_clean();

// 2. Set proper status code (302 for temporary redirect)
http_response_code(302);

// 3. CORS headers (already set above but reinforce)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, HEAD, OPTIONS");
header("Access-Control-Allow-Headers: *");

// 4. Cache control to prevent stale redirects
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 5. Content type hint for video
// Fix: Check TARGET content type, not just requested extension.
// If target is m3u8, we MUST say it is m3u8, otherwise players like TiviMate fail.
if (strpos($target_url, '.m3u8') !== false) {
    header("Content-Type: application/vnd.apple.mpegurl");
} elseif ($ext === 'ts') {
    header("Content-Type: video/mp2t");
} else {
    header("Content-Type: video/mp4");
}

// 6. Redirect
// file_put_contents(__DIR__ . '/../debug_live_log.txt', "Redirecting to: $target_url\n", FILE_APPEND);
header("Location: " . $target_url);
exit;
// End of file