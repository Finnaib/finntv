<?php
// retro.php - Legacy Console Player (PSP, PS Vita, DSi, 3DS)

error_reporting(0); // Suppress errors for clean HTML output

$m3u_url = isset($_GET['m3u']) ? $_GET['m3u'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;

// Base HTML Header optimized for NetFront (PSP/3DS) and Opera (DSi)
function render_header($title) {
    echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
    echo '<html><head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">';
    echo '<title>' . htmlspecialchars($title) . '</title>';
    echo '<style type="text/css">';
    echo 'body { background-color: #000000; color: #ffffff; font-family: sans-serif; font-size: 14px; margin: 0; padding: 5px; }';
    echo 'a { color: #00d4ff; text-decoration: none; }';
    echo 'a:hover { color: #ffffff; }';
    echo '.header { background-color: #1a6bb3; padding: 10px; font-weight: bold; text-align: center; margin-bottom: 10px; border-bottom: 2px solid #00d4ff; }';
    echo '.btn { display: block; background-color: #333333; color: #ffffff; padding: 10px; margin-bottom: 5px; border: 1px solid #555555; text-align: left; }';
    echo '.btn-primary { background-color: #1a6bb3; text-align: center; font-weight: bold; }';
    echo '.pagination { text-align: center; margin: 10px 0; padding: 10px; background-color: #222222; }';
    echo '.nav-links { font-size: 16px; font-weight: bold; }';
    echo '.chan-name { font-weight: bold; font-size: 16px; display: block; }';
    echo '.chan-group { font-size: 10px; color: #aaaaaa; }';
    echo '</style>';
    echo '</head><body>';
}

function render_footer() {
    echo '<div style="text-align:center; padding: 10px; color: #555555; font-size: 10px;">FinnTV Retro Player</div>';
    echo '</body></html>';
}

if (empty($m3u_url)) {
    render_header("FinnTV Retro");
    echo '<div class="header">FinnTV Retro Portal</div>';
    echo '<p style="text-align:center; font-size:12px; color:#aaa;">Optimized for PSP, PS Vita, 3DS, and DSi</p>';
    
    $playlists = [
        "Asia" => "/m3u/asia.m3u",
        "Egypt" => "/m3u/egypt.m3u",
        "India" => "/m3u/india.m3u",
        "World" => "/m3u/world.m3u",
        "Indonesia" => "/m3u/indonesia.m3u",
        "Sport" => "/m3u/sport.m3u"
    ];
    
    foreach ($playlists as $name => $url) {
        echo '<a class="btn btn-primary" href="retro.php?m3u=' . urlencode($url) . '">Load ' . $name . ' Channels</a>';
    }
    
    // Allow custom input via basic form
    echo '<div style="margin-top: 20px; background: #222; padding: 10px;">';
    echo '<form action="retro.php" method="GET">';
    echo 'Custom M3U URL:<br>';
    echo '<input type="text" name="m3u" style="width: 100%; padding: 5px; margin-bottom: 5px;">';
    echo '<input type="submit" value="Load URL" style="padding: 5px 15px; background: #1a6bb3; color: white; border: none;">';
    echo '</form></div>';
    
    echo '<div style="margin-top: 10px; text-align: center;"><a href="index.html">Back to Main Site</a></div>';
    render_footer();
    exit;
}

// Fetch M3U
$ch = curl_init($m3u_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
$content = curl_exec($ch);
curl_close($ch);

if (!$content) {
    render_header("Error");
    echo '<div class="header">Connection Error</div>';
    echo '<p>Failed to load playlist. Make sure the link is valid and accessible.</p>';
    echo '<a class="btn btn-primary" href="retro.php">Back to Menu</a>';
    render_footer();
    exit;
}

// Parse M3U
$lines = explode("\n", str_replace("\r", "", $content));
$channels = [];
$current_channel = null;

foreach ($lines as $line) {
    $line = trim($line);
    if (strpos($line, '#EXTINF:') === 0) {
        $current_channel = ['name' => 'Unknown', 'group' => 'Uncategorized', 'url' => ''];
        
        if (preg_match('/group-title="([^"]+)"/', $line, $m)) {
            $current_channel['group'] = $m[1];
        }
        $comma_pos = strrpos($line, ',');
        if ($comma_pos !== false) {
            $current_channel['name'] = trim(substr($line, $comma_pos + 1));
        }
    } elseif (strpos($line, 'http') === 0 && $current_channel) {
        $current_channel['url'] = $line;
        $channels[] = $current_channel;
        $current_channel = null;
    }
}

$total_channels = count($channels);
$total_pages = ceil($total_channels / $per_page);
$start_idx = ($page - 1) * $per_page;
$end_idx = min($start_idx + $per_page, $total_channels);

render_header("FinnTV Retro - Page $page");

echo '<div class="header">Channel Guide (' . $total_channels . ')</div>';

if ($total_channels === 0) {
    echo '<p>No channels found.</p>';
} else {
    for ($i = $start_idx; $i < $end_idx; $i++) {
        $c = $channels[$i];
        $play_url = 'play_retro.php?url=' . urlencode(base64_encode($c['url'])) . '&name=' . urlencode($c['name']);
        
        echo '<a class="btn" href="' . $play_url . '">';
        echo '<span class="chan-name">' . htmlspecialchars($c['name']) . '</span>';
        echo '<span class="chan-group">' . htmlspecialchars($c['group']) . '</span>';
        echo '</a>';
    }
}

// Pagination
echo '<div class="pagination">';
if ($page > 1) {
    echo '<a class="nav-links" href="retro.php?m3u=' . urlencode($m3u_url) . '&page=' . ($page - 1) . '">&laquo; Prev</a> ';
} else {
    echo '<span style="color:#555;">&laquo; Prev</span> ';
}

echo ' [ Page ' . $page . ' of ' . $total_pages . ' ] ';

if ($page < $total_pages) {
    echo '<a class="nav-links" href="retro.php?m3u=' . urlencode($m3u_url) . '&page=' . ($page + 1) . '">Next &raquo;</a>';
} else {
    echo '<span style="color:#555;">Next &raquo;</span>';
}
echo '</div>';

echo '<div style="text-align: center; padding: 10px;"><a href="retro.php">Back to Categories</a></div>';

render_footer();
?>
