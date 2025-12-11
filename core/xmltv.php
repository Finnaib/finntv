<?php
/**
 * XMLTV EPG Generator
 * 
 * Provides Electronic Program Guide in XMLTV format
 * Compatible with all EPG-enabled IPTV apps
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/xml; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: max-age=3600'); // Cache for 1 hour

require_once __DIR__ . '/../config.php';

// Optional authentication
$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';

// Build server URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$serverUrl = $server_config['base_url'] ?? ($protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

// Output XMLTV header
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<!DOCTYPE tv SYSTEM "xmltv.dtd">' . "\n";
echo '<tv source-info-url="' . $serverUrl . '" source-info-name="FinnTV IPTV" generator-info-name="FinnTV EPG" generator-info-url="' . $serverUrl . '">' . "\n";

// Add channel definitions
foreach ($channels as $ch) {
    $tvgId = $ch['tvg_id'] ?? 'ch' . $ch['id'];
    $name = htmlspecialchars($ch['name'], ENT_XML1, 'UTF-8');
    $logo = $ch['logo'] ?? '';

    echo '  <channel id="' . $tvgId . '">' . "\n";
    echo '    <display-name>' . $name . '</display-name>' . "\n";
    if ($logo) {
        echo '    <icon src="' . htmlspecialchars($logo, ENT_XML1, 'UTF-8') . '" />' . "\n";
    }
    echo '  </channel>' . "\n";
}

// Sample program data (you can extend this with real EPG data)
// For now, we output minimal structure for compatibility
$now = time();
$tomorrow = $now + 86400;

foreach ($channels as $ch) {
    $tvgId = $ch['tvg_id'] ?? 'ch' . $ch['id'];
    $name = htmlspecialchars($ch['name'], ENT_XML1, 'UTF-8');

    // Sample program
    echo '  <programme start="' . date('YmdHis O', $now) . '" stop="' . date('YmdHis O', $tomorrow) . '" channel="' . $tvgId . '">' . "\n";
    echo '    <title lang="en">' . $name . ' Programming</title>' . "\n";
    echo '    <desc lang="en">24/7 Live Stream</desc>' . "\n";
    echo '  </programme>' . "\n";
}

echo '</tv>' . "\n";
// End of file