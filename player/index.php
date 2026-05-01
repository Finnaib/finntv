<?php
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-cache, no-store, must-revalidate");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=640, user-scalable=yes">
    <title>FinnTV Premium Player</title>
    <style>
        * { box-sizing: border-box; }
        body, html { 
            margin:0; padding:0; background:#060913; color:#fff; 
            overflow:hidden; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        .app-layout { display: flex; height: 100vh; width: 100vw; }
        .video-pane { flex: 1; background:#000; position: relative; display: flex; flex-direction: column; }
        #player { width:100%; height: auto; flex: 1; background: #000; }
        .video-footer { padding: 12px; background: rgba(0,0,0,0.8); border-top: 1px solid #1a1a1a; }
        #ch-title { font-size: 18px; color: #00d4ff; font-weight: bold; }
        #ch-grp { font-size: 11px; color: #888; text-transform: uppercase; margin-top: 4px; }
        .list-pane { width: 280px; background:#0f121d; border-left: 1px solid #1a1a1a; display: flex; flex-direction: column; }
        .search-container { padding: 10px; background: #161b2a; }
        #search { width:100%; padding: 10px; background:#0a0d16; border:1px solid #2a2f3e; color:#fff; font-size:14px; border-radius: 8px; }
        .scroller { flex: 1; overflow-y: scroll !important; -webkit-overflow-scrolling: touch; padding: 5px; }
        .card { padding: 12px; border-bottom: 1px solid #1a1a1a; cursor:pointer; background:#111625; margin-bottom: 5px; border-radius: 6px; }
        .card:active { background: #1a2238; }
        .card.active { background:#00d4ff; color:#000; border-color: #00d4ff; }
        .v-name { display:block; font-weight: 600; font-size: 14px; }
        .v-grp { font-size:10px; opacity:0.6; font-weight: bold; margin-top: 4px; display: block; }
        
        /* Legacy Console Tweaks (Vita, PSP, Nintendo, Xbox) */
        .is-vita .video-pane, .is-retro .video-pane { display: none; }
        .is-vita .list-pane, .is-retro .list-pane { width: 100%; border-left: none; }
        .is-vita .card, .is-retro .card { padding: 20px; }
        .is-vita .v-name, .is-retro .v-name { font-size: 18px; }

        /* Modern Mobile Responsive Tweaks */
        @media (max-width: 768px) {
            body:not(.is-vita):not(.is-retro) .app-layout { flex-direction: column; }
            body:not(.is-vita):not(.is-retro) .video-pane { height: 35vh; flex: none; }
            body:not(.is-vita):not(.is-retro) .list-pane { width: 100%; border-left: none; border-top: 1px solid #1a1a1a; flex: 1; }
        }
    </style>
</head>
<body id="app-body">
    <div class="app-layout">
        <div class="video-pane">
            <video id="player" controls playsinline preload="auto"></video>
            <div class="video-footer">
                <div id="ch-title">FinnTV Premium</div>
                <div id="ch-grp">Select a channel</div>
            </div>
        </div>
        <div class="list-pane">
            <div class="search-container">
                <input type="text" id="search" placeholder="Search channels...">
            </div>
            <div class="scroller" id="list">
                <div style="padding:20px; text-align:center; color:#666;">Loading...</div>
            </div>
        </div>
    </div>

    <script>
        var player = document.getElementById("player"),
            list = document.getElementById("list"),
            title = document.getElementById("ch-title"),
            grpText = document.getElementById("ch-grp"),
            search = document.getElementById("search"),
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
                        
                        var needsProxy = isVita || isRetro || c.u.indexOf('http:') !== -1;
                        var finalUrl = needsProxy ? ('../api/stream_proxy.php?url=' + encodeURIComponent(safeBtoa(c.u))) : c.u;
                        
                        if (isVita || isRetro) {
                            window.location.href = finalUrl;
                            return;
                        }

                        title.innerText = c.t;
                        grpText.innerText = c.g;
                        player.src = finalUrl; player.play().catch(function(){});
                    };
                    frag.appendChild(el);
                })(filtered[i]);
            }
            list.appendChild(frag);
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
