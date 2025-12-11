<?php
echo "<h1>File Permissions Check</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#0f0;} .ok{color:#0f0;} .error{color:#f00;} .warning{color:#fa0;} table{background:#2d2d2d;} th{background:#444;color:#0ff;} td{padding:8px;border:1px solid #555;}</style>";

$files = [
    'config.php',
    'player_api.php',
    'live.php',
    'stream.php',
    'get.php',
    'sessions.php',
    '.htaccess',
    'index.php',
    'debug.php',
    'check_permissions.php',
    'm3u/',
    'm3u/india.m3u',
    'm3u/asia.m3u',
    'm3u/usa.m3u',
    'm3u/egypt.m3u',
    'm3u/sport.m3u',
    'data/',
    'data/sessions.json'
];

echo "<h2>📁 Files & Permissions:</h2>";
echo "<table border='1' cellpadding='8' cellspacing='0'>";
echo "<tr><th>File/Folder</th><th>Exists</th><th>Readable</th><th>Writable</th><th>Permissions</th><th>Status</th></tr>";

foreach ($files as $file) {
    $exists = file_exists($file);
    $readable = $exists ? is_readable($file) : false;
    $writable = $exists ? is_writable($file) : false;
    $perms = $exists ? substr(sprintf('%o', fileperms($file)), -4) : 'N/A';
    
    $isDir = is_dir($file);
    $requiredPerms = $isDir ? '0755' : '0644';
    $status = '';
    
    if (!$exists) {
        $status = '<span class="error">❌ NOT FOUND</span>';
    } elseif (!$readable) {
        $status = '<span class="error">❌ NOT READABLE</span>';
    } elseif ($perms != $requiredPerms) {
        $status = '<span class="warning">⚠️ Should be ' . $requiredPerms . '</span>';
    } else {
        $status = '<span class="ok">✅ OK</span>';
    }
    
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($file) . "</strong></td>";
    echo "<td>" . ($exists ? '<span class="ok">✓</span>' : '<span class="error">✗</span>') . "</td>";
    echo "<td>" . ($readable ? '<span class="ok">✓</span>' : '<span class="error">✗</span>') . "</td>";
    echo "<td>" . ($writable ? '<span class="ok">✓</span>' : '<span class="error">✗</span>') . "</td>";
    echo "<td>" . $perms . "</td>";
    echo "<td>" . $status . "</td>";
    echo "</tr>";
}

echo "</table>";

// Test M3U file content
echo "<h2>📺 M3U Files Content Check:</h2>";
$m3uFiles = ['india.m3u', 'asia.m3u', 'usa.m3u', 'egypt.m3u', 'sport.m3u'];
foreach ($m3uFiles as $m3u) {
    $path = 'm3u/' . $m3u;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        $channelCount = 0;
        foreach ($lines as $line) {
            if (strpos($line, '#EXTINF:') === 0) {
                $channelCount++;
            }
        }
        echo "<p><strong>$m3u:</strong> <span class='ok'>✓ $channelCount channels found</span></p>";
    } else {
        echo "<p><strong>$m3u:</strong> <span class='error'>✗ Not found</span></p>";
    }
}

// Test config loading
echo "<h2>⚙️ Configuration Test:</h2>";
if (file_exists('config.php')) {
    require_once 'config.php';
    echo "<p><span class='ok'>✓</span> config.php loaded successfully</p>";
    echo "<p><strong>Users defined:</strong> " . count($users) . "</p>";
    echo "<p><strong>Categories defined:</strong> " . count($categories) . "</p>";
    echo "<p><strong>Channels loaded:</strong> " . count($channels) . "</p>";
    
    if (count($channels) > 0) {
        echo "<p><span class='ok'>✓</span> Channels are loading from M3U files</p>";
        echo "<h3>Sample Channel:</h3>";
        $sample = $channels[0];
        echo "<pre>";
        echo "ID: " . $sample['id'] . "\n";
        echo "Name: " . $sample['name'] . "\n";
        echo "Category: " . $sample['category'] . "\n";
        echo "URL: " . htmlspecialchars($sample['url']) . "\n";
        echo "Logo: " . htmlspecialchars($sample['logo']) . "\n";
        echo "</pre>";
    } else {
        echo "<p><span class='error'>✗</span> No channels loaded! Check M3U files.</p>";
    }
} else {
    echo "<p><span class='error'>✗</span> config.php not found!</p>";
}

// Test API endpoints
echo "<h2>🔗 API Endpoints Test:</h2>";
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
echo "<p><a href='player_api.php?username=finn&password=finn123' target='_blank'>Test Authentication</a></p>";
echo "<p><a href='player_api.php?username=finn&password=finn123&action=get_live_categories' target='_blank'>Test Categories</a></p>";
echo "<p><a href='player_api.php?username=finn&password=finn123&action=get_live_streams' target='_blank'>Test Channels (check direct_source field)</a></p>";
echo "<p><a href='get.php?username=finn&password=finn123&type=m3u_plus' target='_blank'>Test M3U Generation</a></p>";

// PHP info
echo "<h2>🖥️ Server Information:</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";

// Session tracking test
echo "<h2>📊 Session Tracking:</h2>";
if (file_exists('sessions.php')) {
    require_once 'sessions.php';
    echo "<p><span class='ok'>✓</span> sessions.php loaded</p>";
    
    if (file_exists('data/sessions.json')) {
        $sessions = json_decode(file_get_contents('data/sessions.json'), true);
        echo "<p><strong>Active sessions:</strong> " . count($sessions) . "</p>";
        if (count($sessions) > 0) {
            echo "<pre>" . json_encode($sessions, JSON_PRETTY_PRINT) . "</pre>";
        }
    } else {
        echo "<p><span class='warning'>⚠️</span> data/sessions.json not yet created (will be created on first login)</p>";
    }
} else {
    echo "<p><span class='error'>✗</span> sessions.php not found!</p>";
}

echo "<hr>";
echo "<h2>✅ Summary:</h2>";
echo "<ul>";
echo "<li>All PHP files should be <strong>0644</strong></li>";
echo "<li>All folders should be <strong>0755</strong></li>";
echo "<li>M3U files should contain channels with URLs</li>";
echo "<li>config.php should load channels successfully</li>";
echo "<li>API should return channels with <strong>direct_source</strong> field containing stream URLs</li>";
echo "</ul>";
?>