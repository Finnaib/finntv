<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$file = __DIR__ . '/data.json';
if (!file_exists($file)) {
    echo "data.json not found\n";
    exit;
}

$content = file_get_contents($file);
$data = json_decode($content, true);

if ($data === null) {
    echo "JSON Decode Error: " . json_last_error_msg() . "\n";
} else {
    echo "JSON Loaded Successfully.\n";
    echo "Live Config Params: " . (count($data) > 0 ? "Yes" : "No") . "\n";

    $keys = ['live_streams', 'live_categories', 'vod_streams', 'vod_categories', 'series', 'series_categories'];
    foreach ($keys as $k) {
        if (isset($data[$k])) {
            echo "$k: " . count($data[$k]) . "\n";
            // Print first item of vod_streams and series to check structure
            if (($k == 'vod_streams' || $k == 'series') && count($data[$k]) > 0) {
                echo "Sample $k: " . print_r($data[$k][0], true) . "\n";
            }
        } else {
            echo "$k: MISSING\n";
        }
    }
}
