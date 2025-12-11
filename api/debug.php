<?php
chdir(__DIR__ . '/..');

header('Content-Type: application/json');

$debug = [
    'cwd' => getcwd(),
    'dir' => __DIR__,
    'parent_dir' => dirname(__DIR__),
    'm3u_folder' => __DIR__ . '/../m3u',
    'm3u_exists' => is_dir(__DIR__ . '/../m3u'),
    'm3u_files' => [],
    'config_loaded' => false,
    'channels_count' => 0
];

// Check if m3u directory exists and list files
if (is_dir(__DIR__ . '/../m3u')) {
    $debug['m3u_files'] = scandir(__DIR__ . '/../m3u');
}

// Try loading config
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
    $debug['config_loaded'] = true;
    $debug['channels_count'] = isset($channels) ? count($channels) : 0;
    $debug['category_map'] = $category_map ?? [];
    $debug['users'] = array_keys($users ?? []);
}

echo json_encode($debug, JSON_PRETTY_PRINT);
