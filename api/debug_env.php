<?php
header('Content-Type: text/plain');
echo "Checking environment...\n";
echo "CWD: " . getcwd() . "\n";
echo "__DIR__: " . __DIR__ . "\n";

$files = [
    '../data/data.json',
    '../data/id_map.json',
    '../config.php',
    '../xtream_config.json'
];

foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    echo "File: $f -> " . (file_exists($path) ? "EXISTS" : "MISSING") . " (" . realpath($path) . ")\n";
    if (file_exists($path)) {
        echo "  Size: " . filesize($path) . " bytes\n";
        $content = file_get_contents($path, false, null, 0, 100);
        echo "  Preview: " . substr($content, 0, 50) . "...\n";
    }
}
