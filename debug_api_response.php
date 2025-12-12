<?php
// Mock Request
$_GET['username'] = 'test';
$_GET['password'] = 'test';
$_GET['action'] = 'get_vod_streams';
$_GET['category_id'] = '2444284409'; // Alwan Club 4K

// Capture output
ob_start();
require __DIR__ . '/core/player_api.php';
$output = ob_get_clean();

// Check if output is valid JSON
$data = json_decode($output, true);
if ($data) {
    echo "Streams Count: " . count($data) . "\n";
    if (count($data) > 0) {
        print_r($data[0]);
    } else {
        echo "No streams found for this category.\n";
    }
} else {
    echo "Invalid JSON Output:\n" . substr($output, 0, 500) . "\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}
