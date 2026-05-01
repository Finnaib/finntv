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
        .app-layout { display: flex; height: 100vh; width: 100vw; }
        
        /* List Pane (Sidebar) */
        .list-pane { 
            width: 320px; 
            background:#0f172a; 
            border-right: 1px solid rgba(255,255,255,0.05); 
            display: flex; 
            flex-direction: column; 
            z-index: 20;
            box-shadow: 5px 0 20px rgba(0,0,0,0.3);
        }
        .search-container { padding: 15px 20px 5px 20px; background: #0f172a; }
        .custom-url-container { padding: 5px 20px 15px 20px; background: #0f172a; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; gap: 8px; }
        #custom-url { 
            flex: 1; padding: 10px; 
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); 
            color:#fff; font-size:12px; border-radius: 8px; outline: none;
            transition: all 0.3s ease;
        }
        #custom-url:focus { border-color: #3b82f6; background: rgba(255,255,255,0.1); }
        #play-custom { 
            padding: 10px 15px; background: #2563eb; color: #fff; 
            border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 13px;
            transition: background 0.2s;
        }
        #play-custom:hover { background: #1d4ed8; }
        #search { 
            width:100%; padding: 12px 15px; 
            background: rgba(255,255,255,0.05); 
            border: 1px solid rgba(255,255,255,0.1); 
            color:#fff; font-size:14px; 
            border-radius: 10px; 
            transition: all 0.3s ease;
        }
        #search:focus { outline: none; border-color: #3b82f6; background: rgba(255,255,255,0.1); }
        
        .scroller { flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; padding: 10px; }
        
        /* Custom Scrollbar */
        .scroller::-webkit-scrollbar { width: 6px; }
        .scroller::-webkit-scrollbar-track { background: transparent; }
        .scroller::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
        .scroller::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }

        .card { 
            padding: 15px; 
            cursor:pointer; 
            background: transparent; 
            margin-bottom: 8px; 
            border-radius: 12px; 
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        .card:hover { background: rgba(255,255,255,0.03); transform: translateX(5px); }
        .card:active { transform: scale(0.98); }
        .card.active { 
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); 
            color:#fff; 
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }
        .card.active .v-grp { color: rgba(255,255,255,0.8); }
        
        .v-name { display:block; font-weight: 600; font-size: 15px; margin-bottom: 4px; }
        .v-grp { font-size:11px; color:#9ca3af; font-weight: 500; display: block; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Video Pane */
        .video-pane { flex: 1; background: #000; position: relative; display: flex; flex-direction: column; }
        
        .video-placeholder {
            position: absolute; top:0; left:0; right:0; bottom:0;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: radial-gradient(circle at center, #1f2937 0%, #030712 100%);
            z-index: 5; 
            transition: opacity 0.5s ease;
        }
        .video-placeholder h2 { color: rgba(255,255,255,0.4); font-size: 1.8rem; font-weight: 300; margin-top: 20px;}
        .video-placeholder .icon { font-size: 4rem; opacity: 0.5; }

        #player { width:100%; height: 100%; flex: 1; background: transparent; z-index: 2; position: relative; outline: none; }
        
        .video-footer { 
            padding: 20px 30px; 
            background: #0b1120; 
            border-top: 1px solid rgba(255,255,255,0.05); 
            z-index: 3;
        }
        #ch-title { font-size: 24px; color: #60a5fa; font-weight: 600; letter-spacing: 0.5px; }
        #ch-grp { font-size: 13px; color: #9ca3af; text-transform: uppercase; margin-top: 5px; letter-spacing: 1px;}
        #ch-url { font-size: 11px; color: #4b5563; margin-top: 8px; font-family: monospace; word-break: break-all; }
        
        /* Legacy Console Tweaks (Vita, PSP, Nintendo, Xbox) */
        .is-vita .video-pane, .is-retro .video-pane { display: none; }
        .is-vita .list-pane, .is-retro .list-pane { width: 100%; border-right: none; box-shadow: none; }
        .is-vita .card, .is-retro .card { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); border-radius: 0; margin-bottom: 0; }
        .is-vita .card:hover, .is-retro .card:hover { transform: none; background: transparent; }
        .is-vita .card.active, .is-retro .card.active { background: #2563eb; }
        .is-vita .v-name, .is-retro .v-name { font-size: 18px; }

        /* Modern Mobile Responsive Tweaks */
        @media (max-width: 768px) {
            body:not(.is-vita):not(.is-retro) .app-layout { flex-direction: column-reverse; } /* Video on top */
            body:not(.is-vita):not(.is-retro) .video-pane { height: 35vh; flex: none; }
            body:not(.is-vita):not(.is-retro) .list-pane { width: 100%; border-right: none; border-top: 1px solid rgba(255,255,255,0.05); flex: 1; }
            body:not(.is-vita):not(.is-retro) .video-placeholder h2 { font-size: 1.2rem; }
            body:not(.is-vita):not(.is-retro) .video-footer { padding: 15px; }
            body:not(.is-vita):not(.is-retro) #ch-title { font-size: 18px; }
        }
    </style>
</head>
<body id="app-body">
    <div class="app-layout">
        <div class="list-pane">
            <div class="search-container">
                <input type="text" id="search" placeholder="Search channels...">
            </div>
            <div class="custom-url-container">
                <input type="text" id="custom-url" placeholder="Paste custom URL here...">
                <button id="play-custom" onclick="playCustom()">Play</button>
            </div>
            <div class="scroller" id="list">
                <div style="padding:40px 20px; text-align:center; color:#666;">
                    <div style="font-size: 2rem; margin-bottom: 10px;">⏳</div>
                    Loading Playlist...
                </div>
            </div>
        </div>
        <div class="video-pane">
            <div class="video-placeholder" id="placeholder">
                <div class="icon">📺</div>
                <h2>Select a channel to start watching</h2>
            </div>
            <video id="player" controls playsinline preload="auto"></video>
            <div class="video-footer">
                <div id="ch-title">FinnTV Premium</div>
                <div id="ch-grp">Ready to play</div>
                <div id="ch-url"></div>
            </div>
        </div>
    </div>

    <script>
        var player = document.getElementById("player"),
            list = document.getElementById("list"),
            title = document.getElementById("ch-title"),
            grpText = document.getElementById("ch-grp"),
            search = document.getElementById("search"),
            placeholder = document.getElementById("placeholder"),
            channels = [], filtered = [];

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
            var limit = Math.min(filtered.length, 500);
            for (var i=0; i<limit; i++) {
                (function(c) {
                    var el = document.createElement("div");
                    el.className = "card";
                    el.innerHTML = '<span class="v-name">' + c.t + '</span><span class="v-grp">' + c.g + '</span>';
                    el.onclick = function() {
                        var active = list.getElementsByClassName("card active");
                        for(var j=0; j<active.length; j++) active[j].className = "card";
                        el.className = "card active";
                        
                        var streamUrl = c.u;
                        
                        // Convert .ts to .m3u8 for Xtream Codes compatibility
                        if ((streamUrl.indexOf('/live/') !== -1 || streamUrl.indexOf('/movie/') !== -1) && streamUrl.indexOf('.ts') !== -1) {
                            streamUrl = streamUrl.substring(0, streamUrl.length - 3) + '.m3u8';
                        }
                        
                        var needsProxy = isVita || isRetro || streamUrl.indexOf('http:') !== -1;
                        var finalUrl = needsProxy ? ('../api/stream_proxy.php?url=' + encodeURIComponent(safeBtoa(streamUrl))) : streamUrl;
                        
                        // Ensure Vita appends ?ext=.m3u8 for native player pickup
                        if (isVita && finalUrl.toLowerCase().indexOf('.m3u8') === -1 && finalUrl.toLowerCase().indexOf('.mp4') === -1) {
                            finalUrl += (finalUrl.indexOf('?') === -1 ? '?' : '&') + 'ext=.m3u8';
                        }
                        
                        if (isVita || isRetro) {
                            window.location.href = finalUrl;
                            return;
                        }

                        // Hide placeholder and update UI
                        placeholder.style.opacity = '0';
                        setTimeout(function() { placeholder.style.display = 'none'; }, 500);
                        
                        title.innerText = c.t;
                        grpText.innerText = c.g;
                        document.getElementById("ch-url").innerText = c.u;
                        player.src = finalUrl; player.play().catch(function(){});
                    };
                    frag.appendChild(el);
                })(filtered[i]);
            }
            list.appendChild(frag);
        }

        function playCustom() {
            var inputUrl = document.getElementById("custom-url").value.trim();
            if (!inputUrl) return;

            // Load the custom URL as a playlist
            document.getElementById("list").innerHTML = '<div style="padding:40px 20px; text-align:center; color:#666;"><div style="font-size: 2rem; margin-bottom: 10px;">⏳</div>Loading Custom Playlist...</div>';
            loadPlaylist(inputUrl);
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
        loadPlaylist(m3u);
    </script>
</body>
</html>
