<?php
// play_retro.php - Device-Specific Playback Handler for Legacy Consoles
error_reporting(0);

$raw_url = isset($_GET['url']) ? $_GET['url'] : '';
$url = base64_decode(str_replace(' ', '+', $raw_url));
$name = isset($_GET['name']) ? $_GET['name'] : 'Unknown Channel';

if (empty($url)) {
    die("Invalid Stream URL");
}

$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$is_vita = (stripos($ua, 'Vita') !== false || stripos($ua, 'PlayStation Vita') !== false);
$is_psp = (stripos($ua, 'PlayStation Portable') !== false || stripos($ua, 'PSP') !== false);
$is_3ds = (stripos($ua, 'Nintendo 3DS') !== false);
$is_dsi = (stripos($ua, 'Nintendo DSi') !== false);

// Proxy logic (reuse stream_proxy if needed, but for native devices direct is often better unless CORS is an issue. 
// PSP/DSi natively don't care about CORS, but HTTPS issues might happen. We'll use the proxy for Vita to fix HLS).
$host_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$proxy_url = $host_url . '/api/stream_proxy.php?url=' . urlencode(base64_encode($url));

// Convert raw Xtream Codes .ts to .m3u8 for broader compatibility
$lower_url = strtolower($url);
if ((strpos($lower_url, '/live/') !== false || strpos($lower_url, '/movie/') !== false) && strpos($lower_url, '.ts') !== false) {
    $url = substr($url, 0, -3) . '.m3u8';
    $proxy_url = $host_url . '/api/stream_proxy.php?url=' . urlencode(base64_encode($url));
}

// Ensure Vita appends ?ext=.m3u8 for native player pickup. 
// We bypass the proxy for Vita because its native player doesn't have CORS issues,
// and the Vercel proxy might be blocked by the IPTV provider.
$vita_url = $url;
if ($is_vita && strpos(strtolower($vita_url), '.m3u8') === false && strpos(strtolower($vita_url), '.mp4') === false) {
    $vita_url .= (strpos($vita_url, '?') === false ? '?' : '&') . 'ext=.m3u8';
}

// Force HTTP for DSi to avoid certificate errors if the stream is HTTPS
$dsi_url = str_replace('https://', 'http://', $url);

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
echo '<html><head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">';
echo '<title>Play - ' . htmlspecialchars($name) . '</title>';
echo '<style type="text/css">';
echo 'body { background-color: #000000; color: #ffffff; font-family: sans-serif; text-align: center; margin: 0; padding: 20px; }';
echo '.header { font-size: 18px; font-weight: bold; color: #00d4ff; margin-bottom: 20px; }';
echo '.btn { display: inline-block; background-color: #1a6bb3; color: #ffffff; padding: 15px 30px; font-size: 18px; font-weight: bold; text-decoration: none; border-radius: 5px; border: 2px solid #ffffff; }';
echo 'video { width: 100%; max-width: 800px; background: #111; border: 1px solid #333; margin-bottom: 20px; }';
echo '.back-link { margin-top: 30px; display: block; color: #aaaaaa; }';
echo '</style>';
echo '</head><body>';

echo '<div class="header">Now Playing: ' . htmlspecialchars($name) . '</div>';

    $fallback_url = $url;
    if ($is_vita) $fallback_url = $vita_url;
    if ($is_dsi) $fallback_url = $dsi_url;
    
    echo '<div style="margin-bottom: 20px; font-size: 14px; color: #aaaaaa;">Launching stream in Native Media Player...<br><br>If it does not open automatically, click the button below.</div>';
    echo '<a class="btn" href="' . htmlspecialchars($fallback_url) . '">LAUNCH NATIVE PLAYER</a>';
    
    // Auto-launch if possible
    echo '<script>setTimeout(function(){ window.location.href = "' . htmlspecialchars($fallback_url) . '"; }, 1000);</script>';

echo '<a class="back-link" href="javascript:history.back()">[ Go Back ]</a>';
echo '</body></html>';
?>
