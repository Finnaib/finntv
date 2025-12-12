<?php
// Check actual category IDs in live_streams
$json = file_get_contents(__DIR__ . '/data.json');
$data = json_decode($json, true);

echo "=== CATEGORY ID ANALYSIS ===\n\n";

// Get unique category IDs from live_streams
$cat_ids = [];
foreach ($data['live_streams'] as $stream) {
    $cat_id = $stream['category_id'];
    if (!isset($cat_ids[$cat_id])) {
        $cat_ids[$cat_id] = 0;
    }
    $cat_ids[$cat_id]++;
}

echo "Unique Category IDs in live_streams (" . count($cat_ids) . " total):\n\n";
$count = 0;
foreach ($cat_ids as $id => $stream_count) {
    echo "Category ID: '$id' (type: " . gettype($id) . ") - $stream_count streams\n";
    $count++;
    if ($count >= 5)
        break;
}

echo "\n\nChecking live_categories:\n";
for ($i = 0; $i < min(3, count($data['live_categories'])); $i++) {
    $cat = $data['live_categories'][$i];
    echo "Category: {$cat['category_name']} - ID: '{$cat['category_id']}' (type: " . gettype($cat['category_id']) . ")\n";
}
