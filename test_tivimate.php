<?php
/**
 * TiviMate Connection Tester
 * Tests all API endpoints that TiviMate uses
 */

header('Content-Type: text/html; charset=utf-8');

$test_user = 'finn';
$test_pass = 'finn123';
$base_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/";

echo "<!DOCTYPE html><html><head><title>TiviMate API Tester</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#0f0;}";
echo ".pass{color:#0f0;}.fail{color:#f00;}.section{margin:20px 0;padding:15px;background:#2a2a2a;border-left:4px solid #0f0;}";
echo "pre{background:#000;padding:10px;overflow-x:auto;max-height:300px;overflow-y:auto;}</style></head><body>";

echo "<h1>🔍 TiviMate API Connection Test</h1>";
echo "<p><strong>Base URL:</strong> $base_url</p>";
echo "<p><strong>Test User:</strong> $test_user</p>";

// Test 1: Authentication
echo "<div class='section'>";
echo "<h2>Test 1: Authentication (player_api.php)</h2>";
$auth_url = $base_url . "player_api.php?username=$test_user&password=$test_pass";
echo "<p><strong>URL:</strong> <a href='$auth_url' target='_blank'>$auth_url</a></p>";

$auth_response = @file_get_contents($auth_url);
if ($auth_response) {
    $auth_data = json_decode($auth_response, true);
    if (isset($auth_data['user_info']['auth']) && $auth_data['user_info']['auth'] == 1) {
        echo "<p class='pass'>✓ Authentication PASSED</p>";
        echo "<pre>" . json_encode($auth_data, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p class='fail'>✗ Authentication FAILED</p>";
        echo "<pre>$auth_response</pre>";
    }
} else {
    echo "<p class='fail'>✗ Cannot connect to API</p>";
}
echo "</div>";

// Test 2: Live Categories
echo "<div class='section'>";
echo "<h2>Test 2: Live Categories</h2>";
$cat_url = $base_url . "player_api.php?username=$test_user&password=$test_pass&action=get_live_categories";
echo "<p><strong>URL:</strong> <a href='$cat_url' target='_blank'>$cat_url</a></p>";

$cat_response = @file_get_contents($cat_url);
if ($cat_response) {
    $cat_data = json_decode($cat_response, true);
    $count = is_array($cat_data) ? count($cat_data) : 0;
    if ($count > 0) {
        echo "<p class='pass'>✓ Found $count categories</p>";
        echo "<pre>" . json_encode(array_slice($cat_data, 0, 5), JSON_PRETTY_PRINT) . "\n... (showing first 5)</pre>";
    } else {
        echo "<p class='fail'>✗ No categories found</p>";
        echo "<pre>$cat_response</pre>";
    }
} else {
    echo "<p class='fail'>✗ Cannot fetch categories</p>";
}
echo "</div>";

// Test 3: Live Streams
echo "<div class='section'>";
echo "<h2>Test 3: Live Streams (All)</h2>";
$streams_url = $base_url . "player_api.php?username=$test_user&password=$test_pass&action=get_live_streams";
echo "<p><strong>URL:</strong> <a href='$streams_url' target='_blank'>$streams_url</a></p>";

$streams_response = @file_get_contents($streams_url);
if ($streams_response) {
    $streams_data = json_decode($streams_response, true);
    $count = is_array($streams_data) ? count($streams_data) : 0;
    if ($count > 0) {
        echo "<p class='pass'>✓ Found $count live streams</p>";
        echo "<pre>" . json_encode(array_slice($streams_data, 0, 3), JSON_PRETTY_PRINT) . "\n... (showing first 3)</pre>";
    } else {
        echo "<p class='fail'>✗ No live streams found</p>";
        echo "<pre>$streams_response</pre>";
    }
} else {
    echo "<p class='fail'>✗ Cannot fetch live streams</p>";
}
echo "</div>";

// Test 4: Stream URL Format
if (isset($streams_data) && is_array($streams_data) && count($streams_data) > 0) {
    echo "<div class='section'>";
    echo "<h2>Test 4: Stream URL Format</h2>";
    $sample = $streams_data[0];
    $stream_id = $sample['stream_id'] ?? 0;
    $ext = $sample['container_extension'] ?? 'ts';
    
    $stream_url = $base_url . "$test_user/$test_pass/$stream_id.$ext";
    echo "<p><strong>Sample Stream URL:</strong> <a href='$stream_url' target='_blank'>$stream_url</a></p>";
    echo "<p><strong>Stream Name:</strong> " . ($sample['name'] ?? 'Unknown') . "</p>";
    echo "<p class='pass'>✓ Stream URL format looks correct</p>";
    echo "</div>";
}

// TiviMate Configuration Guide
echo "<div class='section'>";
echo "<h2>📱 TiviMate Configuration</h2>";
echo "<p>Use these settings in TiviMate:</p>";
echo "<ul>";
echo "<li><strong>Connection Type:</strong> Xtream Codes API</li>";
echo "<li><strong>Server URL:</strong> <code>" . rtrim($base_url, '/') . "</code></li>";
echo "<li><strong>Username:</strong> <code>$test_user</code></li>";
echo "<li><strong>Password:</strong> <code>$test_pass</code></li>";
echo "</ul>";
echo "<p><strong>Note:</strong> Make sure to remove any trailing slashes and do NOT include 'player_api.php' in the URL.</p>";
echo "</div>";

// Summary
echo "<div class='section'>";
echo "<h2>📊 Summary</h2>";
$issues = [];
if (!isset($auth_data) || !isset($auth_data['user_info']['auth']) || $auth_data['user_info']['auth'] != 1) {
    $issues[] = "Authentication failed";
}
if (!isset($cat_data) || count($cat_data) == 0) {
    $issues[] = "No categories found";
}
if (!isset($streams_data) || count($streams_data) == 0) {
    $issues[] = "No live streams found";
}

if (empty($issues)) {
    echo "<p class='pass'>✓ All tests passed! Your server is ready for TiviMate.</p>";
    echo "<p>If channels still don't show in TiviMate, try:</p>";
    echo "<ol>";
    echo "<li>Clear TiviMate cache (Settings → General → Clear cache)</li>";
    echo "<li>Remove and re-add the playlist</li>";
    echo "<li>Make sure you're using the exact URL format shown above</li>";
    echo "<li>Check if your server is publicly accessible (not localhost)</li>";
    echo "</ol>";
} else {
    echo "<p class='fail'>✗ Issues found:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}
echo "</div>";

echo "</body></html>";
