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

require_once 'config.php';

// Parse URL - Support multiple formats for compatibility
$username = '';
$password = '';
$streamId = 0;
$extension = '';

// Method 1: PATH_INFO (Standard Xtream format)
if (isset($_SERVER['PATH_INFO'])) {
    if (preg_match('#/([^/]+)/([^/]+)/(\d+)(\.([a-zA-Z0-9]+))?$#', $_SERVER['PATH_INFO'], $m)) {
        $username = $m[1];
        $password = $m[2];
        $streamId = (int) $m[3];
        $extension = isset($m[5]) ? $m[5] : '';
    }
}

// Method 2: REQUEST_URI (Fallback for rewrite rules)
if (empty($username) && isset($_SERVER['REQUEST_URI'])) {
    if (preg_match('#/live/([^/]+)/([^/]+)/(\d+)(\.([a-zA-Z0-9]+))?(\?|$)#', $_SERVER['REQUEST_URI'], $m)) {
        $username = $m[1];
        $password = $m[2];
        $streamId = (int) $m[3];
        $extension = isset($m[5]) ? $m[5] : '';
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

// --- PROXY LOGIC ---

// Check if proxy is disabled or if we should use direct redirect
$useProxy = $server_config['use_proxy'] ?? true;

// For certain apps or stream types, prefer redirect
if ($clientApp === 'vlc' || $clientApp === 'kodi' || !$useProxy) {
    ob_end_clean();
    header('Location: ' . $upstreamUrl);
    exit;
}

// Proxy mode with User-Agent spoofing
ob_end_clean();

// Context options with spoofed User-Agent
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: " . $spoofAgent . "\r\n" .
            "Accept: */*\r\n" .
            "Connection: keep-alive\r\n",
        'follow_location' => 1,
        'max_redirects' => 5,
        'timeout' => 30,
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
];

$ctx = stream_context_create($opts);
$fp = @fopen($upstreamUrl, 'rb', false, $ctx);

if ($fp === false) {
    error_log("Stream proxy failed for: $upstreamUrl (Client: $clientApp)");
    // Fallback to redirect
    header('Location: ' . $upstreamUrl);
    exit;
}

// Forward important headers
if (isset($http_response_header)) {
    foreach ($http_response_header as $h) {
        if (stripos($h, 'Content-Type:') === 0) {
            header($h);
        } elseif (stripos($h, 'Content-Length:') === 0) {
            header($h);
        } elseif (stripos($h, 'Content-Disposition:') === 0) {
            header($h);
        }
    }
}

// Set default content type if not set
if (!headers_sent()) {
    header('Content-Type: video/mp2t');
}

// Stream content to client
while (!feof($fp)) {
    echo fread($fp, 8192);
    if (connection_aborted()) {
        break;
    }
    @flush();
}

fclose($fp);
exit;
?>