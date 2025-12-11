<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>FinnTV Debug Info</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #00ff00; }
        .section { background: #2d2d2d; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { color: #ff4444; }
        .success { color: #44ff44; }
        .warning { color: #ffaa44; }
        h2 { color: #00aaff; }
        pre { background: #000; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 FinnTV Debug Information</h1>

    <div class="section">
        <h2>📂 M3U Folder Path</h2>
        <p><strong>Expected:</strong> <?php echo $server_config['m3u_folder']; ?></p>
        <p><strong>Exists:</strong> <?php echo is_dir($server_config['m3u_folder']) ? '<span class="success">YES</span>' : '<span class="error">NO</span>'; ?></p>
    </div>

    <div class="section">
        <h2>📄 M3U Files Status</h2>
        <?php foreach ($category_map as $catName => $catInfo): ?>
            <?php 
                $filepath = $server_config['m3u_folder'] . $catInfo['file'];
                $exists = file_exists($filepath);
                $size = $exists ? filesize($filepath) : 0;
            ?>
            <p>
                <strong><?php echo $catInfo['file']; ?>:</strong> 
                <?php if ($exists): ?>
                    <span class="success">✓ Found</span> (<?php echo number_format($size); ?> bytes)
                <?php else: ?>
                    <span class="error">✗ Not Found</span>
                <?php endif; ?>
            </p>
        <?php endforeach; ?>
    </div>

    <div class="section">
        <h2>📺 Loaded Channels</h2>
        <p><strong>Total Channels:</strong> <?php echo count($channels); ?></p>
        
        <?php if (count($channels) > 0): ?>
            <h3>Sample Channels (first 5):</h3>
            <pre><?php 
                $sample = array_slice($channels, 0, 5);
                foreach ($sample as $ch) {
                    echo "ID: {$ch['id']} | Name: {$ch['name']} | Category: {$ch['category']}\n";
                    echo "URL: {$ch['url']}\n";
                    echo "Logo: {$ch['logo']}\n";
                    echo "---\n";
                }
            ?></pre>
        <?php else: ?>
            <p class="error">⚠ No channels loaded! Check if M3U files exist and contain valid data.</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>🔗 Test Stream URLs</h2>
        <?php if (count($channels) > 0): ?>
            <?php $testChannel = $channels[0]; ?>
            <p>Testing with first channel: <strong><?php echo $testChannel['name']; ?></strong></p>
            <p><strong>Stream ID:</strong> <?php echo $testChannel['id']; ?></p>
            <p><strong>Original URL:</strong> <code><?php echo htmlspecialchars($testChannel['url']); ?></code></p>
            <p><strong>Xtream URL:</strong> <code>http://finntv.atwebpages.com/xtream_server/live/finn/finn123/<?php echo $testChannel['id']; ?>.m3u8</code></p>
            
            <p class="warning">⚠ Click the Xtream URL above to test if it redirects to the stream</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>⚙️ Server Configuration</h2>
        <pre><?php print_r($server_config); ?></pre>
    </div>

    <div class="section">
        <h2>🧪 Quick Tests</h2>
        <p><a href="player_api.php?username=finn&password=finn123" target="_blank">Test Authentication</a></p>
        <p><a href="player_api.php?username=finn&password=finn123&action=get_live_categories" target="_blank">Test Categories</a></p>
        <p><a href="player_api.php?username=finn&password=finn123&action=get_live_streams" target="_blank">Test Channels List</a></p>
    </div>

</body>
</html>