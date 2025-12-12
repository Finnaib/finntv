<?php
// Simulate Stream Request
// Target: /live/test/test/1154.ts (From previous debug output: Alwan Aflam 1 4K, ID 1154)

$_SERVER['REQUEST_URI'] = '/live/test/test/1154.ts';
$_SERVER['HTTP_USER_AGENT'] = 'IPTVnator/1.0'; // Simulate App
$_SERVER['REQUEST_METHOD'] = 'GET';

// Mock routing parameters usually passed by .htaccess
// RewriteRule ^live/([^/]+)/([^/]+)/([0-9]+)\.(m3u8|ts)$ live.php/$1/$2/$3.$4 [L,QSA]
// But root live.php just requires core/live.php which parses REQUEST_URI.
// So we just need to ensure core/live.php is reachable.

// We need to capture the header() calls since we are in CLI
// core/live.php uses header("Location: ...") or outputs content.

// Mock header function if possible? No, we can't override built-in.
// But we can use ob_start to catch output (if proxy mode) 
// or xdebug_get_headers (if available, likely not).
// Instead, we 'll rely on the script dying or printing.

echo "--- Simulating Stream Request: {$_SERVER['REQUEST_URI']} ---\n";

ob_start();
try {
    require __DIR__ . '/core/live.php';
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

// Check headers (only works if running via php-cgi or web server, but let's see if we can infer from side effects or modify core temporarily)
// Actually, core/live.php sets $target_url. 
// I will create a wrapper that inspects the variable if possible, 
// OR I will modify core/live.php to echo the target in debug mode.

// Better: Just modify core/live.php to log to a file.
