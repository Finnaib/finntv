<?php
/**
 * FinnTV Xtream Server V2 - Config & Parser
 * 
 * - Auto-detects M3U files in /m3u/
 * - Categorizes content into Live, Movies, and Series base on keywords
 * - Central configuration
 */

// Basic Security & Headers
error_reporting(0);
ini_set('display_errors', 0);
// header("Access-Control-Allow-Origin: *"); // Moved to player_api.php

// --- Provider Context (Loaded from xtream_config.json) ---
$provider_config = [
    'host' => '',
    'username' => '',
    'password' => ''
];
$cfg_p = __DIR__ . '/xtream_config.json';
if (file_exists($cfg_p)) {
    $c = json_decode(file_get_contents($cfg_p), true);
    if ($c)
        $provider_config = array_merge($provider_config, $c);
}

// --- Configuration ---

$server_config = [
    'server_name' => 'FinnTV V2',
    'timezone' => 'UTC',
    'm3u_dir' => __DIR__ . '/m3u',

    // Keywords to detect Movies (VOD)
    'vod_keywords' => ['movie', 'vod', 'film', 'cinema'], // Not used in strict mode

    // Keywords to detect Series
    'series_keywords' => ['series', 'season', 'episode', 'show'],

    // Base URL - Use actual server IP for TiviMate compatibility
    // TiviMate needs the real IP to build stream URLs
    'base_url' => 'https://finntv.vercel.app/',
    'stream_mode' => 'redirect', // Options: 'redirect' (faster), 'proxy' (secure/hidden)
];

// --- Users Database ---
// Username => Password
// Username => [Password, Created_At (Optional Timestamp)]
// "1672531200" = Jan 1, 2023. If empty, defaults to Dynamic.
$users_db = [
    "finn" => ["password" => "finn123", "created_at" => null], // Dynamic (valid for 1 year from first login)
    "tabby" => ["password" => "tabby123", "created_at" => null],       // Dynamic (Always 1 year from now)
    "test" => ["password" => "test", "created_at" => null],
    "shoaibwwe01@gmail.com" => ["password" => "Fatima786@", "created_at" => null], // Admin Account

    // --- YOUR CUSTOM USER ---
    // Change "myuser" and "mypassword" to whatever you want
    "myuser" => ["password" => "mypassword", "created_at" => null],

    "devz" => ["password" => "devz123", "created_at" => null],
    "aayush787" => ["password" => "aayush@1091", "created_at" => null]
];

// --- Data Store (InMemory) ---
// In a real DB app this would be SQL. Here we parse on the fly (Vercel caches somewhat).
$data = [
    'live_streams' => [],
    'live_categories' => [],
    'vod_streams' => [],
    'vod_categories' => [],
    'series' => [],
    'series_categories' => []
];

// Optimization: Load pre-built data.json if available (Fast Mode)
// Optimization: Load pre-built data.json if available (Fast Mode)
$json_path = __DIR__ . '/data/data.json'; // Default: in data subdir
if (!file_exists($json_path)) {
    $json_path = __DIR__ . '/data.json'; // Fallback: in root
}
if (!file_exists($json_path) && isset($_SERVER['DOCUMENT_ROOT'])) {
    $json_path = $_SERVER['DOCUMENT_ROOT'] . '/data/data.json';
}

if (file_exists($json_path)) {
    $json = file_get_contents($json_path);
    $decoded = json_decode($json, true);
    if ($decoded) {
        $data = $decoded;
    }
}

// --- Parser Logic ---

