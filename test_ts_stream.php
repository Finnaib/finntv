<?php
require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$map_file = __DIR__ . '/id_map.json';
if (file_exists($map_file)) {
    $id_map = json_decode(file_get_contents($map_file), true);
    $url = $id_map[1] ?? ''; // MBN

    if ($url && strpos($url, '.m3u8') !== false) {
        $ts_url = str_replace('.m3u8', '.ts', $url);
        echo "Testing: $ts_url\n";

        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 5,
                'user_agent' => 'TiviMate/4.7.0'
            ]
        ]);

        $headers = get_headers($ts_url, 1);
        if ($headers) {
            print_r($headers);
        } else {
            echo "Failed to get headers.\n";
        }
    } else {
        echo "URL not suitable for test: $url\n";
    }
} else {
    echo "Map not found.\n";
}
