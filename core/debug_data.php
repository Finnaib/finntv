<?php
// core/debug_data.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Current Directory: " . getcwd() . "\n";
echo "Dir of this file: " . __DIR__ . "\n";

require_once __DIR__ . '/../config.php';

echo "M3U Folder Config: " . $server_config['m3u_folder'] . "\n";

if (!is_dir($server_config['m3u_folder'])) {
    echo "ERROR: M3U Folder does not exist!\n";
} else {
    echo "M3U Folder exists.\n";
    $files = scandir($server_config['m3u_folder']);
    echo "Files in M3U folder: " . implode(", ", $files) . "\n";
}

echo "Loading channels...\n";
$channels = loadAllChannels($category_map, $server_config);
echo "Total Channels Loaded: " . count($channels) . "\n";

if (count($channels) > 0) {
    echo "First Channel: " . print_r($channels[0], true) . "\n";
} else {
    echo "No channels found. Checking first category map...\n";
    $firstCat = reset($category_map);
    echo "Trying to parse: " . $server_config['m3u_folder'] . $firstCat['file'] . "\n";
    if (file_exists($server_config['m3u_folder'] . $firstCat['file'])) {
        echo "File exists. Content preview:\n";
        echo substr(file_get_contents($server_config['m3u_folder'] . $firstCat['file']), 0, 200) . "\n";
    } else {
        echo "File not found!\n";
    }
}
?>