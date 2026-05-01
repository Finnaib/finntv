<?php
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=640, user-scalable=yes">
    <title>FinnTV Vita Player</title>
    <style>
        * { box-sizing: border-box; }
        body, html { 
            margin:0; padding:0; background:#060913; color:#fff; 
            overflow:hidden; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        .app-layout { display: flex; height: 100vh; width: 100vw; }
        
        /* Video Section */
        .video-pane { 
            flex: 1; background:#000; position: relative; 
            display: flex; flex-direction: column;
        }
        #player { width:100%; height: auto; flex: 1; background: #000; }
        .video-footer { padding: 12px; background: rgba(0,0,0,0.8); border-top: 1px solid #1a1a1a; }
        #ch-title { font-size: 18px; color: #00d4ff; font-weight: bold; }
        #ch-grp { font-size: 11px; color: #888; text-transform: uppercase; margin-top: 4px; }

        /* List Section */
        .list-pane { 
            width: 280px; background:#0f121d; border-left: 1px solid #1a1a1a; 
            display: flex; flex-direction: column;
        }
        .search-container { padding: 10px; background: #161b2a; }
        #search { 
            width:100%; padding: 10px; background:#0a0d16; border:1px solid #2a2f3e; 
            color:#fff; font-size:14px; border-radius: 8px;
        }
        .scroller { 
            flex: 1; overflow-y: scroll !important; -webkit-overflow-scrolling: touch; 
            padding: 5px; -webkit-transform: translate3d(0,0,0);
        }
        .card { 
            padding: 12px; border-bottom: 1px solid #1a1a1a; cursor:pointer; 
            background:#111625; margin-bottom: 5px; border-radius: 6px;
            transition: background 0.2s;
        }
        .card:active { background: #1a2238; }
        .card.active { background:#00d4ff; color:#000; border-color: #00d4ff; }
        .card.active .v-grp { color: #000; opacity: 0.8; }
        .v-name { display:block; font-weight: 600; font-size: 14px; }
        .v-grp { font-size:10px; opacity:0.6; font-weight: bold; margin-top: 4px; display: block; }
        
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: #2a2f3e; border-radius: 2px; }

        /* Vita Optimization */
        .is-vita .video-pane { display: none; }
        .is-vita .list-pane { width: 100%; border-left: none; }
        .is-vita .card { padding: 20px; }
        .is-vita .v-name { font-size: 18px; }
        .is-vita .v-grp { font-size: 12px; }
    </style>
</head>
<body id="app-body">
    <div class="app-layout">
        <div class="video-pane">
            <video id="player" controls playsinline preload="auto"></video>
            <div class="video-footer">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div id="ch-title">FinnTV Premium</div>
                        <div id="ch-grp">Ready to stream</div>
                    </div>
                    <div id="native-ctrl" style="display:none;">
                        <a id="native-btn" href="#" target="_blank" style="background:#28a745; color:#fff; text-decoration:none; padding:8px 12px; border-radius:4px; font-size:12px; font-weight:bold;">🚀 Native Play</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="list-pane">
            <div class="search-container">
                <input type="text" id="search" placeholder="Search channels...">
            </div>
            <div class="scroller" id="list">
                <div style="padding:20px; text-align:center; color:#666;">Loading Channels...</div>
            </div>
        </div>
    </div>

    <script>
        var player = document.getElementById("player"),
            list = document.getElementById("list"),
            title = document.getElementById("ch-title"),
            grpText = document.getElementById("ch-grp"),
            search = document.getElementById("search"),
            nativeCtrl = document.getElementById("native-ctrl"),
            nativeBtn = document.getElementById("native-btn"),
            channels = [], filtered = [];

        var isVita = navigator.userAgent.indexOf('PlayStation Vita') !== -1;
        if (isVita) document.getElementById("app-body").classList.add("is-vita");

        // Vita Scroll Fix
        function applyVitaScrollFix(el) {
            if (!el || !isVita) return;
            var startY = 0, startScroll = 0;
            el.addEventListener('touchstart', function(e) {
                startY = e.touches[0].pageY;
                startScroll = el.scrollTop;
            }, { passive: true });
            el.addEventListener('touchmove', function(e) {
                var currentY = e.touches[0].pageY;
                el.scrollTop = startScroll + (startY - currentY);
                if (e.cancelable) e.preventDefault();
            }, { passive: false });
        }

        function safeBtoa(str) {
            try { return btoa(str); } catch (e) {
                try { return btoa(unescape(encodeURIComponent(str))); } catch (e2) { return ''; }
            }
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
            var fragment = document.createDocumentFragment();
            var limit = Math.min(filtered.length, 500); 
            for (var i=0; i<limit; i++) {
                (function(c) {
                    var el = document.createElement("div");
                    el.className = "card";
                    el.innerHTML = "<span class=\"v-name\">" + c.t + "</span><span class=\"v-grp\">" + c.g + "</span>";
                    el.onclick = function() {
                        var lowerUrl = c.u.toLowerCase();
                        var needsProxy = (window.location.protocol === 'https:' && c.u.indexOf('http:') !== -1) || 
                                       lowerUrl.indexOf('.ts') !== -1 || isVita;
                        var finalUrl = needsProxy ? ('/api/stream_proxy.php?url=' + encodeURIComponent(safeBtoa(c.u))) : c.u;
                        
                        if (isVita) {
                            // High-stability mode for Vita: Jump straight to system player
                            el.style.background = "#28a745";
                            el.innerHTML = "<span class=\"v-name\">🚀 Launching Player...</span><span class=\"v-grp\">Please wait</span>";
                            window.location.href = finalUrl;
                            setTimeout(function() { render(); }, 3000); // Reset list after 3s
                            return;
                        }

                        var active = list.querySelector(".card.active");
                        if(active) active.classList.remove("active");
                        el.classList.add("active");
                        title.innerText = c.t;
                        grpText.innerText = c.g;
                        player.removeAttribute('type');
                        player.src = finalUrl; player.load(); player.play().catch(function(){});
                    };
                    fragment.appendChild(el);
                })(filtered[i]);
            }
            list.appendChild(fragment);
            if (filtered.length === 0) list.innerHTML = '<div style="padding:20px; color:#666;">No channels found.</div>';
        }
        applyVitaScrollFix(list);

        search.oninput = function() {
            var q = search.value.toLowerCase();
            filtered = channels.filter(function(c) { return (c.t + c.g).toLowerCase().indexOf(q) !== -1; });
            render();
        };

        var m3u = "";
        var query = window.location.search.substring(1);
        if (query) {
            var vars = query.split("&");
            for (var i=0; i<vars.length; i++) {
                var pair = vars[i].split("=");
                if(pair[0] == "m3u"){ m3u = decodeURIComponent(pair[1]); }
            }
        }
        if (!m3u) m3u = "https://iptv-org.github.io/iptv/index.m3u";

        var xhr = new XMLHttpRequest();
        xhr.open("GET", m3u, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) parse(xhr.responseText);
        };
        xhr.send();
    </script>
</body>
</html>
