<?php
require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
$id = 1; // MBN

// Search
$target_url = "";
$map_file = __DIR__ . '/data/id_map.json';
$map_file = __DIR__ . '/id_map.json';
if (file_exists($map_file)) {
    echo "Map file found.\n";
    $id_map = json_decode(file_get_contents($map_file), true);
    if (isset($id_map[$id])) {
        $target_url = $id_map[$id];
        echo "Found in Map: $target_url\n";
    } else {
        echo "ID $id not in map.\n";
    }
} else {
    echo "Map file not found.\n";
}

if ($target_url) {
    echo "Original: $target_url\n";

    // Check Original (M3U8)
    check_url($target_url, "Original (M3U8)");

    // Check TS Variant
    if (strpos($target_url, '.m3u8') !== false) {
        $ts_url = str_replace('.m3u8', '.ts', $target_url);
        echo "\nTesting TS Variant: $ts_url\n";
        check_url($ts_url, "TS Variant");
    }
}

function check_url($url, $label)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'TiviMate/4.7.0');
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    echo "[$label] Status: $code, Content-Type: $type\n";
}
