<?php
/**
 * PHP Error Diagnostic Tool
 * Save as: diagnose_errors.php
 * Visit: http://finntv.atwebpages.com/xtream_server/diagnose_errors.php
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Error Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #0f0; }
        .box { background: #000; padding: 20px; margin: 15px 0; border: 3px solid #0f0; border-radius: 10px; }
        .error { border-color: #f00; color: #f00; }
        .success { border-color: #0f0; color: #0f0; }
        .warning { border-color: #fa0; color: #fa0; }
        h1 { color: #0ff; }
        h2 { color: #0f0; border-bottom: 2px solid #0f0; padding-bottom: 10px; }
        pre { background: #2d2d2d; padding: 15px; overflow-x: auto; color: #fff; }
        code { background: #333; padding: 3px 8px; color: #fa0; }
        .step { background: rgba(0,255,0,0.1); padding: 15px; margin: 10px 0; border-left: 4px solid #0f0; }
    </style>
</head>
<body>
    <h1>🔧 PHP Error Diagnostic Tool</h1>

    <?php
    echo "<h2>Step 1: PHP Configuration</h2>";
    echo "<div class='box success'>";
    echo "✅ PHP Version: <strong>" . phpversion() . "</strong><br>";
    echo "✅ Server: <strong>" . $_SERVER['SERVER_SOFTWARE'] . "</strong><br>";
    echo "✅ Document Root: <strong>" . $_SERVER['DOCUMENT_ROOT'] . "</strong><br>";
    echo "✅ Current Directory: <strong>" . __DIR__ . "</strong><br>";
    echo "</div>";

    echo "<h2>Step 2: Check Critical Files</h2>";
    $files = [
        'config.php' => 'Configuration file',
        'player_api.php' => 'Main API file',
        'get.php' => 'M3U generator',
        'live.php' => 'Stream proxy',
        'xmltv.php' => 'EPG export',
        'm3u/' => 'M3U folder',
        'm3u/india.m3u' => 'India channels'
    ];

    foreach ($files as $file => $desc) {
        $exists = file_exists($file);
        $readable = $exists && is_readable($file);
        
        if ($exists && $readable) {
            echo "<div class='box success'>✅ <strong>$file</strong> - $desc</div>";
        } elseif ($exists) {
            echo "<div class='box error'>❌ <strong>$file</strong> - EXISTS but NOT READABLE</div>";
        } else {
            echo "<div class='box error'>❌ <strong>$file</strong> - MISSING</div>";
        }
    }

    echo "<h2>Step 3: Test config.php Loading</h2>";
    if (file_exists('config.php')) {
        echo "<div class='box'>";
        echo "Attempting to load config.php...<br><br>";
        
        ob_start();
        try {
            require_once 'config.php';
            $output = ob_get_clean();
            
            if (!empty($output)) {
                echo "<div class='error'>⚠️ config.php produced output (should be silent):<br>";
                echo "<pre>" . htmlspecialchars($output) . "</pre></div>";
            } else {
                echo "✅ config.php loaded successfully (no output)<br><br>";
            }
            
            // Check variables
            if (isset($users)) {
                echo "✅ \$users array exists (" . count($users) . " users)<br>";
                echo "Users: " . implode(', ', array_keys($users)) . "<br>";
            } else {
                echo "<span class='error'>❌ \$users array NOT defined</span><br>";
            }
            
            if (isset($channels)) {
                echo "✅ \$channels array exists (" . count($channels) . " channels)<br>";
            } else {
                echo "<span class='error'>❌ \$channels array NOT defined</span><br>";
            }
            
            if (isset($category_map)) {
                echo "✅ \$category_map array exists<br>";
            } else {
                echo "<span class='error'>❌ \$category_map array NOT defined</span><br>";
            }
            
        } catch (Exception $e) {
            $output = ob_get_clean();
            echo "<div class='error'>❌ Error loading config.php:<br>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
            if ($output) {
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
            }
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<div class='box error'>❌ config.php NOT FOUND</div>";
    }

    echo "<h2>Step 4: Test player_api.php Syntax</h2>";
    if (file_exists('player_api.php')) {
        echo "<div class='box'>";
        
        // Check for syntax errors
        $output = shell_exec('php -l player_api.php 2>&1');
        if ($output) {
            if (strpos($output, 'No syntax errors') !== false) {
                echo "✅ player_api.php has no syntax errors<br>";
            } else {
                echo "<div class='error'>❌ Syntax errors found:<br>";
                echo "<pre>" . htmlspecialchars($output) . "</pre></div>";
            }
        } else {
            echo "<span class='warning'>⚠️ Cannot check syntax (shell_exec disabled)</span><br>";
        }
        
        // Check file size
        $size = filesize('player_api.php');
        echo "File size: " . number_format($size) . " bytes<br>";
        
        if ($size < 100) {
            echo "<span class='error'>❌ File is too small - probably not uploaded correctly</span><br>";
        } elseif ($size > 50000) {
            echo "<span class='warning'>⚠️ File is unusually large</span><br>";
        } else {
            echo "✅ File size looks normal<br>";
        }
        
        echo "</div>";
    } else {
        echo "<div class='box error'>❌ player_api.php NOT FOUND</div>";
    }

    echo "<h2>Step 5: Test API Direct Call</h2>";
    echo "<div class='box'>";
    echo "Testing API with credentials finn/finn123...<br><br>";
    
    $_GET['username'] = 'finn';
    $_GET['password'] = 'finn123';
    $_GET['action'] = '';
    
    ob_start();
    try {
        include 'player_api.php';
        $apiOutput = ob_get_clean();
        
        if (empty($apiOutput)) {
            echo "<span class='error'>❌ API produced no output</span><br>";
        } else {
            // Try to decode as JSON
            $json = json_decode($apiOutput, true);
            if ($json !== null) {
                echo "✅ API returned valid JSON<br><br>";
                echo "<strong>Response:</strong><br>";
                echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT) . "</pre>";
                
                if (isset($json['user_info']['auth']) && $json['user_info']['auth'] == 1) {
                    echo "<br>✅ <strong>Authentication SUCCESSFUL!</strong>";
                } else {
                    echo "<br><span class='error'>❌ Authentication FAILED</span>";
                }
            } else {
                echo "<span class='error'>❌ API returned invalid JSON:<br>";
                echo "<pre>" . htmlspecialchars(substr($apiOutput, 0, 1000)) . "</pre></span>";
            }
        }
    } catch (Exception $e) {
        $apiOutput = ob_get_clean();
        echo "<div class='error'>❌ Error running API:<br>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        if ($apiOutput) {
            echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";
        }
        echo "</div>";
    }
    echo "</div>";

    echo "<h2>📋 Recommendations</h2>";
    echo "<div class='step'>";
    echo "<h3>If you see errors above:</h3>";
    echo "<ol>";
    echo "<li><strong>Syntax Errors:</strong> Re-download and re-upload player_api.php (make sure it's the COMPLETE file)</li>";
    echo "<li><strong>Missing config.php:</strong> Upload config.php to the same directory</li>";
    echo "<li><strong>Permission Errors:</strong> Set file permissions to 644 for PHP files</li>";
    echo "<li><strong>Invalid JSON:</strong> Check if error_reporting is off in player_api.php</li>";
    echo "<li><strong>\$users not defined:</strong> Your config.php is incomplete or corrupt</li>";
    echo "</ol>";
    echo "</div>";

    echo "<h2>✅ Next Steps</h2>";
    echo "<div class='step'>";
    echo "<p>If all checks pass above, test the API directly:</p>";
    echo "<p><a href='player_api.php?username=finn&password=finn123' target='_blank' style='color:#0ff;'>Test API Authentication →</a></p>";
    echo "<p><a href='player_api.php?username=finn&password=finn123&action=get_live_categories' target='_blank' style='color:#0ff;'>Test Live Categories →</a></p>";
    echo "</div>";
    ?>
</body>
</html>