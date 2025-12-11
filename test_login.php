<?php
/**
 * Test Login Script - Quick diagnosis
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

// Load config
require_once __DIR__ . '/config.php';

echo "<h1>Login Diagnosis Test</h1>";
echo "<hr>";

// Test credentials
$test_username = 'test';
$test_password = 'test123';

echo "<h2>1. Testing user credentials</h2>";
echo "Username: <strong>$test_username</strong><br>";
echo "Password: <strong>$test_password</strong><br><br>";

// Check if user exists
if (isset($users[$test_username])) {
    echo "✅ User '$test_username' exists in config<br>";
    $user = $users[$test_username];
    
    echo "<h3>User Details:</h3>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    // Check password
    if ($user['pass'] === $test_password) {
        echo "✅ Password matches<br>";
    } else {
        echo "❌ Password mismatch<br>";
    }
    
    // Check categories
    echo "<h3>User Categories:</h3>";
    echo "<pre>";
    print_r($user['categories']);
    echo "</pre>";
    
    // Check allowed channels
    echo "<h3>Checking channel access...</h3>";
    $allowed = [];
    foreach ($user['categories'] as $c) {
        if (isset($category_map[$c])) {
            $allowed[] = $category_map[$c]['id'];
            echo "✅ Category '$c' → ID: {$category_map[$c]['id']}<br>";
        } else {
            echo "❌ Category '$c' NOT FOUND in category_map<br>";
        }
    }
    
    echo "<h3>Total Channels Available:</h3>";
    echo "Total channels loaded: <strong>" . count($channels) . "</strong><br>";
    
    // Filter channels for this user
    $user_channels = [];
    foreach ($channels as $ch) {
        if (in_array($ch['category'], $allowed)) {
            $user_channels[] = $ch;
        }
    }
    
    echo "Channels for this user: <strong>" . count($user_channels) . "</strong><br>";
    
    if (count($user_channels) > 0) {
        echo "<h3>Sample Channels (first 5):</h3>";
        echo "<pre>";
        print_r(array_slice($user_channels, 0, 5));
        echo "</pre>";
    } else {
        echo "❌ <strong>NO CHANNELS AVAILABLE FOR THIS USER!</strong><br>";
        echo "<p style='color:red'>This is the problem! The user has access to category 'xtream' but no channels are being loaded from xtream.m3u</p>";
    }
    
} else {
    echo "❌ User '$test_username' NOT FOUND in config<br>";
}

echo "<hr>";
echo "<h2>2. Testing M3U File Loading</h2>";
$m3u_file = $server_config['m3u_folder'] . 'xtream.m3u';
echo "M3U file path: <strong>$m3u_file</strong><br>";

if (file_exists($m3u_file)) {
    echo "✅ File exists<br>";
    echo "File size: " . filesize($m3u_file) . " bytes<br>";
    
    // Test parsing
    $test_channels = parseM3U($m3u_file, 6);
    echo "Parsed channels: <strong>" . count($test_channels) . "</strong><br>";
    
    if (count($test_channels) > 0) {
        echo "<h3>First 3 channels from xtream.m3u:</h3>";
        echo "<pre>";
        print_r(array_slice($test_channels, 0, 3));
        echo "</pre>";
    }
} else {
    echo "❌ File does NOT exist<br>";
}

echo "<hr>";
echo "<h2>3. Testing Player API Response</h2>";
$api_url = "./player_api.php?username=$test_username&password=$test_password&action=get_live_streams";
echo "API URL: <a href='$api_url' target='_blank'>$api_url</a><br>";
echo "<button onclick=\"window.open('$api_url', '_blank')\">Test API</button>";
?>
