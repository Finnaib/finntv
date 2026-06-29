<?php
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-cache, no-store, must-revalidate");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>FinnTV Premium Player</title>

    <script>
        // Auto-detect legacy browsers (PS Vita, PS3, old Smart TVs) or browsers lacking modern features
        // and redirect them to the native HTML5 player.
        (function() {
            var ua = navigator.userAgent.toLowerCase();
            var isLegacyConsole = (ua.indexOf('playstation vita') !== -1 || ua.indexOf('playstation 3') !== -1 || ua.indexOf('nintendo wiiu') !== -1);
            var isLegacyBrowser = (typeof Promise === 'undefined' || typeof fetch === 'undefined');
            
            if (isLegacyConsole || isLegacyBrowser) {
                var search = window.location.search;
                window.location.href = '../vita.html' + search;
            }
        })();
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Video.js & plugins -->
    <link href="https://cdn.jsdelivr.net/npm/video.js@8.21.1/dist/video-js.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/video.js@8.21.1/dist/video.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.13/dist/hls.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mpegts.js@1.7.3/dist/mpegts.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- App Styles -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div id="app" class="app-container">
        <!-- Application will be mounted here by app.js -->
    </div>

    <!-- Toast Notifications -->
    <div id="toast-container" class="toast-container"></div>

    <!-- App Scripts -->
    <script type="module" src="js/api.js"></script>
    <script type="module" src="js/app.js"></script>
</body>
</html>
