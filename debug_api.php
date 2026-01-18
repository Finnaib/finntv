<?php
require_once 'config.php';
$_GET['username'] = 'finn';
$_GET['password'] = 'finn123';
$_GET['action'] = 'get_live_streams';

ob_start();
require 'core/player_api.php';
$output = ob_get_clean();

echo "Response Size: " . strlen($output) . " bytes\n";
$data = json_decode($output, true);
if ($data === null) {
    echo "JSON Decode Error: " . json_last_error_msg() . "\n";
    echo "First 100 bytes: " . substr($output, 0, 100) . "\n";
} else {
    echo "Count: " . count($data) . " items\n";
    if (count($data) > 0) {
        echo "First Item: " . json_encode($data[0], JSON_PRETTY_PRINT) . "\n";
    }
}
