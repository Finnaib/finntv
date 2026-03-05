<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
echo json_encode([
    'live_categories' => count($data['live_categories']),
    'vod_categories' => count($data['vod_categories']),
    'series_categories' => count($data['series_categories']),
    'live_streams' => count($data['live_streams']),
    'vod_streams' => count($data['vod_streams']),
    'series' => count($data['series'])
]);
