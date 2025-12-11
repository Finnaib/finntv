<?php
// Users database with plain text passwords (simple version)
$users = [
    "finn" => [
        "pass" => "finn123",
        "categories" => ["india", "asia", "usa", "egypt", "sport", "xtream"],
        "max_conn" => 10,
        "exp_date" => strtotime('+1 year')
    ],
    "tabby" => [
        "pass" => "tabby123",
        "categories" => ["asia", "sport"],
        "max_conn" => 10,
        "exp_date" => strtotime('+1 year')
    ],
    "fatima" => [
        "pass" => "fatima123",
        "categories" => ["india", "egypt", "sport"],
        "max_conn" => 10,
        "exp_date" => strtotime('+1 year')
    ],
    "devz" => [
        "pass" => "devz123",
        "categories" => ["india", "usa", "sport"],
        "max_conn" => 10,
        "exp_date" => strtotime('+1 year')
    ],
    "test" => [
        "pass" => "test123",
        "categories" => ["xtream"],
        "max_conn" => 10,
        "exp_date" => strtotime('+1 year')
    ]
];

// Category mapping to M3U files
$category_map = [
    "india" => ["id" => 1, "file" => "india.m3u"],
    "asia" => ["id" => 2, "file" => "asia.m3u"],
    "usa" => ["id" => 3, "file" => "usa.m3u"],
    "egypt" => ["id" => 4, "file" => "egypt.m3u"],
    "sport" => ["id" => 5, "file" => "sport.m3u"],
    "xtream" => ["id" => 6, "file" => "xtream.m3u"]
];

// Live TV Categories
$categories = [
    1 => ["category_id" => "1", "category_name" => "India", "parent_id" => 0],
    2 => ["category_id" => "2", "category_name" => "Asia", "parent_id" => 0],
    3 => ["category_id" => "3", "category_name" => "USA", "parent_id" => 0],
    4 => ["category_id" => "4", "category_name" => "Egypt", "parent_id" => 0],
    5 => ["category_id" => "5", "category_name" => "Sport", "parent_id" => 0],
    6 => ["category_id" => "6", "category_name" => "xtream", "parent_id" => 0]
];

// Server configuration
$server_config = [
    'base_url' => 'https://finntv.vercel.app',
    'domain' => 'finntv.vercel.app',
    'port' => '443',
    'https_port' => '443',
    'use_proxy' => true,
    'proxy_user_agent' => 'IPTVSmartersPro/3.1.5 (iPad; iOS 16.6; Scale/2.00)',
    'm3u_folder' => __DIR__ . DIRECTORY_SEPARATOR . 'm3u' . DIRECTORY_SEPARATOR,

    // Authentication tokens (optional - for premium features)
    'auth_tokens' => [
        // 'token123' => 'finn',
        // Add tokens here if needed
    ],

    // EPG settings
    'epg_enabled' => true,
    'epg_cache_hours' => 6,

    // App-specific optimizations
    'app_detection_enabled' => true,

    // Stream delivery preferences
    'prefer_redirect_for' => ['vlc', 'kodi'], // Apps that work better with direct redirect
    'force_proxy_for' => ['smarters', 'tivimate'], // Apps that require proxy
];

// If running through a web request, prefer a runtime-derived base_url so
// returned `stream_url` values match the host the client used to reach us.
if (php_sapi_name() !== 'cli' && isset($_SERVER['HTTP_HOST'])) {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    // Derive path portion from script location (e.g. '/xtream_server')
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $server_config['base_url'] = rtrim($proto . '://' . $host . $scriptDir, '/');
    // Also update domain/port for consistency
    $server_config['domain'] = $host;
    $server_config['port'] = ($proto === 'https') ? $server_config['https_port'] : '80';
}

// Function to parse M3U file
function parseM3U($filepath, $categoryId)
{
    if (!file_exists($filepath)) {
        error_log("M3U file not found: $filepath");
        return [];
    }

    $channels = [];
    $content = file_get_contents($filepath);
    $lines = explode("\n", $content);

    $currentChannel = null;
    $streamId = $categoryId * 1000 + 1;

    foreach ($lines as $line) {
        $line = trim($line);

        // Parse #EXTINF line
        if (strpos($line, '#EXTINF:') === 0) {
            $currentChannel = [];

            // Extract channel name (after the comma)
            if (preg_match('/,(.+)$/', $line, $matches)) {
                $currentChannel['name'] = trim($matches[1]);
            }

            // Extract tvg-logo
            if (preg_match('/tvg-logo="([^"]+)"/', $line, $matches)) {
                $currentChannel['logo'] = $matches[1];
            } else {
                $currentChannel['logo'] = 'https://via.placeholder.com/150';
            }

            // Extract tvg-id
            if (preg_match('/tvg-id="([^"]+)"/', $line, $matches)) {
                $currentChannel['tvg_id'] = $matches[1];
            } else {
                $currentChannel['tvg_id'] = '';
            }

            // Extract group-title
            if (preg_match('/group-title="([^"]+)"/', $line, $matches)) {
                $currentChannel['group'] = $matches[1];
            }

            $currentChannel['id'] = $streamId++;
            $currentChannel['category'] = $categoryId;

        }
        // Parse stream URL
        else if (!empty($line) && strpos($line, '#') !== 0 && $currentChannel !== null) {
            $currentChannel['url'] = $line;
            $channels[] = $currentChannel;
            $currentChannel = null;
        }
    }

    return $channels;
}

// Function to load all channels from M3U files
function loadAllChannels($category_map, $server_config)
{
    $allChannels = [];

    foreach ($category_map as $catName => $catInfo) {
        $filepath = $server_config['m3u_folder'] . $catInfo['file'];
        $channels = parseM3U($filepath, $catInfo['id']);
        if (count($channels) > 0) {
            error_log("Loaded " . count($channels) . " channels from $catName (ID: {$catInfo['id']})");
        }
        $allChannels = array_merge($allChannels, $channels);
    }

    error_log("Total channels loaded: " . count($allChannels));
    return $allChannels;
}

// Load channels from M3U files
$channels = loadAllChannels($category_map, $server_config);
// End of file (Omitted closing tag to prevent whitespace)