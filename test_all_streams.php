<?php
// Test API: get_live_streams WITHOUT category filter (get all)
$_GET['username'] = 'test';
$_GET['password'] = 'test';
$_GET['action'] = 'get_live_streams';
// NO category_id parameter

ob_start();
require __DIR__ . '/api/player_api.php';
$output = ob_get_clean();

$data = json_decode($output, true);
if ($data && is_array($data)) {
    echo "Total Streams Retrieved: " . count($data) . "\n\n";
    if (count($data) > 0) {
        echo "First 2 streams:\n";
        for ($i = 0; $i < min(2, count($data)); $i++) {
            echo "\nStream #" . ($i + 1) . ":\n";
            echo "  Name: {$data[$i]['name']}\n";
            echo "  Category ID: {$data[$i]['category_id']}\n";
            echo "  Stream ID: {$data[$i]['stream_id']}\n";
        }
    }
}
