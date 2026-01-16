<?php
// Generate Optimized ID Map for Fast Stream Redirection
// Usage: php tools/generate_id_map.php

require_once __DIR__ . '/../config.php';

$map = [];

echo "Generating ID Map...\n";

// Live Streams
$count = 0;
foreach ($data['live_streams'] as $s) {
    if (isset($s['num']) && isset($s['direct_source'])) {
        $map[$s['num']] = $s['direct_source'];
        $count++;
    }
}
echo "Added {$count} Live Streams\n";

// VOD Streams
$count = 0;
foreach ($data['vod_streams'] as $s) {
    if (isset($s['num']) && isset($s['direct_source'])) {
        $map[$s['num']] = $s['direct_source'];
        $count++;
    }
}
echo "Added {$count} VOD Streams\n";

// Series
$count = 0;
foreach ($data['series'] as $s) {
    if (isset($s['num']) && isset($s['direct_source'])) {
        $map[$s['num']] = $s['direct_source'];
        $count++;
    }
}
echo "Added {$count} Series\n";

// Save
$outFile = __DIR__ . '/../id_map.json';
file_put_contents($outFile, json_encode($map));

echo "Saved map to {$outFile}\n";
echo "Total Streams: " . count($map) . "\n";
