<?php
require_once 'config.php';

// Count total channels
$totalChannels = count($channels);
$channelsByCategory = [];
foreach ($channels as $ch) {
    if (!isset($channelsByCategory[$ch['category']])) {
        $channelsByCategory[$ch['category']] = 0;
    }
    $channelsByCategory[$ch['category']]++;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>FinnTV Xtream Server</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        .info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        code { background: #e8e8e8; padding: 3px 8px; border-radius: 3px; font-size: 14px; }
        .users { margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-box h3 { margin: 0; font-size: 32px; }
        .stat-box p { margin: 5px 0 0 0; opacity: 0.9; }
        .success { color: #4CAF50; font-weight: bold; }
        .category-stats { background: white; padding: 15px; border-radius: 8px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🎬 FinnTV Xtream Server</h1>
    
    <div class="info">
        <h2>✅ Server Status: <span class="success">ONLINE</span></h2>
        <p><strong>Server URL:</strong> <code>http://finntv.atwebpages.com/xtream_server</code></p>
        <p><strong>API Endpoint:</strong> <code>http://finntv.atwebpages.com/xtream_server/player_api.php</code></p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <h3><?php echo $totalChannels; ?></h3>
            <p>Total Channels</p>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <h3><?php echo count($users); ?></h3>
            <p>Active Users</p>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <h3><?php echo count($categories); ?></h3>
            <p>Categories</p>
        </div>
    </div>

    <div class="info category-stats">
        <h3>📊 Channels by Category</h3>
        <?php foreach ($categories as $cat): ?>
            <p>
                <strong><?php echo $cat['category_name']; ?>:</strong> 
                <?php echo isset($channelsByCategory[$cat['category_id']]) ? $channelsByCategory[$cat['category_id']] : 0; ?> channels
            </p>
        <?php endforeach; ?>
    </div>

    <h2>📱 IPTV Box Configuration</h2>
    <div class="info">
        <p><strong>Server:</strong> <code>finntv.atwebpages.com/xtream_server</code></p>
        <p><strong>Port:</strong> <code>80</code></p>
        <p><strong>Login Type:</strong> Xtream Codes API</p>
    </div>

    <div class="users">
        <h2>👥 Available Users</h2>
        <table>
            <tr>
                <th>Username</th>
                <th>Categories Access</th>
                <th>Max Connections</th>
            </tr>
            <tr>
                <td><code>finn</code></td>
                <td>India, Asia, USA, Egypt, Sport</td>
                <td>10</td>
            </tr>
            <tr>
                <td><code>tabby</code></td>
                <td>Asia, Sport</td>
                <td>10</td>
            </tr>
            <tr>
                <td><code>fatima</code></td>
                <td>India, Egypt, Sport</td>
                <td>10</td>
            </tr>
            <tr>
                <td><code>devz</code></td>
                <td>India, USA, Sport</td>
                <td>10</td>
            </tr>
        </table>
    </div>

    <h2>🧪 Test API</h2>
    <div class="info">
        <p>Test authentication: <a href="player_api.php?username=finn&password=finn123" target="_blank">Click here</a></p>
        <p>Get categories: <a href="player_api.php?username=finn&password=finn123&action=get_live_categories" target="_blank">Click here</a></p>
        <p>Get channels: <a href="player_api.php?username=finn&password=finn123&action=get_live_streams" target="_blank">Click here</a></p>
    </div>

    <p style="margin-top: 40px; color: #888; text-align: center;">
        Powered by FinnTV Xtream API v2.0 | Loaded from M3U files
    </p>
</body>
</html>