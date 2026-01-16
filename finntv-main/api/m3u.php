<?php
/**
 * M3U File Server API
 * Serves M3U playlist files with proper headers
 * Backup solution if static file serving fails
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the requested M3U file from query parameter
$file = isset($_GET['file']) ? $_GET['file'] : '';

// Sanitize the filename to prevent directory traversal
$file = basename($file);

// Ensure it's an M3U file
if (!preg_match('/\.m3u$/i', $file)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only .m3u files are allowed.']);
    exit;
}

// Construct the full path to the M3U file
$m3uPath = __DIR__ . '/../m3u/' . $file;

// Check if file exists
if (!file_exists($m3uPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'M3U file not found: ' . $file]);
    exit;
}

// Set the content type for M3U files
header('Content-Type: application/x-mpegurl; charset=utf-8');
header('Content-Disposition: inline; filename="' . $file . '"');
header('Content-Length: ' . filesize($m3uPath));

// Output the file contents
readfile($m3uPath);
exit;
