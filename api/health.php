<?php
/**
 * FinnTV Health Check
 * visit: finntv.vercel.app/api/health.php
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$json_path = (strpos($json_path, 'data/data.json') !== false) ? $json_path : 'Unknown';

echo json_encode([
    'status' => 'OK',
    'php_version' => PHP_VERSION,
    'memory_limit' => ini_get('memory_limit'),
    'data_json' => [
        'path' => $json_path,
        'exists' => file_exists($json_path),
        'size' => file_exists($json_path) ? filesize($json_path) : 0,
        'live_count' => isset($data['live_streams']) ? count($data['live_streams']) : 0,
        'movie_count' => isset($data['vod_streams']) ? count($data['vod_streams']) : 0,
        'series_count' => isset($data['series']) ? count($data['series']) : 0
    ],
    'm3u_dir' => [
        'path' => $server_config['m3u_dir'],
        'exists' => is_dir($server_config['m3u_dir'])
    ]
], JSON_PRETTY_PRINT);
