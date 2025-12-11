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
header("Access-Control-Allow-Origin: *");

// --- Configuration ---

$server_config = [
    'server_name' => 'FinnTV V2',
    'timezone' => 'UTC',
    'm3u_dir' => __DIR__ . '/m3u',

    // Keywords to detect Movies (VOD)
    'vod_keywords' => ['movie', 'film', 'cinema', 'vod', '4k movie', 'vip'],

    // Keywords to detect Series
    'series_keywords' => ['series', 'season', 'episodes', 'netflix'],

    // Base URL determination
    'base_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]"
];

// --- Users Database ---
// Username => Password
$users_db = [
    "finn" => "finn123",
    "tabby" => "tabby123",
    "test" => "test",
    "admin" => "admin"
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
    $category_index = 1;
    $stream_index = 1;

    // Track unique category names to assign IDs
    $cat_map = [
        'live' => [],
        'movie' => [],
        'series' => []
    ];

    foreach ($files as $file) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $current_group = "Uncategorized";
        $current_logo = "";

        foreach ($lines as $line) {
            if (strpos($line, '#EXTINF') === 0) {
                // reset
                $is_vod = false;
                $is_series = false;

                // Extract Attributes
                preg_match('/group-title="([^"]*)"/', $line, $gMatch);
                $current_group = $gMatch[1] ?? 'Uncategorized';

                preg_match('/tvg-logo="([^"]*)"/', $line, $lMatch);
                $current_logo = $lMatch[1] ?? '';

                preg_match('/,(.*)$/', $line, $nMatch);
                $name = $nMatch[1] ?? 'Unknown Channel';

                // Classification
                $group_lower = strtolower($current_group);

                foreach ($server_config['series_keywords'] as $kw) {
                    if (strpos($group_lower, $kw) !== false) {
                        $is_series = true;
                        break;
                    }
                }

                if (!$is_series) {
                    foreach ($server_config['vod_keywords'] as $kw) {
                        if (strpos($group_lower, $kw) !== false) {
                            $is_vod = true;
                            break;
                        }
                    }
                }

                // Store metadata for next URL line
                $meta = [
                    'id' => $stream_index++,
                    'name' => $name,
                    'logo' => $current_logo,
                    'group' => $current_group,
                    'type' => $is_series ? 'series' : ($is_vod ? 'movie' : 'live')
                ];

            } else if (strpos($line, 'http') === 0) {
                // It's a URL
                if (empty($meta))
                    continue;

                $type = $meta['type']; // live, movie, series

                // Handle Category ID
                if (!isset($cat_map[$type][$current_group])) {
                    $cat_map[$type][$current_group] = count($cat_map[$type]) + 1;

                    // Add to main category list
                    $cat_entry = [
                        'category_id' => (string) $cat_map[$type][$current_group],
                        'category_name' => $current_group,
                        'parent_id' => 0
                    ];

                    if ($type == 'live')
                        $data['live_categories'][] = $cat_entry;
                    if ($type == 'movie')
                        $data['vod_categories'][] = $cat_entry;
                    if ($type == 'series')
                        $data['series_categories'][] = $cat_entry;
                }

                $cat_id = $cat_map[$type][$current_group];

                // Build Stream Object
                $stream = [
                    'num' => $meta['id'],
                    'name' => $meta['name'],
                    'stream_id' => $meta['id'],
                    'stream_icon' => $meta['logo'],
                    'category_id' => (string) $cat_id,
                    'container_extension' => ($type == 'live') ? 'ts' : 'mp4',
                    'direct_source' => $line // Important: Store upstream URL
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
                    // Series needs simplified "Series" entry, not individual episodes in this list usually, 
                    // but for M3U gateway, we treat them as VOD streams or grouped series.
                    // For simplicity in V1 of this re-write, treating Series as VOD Files
                    // mapped to a "Series" category for generic players.
                    $stream['series_id'] = $meta['id'];
                    $stream['cover'] = $meta['logo'];
                    // Logic hack: Many players accept series as VOD if configured this way
                    $data['series'][] = $stream;
                }

                $meta = []; // Clear
            }
        }
    }
}

// Run Parser
parseMoviesAndSeries();

// End of file