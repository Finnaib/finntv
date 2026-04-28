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

// Convert raw Xtream Codes .ts streams to .m3u8
$lower_url = strtolower($url);
if ((strpos($lower_url, '/live/') !== false || strpos($lower_url, '/movie/') !== false || strpos($lower_url, '/series/') !== false) && strpos($lower_url, '.ts') !== false) {
    $url = substr($url, 0, strrpos($url, '.ts')) . '.m3u8';
}

$dsi_url = str_replace('https://', 'http://', $url);
$host_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

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

if ($is_vita) {
    // PS Vita cannot decode modern 1080p streams. We provide a NetStream downloader.
    $download_url = $host_url . '/api/download_m3u.php?name=' . urlencode($name) . '&url=' . urlencode(base64_encode($url));
    
    echo '<div style="margin-bottom: 20px; font-size: 14px; color: #ff5555; font-weight: bold;">Hardware Limitation Detected</div>';
    echo '<div style="margin-bottom: 20px; font-size: 13px; color: #aaaaaa; text-align: left; max-width: 400px; margin: 0 auto 20px auto;">';
    echo 'Your PS Vita\'s browser cannot natively play 1080p IPTV streams without crashing. ';
    echo 'To watch this channel perfectly, please use the <b>NetStream</b> homebrew application.</div>';
    
    echo '<a class="btn" href="' . htmlspecialchars($download_url) . '">Download for NetStream (.m3u)</a>';

} elseif ($is_3ds) {
    // 3DS requires the HTML5 video tag present in the DOM for its browser to hook the native player overlay.
    echo '<div id="player-container">';
    echo '<video id="retro-video" width="100%" controls preload="auto" playsinline webkit-playsinline x-webkit-airplay="allow"></video>';
    echo '</div>';
    echo '<a class="btn" href="' . htmlspecialchars($url) . '">Force Native Player</a>';

    echo '<script type="text/javascript">';
    echo '  var video = document.getElementById("retro-video");';
    echo '  video.pause();';
    echo '  video.src = "' . htmlspecialchars($url) . '";';
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
