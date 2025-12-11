<?php
/**
 * PROXY live.php - Router for /live/ directory requests
 * This acts as a catch-all for /live/username/password/ID.ext URLs
 */

// If accessed directly via /live.php, handle query params
if (isset($_GET['username']) || isset($_SERVER['PATH_INFO'])) {
    require_once __DIR__ . '/live.php';
    exit;
}

// Otherwise, we shouldn't be here
http_response_code(404);
echo "Not Found";
?>