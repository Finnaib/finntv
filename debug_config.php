<?php
// Debug Config Parser
require_once 'config.php';

// Force parsing
parseMoviesAndSeries();

echo "Counts:\n";
echo "Live Streams: " . count($data['live_streams']) . "\n";
echo "VOD Streams: " . count($data['vod_streams']) . "\n";
echo "VOD Categories: " . count($data['vod_categories']) . "\n";

echo "\n--- Searching for Amazon ---\n";
if (!empty($data['vod_categories'])) {
    $target_cat_id = null;
    foreach ($data['vod_categories'] as $c) {
        if (stripos($c['category_name'], "Amazon") !== false) {
            print_r($c);
            $target_cat_id = $c['category_id'];
            break;
        }
    }

    if ($target_cat_id) {
        echo "\n--- Searching for stream with Cat ID: $target_cat_id ---\n";
        $found = 0;
        foreach ($data['vod_streams'] as $s) {
            if ($s['category_id'] == $target_cat_id) {
                // print_r($s); // too verbose
                $found++;
            }
        }
        echo "Total Found: $found\n";
    } else {
        echo "Amazon category not found in config!\n";
    }
} else {
    echo "No VOD categories found.\n";
}
