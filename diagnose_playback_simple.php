<?php
// Mock Environment for live.php
// We need to set $_SERVER['REQUEST_URI'] to simulate /live/user/pass/123.ts
$_SERVER['REQUEST_URI'] = '/live/finn/finn123/1.ts';
$_SERVER['HTTP_USER_AGENT'] = 'TiviMate/4.7.0';
$_SERVER['REQUEST_METHOD'] = 'GET';
// Mock $_SERVER['DOCUMENT_ROOT'] if needed, but __DIR__ is used mostly.

// Trap output and headers
ob_start();
// Mock function to capture headers
if (!function_exists('x_header')) {
    function x_header($string)
    {
        echo "HEADER: $string\n";
    }
}
// We can't redefine header(), so we'll have to rely on the script being runnable via CLI
// and checking if it errors or prints the Location.
// Actually, running `live.php` directly in CLI might fail due to missing HTTP env.
// Let's create a wrapper that includes `core/live.php` but inspects what it DOES.

// Ideally, we want to know what $target_url becomes.
// Let's just include core/live.php but redefine internal stuff? No, too hard.

// Better approach: Create a standalone script that copies the logic of live.php regarding ID lookup
// and prints the result.

require_once __DIR__ . '/config.php';
$id = 1; // Assuming stream 1 exists (MBN from previous logs)

// Search logic from live.php
$target_url = "";
$map_file = __DIR__ . '/data/id_map.json';
if (file_exists($map_file)) {
    $id_map = json_decode(file_get_contents($map_file), true);
    if (isset($id_map[$id])) {
        $target_url = $id_map[$id];
        echo "Found via Map: $target_url\n";
    } else {
        echo "Not found in ID Map.\n";
    }
} else {
    echo "ID Map not found. Using linear search.\n";
    if (empty($data['live_streams'])) {
        // Need to populate $data if not loaded (config.php handles it if config.json exists)
        // config.php logic: parseMoviesAndSeries() is NOT called automatically in config.php
        // It's called in player_api.php.
        // So we need to call it here if data is empty.
        parseMoviesAndSeries();
    }

    foreach ($data['live_streams'] as $s) {
        if ($s['num'] == $id) {
            $target_url = $s['direct_source'];
            echo "Found via Linear Search: $target_url\n";
            break;
        }
    }
}

if ($target_url) {
    echo "Original Target URL: $target_url\n";

    // Test logic for TS rewriting
    $ext = 'ts';
    // Logic from live.php (Redirect Mode)
    // Universal Logic: Rewrite HLS to TS if requested (and it looks like HLS)
    // In live.php this was commented out for redirect mode!
    // // if ($ext === 'ts' && stripos($target_url, '.m3u8') !== false) {
    // // $target_url = str_replace('.m3u8', '.ts', $target_url);
    // // }

    echo "Checking reachability for: [$target_url]\n";
    $ch = curl_init($target_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'TiviMate/4.7.0'); // Emulate TiviMate

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    echo "HTTP Status: $http_code\n";
    if ($curl_error) {
        echo "Curl Error: $curl_error\n";
    }

    // Check if it's an m3u8
    if (strpos($target_url, '.m3u8') !== false) {
        echo "Target is M3U8. TiviMate requested TS. This might be the issue if TiviMate expects raw TS.\n";
    }
} else {
    echo "Stream ID $id not found.\n";
}
