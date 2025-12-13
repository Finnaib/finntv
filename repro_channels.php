<?php
// Mock Environment
$_SERVER['HTTP_HOST'] = 'localhost:8000';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get_live_streams';
$_GET['username'] = 'finn';
$_GET['password'] = 'finn123';

// Capture output
ob_start();
require __DIR__ . '/core/player_api.php';
$content = ob_get_clean();

// Analyze output
echo "Output Size: " . strlen($content) . " bytes (" . round(strlen($content) / 1024 / 1024, 2) . " MB)\n";
$json = json_decode($content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Error: " . json_last_error_msg() . "\n";
    // Check for PHP errors in the output (e.g. valid JSON starts with [ or {)
    if ($content[0] !== '[' && $content[0] !== '{') {
        echo "Output does not start with JSON! First 100 chars:\n" . substr($content, 0, 100) . "\n";
    }
} else {
    echo "JSON Valid. Count: " . count($json) . "\n";
    if (count($json) > 0) {
        $first = $json[0];
        // Print keys to verify structure
        echo "Keys in first item: " . implode(", ", array_keys($first)) . "\n";

        // Print tv_archive and added
        echo "tv_archive: " . $first['tv_archive'] . "\n";
        echo "added: " . $first['added'] . "\n";
        echo "epg_channel_id: " . ($first['epg_channel_id'] ?? "MISSING") . "\n";

        // Print category_id type and value
        echo "Category ID Type: " . gettype($first['category_id']) . " Value: " . $first['category_id'] . "\n";

        // Check for duplicate keys or weirdness? No, json_decode handles that.
    }
}
