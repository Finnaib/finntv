<?php
/**
 * Quick debug endpoint - Check if channels are loading
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

// Load config
require_once __DIR__ . '/config.php';

$debug_info = [
    'config_loaded' => true,
    'm3u_folder' => $server_config['m3u_folder'],
    'm3u_folder_exists' => file_exists($server_config['m3u_folder']),
    'category_map' => $category_map,
    'total_channels_loaded' => count($channels),
    'channels_by_category' => [],
    'sample_channels' => array_slice($channels, 0, 3),
    'users' => array_keys($users)
];

// Count channels by category
foreach ($channels as $ch) {
    $catId = $ch['category'];
    if (!isset($debug_info['channels_by_category'][$catId])) {
        $debug_info['channels_by_category'][$catId] = 0;
    }
    $debug_info['channels_by_category'][$catId]++;
}

// Test specific user
$test_user = 'test';
if (isset($users[$test_user])) {
    $user = $users[$test_user];
    $allowed = [];
    foreach ($user['categories'] as $c) {
        if (isset($category_map[$c])) {
            $allowed[] = $category_map[$c]['id'];
        }
    }

    $user_channels = 0;
    foreach ($channels as $ch) {
        if (in_array($ch['category'], $allowed)) {
            $user_channels++;
        }
    }

    $debug_info['test_user'] = [
        'username' => $test_user,
        'categories' => $user['categories'],
        'allowed_cat_ids' => $allowed,
        'total_channels_available' => $user_channels
    ];
}

echo json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>