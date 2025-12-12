<?php
// Check data.json structure
$file = __DIR__ . '/data.json';
if (!file_exists($file)) {
    echo "ERROR: data.json not found at: $file\n";
    exit(1);
}

$json = file_get_contents($file);
$data = json_decode($json, true);

if (!$data) {
    echo "ERROR: Invalid JSON\n";
    exit(1);
}

echo "=== DATA.JSON STRUCTURE ===\n\n";

$keys = ['live_streams', 'live_categories', 'vod_streams', 'vod_categories', 'series', 'series_categories'];
foreach ($keys as $key) {
    if (isset($data[$key])) {
        $count = count($data[$key]);
        echo "$key: $count items\n";

        // Show first item for live_streams specifically
        if ($key === 'live_streams' && $count > 0) {
            echo "\nFirst Live Stream Sample:\n";
            print_r($data[$key][0]);
            echo "\n";
        }
    } else {
        echo "$key: MISSING\n";
    }
}
