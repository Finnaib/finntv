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
// Username => [Password, Created_At (Optional Timestamp)]
// "1672531200" = Jan 1, 2023. If empty, defaults to Dynamic.
$users_db = [
    "finn" => ["password" => "finn123", "created_at" => 1704067200], // Example: Jan 1 2024
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

        // Optimizations
        $is_xtream = (stripos($filename, 'xtream.m3u') !== false);
        $is_vod_file = (stripos($filename, 'vod.m3u') !== false);

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
                if ($is_xtream) {
                    $is_vod = false;
                    $is_series = false;
                } elseif ($is_vod_file) {
                    // Check Series keywords
                    $group_lower = strtolower($current_group);
                    $is_series = false;
                    foreach ($server_config['series_keywords'] as $kw) {
                        if (strpos($group_lower, $kw) !== false) {
                            $is_series = true;
                            break;
                        }
                    }
                    if (!$is_series) {
                        $is_vod = true;
                    }
                } else {
                    // Auto-Detect
                    $group_lower = strtolower($current_group);
                    $is_series = false;
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
parseMoviesAndSeries();

// End of file