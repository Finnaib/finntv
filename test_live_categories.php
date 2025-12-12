<?php
// Test API: get_live_categories
$_GET['username'] = 'test';
$_GET['password'] = 'test';
$_GET['action'] = 'get_live_categories';

ob_start();
require __DIR__ . '/api/player_api.php';
$output = ob_get_clean();

echo "=== GET_LIVE_CATEGORIES RESPONSE ===\n\n";
$data = json_decode($output, true);
if ($data && is_array($data)) {
    echo "Categories Count: " . count($data) . "\n\n";
    if (count($data) > 0) {
        echo "First 3 categories:\n";
        for ($i = 0; $i < min(3, count($data)); $i++) {
            print_r($data[$i]);
        }
    } else {
        echo "WARNING: Categories array is EMPTY\n";
    }
} else {
    echo "ERROR: Invalid JSON response\n";
    echo substr($output, 0, 500) . "\n";
}
