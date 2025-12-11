<?php
/**
 * Quick Check: Is live.php the fixed version?
 * Save as: check_live.php
 * Visit: http://finntv.atwebpages.com/xtream_server/check_live.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check live.php</title>
    <style>
        body { font-family: Arial; padding: 30px; background: #1e1e1e; color: #0f0; }
        .box { background: #000; padding: 25px; margin: 20px 0; border: 3px solid #0f0; border-radius: 15px; }
        .error { border-color: #f00; color: #f00; }
        .success { border-color: #0f0; color: #0f0; }
        h1 { color: #0ff; font-size: 2.5rem; }
        pre { background: #2d2d2d; padding: 15px; overflow-x: auto; }
        .big { font-size: 2rem; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Check live.php Version</h1>

    <?php
    if (!file_exists('live.php')) {
        echo "<div class='box error'>";
        echo "<p class='big'>❌ live.php NOT FOUND!</p>";
        echo "<p>Upload live.php to /xtream_server/</p>";
        echo "</div>";
        exit;
    }

    $content = file_get_contents('live.php');
    $fileSize = filesize('live.php');
    $lastModified = date('Y-m-d H:i:s', filemtime('live.php'));

    echo "<div class='box'>";
    echo "<h2>File Info:</h2>";
    echo "<p>Size: " . number_format($fileSize) . " bytes</p>";
    echo "<p>Last Modified: $lastModified</p>";
    echo "</div>";

    // Check for critical indicators
    $checks = [
        'has_cors' => strpos($content, 'Access-Control-Allow-Origin') !== false,
        'has_proxy' => strpos($content, 'fopen') !== false || strpos($content, 'file_get_contents') !== false,
        'no_redirect' => strpos($content, 'header(\'Location:') === false,
        'has_m3u8_handler' => strpos($content, 'application/vnd.apple.mpegurl') !== false
    ];

    $allGood = true;
    foreach ($checks as $check => $result) {
        if (!$result) $allGood = false;
    }

    if ($allGood) {
        echo "<div class='box success'>";
        echo "<p class='big'>✅ live.php IS THE FIXED VERSION!</p>";
        echo "<h3>Checks:</h3>";
        echo "<p>✅ Has CORS headers</p>";
        echo "<p>✅ Has proxy code</p>";
        echo "<p>✅ No redirect (good!)</p>";
        echo "<p>✅ Has M3U8 handler</p>";
        echo "<p style='margin-top: 20px;'>Your live.php should now work!</p>";
        echo "</div>";
    } else {
        echo "<div class='box error'>";
        echo "<p class='big'>❌ live.php IS OLD VERSION!</p>";
        echo "<h3>Missing:</h3>";
        if (!$checks['has_cors']) echo "<p>❌ CORS headers</p>";
        if (!$checks['has_proxy']) echo "<p>❌ Proxy code</p>";
        if (!$checks['no_redirect']) echo "<p>⚠️ Still has redirect (bad!)</p>";
        if (!$checks['has_m3u8_handler']) echo "<p>❌ M3U8 handler</p>";
        echo "<p style='margin-top: 20px; color: #fa0;'><strong>You need to upload the FIXED live.php!</strong></p>";
        echo "</div>";
    }

    // Show first 50 lines to help debug
    echo "<div class='box'>";
    echo "<h2>First 50 lines of live.php:</h2>";
    $lines = explode("\n", $content);
    $preview = implode("\n", array_slice($lines, 0, 50));
    echo "<pre>" . htmlspecialchars($preview) . "\n\n... (truncated)</pre>";
    echo "</div>";

    // Test URL
    echo "<div class='box'>";
    echo "<h2>📋 Next Step:</h2>";
    if ($allGood) {
        echo "<p>Go back to CORS test and click 'Try Proxied' again!</p>";
        echo "<p><a href='cors_test.html' style='color: #0ff;'>→ Back to CORS Test</a></p>";
    } else {
        echo "<p style='color: #fa0;'>Download 'REAL FIX: live.php with Full Proxy (For CORS)' from artifacts above</p>";
        echo "<p style='color: #fa0;'>Replace your current live.php with it</p>";
        echo "<p style='color: #fa0;'>Then refresh this page to verify</p>";
    }
    echo "</div>";
    ?>
</body>
</html>