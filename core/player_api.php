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

// Detect Protocol, Host and Port dynamically
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
}

// Extract hostname and port safely
if (preg_match('/^([^:]+):(\d+)$/', $host, $matches)) {
    $host_only = $matches[1];
    $port = $matches[2];
} else {
    $host_only = $host;
    $port = $_SERVER['SERVER_PORT'] ?? ($scheme === 'https' ? '443' : '80');
}

$server_info = [
    'url' => (string) $host_only,
    'port' => (string) $port,
    'https_port' => '443',
    'server_protocol' => (string) $scheme,
    'rtmp_port' => '88',
    'timezone' => (string) ($server_config['timezone'] ?? 'UTC'),
    'timestamp_now' => time(),
    'time_now' => date("Y-m-d H:i:s"),
    'process' => true,
    'server_name' => (string) ($server_config['server_name'] ?? 'FinnTV')
];

// --- Action Router ---

// Optimization: Only parse M3U files if we are NOT logging in. 
// This prevents timeouts during the handshake phase.
if ($action !== '' && $action !== 'get_panel_info') {
    parseMoviesAndSeries();
}

if ($action === '' || $action === 'get_panel_info') {
    // 1. Login / Handshake - Standard Pure Xtream Format
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

        // Universal Compatibility (Smarters + TiviMate)
        $s_id = (int) $s['stream_id'];
        $s_num = (int) $s['num'];

        $item = [
            'num' => $s_num,
            'name' => (string) $s['name'],
            'stream_type' => 'live',
            'stream_id' => $s_id,
            'stream_icon' => (string) ($s['stream_icon'] ?? ''),
            'epg_channel_id' => (string) ($s['epg_channel_id'] ?? ''),
            'added' => (string) ($s['added'] ?? time()),
            'category_id' => (string) $s['category_id'],
            'custom_sid' => (string) ($s['custom_sid'] ?? ""),
            'tv_archive' => (int) ($s['tv_archive'] ?? 0),
            'direct_source' => "",
            'tv_archive_duration' => (int) ($s['tv_archive_duration'] ?? 0),
            'thumbnail' => (string) ($s['stream_icon'] ?? ''),
            'is_adult' => 0
        ];

        // --- BALANCED PRUNING for FULL SYNC ---
        // Restore icons but strip hidden fields to stay under 4.5MB limit.
        if (!$cat_id) {
            unset($item['added']);
            unset($item['custom_sid']);
            unset($item['tv_archive_duration']);
        }

        $out[] = $item;
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
        // Filter by category if requested
        if ($cat_id && (string) $s['category_id'] !== (string) $cat_id)
            continue;

        // Compliance Fixes - Pure VOD Metadata
        $item = [
            'num' => (int) $s['num'],
            'name' => (string) $s['name'],
            'stream_id' => (int) $s['stream_id'],
            'stream_icon' => (string) ($s['stream_icon'] ?? ''),
            'added' => (string) ($s['added'] ?? time()),
            'category_id' => (string) $s['category_id'],
            'container_extension' => (string) ($s['container_extension'] ?? 'mp4'),
            'rating' => (string) ($s['rating'] ?? '5'),
            'rating_5based' => 5,
            'custom_sid' => (string) ($s['custom_sid'] ?? ""),
            'direct_source' => ""
        ];

        // --- BALANCED VOD PRUNING & LIMIT ---
        // For the FULL list, we MUST stay under Vercel's 4.5MB limit.
        // 16,000 items * ~250 bytes = 4.0MB (Safe Buffer).
        if (!$cat_id) {
            // Restore icons so Smarters shows the movie posters!
            unset($item['added']);
            unset($item['rating']);
            unset($item['rating_5based']);
            unset($item['custom_sid']);

            // Hard Limit for stability in "All" view
            if (count($out) >= 16000) {
                break;
            }
        }

        $out[] = $item;
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