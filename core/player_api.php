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
    // Debug Logging (Commented out)
    // file_put_contents(__DIR__ . '/../debug_json_in.txt', print_r($data, true));

    // Clear any previous output (whitespace, warnings)
    if (ob_get_length())
        ob_clean();

    // CORS Headers
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

    header('Content-Type: application/json; charset=utf-8');
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);

    // file_put_contents(__DIR__ . '/../debug_json_out.txt', $json);

    if ($json === false) {
        $err = 'JSON Encoding Failed: ' . json_last_error_msg();
        file_put_contents(__DIR__ . '/../debug_json_error.txt', $err);
        echo json_encode(['error' => $err]);
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
    'username' => (string) $username,
    'password' => (string) $password,
    'message' => 'Login Successful',
    'auth' => 1,
    'status' => 'Active',
    'exp_date' => (string) ($user_data['exp_date'] ?? strtotime('+1 year')),
    'is_trial' => '0',
    'active_cons' => '0',
    'created_at' => (string) ($user_data['created_at'] ?? time()),
    'max_connections' => '5',
    'allowed_output_formats' => ['m3u8', 'ts', 'rtmp']
];

// Detect Protocol from Base URL
$p_parts = parse_url($server_config['base_url']);
$scheme = $p_parts['scheme'] ?? 'http';
$host = $p_parts['host'] ?? 'localhost';
$port = $p_parts['port'] ?? ($scheme === 'https' ? '443' : '80');

$server_info = [
    'url' => $host, // Strict Hostname
    'port' => (string) $port,
    'https_port' => (string) ($scheme === 'https' ? $port : '443'),
    'server_protocol' => $scheme,
    'rtmp_port' => '88',
    'timezone' => $server_config['timezone'],
    'timestamp_now' => time(),
    'time_now' => date("Y-m-d H:i:s", time()),
    'process' => true,
    'server_name' => $server_config['server_name'] ?? 'Xtream Server' // Required by some
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
        // String-safe comparison
        if ($cat_id && (string) $s['category_id'] !== (string) $cat_id)
            continue;

        // Remove direct_source for TiviMate compatibility
        // TiviMate builds stream URLs itself using the Xtream format
        unset($s['direct_source']);

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
        // String-safe comparison
        if ($cat_id && (string) $s['category_id'] !== (string) $cat_id)
            continue;

        // Strip largest non-essential fields for ALL responses
        unset($s['direct_source']);
        unset($s['uniq_id']);
        unset($s['group_title']);

        // Optimize FULL SYNC (no cat_id requested) to stay under Vercel's 4.5MB limit
        if (!$cat_id) {
            unset($s['stream_id']); // Identical to 'num', saves ~0.4MB
            unset($s['added']);
            unset($s['rating']);
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
        // String-safe comparison
        if ($cat_id && (string) $s['category_id'] !== (string) $cat_id)
            continue;

        // Return series format
        $out[] = [
            'num' => $s['num'],
            'name' => $s['name'],
            'series_id' => $s['num'],
            'cover' => $s['cover'] ?? $s['stream_icon'] ?? "",
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
    // 8. Series Info (Episodes) - PROXY TO UPSTREAM
    $series_id = $_GET['series_id'] ?? 0;

    // Load Upstream Credentials
    $conf_file = __DIR__ . '/../xtream_config.json';
    if (file_exists($conf_file)) {
        $c = json_decode(file_get_contents($conf_file), true);
        $u_host = $c['host'] ?? '';
        $u_user = $c['username'] ?? '';
        $u_pass = $c['password'] ?? '';

        if ($u_host && $u_user && $u_pass) {
            // Build Upstream URL
            $url = "{$u_host}/player_api.php?username={$u_user}&password={$u_pass}&action=get_series_info&series_id={$series_id}";

            // Fetch from Upstream
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $resp = curl_exec($ch);
            curl_close($ch);

            if ($resp) {
                // Return upstream response directly
                // We don't cache this as it's dynamic
                header('Content-Type: application/json');
                echo $resp;
                exit;
            }
        }
    }

    // Fallback if upstream fails (Original Logic)
    // Find Series
    $found = null;
    foreach ($data['series'] as $s) {
        if ($s['num'] == $series_id) {
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
    // 9. VOD Info - PROXY TO UPSTREAM
    $vod_id = $_GET['vod_id'] ?? 0;

    // Load Upstream Credentials
    $conf_file = __DIR__ . '/../xtream_config.json';
    if (file_exists($conf_file)) {
        $c = json_decode(file_get_contents($conf_file), true);
        $u_host = $c['host'] ?? '';
        $u_user = $c['username'] ?? '';
        $u_pass = $c['password'] ?? '';

        if ($u_host && $u_user && $u_pass) {
            $url = "{$u_host}/player_api.php?username={$u_user}&password={$u_pass}&action=get_vod_info&vod_id={$vod_id}";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $resp = curl_exec($ch);
            curl_close($ch);
            if ($resp) {
                header('Content-Type: application/json');
                echo $resp;
                exit;
            }
        }
    }

    // Fallback
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