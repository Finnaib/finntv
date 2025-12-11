<?php
// Returns a JSON list of available channels with id & name for mapping stream IDs
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$out = [];
foreach ($channels as $ch) {
    $out[] = [
        'id' => $ch['id'],
        'name' => $ch['name'],
        'category' => $categories[$ch['category']]['category_name'] ?? (string)$ch['category'],
        'direct_source' => $ch['url'] ?? '',
    ];
}

$meta = [
    'base_url' => $server_config['base_url'] ?? null,
    // Use proxy.php template so clients request proxied streams by default
    'stream_url_template' => rtrim($server_config['base_url'] ?? '', '/') . '/proxy.php/{username}/{password}/{stream_id}.m3u8'
];

echo json_encode(['meta' => $meta, 'channels' => $out], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
