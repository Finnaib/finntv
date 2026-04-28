<?php
// play_retro.php - Completely Rebuilt Device-Specific Playback Handler
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

// We will NOT use the Vercel proxy by default because it often gets blocked by IPTV providers (Timeout).
// We rely on the native device fetching the stream directly using the user's home IP.

// Convert raw Xtream Codes .ts streams to .m3u8 to force the IPTV server to provide an HLS playlist,
// which is natively supported by the PS Vita.
$lower_url = strtolower($url);
if ((strpos($lower_url, '/live/') !== false || strpos($lower_url, '/movie/') !== false || strpos($lower_url, '/series/') !== false) && strpos($lower_url, '.ts') !== false) {
    $url = substr($url, 0, strrpos($url, '.ts')) . '.m3u8';
}

// Vita requires the URL to end in an extension it recognizes, otherwise the native player won't attach.
$vita_url = $url;
if ($is_vita && stripos($vita_url, '.m3u8') === false && stripos($vita_url, '.mp4') === false) {
    $vita_url .= (strpos($vita_url, '?') === false ? '?' : '&') . 'ext=.m3u8';
}

$dsi_url = str_replace('https://', 'http://', $url);

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
echo '<html><head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">';
echo '<title>Play - ' . htmlspecialchars($name) . '</title>';
echo '<style type="text/css">';
echo 'body { background-color: #000000; color: #ffffff; font-family: sans-serif; text-align: center; margin: 0; padding: 20px; }';
echo '.header { font-size: 16px; font-weight: bold; color: #00d4ff; margin-bottom: 20px; }';
echo '.btn { display: inline-block; background-color: #1a6bb3; color: #ffffff; padding: 10px 20px; font-size: 16px; font-weight: bold; text-decoration: none; border-radius: 5px; border: 2px solid #ffffff; margin-top: 10px; }';
echo '#player-container { background: #111; border: 1px solid #333; margin: 0 auto 20px auto; width: 100%; max-width: 640px; }';
echo 'video { width: 100%; height: auto; }';
echo '.back-link { margin-top: 30px; display: block; color: #aaaaaa; }';
echo '</style>';
echo '</head><body>';

echo '<div class="header">Loading: ' . htmlspecialchars($name) . '</div>';

if ($is_vita || $is_3ds) {
    // Both Vita and 3DS require the HTML5 video tag present in the DOM for their browsers to hook the native player overlay.
    $src = $is_vita ? $vita_url : $url;
    
    echo '<div id="player-container">';
    echo '<video id="retro-video" width="100%" controls preload="auto" playsinline webkit-playsinline x-webkit-airplay="allow"></video>';
    echo '</div>';
    echo '<a class="btn" href="' . htmlspecialchars($src) . '">Force Native Player</a>';

    echo '<script type="text/javascript">';
    echo '  var video = document.getElementById("retro-video");';
    echo '  video.pause();';
    echo '  video.src = "' . htmlspecialchars($src) . '";';
    echo '  video.load();';
    echo '  setTimeout(function() {';
    echo '      try {';
    echo '          var p = video.play();';
    echo '          if(p && p.catch) p.catch(function(e){});';
    echo '      } catch(e) {}';
    echo '  }, 500);';
    echo '</script>';

} else {
    // PSP and DSi do not support HTML5 video at all. They must click a direct link.
    $fallback_url = $is_dsi ? $dsi_url : $url;
    echo '<div style="margin-bottom: 20px; font-size: 14px; color: #aaaaaa;">Your console requires direct stream access.</div>';
    echo '<a class="btn" href="' . htmlspecialchars($fallback_url) . '">LAUNCH STREAM</a>';
}

echo '<a class="back-link" href="javascript:history.back()">[ Back to Guide ]</a>';
echo '</body></html>';
?>
