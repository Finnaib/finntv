<?php
// Build Data Cache for Vercel
// This script parses the M3U files and saves the resulting $data array to data.json
// This avoids parsing 20k lines on every API request.

require_once 'config.php';

echo "Parsing M3U files...\n";
parseMoviesAndSeries();

echo "Stats:\n";
echo "  Live: " . count($data['live_streams']) . "\n";
echo "  VOD:  " . count($data['vod_streams']) . "\n";
echo "  Series: " . count($data['series']) . "\n";

// Save to data.json
$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($json === false) {
    die("Error: JSON Encoding failed! " . json_last_error_msg() . "\n");
}

// Write to data/ subdirectory (Writable by Docker)
$bytes = file_put_contents(__DIR__ . '/data/data.json', $json);
echo "Saved data/data.json (" . round($bytes / 1024 / 1024, 2) . " MB)\n";

// --- Build ID Map ---
echo "Building ID Map...\n";
$id_map = [];
foreach ($data['live_streams'] as $s) {
    $id_map[$s['num']] = $s['direct_source'];
}
foreach ($data['vod_streams'] as $s) {
    $id_map[$s['num']] = $s['direct_source'];
}
foreach ($data['series'] as $s) { // Series often don't have direct_source here but let's check structure
    if (isset($s['direct_source']))
        $id_map[$s['num']] = $s['direct_source'];
}

file_put_contents(__DIR__ . '/data/id_map.json', json_encode($id_map, JSON_UNESCAPED_SLASHES));
echo "Saved data/id_map.json\n";

echo "Success.\n";
