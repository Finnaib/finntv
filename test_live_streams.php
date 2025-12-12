<?php
// Test API: get_live_streams for a specific category
$_GET['username'] = 'test';
$_GET['password'] = 'test';
$_GET['action'] = 'get_live_streams';
$_GET['category_id'] = '2638742988'; // US Local from the output

ob_start();
require __DIR__ . '/api/player_api.php';
$output = ob_get_clean();

echo "=== GET_LIVE_STREAMS RESPONSE ===\n\n";
$data = json_decode($output, true);
if ($data && is_array($data)) {
    echo "Streams Count: " . count($data) . "\n\n";
    if (count($data) > 0) {
        echo "First stream sample:\n";
        print_r($data[0]);
    } else {
        echo "WARNING: Streams array is EMPTY for this category\n";
    }
} else {
    echo "ERROR: Invalid JSON response\n";
    echo substr($output, 0, 500) . "\n";
}
