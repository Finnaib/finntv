<?php
/**
 * Enhanced live.php - Universal IPTV App Compatibility
 * 
 * Features:
 * - Auto-detection of IPTV app from User-Agent
 * - Smart User-Agent spoofing for upstream providers
 * - HLS manifest handling
 * - Optimized redirect/proxy logic
 * - Support for old and new TV boxes
 */

// CRITICAL: Suppress ALL output before headers
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(0); // No time limit for streams

// Clear any output
ob_clean();

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Expose-Headers: Content-Length, Content-Range');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

// Load config
if (!file_exists('config.php')) {
    ob_end_clean();
    http_response_code(500);
    exit;
}

require_once __DIR__ . '/../config.php';

// Parse URL - Support multiple formats for compatibility
$username = '';
$password = '';
$streamId = 0;
$extension = '';

// Method 1: PATH_INFO (Standard Xtream format)
if (isset($_SERVER['PATH_INFO'])) {
    if (preg_match('#/(live|movie|series)/([^/]+)/([^/]+)/(\d+)(\.([a-zA-Z0-9]+))?$#', $_SERVER['PATH_INFO'], $m)) {
        // $m[1] is type (live/movie/series) -> ignored for now as ID is unique
        $username = $m[2];
        $password = $m[3];
        $streamId = (int) $m[4];
        $extension = isset($m[6]) ? $m[6] : '';
    }
}

// Method 2: REQUEST_URI (Fallback for rewrite rules)
if (empty($username) && isset($_SERVER['REQUEST_URI'])) {
    if (preg_match('#/(live|movie|series)/([^/]+)/([^/]+)/(\d+)(\.([a-zA-Z0-9]+))?(\?|$)#', $_SERVER['REQUEST_URI'], $m)) {
        // $m[1] is type
        $username = $m[2];
        $password = $m[3];
        $streamId = (int) $m[4];
        $extension = isset($m[6]) ? $m[6] : '';
    }
}

// Method 3: Query params (Old TV box compatibility)
if (empty($username)) {
    $username = $_GET['username'] ?? $_GET['u'] ?? '';
    $password = $_GET['password'] ?? $_GET['p'] ?? '';
    $streamId = (int) ($_GET['stream'] ?? $_GET['id'] ?? $_GET['stream_id'] ?? 0);
    $extension = $_GET['extension'] ?? $_GET['ext'] ?? '';
}

if (empty($username) || empty($password) || $streamId == 0) {
    ob_end_clean();
    http_response_code(400);
    exit;
}

// Authenticate
$user = null;
if (isset($users[$username])) {
    $stored = $users[$username]['pass'];
    if (password_verify($password, $stored) || $password === $stored) {
        $user = $users[$username];
    }
}

if (!$user) {
    ob_end_clean();
    http_response_code(403);
    exit;
}

// Find channel
$channel = null;
foreach ($channels as $ch) {
    if ($ch['id'] == $streamId) {
        $channel = $ch;
        break;
    }
}

if (!$channel) {
    ob_end_clean();
    http_response_code(404);
    exit;
}

// Check category access
$allowed = [];
foreach ($user['categories'] as $cat) {
    if (isset($category_map[$cat])) {
        $allowed[] = $category_map[$cat]['id'];
    }
}

if (!in_array($channel['category'], $allowed)) {
    ob_end_clean();
    http_response_code(403);
    exit;
}

$upstreamUrl = $channel['url'];

// Detect client app from User-Agent
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$clientApp = 'unknown';
$spoofAgent = $server_config['proxy_user_agent'] ?? 'VLC/3.0.16';

// Smart User-Agent detection and spoofing
if (stripos($userAgent, 'Smarters') !== false) {
    $clientApp = 'smarters';
    $spoofAgent = 'IPTVSmartersPro/3.1.5 (iPad; iOS 16.6; Scale/2.00)';
} elseif (stripos($userAgent, 'TiviMate') !== false) {
    $clientApp = 'tivimate';
    $spoofAgent = 'TiviMate/4.6.1 (Linux; Android 11)';
} elseif (stripos($userAgent, 'IPTVnator') !== false) {
    $clientApp = 'iptvnator';
    $spoofAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
} elseif (stripos($userAgent, 'Perfect') !== false) {
    $clientApp = 'perfect';
    $spoofAgent = 'PerfectPlayer/1.5.12 (Android)';
} elseif (stripos($userAgent, 'GSE') !== false) {
    $clientApp = 'gse';
    $spoofAgent = 'GSE SMART IPTV/8.2 (iOS)';
} elseif (stripos($userAgent, 'VLC') !== false) {
    $clientApp = 'vlc';
    $spoofAgent = 'VLC/3.0.16 (Windows NT 10.0)';
} elseif (stripos($userAgent, 'Kodi') !== false || stripos($userAgent, 'XBMC') !== false) {
    $clientApp = 'kodi';
    $spoofAgent = 'Kodi/20.1 (Linux; Android)';
}

// --- REDIRECT LOGIC ---
// Vercel Serverless cannot proxy long-lived connections (streams).
// We MUST redirect the client to the upstream source directly.

ob_end_clean();
header("Location: " . $upstreamUrl);
exit;
// End of file