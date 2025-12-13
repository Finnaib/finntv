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
$parts = explode('/', trim($uri, '/'));

// Expected Structure: 
// /live/user/pass/id.ts
// /movie/user/pass/id.mp4
// /series/user/pass/id.mp4

// Basic detection (assuming rewrite rules work, indices might vary based on folder depth)
// For safe parsing, we look for numeric ID at end.

$id_part = end($parts); // "1055.ts"
$id = (int) filter_var($id_part, FILTER_SANITIZE_NUMBER_INT);
$ext = pathinfo($id_part, PATHINFO_EXTENSION);

// 2. Search for Stream (Optimized)
$target_url = "";

// file_put_contents(__DIR__ . '/../debug_live_log.txt', "Request: ID=$id, EXT=$ext\n", FILE_APPEND);

// Try to load optimized ID Map (Fastest)
$map_file = __DIR__ . '/../data/id_map.json';
if (file_exists($map_file)) {
    $id_map = json_decode(file_get_contents($map_file), true);
    if (isset($id_map[$id])) {
        $target_url = $id_map[$id];
    }
} else {
    // Fallback to legacy linear search (Slow)
    // Check Live
    foreach ($data['live_streams'] as $s) {
        if ($s['num'] == $id) {
            $target_url = $s['direct_source'];
            break;
        }
    }
    // Check VOD
    if (!$target_url) {
        foreach ($data['vod_streams'] as $s) {
            if ($s['num'] == $id) {
                $target_url = $s['direct_source'];
                break;
            }
        }
    }
    // Check Series
    if (!$target_url) {
        foreach ($data['series'] as $s) {
            if ($s['num'] == $id) {
                $target_url = $s['direct_source'];
                break;
            }
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

    // Open Connection to Provider
    $fp = fopen($target_url, 'rb');
    if (!$fp) {
        http_response_code(502); // Bad Gateway
        die("Error: Could not connect to stream provider.");
    }

    // Forward Headers (Minimal)
    header("Content-Type: video/mp2t"); // Assume MPEG-TS for Xtream
    header("Access-Control-Allow-Origin: *");

    // Pump Data
    while (!feof($fp)) {
        echo fread($fp, 8192); // 8KB chunks
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