<?php
// Mock server vars for local test
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';

require_once 'config.php';

echo "\n--- PARSER RESULTS ---\n";
echo "Live Streams: " . count($data['live_streams']) . "\n";
echo "VOD Streams: " . count($data['vod_streams']) . "\n";
echo "Series Episodes: " . count($data['series']) . "\n";

if (count($data['live_streams']) > 0) {
    echo "\n[First Live Stream]\n";
    print_r($data['live_streams'][0]);
}

if (count($data['vod_streams']) > 0) {
    echo "\n[First Movie]\n";
    print_r($data['vod_streams'][0]);
}
