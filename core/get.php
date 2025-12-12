<?php
/**
 * Enhanced get.php - M3U Playlist Generator
 * 
 * Features:
 * - Extended M3U attributes for better EPG support
 * - Multiple output formats
 * - Proper URL generation for all apps
 * - Old TV box compatibility
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: audio/x-mpegurl; charset=utf-8');
header('Content-Disposition: inline; filename="playlist.m3u8"');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config.php';

// Optimization: Call parser explicitly since auto-run is disabled
parseMoviesAndSeries();

$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';
$type = $_GET['type'] ?? 'm3u'; // m3u, m3u_plus, or xtream

// Authenticate
// Authenticate
function authenticateUser($username, $password, $users)
{
    if (!isset($users[$username]))
        return false;

    $u = $users[$username];
    $storedPass = is_array($u) ? $u['password'] : $u;

    // 1. Check Password
    if ($storedPass !== $password) {
        return false;
    }

    // 2. Check Expiration (Consistent with player_api.php)
    $created_at = (is_array($u) && !empty($u['created_at'])) ? $u['created_at'] : time();
    $exp_date = (is_array($u) && !empty($u['created_at']))
        ? strtotime('+1 year', $created_at)
        : strtotime('+1 year'); // Note: dynamic logic simplified here for read-only safety

    if (time() > $exp_date) {
        return false;
    }

    return $u;
}
/* 
    Legacy Bcrypt Logic Removed for simplicity/speed as Config uses plain text for now. 
    If you need hash support, restore the password_verify block.
*/

$user = authenticateUser($username, $password, $users_db);

if (!$user) {
    echo "#EXTM3U\n";
    echo "#EXTINF:-1,Authentication Failed\n";
    echo "http://invalid\n";
    exit;
}

// Build server URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $server_config['base_url'] ?? ($protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

// Output M3U header with extended attributes
echo "#EXTM3U x-tvg-url=\"{$baseUrl}/xmltv.php?username={$username}&password={$password}\"\n";

// Get allowed categories
$allowed = [];
foreach ($user['categories'] as $cat) {
    if (isset($category_map[$cat])) {
        $allowed[] = $category_map[$cat]['id'];
    }
}

// Output channels
foreach ($channels as $ch) {
    if (!in_array($ch['category'], $allowed)) {
        continue;
    }

    $name = $ch['name'];
    $logo = $ch['logo'] ?? '';
    $tvgId = $ch['tvg_id'] ?? '';
    $streamId = $ch['id'];
    $categoryName = '';

    // Get category name
    foreach ($categories as $cat) {
        if ($cat['category_id'] == $ch['category']) {
            $categoryName = $cat['category_name'];
            break;
        }
    }

    // Build stream URL
    if ($type === 'xtream') {
        // Xtream format
        $streamUrl = "{$baseUrl}/live/{$username}/{$password}/{$streamId}.ts";
    } else {
        // Direct URL from M3U
        $streamUrl = $ch['url'];
    }

    // Output EXTINF line with all attributes
    echo "#EXTINF:-1";
    if ($tvgId)
        echo " tvg-id=\"{$tvgId}\"";
    if ($logo)
        echo " tvg-logo=\"{$logo}\"";
    if ($categoryName)
        echo " group-title=\"{$categoryName}\"";
    echo ",{$name}\n";

    // Output stream URL
    echo "{$streamUrl}\n";
}
// End of file