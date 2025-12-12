<?php
// Simulate Browser/Player Login Request
$_GET['username'] = 'test'; // Ensure this user exists in config.php
$_GET['password'] = 'test'; // Ensure this password matches
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost:8000'; // Simulation
$_SERVER['HTTPS'] = 'off';

// Capture API Output
ob_start();
require __DIR__ . '/api/player_api.php';
$output = ob_get_clean();

// Analyze
echo "--- RAW OUTPUT START ---\n";
echo $output . "\n";
echo "--- RAW OUTPUT END ---\n";

$json = json_decode($output, true);
if (!$json) {
    echo "CRITICAL: Invalid JSON returned.\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
} else {
    echo "JSON Valid.\n";
    if (isset($json['user_info']['auth'])) {
        echo "Auth Status: " . $json['user_info']['auth'] . "\n";
        if ($json['user_info']['auth'] == 0) {
            echo "Login FAILED. Check credentials.\n";
        } else {
            echo "Login SUCCESS.\n";
            print_r($json['server_info']);
        }
    } else {
        echo "Unexpected JSON Structure.\n";
    }
}
