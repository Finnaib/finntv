<?php
/**
 * FinnTV Xtream Server V2 - Player API
 * Implements Xtream Codes API Actions
 */

require_once __DIR__ . '/../config.php';

// --- Helpers ---

function json_out($data)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE);
    exit;
}

// --- Auth ---

$username = $_GET['username'] ?? $_POST['username'] ?? '';
$password = $_GET['password'] ?? $_POST['password'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$is_auth = false;
$user_data = [];

if (isset($users_db[$username])) {
    $u = $users_db[$username];
    // Handle both old string format and new array format for backward compatibility
    $stored_pass = is_array($u) ? $u['password'] : $u;

    if ($stored_pass === $password) {
        $is_auth = true;
        // Parse metadata
        $created_at = (is_array($u) && !empty($u['created_at'])) ? $u['created_at'] : time();
        // If created_at is dynamic (null), exp is 1 year from NOW. 
        // If fixed, exp is 1 year from THEN.
        $exp_date = (is_array($u) && !empty($u['created_at']))
            ? strtotime('+1 year', $created_at)
            : strtotime('+1 year');

        $user_data = [
            'created_at' => $created_at,
            'exp_date' => $exp_date
        ];
    }
}

// --- Login Failure ---
if (!$is_auth) {
    json_out(['user_info' => ['auth' => 0]]);
}

// --- Define User Info Structure ---
$user_info = [
    'username' => $username,
    'password' => $password,
    'status' => 'Active',
    'auth' => 1,
    'active_cons' => 0,
    'max_connections' => 5, // Hardcoded Limit: 5 Devices
    'created_at' => (string) $user_data['created_at'],
    'exp_date' => (string) $user_data['exp_date'],
    'is_trial' => '0',
    'allowed_output_formats' => ['m3u8', 'ts', 'rtmp']
];

$server_info = [
    'url' => $server_config['base_url'],
    'port' => '80',
    'https_port' => '443',
    'server_protocol' => 'https',
    'rtmp_port' => '88',
    'timezone' => $server_config['timezone'],
    'timestamp_now' => time(),
    'time_now' => date("Y-m-d H:i:s", time()),
    'process' => true
];

// --- Action Router ---

switch ($action) {
    // 1. Login / Handshake
    case '':
    case 'get_panel_info':
        json_out([
            'user_info' => $user_info,
            'server_info' => $server_info
        ]);
        break;

    // 2. Live TV
    case 'get_live_categories':
        json_out($data['live_categories']);
        break;

    case 'get_live_streams':
        $cat_id = $_GET['category_id'] ?? null;
        $out = [];
        foreach ($data['live_streams'] as $s) {
            if ($cat_id && $s['category_id'] != $cat_id)
                continue;
            $out[] = $s;
        }
        json_out($out);
        break;

    // 3. Movies (VOD)
    case 'get_vod_categories':
        json_out($data['vod_categories']);
        break;

    case 'get_vod_streams':
        $cat_id = $_GET['category_id'] ?? null;
        $out = [];
        foreach ($data['vod_streams'] as $s) {
            if ($cat_id && $s['category_id'] != $cat_id)
                continue;
            $out[] = $s;
        }
        json_out($out);
        break;

    case 'get_vod_info':
        // Return dummy info to prevent errors on details page
        json_out([
            'info' => [
                'name' => 'Unknown Movie',
                'description' => 'No description available',
                'director' => '',
                'releasedate' => '',
                'rating' => '5',
                'movie_image' => '',
            ],
            'movie_data' => []
        ]);
        break;

    // 4. Series
    // Note: Parsing Series from M3U is complex.
    // Here we treat them as VOD categories for simplicity based on the Parser.
    // Some players check 'get_series' separate from VOD.
    case 'get_series_categories':
        json_out($data['series_categories']);
        break;

    case 'get_series':
        $cat_id = $_GET['category_id'] ?? null;
        $out = [];
        foreach ($data['series'] as $s) {
            if ($cat_id && $s['category_id'] != $cat_id)
                continue;
            // Simplify data for series list
            $out[] = [
                'num' => $s['num'],
                'name' => $s['name'],
                'series_id' => $s['num'],
                'cover' => $s['stream_icon'],
                'plot' => '',
                'cast' => '',
                'director' => '',
                'genre' => '',
                'releaseDate' => '',
                'last_modified' => (string) time(),
                'rating' => '5',
                'rating_5based' => '5',
                'backdrop_path' => [],
                'youtube_trailer' => '',
                'episode_run_time' => '0',
                'category_id' => $s['category_id']
            ];
        }
        json_out($out);
        break;

    case 'get_series_info':
        // When user clicks a series, player asks for episodes.
        // Since we parsed them as flat streams in 'series', we need to fake an episode.
        // Real implementation would group S01E01 etc.
        $id = $_GET['series_id'] ?? 0;

        // Find the stream source again (inefficient lookup but works for simple parsing)
        $found = null;
        foreach ($data['series'] as $s) {
            if ($s['num'] == $id) {
                $found = $s;
                break;
            }
        }

        $episodes = [];
        if ($found) {
            $episodes[] = [
                'id' => $found['num'],
                'episode_num' => 1,
                'title' => $found['name'],
                'container_extension' => 'mp4',
                'info' => [],
                'custom_sid' => '',
                'added' => (string) time(),
                'season' => 1,
                'direct_source' => ''
            ];
        }

        json_out([
            'seasons' => [
                [
                    'air_date' => '2023-01-01',
                    'episode_count' => 1,
                    'id' => 1,
                    'name' => 'Season 1',
                    'overview' => '',
                    'season_number' => 1,
                    'cover' => $found['cover'] ?? '',
                    'cover_big' => $found['cover'] ?? ''
                ]
            ],
            'episodes' => [
                "1" => $episodes
            ]
        ]);
        break;

    // Default
    default:
        json_out(['error' => 'Unknown Action']);
}

// End of file