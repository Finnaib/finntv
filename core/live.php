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
// DEBUG LOGGING
file_put_contents(__DIR__ . '/../debug_live_log.txt', date('Y-m-d H:i:s') . " - Request: $uri\n", FILE_APPEND);

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

// 2. Search for Stream
$target_url = "";

// Check Live
foreach ($data['live_streams'] as $s) {
    if ($s['num'] == $id) {
        $target_url = $s['direct_source'];
        break;
    }
}

// Check VOD if not found
if (!$target_url) {
    foreach ($data['vod_streams'] as $s) {
        if ($s['num'] == $id) {
            $target_url = $s['direct_source'];
            break;
        }
    }
}

// Check Series if not found
if (!$target_url) {
    foreach ($data['series'] as $s) {
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
    if ($ext === 'ts' && stripos($target_url, '.m3u8') !== false) {
        $target_url = str_replace('.m3u8', '.ts', $target_url);
    }

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
if ($ext === 'ts' && stripos($target_url, '.m3u8') !== false) {
    // Safest bet for generic upstream: Try replacing extension
    $target_url = str_replace('.m3u8', '.ts', $target_url);
}

// 3. User Agent Logic (Optional Spoofing)
if ($is_smart_app) {
    // If it's a "Smart" app, we can maybe trust it to handle the redirect
}

header("Location: " . $target_url);
exit;
// End of file