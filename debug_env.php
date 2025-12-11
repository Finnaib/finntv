<?php
// Check CURL availability
echo "CURL: " . (function_exists('curl_init') ? 'Enabled' : 'Disabled') . "\n";

// Check if open_basedir is restrictive
echo "open_basedir: " . ini_get('open_basedir') . "\n";

// Check allow_url_fopen
echo "allow_url_fopen: " . ini_get('allow_url_fopen') . "\n";

// Check allowed_url_fopen + stream context
$ctx = stream_context_create(['http' => ['header' => 'User-Agent: VLC/3.0.0']]);
$content = @file_get_contents('http://google.com', false, $ctx);
echo "External Reachable (file_get_contents): " . ($content ? "Yes (" . strlen($content) . " bytes)" : "No") . "\n";

// Test player_api logic fully
require_once 'config.php';
// Mock params for player_api check
$username = 'finn';
$password = 'finn123';
$_GET['username'] = $username;
$_GET['password'] = $password;

// Test actual player_api.php output
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/xtream_server/player_api.php';
$_SERVER['HTTP_HOST'] = 'finntv.atwebpages.com';

ob_start();
include 'player_api.php';
$output = ob_get_clean();

echo "\n--- player_api.php Output ---\n";
echo $output;
echo "\n--- End Output ---\n";

// Validate JSON
$json = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "JSON Status: Valid\n";
    echo "User Info: " . print_r($json['user_info'] ?? 'Missing', true) . "\n";
} else {
    echo "JSON Status: Invalid (" . json_last_error_msg() . ")\n";
}
?>