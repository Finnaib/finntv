<?php
/**
 * FinnTV - M3U Downloader for Homebrew Devices (PS Vita NetStream)
 */
error_reporting(0);

if (!isset($_GET['url']) || !isset($_GET['name'])) {
    http_response_code(400);
    die("Error: Missing parameters");
}

$raw_url = str_replace(' ', '+', $_GET['url']);
$url = base64_decode($raw_url);
$name = $_GET['name'];

if (!$url || filter_var($url, FILTER_VALIDATE_URL) === false) {
    http_response_code(400);
    die("Error: Invalid URL");
}

// Convert raw Xtream Codes .ts to .m3u8 if applicable
$lower_url = strtolower($url);
if ((strpos($lower_url, '/live/') !== false || strpos($lower_url, '/movie/') !== false || strpos($lower_url, '/series/') !== false) && strpos($lower_url, '.ts') !== false) {
    $url = substr($url, 0, strrpos($url, '.ts')) . '.m3u8';
}

// Sanitize filename to preserve spaces but remove illegal characters
$filename = preg_replace('/[\\\\\\/\\:\\*\\?\\"\\<\\>\\|]/', '', $name);
$filename = $filename ? trim($filename) . '.m3u8' : 'channel.m3u8';

// Generate M3U content
$m3u_content = "#EXTM3U\n";
$m3u_content .= "#EXTINF:-1 tvg-name=\"" . $name . "\"," . $name . "\n";
$m3u_content .= $url . "\n";

// Force download
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.apple.mpegurl');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($m3u_content));

echo $m3u_content;
exit;
?>