function parseMoviesAndSeries()
{
    global $server_config, $data;

    // If data already loaded from JSON, skip parsing!
    if (!empty($data['live_streams']) || !empty($data['vod_streams'])) {
        return;
    }

    if (!is_dir($server_config['m3u_dir']))
        return;

    $files = glob($server_config['m3u_dir'] . '/*.m3u');
    if ($files) {
        sort($files); // Ensure deterministic order across different OS/Deployments
    }
    $category_index = 1;
    $stream_index = 1;

    // Track unique category names to assign IDs
    $cat_map = [
        'live' => [],
        'movie' => [],
        'series' => []
    ];

    foreach ($files as $file) {
        $filename = basename($file);


        $handle = fopen($file, "r");
        if (!$handle)
            continue;

        $current_group = "Uncategorized";
        $current_logo = "";

        // Optimizations: Broaden 'xtream' detection to any file containing 'xtream' OR 'live'
        $is_xtream = (stripos($filename, 'xtream') !== false || stripos($filename, 'live') !== false);

        // Strict File Detection
        $strict_type = null;
        if (strtolower($filename) === 'vod.m3u') {
            $strict_type = 'movie';
        } elseif (strtolower($filename) === 'series.m3u') {
            $strict_type = 'series';
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line))
                continue;

            if (strpos($line, '#EXTINF') === 0) {
                // reset
                $is_vod = false;
                $is_series = false;

                // Extract Attributes
                // Optimize: Simple string parsing or keep regex if robust
                preg_match('/group-title="([^"]*)"/', $line, $gMatch);
                $current_group = $gMatch[1] ?? 'Uncategorized';

                preg_match('/tvg-logo="([^"]*)"/', $line, $lMatch);
                $current_logo = $lMatch[1] ?? '';

                preg_match('/tvg-id="([^"]*)"/', $line, $idMatch);
                $current_epg_id = $idMatch[1] ?? null;

                preg_match('/,(.*)$/', $line, $nMatch);
                $name = $nMatch[1] ?? 'Unknown Channel';

                // --- Classification Logic ---

                if ($strict_type) {
                    // Rule 0: Strict Filename Enforcement
                    if ($strict_type === 'movie') {
                        $is_vod = true;
                        $is_series = false;
                    } else {
                        $is_series = true;
                        $is_vod = false;
                    }
                } else {
                    // For non-strict files (live.m3u, asia.m3u, sport.m3u, etc.)
                    // ALWAYS treat as LIVE TV - ignore category keywords
                    // This prevents channels like "beIN Movies" or "AMC电影台" from being classified as VOD
                    $is_vod = false;
                    $is_series = false;
                }

                // Determine Type
                $type = $is_series ? 'series' : ($is_vod ? 'movie' : 'live');

                $unique_group_str = ($type === 'live' ? 'L_' : ($type === 'movie' ? 'M_' : 'S_')) . $current_group;

                // --- Category ID Generation ---
                static $cat_name_to_id = [];
                static $next_cat_id = 1;

                if (!isset($cat_name_to_id[$unique_group_str])) {
                    $cat_name_to_id[$unique_group_str] = (string) $next_cat_id++;
                }
                $cat_id = $cat_name_to_id[$unique_group_str];

                // Store metadata
                $meta = [
                    'id' => $stream_index++,
                    'name' => $name,
                    'logo' => $current_logo,
                    'group' => $current_group,
                    'type' => $type,
                    'cat_id' => $cat_id,
                    'epg_id' => $current_epg_id
                ];

            } else if (strpos($line, 'http') === 0 && !empty($meta)) {

                // --- Add Category if new ---
                $type = $meta['type'];
                $cat_key = $meta['cat_id'];

                if (!isset($cat_map[$type][$cat_key])) {
                    $cat_map[$type][$cat_key] = true;

                    $cat_entry = [
                        'category_id' => (string) $cat_key,
                        'category_name' => $meta['group'],
                        'parent_id' => 0
                    ];

                    if ($type == 'live')
                        $data['live_categories'][] = $cat_entry;
                    if ($type == 'movie')
                        $data['vod_categories'][] = $cat_entry;
                    if ($type == 'series')
                        $data['series_categories'][] = $cat_entry;
                }

                // --- Build Stream Object ---

                // Detect Extension
                $ext = 'ts'; // Default
                if (preg_match('/\.([a-zA-Z0-9]{2,4})$/', $line, $eMatch)) {
                    $ext = $eMatch[1];
                }

                $stream = [
                    'num' => $meta['id'],
                    'name' => $meta['name'],
                    'stream_id' => $meta['id'],
                    'stream_icon' => $meta['logo'],
                    'category_id' => (string) $meta['cat_id'],
                    'container_extension' => $ext,

                    'direct_source' => $line,
                    'added' => (string) time(),
                    'custom_sid' => "",
                    'tv_archive' => 0,
                    'tv_archive_duration' => 0,
                    'epg_channel_id' => $current_epg_id ?? ""
                ];

                // if (!empty($meta['epg_id'])) {
                //    $stream['epg_channel_id'] = $meta['epg_id'];
                // }

                // Add to specific arrays
                if ($type == 'live') {
                    $stream['stream_type'] = 'live';
                    $data['live_streams'][] = $stream;
                } elseif ($type == 'movie') {
                    $stream['stream_type'] = 'movie';
                    $stream['rating'] = '5';
                    $stream['added'] = (string) time();
                    $data['vod_streams'][] = $stream;
                } elseif ($type == 'series') {
                    $stream['series_id'] = $meta['id'];
                    $stream['cover'] = $meta['logo'];
                    $data['series'][] = $stream;
                }

                $meta = []; // Clear pair
            }
        }
        fclose($handle);
    }
}

// Run Parser
// Run Parser (Optimized: Execution moved to player_api.php)
// parseMoviesAndSeries();

// End of file