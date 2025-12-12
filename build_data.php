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

$bytes = file_put_contents('data.json', $json);
echo "Saved data.json (" . round($bytes / 1024 / 1024, 2) . " MB)\n";
echo "Success.\n";
