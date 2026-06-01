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
    <style>
        * { box-sizing: border-box; }
        body, html { 
            margin:0; padding:0; background:#060913; color:#fff; 
            overflow:hidden; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
        }
        
        /* Modern Top Navigation Bar (YouTube/iQIYI Style) */
        .top-nav {
            height: 70px;
            background: #0f172a;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 20px;
            z-index: 50;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
        }
        
        .nav-brand {
            font-size: 24px;
            font-weight: 800;
            color: #60a5fa;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            max-width: 800px;
        }

        /* Large size fix for dropdown (Tabs) */
        #playlist-select { 
            padding: 12px 15px; 
            width: 250px;
            background: rgba(255,255,255,0.05); 
            border: 1px solid rgba(255,255,255,0.1); 
            color: #fff; 
            font-size: 16px; 
            font-weight: 600;
            border-radius: 10px; 
            outline: none; 
            cursor: pointer;
            transition: all 0.3s ease;
        }
        #playlist-select:focus { border-color: #3b82f6; background: rgba(255,255,255,0.1); }
        #playlist-select option { background: #0f172a; color: #fff; }

        #custom-url { 
            flex: 1; padding: 12px 15px; 
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); 
            color:#fff; font-size:14px; border-radius: 10px; outline: none;
            transition: all 0.3s ease;
        }
        #custom-url:focus { border-color: #3b82f6; background: rgba(255,255,255,0.1); }
        
        #play-custom { 
            padding: 12px 20px; background: #2563eb; color: #fff; 
            border: none; border-radius: 10px; cursor: pointer; font-weight: bold; font-size: 15px;
            transition: background 0.2s;
        }
        #play-custom:hover { background: #1d4ed8; }
        
        #search { 
            flex: 1; padding: 12px 15px; 
            background: rgba(255,255,255,0.05); 
            border: 1px solid rgba(255,255,255,0.1); 
            color:#fff; font-size:16px; 
            border-radius: 10px; 
            transition: all 0.3s ease;
        }
        #search:focus { outline: none; border-color: #3b82f6; background: rgba(255,255,255,0.1); }

        .app-layout { 
            display: flex; 
            height: calc(100vh - 70px); 
            width: 100vw; 
        }

        /* Video Section (Main Left Area) */
        .video-container { 
            flex: 1; 
            background: #000; 
            position: relative; 
            display: flex; 
            flex-direction: column; 
        }
        
        .video-wrapper {
            flex: 1;
            position: relative;
            background: transparent;
        }

        .video-placeholder {
            position: absolute; top:0; left:0; right:0; bottom:0;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: radial-gradient(circle at center, #1f2937 0%, #030712 100%);
            z-index: 5; 
            transition: opacity 0.5s ease;
        }
        .video-placeholder h2 { color: rgba(255,255,255,0.4); font-size: 2rem; font-weight: 300; margin-top: 20px;}

        #player { width:100%; height: 100%; flex: 1; background: transparent; z-index: 2; position: relative; outline: none; }
        
        .video-footer { 
            padding: 25px 40px; 
            background: #0b1120; 
            border-top: 1px solid rgba(255,255,255,0.05); 
            z-index: 3;
            min-height: 140px;
        }
        #ch-title { font-size: 36px; color: #fff; font-weight: 700; letter-spacing: 0.5px; }
        #ch-grp { font-size: 18px; color: #60a5fa; text-transform: uppercase; margin-top: 8px; letter-spacing: 1px; font-weight: 600;}
        #ch-url { font-size: 12px; color: #4b5563; margin-top: 12px; font-family: monospace; word-break: break-all; }

        /* Right Sidebar (Up Next) */
        .list-pane { 
            width: 400px; 
            background:#0f172a; 
            border-left: 1px solid rgba(255,255,255,0.05); 
            display: flex; 
            flex-direction: column; 
            z-index: 20;
        }
        
        .list-header {
            padding: 15px 20px;
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .scroller { flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; padding: 15px; }
        
        /* Custom Scrollbar */
        .scroller::-webkit-scrollbar { width: 8px; }
        .scroller::-webkit-scrollbar-track { background: transparent; }
        .scroller::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
        .scroller::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }

        .card { 
            padding: 15px; 
            cursor:pointer; 
            background: rgba(255,255,255,0.02); 
            margin-bottom: 10px; 
            border-radius: 12px; 
            transition: all 0.2s ease;
            border: 1px solid rgba(255,255,255,0.05);
            display: flex;
            flex-direction: column;
        }
        .card:hover { background: rgba(255,255,255,0.05); border-color: rgba(96,165,250,0.3); }
        .card:active { transform: scale(0.98); }
        .card.active { 
            background: rgba(37, 99, 235, 0.15); 
            border-color: #2563eb;
        }
        .card.active .v-name { color: #60a5fa; }
        
        .v-name { display:block; font-weight: 600; font-size: 1.2rem; margin-bottom: 6px; color: #e2e8f0; }
        .v-grp { font-size: 0.9rem; color:#9ca3af; font-weight: 500; display: block; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Premium Loader Spinner */
        .spinner {
            border: 4px solid rgba(255,255,255,0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border-left-color: #60a5fa;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Legacy Console Tweaks */
        .is-vita .video-container, .is-retro .video-container { display: none; }
        .is-vita .top-nav, .is-retro .top-nav { flex-wrap: wrap; height: auto; padding: 15px; }
        .is-vita .nav-controls, .is-retro .nav-controls { flex-wrap: wrap; }
        .is-vita .list-pane, .is-retro .list-pane { width: 100%; border-left: none; }
        
        /* Modern Mobile Responsive Tweaks */
        @media (max-width: 1024px) {
            .app-layout { flex-direction: column; }
            .video-container { flex: none; height: 50vh; }
            .list-pane { width: 100%; border-left: none; border-top: 1px solid rgba(255,255,255,0.05); flex: 1; }
            .top-nav { height: auto; flex-wrap: wrap; padding: 15px; }
            .nav-controls { min-width: 100%; }
        }
    </style>

    <!-- Hls.js for HLS (.m3u8) native support -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <!-- Mpegts.js for MPEG-TS (.ts) native support -->
    <script src="https://cdn.jsdelivr.net/npm/mpegts.js@latest/dist/mpegts.js"></script>
    <!-- Artplayer for premium VLC-like video player UI -->
    <script src="https://cdn.jsdelivr.net/npm/artplayer/dist/artplayer.js"></script>
</head>
<body id="app-body">
    
    <!-- Top Navigation (YouTube Style) -->
    <div class="top-nav">
        <a href="../" class="nav-brand">
            FinnTV
        </a>
        <div class="nav-controls">
            <select id="playlist-select" onchange="playCustom()">
                <option value="../m3u/world.m3u">World Channels</option>
                <option value="../m3u/asia.m3u">Asia Channels</option>
                <option value="../m3u/egypt.m3u">Egypt Channels</option>
                <option value="../m3u/india.m3u">India Channels</option>
                <option value="../m3u/spanish.m3u">Spain Channels</option>
                <option value="../m3u/sport.m3u">Sport Channels</option>
                <option value="custom">Custom URL...</option>
            </select>
            
            <input type="text" id="custom-url" placeholder="Paste custom URL here...">
            <button id="play-custom" onclick="playCustom()">Play</button>
            
            <input type="text" id="search" placeholder="Search channels...">
        </div>
    </div>

    <div class="app-layout">
        <!-- Main Video Area -->
        <div class="video-container">
            <div class="video-wrapper">
                <div class="video-placeholder" id="placeholder">
                    <h2>Select a channel to start watching</h2>
                </div>
                <div id="player"></div>
            </div>
            <div class="video-footer">
                <div id="ch-title">FinnTV Premium</div>
                <div id="ch-grp">Ready to play</div>
                <div id="ch-url"></div>
            </div>
        </div>
        
        <!-- Right Sidebar (Up Next) -->
        <div class="list-pane">
            <div class="list-header">Up Next</div>
            <div class="scroller" id="list">
                <div style="padding:40px 20px; text-align:center; color:#666;">
                    <div class="spinner"></div>
                    Loading Playlist...
                </div>
            </div>
        </div>
    </div>

    <script>
        var list = document.getElementById("list"),
            title = document.getElementById("ch-title"),
            grpText = document.getElementById("ch-grp"),
            search = document.getElementById("search"),
            placeholder = document.getElementById("placeholder"),
            channels = [], filtered = [];

        var art = null;
        var currentFallbackUrl = null;
        var currentCategory = "";

        var ua = navigator.userAgent;
        var isVita = ua.indexOf('PlayStation Vita') !== -1;
        var isRetro = ua.indexOf('Nintendo') !== -1 || 
                      ua.indexOf('PlayStation Portable') !== -1 || 
                      ua.indexOf('PlayStation 3') !== -1 || 
                      ua.indexOf('Xbox') !== -1;
        
        var body = document.getElementById("app-body");
        if (isVita) body.className += " is-vita";
        if (isRetro) body.className += " is-retro";

        function safeBtoa(str) {
            try { return btoa(str); } catch (e) {
                try { return btoa(unescape(encodeURIComponent(str))); } catch (e2) { return ''; }
            }
        }

        function checkIsLive(url) {
            var u = url.toLowerCase();
            if (u.indexOf('.mp4') !== -1 || u.indexOf('.mkv') !== -1 || u.indexOf('.avi') !== -1 || u.indexOf('/movies/') !== -1 || u.indexOf('/vod/') !== -1 || u.indexOf('/series/') !== -1) {
                return false;
            }
            return true;
        }

        function cleanUpPlayers(artInstance) {
            if (!artInstance) return;
            if (artInstance.hls) {
                artInstance.hls.destroy();
                artInstance.hls = null;
            }
            if (artInstance.mpegtsPlayer) {
                artInstance.mpegtsPlayer.destroy();
                artInstance.mpegtsPlayer = null;
            }
        }

        function triggerFallback() {
            if (currentFallbackUrl) {
                var fallback = currentFallbackUrl;
                currentFallbackUrl = null; // Prevent loop
                grpText.innerText = currentCategory + ' (switching format...)';
                if (art) {
                    art.switchUrl(fallback, 'ts');
                    art.play();
                }
            } else {
                grpText.innerText = currentCategory + ' (stream unavailable)';
            }
        }

        function initPlayer(isLiveStream) {
            if (art) {
                cleanUpPlayers(art);
                art.destroy();
                art = null;
            }
            
            art = new Artplayer({
                container: '#player',
                url: '', 
                setting: true,
                volume: 0.8,
                isLive: isLiveStream,
                autoplay: true,
                pip: true,
                fullscreen: true,
                fullscreenWeb: true,
                playbackRate: !isLiveStream, 
                aspectRatio: true,
                hotkey: true,
                theme: '#2563eb',
                customType: {
                    m3u8: function (video, url, artInstance) {
                        cleanUpPlayers(artInstance);
                        if (Hls.isSupported()) {
                            const hls = new Hls();
                            hls.loadSource(url);
                            hls.attachMedia(video);
                            artInstance.hls = hls;
                            
                            hls.on(Hls.Events.ERROR, function (event, data) {
                                if (data.fatal) {
                                    triggerFallback();
                                }
                            });
                        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                            video.src = url;
                        } else {
                            artInstance.notice.show = 'HLS is not supported in this browser';
                        }
                    },
                    ts: function (video, url, artInstance) {
                        cleanUpPlayers(artInstance);
                        if (mpegts.getFeatureList().mseLivePlayback) {
                            const mpegtsPlayer = mpegts.createPlayer({
                                type: 'mse',
                                isLive: isLiveStream,
                                url: url
                            });
                            mpegtsPlayer.attachMediaElement(video);
                            mpegtsPlayer.load();
                            artInstance.mpegtsPlayer = mpegtsPlayer;
                        } else {
                            video.src = url;
                        }
                    }
                }
            });

            art.on('video:error', function(e) {
                triggerFallback();
            });
            
            art.on('destroy', function() {
                cleanUpPlayers(art);
            });
        }

        function loadPlaylist(url) {
            var xhr = new XMLHttpRequest();
            xhr.open("GET", url, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) parse(xhr.responseText);
            };
            xhr.send();
        }

        function parse(txt) {
            var lines = txt.replace(/\r/g, '').split("\n"), cur = null;
            channels = [];
            for (var i=0; i<lines.length; i++) {
                var l = lines[i].trim();
                if (l.indexOf("#EXTINF:") === 0) {
                    cur = { t: "Unknown", g: "Live TV", u: "" };
                    var g = l.match(/group-title=\"([^\"]+)\"/);
                    if (g) cur.g = g[1];
                    var p = l.split(",");
                    if (p.length > 1) cur.t = p[p.length-1].trim();
                } else if (l.indexOf("http") === 0 && cur) {
                    cur.u = l; channels.push(cur); cur = null;
                }
            }
            filtered = channels; render();
        }

        function render() {
            list.innerHTML = "";
            var frag = document.createDocumentFragment();
            var limit = Math.min(filtered.length, 5000);
            for (var i=0; i<limit; i++) {
                (function(c) {
                    var el = document.createElement("div");
                    el.className = "card";
                    el.innerHTML = '<span class="v-name">' + c.t + '</span><span class="v-grp">' + c.g + '</span>';
                    el.onclick = function() {
                        var active = list.getElementsByClassName("card active");
                        for(var j=0; j<active.length; j++) active[j].className = "card";
                        el.className = "card active";
                        
                        var rawUrl = c.u;
                        var needsProxy = rawUrl.indexOf('http:') !== -1 || rawUrl.indexOf('https:') !== -1;
                        var proxyBase = '../api/stream_proxy.php?url=';
                        var finalUrl = needsProxy ? (proxyBase + encodeURIComponent(safeBtoa(rawUrl))) : rawUrl;
                        
                        if (isVita || isRetro) {
                            var finalVita = finalUrl;
                            if (finalVita.toLowerCase().indexOf('.m3u8') === -1 && finalVita.toLowerCase().indexOf('.ts') === -1 && finalVita.toLowerCase().indexOf('.mp4') === -1) {
                                finalVita += (finalVita.indexOf('?') === -1 ? '?' : '&') + 'ext=.ts';
                            }
                            window.location.href = finalVita;
                            return;
                        }

                        placeholder.style.opacity = '0';
                        setTimeout(function() { placeholder.style.display = 'none'; }, 500);
                        
                        title.innerText = c.t;
                        currentCategory = c.g;
                        grpText.innerText = c.g;
                        document.getElementById("ch-url").innerText = rawUrl;
                        
                        var isLive = checkIsLive(rawUrl);
                        initPlayer(isLive);
                        
                        if (rawUrl.toLowerCase().indexOf('.ts') !== -1) {
                            art.switchUrl(finalUrl, 'ts');
                        } else if (rawUrl.toLowerCase().indexOf('.m3u8') !== -1) {
                            art.switchUrl(finalUrl, 'm3u8');
                        } else {
                            art.switchUrl(finalUrl);
                        }
                    };
                    frag.appendChild(el);
                })(filtered[i]);
            }
            list.appendChild(frag);
        }

        function playCustom() {
            var select = document.getElementById("playlist-select");
            var inputUrl = document.getElementById("custom-url");
            var playBtn = document.getElementById("play-custom");
            
            var url = "";
            if (select.value === "custom") {
                inputUrl.style.display = "block";
                playBtn.style.display = "block";
                url = inputUrl.value.trim();
                if (!url) return;
            } else {
                inputUrl.style.display = "none";
                playBtn.style.display = "none";
                url = select.value;
            }

            if (select.value === "custom" && (url.toLowerCase().indexOf('.m3u8') !== -1 || url.toLowerCase().indexOf('.ts') !== -1 || url.toLowerCase().indexOf('.mp4') !== -1) && url.toLowerCase().indexOf('.m3u') === -1) {
                placeholder.style.opacity = '0';
                placeholder.style.display = 'none';
                title.innerText = "Custom Stream";
                currentCategory = "Custom";
                grpText.innerText = "Custom Stream";
                document.getElementById("ch-url").innerText = url;
                
                var needsProxy = url.indexOf('http:') !== -1 || url.indexOf('https:') !== -1;
                var proxyBase = '../api/stream_proxy.php?url=';
                var finalUrl = needsProxy ? (proxyBase + encodeURIComponent(safeBtoa(url))) : url;
                
                var isLive = checkIsLive(url);
                initPlayer(isLive);
                
                if (url.toLowerCase().indexOf('.ts') !== -1) {
                    art.switchUrl(finalUrl, 'ts');
                } else if (url.toLowerCase().indexOf('.m3u8') !== -1) {
                    art.switchUrl(finalUrl, 'm3u8');
                } else {
                    art.switchUrl(finalUrl);
                }
            } else {
                document.getElementById("list").innerHTML = '<div style="padding:40px 20px; text-align:center; color:#666;"><div class="spinner"></div>Loading Playlist...</div>';
                loadPlaylist(url);
            }
        }

        search.oninput = function() {
            var q = search.value.toLowerCase();
            filtered = [];
            for(var i=0; i<channels.length; i++) {
                if((channels[i].t + channels[i].g).toLowerCase().indexOf(q) !== -1) filtered.push(channels[i]);
            }
            render();
        };

        var m3u = "";
        var query = window.location.search.substring(1);
        if (query) {
            var vars = query.split("&");
            for (var i=0; i<vars.length; i++) {
                var pair = vars[i].split("=");
                if(pair[0] == "m3u") m3u = decodeURIComponent(pair[1]);
            }
        }
        if (!m3u) m3u = "../m3u/world.m3u";
        
        var select = document.getElementById("playlist-select");
        var found = false;
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].value === m3u) {
                select.selectedIndex = i;
                found = true;
                break;
            }
        }
        if (!found) {
            select.value = "custom";
            document.getElementById("custom-url").value = m3u;
            document.getElementById("custom-url").style.display = "block";
            document.getElementById("play-custom").style.display = "block";
        }

        // Hide custom controls by default on load
        if (select.value !== "custom") {
            document.getElementById("custom-url").style.display = "none";
            document.getElementById("play-custom").style.display = "none";
        }

        loadPlaylist(m3u);
    </script>
</body>
</html>
