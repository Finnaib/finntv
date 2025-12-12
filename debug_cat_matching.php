<?php
// Debug: Check what's happening with category filtering
$json = file_get_contents(__DIR__ . '/data.json');
$data = json_decode($json, true);

$test_cat_id = '2638742988'; // From earlier output

echo "Testing with category_id: '$test_cat_id'\n\n";

echo "Checking if this category exists in live_categories:\n";
$found = false;
foreach ($data['live_categories'] as $cat) {
    if ((string) $cat['category_id'] === (string) $test_cat_id) {
        echo "FOUND: {$cat['category_name']}\n";
        $found = true;
        break;
    }
}
if (!$found) {
    echo "NOT FOUND in live_categories\n";
    echo "First 3 live_categories for reference:\n";
    for ($i = 0; $i < min(3, count($data['live_categories'])); $i++) {
        echo "  - {$data['live_categories'][$i]['category_name']} (ID: {$data['live_categories'][$i]['category_id']})\n";
    }
}

echo "\nChecking if any live_streams have this category_id:\n";
$count = 0;
foreach ($data['live_streams'] as $stream) {
    if ((string) $stream['category_id'] === (string) $test_cat_id) {
        $count++;
    }
}
echo "Found $count streams with this category_id\n";
