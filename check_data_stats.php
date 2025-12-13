<?php
$json_path = __DIR__ . '/data/data.json';
if (!file_exists($json_path)) {
    echo "data.json not found!\n";
    exit;
}

$json = file_get_contents($json_path);
$data = json_decode($json, true);

if (!$data) {
    echo "Failed to decode data.json\n";
    exit;
}

echo "Live Streams: " . count($data['live_streams']) . "\n";
echo "Live Categories: " . count($data['live_categories']) . "\n";
echo "VOD Streams: " . count($data['vod_streams']) . "\n";
echo "VOD Categories: " . count($data['vod_categories']) . "\n";
echo "Series: " . count($data['series']) . "\n";
echo "Series Categories: " . count($data['series_categories']) . "\n";

// Print first live stream to check structure
if (!empty($data['live_streams'])) {
    echo "\nSample Live Stream:\n";
    $sample = $data['live_streams'][0];
    print_r($sample);

    // Check if category exists
    $found_cat = false;
    foreach ($data['live_categories'] as $c) {
        if ($c['category_id'] === $sample['category_id']) {
            $found_cat = true;
            echo "Category found: " . $c['category_name'] . "\n";
            break;
        }
    }
    if (!$found_cat) {
        echo "WARNING: Category ID " . $sample['category_id'] . " not found in live_categories!\n";
    }
}
