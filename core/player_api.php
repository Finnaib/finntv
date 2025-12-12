<?php
/**
 * FinnTV Xtream Server V2 - Player API
 * Implements Xtream Codes API Actions
 */

ob_start();

// Handle CORS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("HTTP/1.1 200 OK");
    exit;
}

require_once __DIR__ . '/../config.php';

// --- Helpers ---

function json_out($data)
{
    // Clear any previous output (whitespace, warnings)
    if (ob_get_length())
        ob_clean();

    header('Content-Type: application/json; charset=utf-8');
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE);

    if ($json === false) {
        // Fallback for encoding errors
        echo json_encode(['error' => 'JSON Encoding Failed: ' . json_last_error_msg()]);
    } else {
        echo $json;
    }
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
        // Logic: First-Login Persistence for Dynamic Users
        $created_at = null;

        if (is_array($u) && !empty($u['created_at'])) {
            // Case A: Fixed Date in Config
            $created_at = $u['created_at'];
        }

        // Case B: Dynamic User (First Login Detection)
        // Fix for Vercel/Serverless: Use /tmp if local data dir is not writable
        $local_data_dir = __DIR__ . '/../data';
        $dyn_file = is_writable($local_data_dir)
            ? $local_data_dir . '/users_dynamic.json'
            : sys_get_temp_dir() . '/users_dynamic.json';

        $dyn_db = [];

        // Try to read existing DB
        if (file_exists($dyn_file)) {
            $json = file_get_contents($dyn_file);
            $dyn_db = json_decode($json, true) ?? [];
        }

        if (isset($dyn_db[$username])) {
            // User has logged in before, retrieve original date
            $created_at = $dyn_db[$username];
        } else {
            // First time login! Save NOW as start date.
            $created_at = time();
            $dyn_db[$username] = $created_at;
            @file_put_contents($dyn_file, json_encode($dyn_db));
        }


        // Calculate Expiration (1 Year from Created Date)
        $exp_date = strtotime('+1 year', $created_at);

        // Check Expiration
        if (time() > $exp_date) {
            $is_auth = false;
        } else {
            $is_auth = true;
            $user_data = [
                'created_at' => $created_at,
                'exp_date' => $exp_date
            ];
        }
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

// Optimization: Only parse M3U files if we are NOT logging in. 
// This prevents timeouts during the handshake phase.
if ($action !== '' && $action !== 'get_panel_info') {
    parseMoviesAndSeries();
}

if ($action === '' || $action === 'get_panel_info') {
    // 1. Login / Handshake
    json_out([
        'user_info' => $user_info,
        'server_info' => $server_info
    ]);

} elseif ($action === 'get_live_categories') {
    // 2. Live Categories
    json_out($data['live_categories']);

} elseif ($action === 'get_live_streams') {
    // 3. Live Streams
    $cat_id = $_GET['category_id'] ?? null;
    $out = [];
    foreach ($data['live_streams'] as $s) {
        if ($cat_id && $s['category_id'] != $cat_id)
            continue;
        $out[] = $s;
    }
    json_out($out);

} elseif ($action === 'get_vod_categories') {
    // 4. VOD Categories
    json_out($data['vod_categories']);

} elseif ($action === 'get_vod_streams') {
    // 5. VOD Streams
    $cat_id = $_GET['category_id'] ?? null;
    $out = [];
    foreach ($data['vod_streams'] as $s) {
        if ($cat_id && $s['category_id'] != $cat_id)
            continue;

        // Optimize: Always remove large internal fields
        unset($s['direct_source']);
        unset($s['uniq_id']);

        // Smart Optimization: If requesting ALL streams, remove icons to prevent >4.5MB payload
        if (!$cat_id) {
            unset($s['stream_icon']);
        }

        $out[] = $s;
    }
    json_out($out);

} elseif ($action === 'get_series_categories') {
    // 6. Series Categories
    json_out($data['series_categories']);

} elseif ($action === 'get_series') {
    // 7. Series List
    $cat_id = $_GET['category_id'] ?? null;
    $out = [];
    foreach ($data['series'] as $s) {
        if ($cat_id && $s['category_id'] != $cat_id)
            continue;

        // Return series format
        $out[] = [
            'num' => $s['num'],
            'name' => $s['name'],
            'series_id' => $s['num'],
            'cover' => $s['cover'],
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

} elseif ($action === 'get_series_info') {
    // 8. Series Info (Episodes)
    // Fake 1 Season / 1 Episode mapping
    $id = $_GET['series_id'] ?? 0;

    // Find Series
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
        'episodes' => ["1" => $episodes]
    ]);

} elseif ($action === 'get_vod_info') {
    // 9. VOD Info (Dummy)
    $vod_id = $_GET['vod_id'] ?? 0;
    json_out([
        'info' => [
            'name' => 'Unknown Movie',
            'description' => 'No description available.',
            'director' => '',
            'releasedate' => '',
            'genre' => '',
            'cast' => '',
            'rating' => '5',
            'duration' => '',
            'poster_url' => ''
        ],
        'movie_data' => [
            'stream_id' => $vod_id,
            'container_extension' => 'mp4',
            'name' => 'Unknown Movie'
        ]
    ]);

} elseif ($action === 'get_stats') {
    // 10. Admin Stats
    if ($username !== 'shoaibwwe01@gmail.com') {
        json_out(['error' => 'Unauthorized']);
        return;
    }

    json_out([
        'live_streams' => count($data['live_streams']),
        'vod_streams' => count($data['vod_streams']),
        'series' => count($data['series']),
        'users' => count($users_db),
        'uptime' => 'Running on Vercel'
    ]);

} elseif ($action === 'get_users') {
    // 11. Admin Users
    if ($username !== 'shoaibwwe01@gmail.com') {
        json_out(['error' => 'Unauthorized']);
        return;
    }

    $display_users = [];
    foreach ($users_db as $u => $p) {
        $pass = is_array($p) ? $p['password'] : $p;
        $created = (is_array($p) && !empty($p['created_at'])) ? date("Y-m-d", $p['created_at']) : "Dynamic";
        $exp = (is_array($p) && empty($p['created_at'])) ? "Dynamic (+1 Year)" : date("Y-m-d", strtotime('+1 year', $p['created_at']));

        $display_users[] = [
            'username' => $u,
            'password' => $pass,
            'created' => $created,
            'exp' => $exp,
            'max_connections' => 5,
            'status' => 'Active'
        ];
    }
    json_out($display_users);

} else {
    // Default
    json_out(['error' => 'Unknown Action']);
}

// End of file