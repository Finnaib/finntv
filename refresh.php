<?php
// Force Refresh Data Cache (Web Friendly)
// Access this via http://your-server/refresh.php to rebuild the list.

require_once 'config.php';

// Security: Simple protection (Optional: Add a password check here if needed)
// if ($_GET['key'] !== 'secret') die('Access Denied');

echo "<h1>Refreshing Server Data...</h1>";

// 1. Force Clear Data to trigger re-parse
$data = [
    'live_streams' => [],
    'vod_streams' => [],
    'series' => [],
    'live_categories' => [],
    'vod_categories' => [],
    'series_categories' => []
];

// 2. Run Parser
echo "<p>Scanning M3U files...</p>";
parseMoviesAndSeries();

echo "<pre>";
echo "Found:\n";
echo "  Live: " . count($data['live_streams']) . "\n";
echo "  VOD:  " . count($data['vod_streams']) . "\n";
echo "  Series: " . count($data['series']) . "\n";
echo "</pre>";

// 3. Save Cache
$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$file = __DIR__ . '/data/data.json';
$bytes = file_put_contents($file, $json);

// 4. Update ID Map
$id_map = [];
foreach ($data['live_streams'] as $s)
    $id_map[$s['num']] = $s['direct_source'];
foreach ($data['vod_streams'] as $s)
    $id_map[$s['num']] = $s['direct_source'];
foreach ($data['series'] as $s)
    if (isset($s['direct_source']))
        $id_map[$s['num']] = $s['direct_source'];
file_put_contents(__DIR__ . '/data/id_map.json', json_encode($id_map, JSON_UNESCAPED_SLASHES));

if ($bytes) {
    echo "<h2 style='color:green'>Success! Saved " . round($bytes / 1024 / 1024, 2) . " MB.</h2>";
    echo "<p>Your apps should now see the new logos immediately (you may need to Reload the app).</p>";
} else {
    echo "<h2 style='color:red'>Error: Could not write to data.json. Check permissions.</h2>";
}
?>