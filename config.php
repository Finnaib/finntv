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

// --- Configuration ---

$server_config = [
    'server_name' => 'FinnTV V2',
    'timezone' => 'UTC',
    'm3u_dir' => __DIR__ . '/m3u',

    // Keywords to detect Movies (VOD)
    'vod_keywords' => ['movie', 'vod', 'film', 'cinema'], // Not used in strict mode

    // Keywords to detect Series
    'series_keywords' => ['series', 'season', 'episode', 'show'],

    'base_url' => 'https://finntv.vercel.app/', // Change this to your VPS IP if using Docker!
    'stream_mode' => 'proxy', // Options: 'redirect' (faster), 'proxy' (secure/hidden)
];

// --- Users Database ---
// Username => Password
// Username => [Password, Created_At (Optional Timestamp)]
// "1672531200" = Jan 1, 2023. If empty, defaults to Dynamic.
$users_db = [
    "finn" => ["password" => "finn123", "created_at" => 1735689600], // Example: Jan 1 2025
    "tabby" => ["password" => "tabby123", "created_at" => null],       // Dynamic (Always 1 year from now)
    "test" => ["password" => "test", "created_at" => null],
    "shoaibwwe01@gmail.com" => ["password" => "Fatima786@", "created_at" => null] // Admin Account
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

// --- Parser Logic ---

function parseMoviesAndSeries()
{
    global $server_config, $data;

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
        $is_vod_file = (stripos($filename, 'vod') !== false || stripos($filename, 'movie') !== false);
        $is_series_file = (stripos($filename, 'series') !== false);

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

                preg_match('/,(.*)$/', $line, $nMatch);
                $name = $nMatch[1] ?? 'Unknown Channel';

                // --- Classification Logic ---
                // STRICT SEPARATION as requested:
                // Live -> Live Only
                // Series -> Series Only
                // VOD -> Movies Only
                if ($is_xtream) {
                    $is_vod = false;
                    $is_series = false;
                } elseif ($is_series_file) {
                    $is_series = true;
                    $is_vod = false;
                } elseif ($is_vod_file) {
                    $is_vod = true;
                    $is_series = false;
                } else {
                    // Default for generic files (asia.m3u etc) -> Live
                    $is_vod = false;
                    $is_series = false;
                }

                // Determine Type
                $type = $is_series ? 'series' : ($is_vod ? 'movie' : 'live');

                // --- Category ID Generation ---
                $unique_group_str = ($type === 'live' ? 'L_' : ($type === 'movie' ? 'M_' : 'S_')) . $current_group;
                $cat_id = sprintf("%u", crc32($unique_group_str));

                // Store metadata
                $meta = [
                    'id' => $stream_index++,
                    'name' => $name,
                    'logo' => $current_logo,
                    'group' => $current_group,
                    'type' => $type,
                    'cat_id' => $cat_id
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
                $stream = [
                    'num' => $meta['id'],
                    'name' => $meta['name'],
                    'stream_id' => $meta['id'],
                    'stream_icon' => $meta['logo'],
                    'category_id' => (string) $meta['cat_id'],
                    'container_extension' => ($type == 'live') ? 'ts' : 'mp4',
                    'direct_source' => $line
                ];

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