<?php
/**
 * FinnTV Universal Player
 * One app for PC, PS Vita, and Retro Consoles
 */
header("Access-Control-Allow-Origin: *");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>FinnTV - Universal Player</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, html { 
            height: 100%; width: 100%; background: #060913; color: #fff; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow: hidden;
        }
        .app-container { display: flex; height: 100vh; width: 100vw; }
        
        /* Sidebar / Playlist Menu */
        .sidebar { 
            width: 300px; background: #0f121d; border-right: 1px solid #1a1a1a; 
            display: flex; flex-direction: column; z-index: 100;
        }
        .sidebar-header { padding: 20px; background: #161b2a; border-bottom: 1px solid #1a1a1a; }
        .logo { font-size: 24px; font-weight: bold; color: #00d4ff; text-align: center; }
        
        .menu-section { padding: 15px; border-bottom: 1px solid #1a1a1a; }
        .menu-title { font-size: 10px; color: #444; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-weight: bold; }
        
        .playlist-item { 
            padding: 12px; margin-bottom: 5px; background: #111625; border-radius: 6px; 
            cursor: pointer; transition: 0.2s; font-size: 14px; display: flex; align-items: center; gap: 10px;
        }
        .playlist-item:hover { background: #1a2238; }
        .playlist-item.active { background: #00d4ff; color: #000; font-weight: bold; }
        
        .search-box { padding: 10px; }
        #search { 
            width: 100%; padding: 12px; background: #0a0d16; border: 1px solid #2a2f3e; 
            color: #fff; border-radius: 8px; font-size: 14px;
        }

        .channel-list { flex: 1; overflow-y: auto; padding: 10px; -webkit-overflow-scrolling: touch; }
        .channel-card { 
            padding: 12px; background: #111625; border-radius: 6px; margin-bottom: 5px; 
            cursor: pointer; border-bottom: 1px solid #1a1a1a;
        }
        .channel-card:active { background: #1a2238; }
        .channel-card.active { background: #00d4ff; color: #000; }
        .chan-name { display: block; font-weight: 600; font-size: 14px; }
        .chan-grp { font-size: 10px; opacity: 0.6; margin-top: 4px; display: block; }

        /* Main Player Area */
        .main-content { flex: 1; display: flex; flex-direction: column; background: #000; position: relative; }
        #video-player { width: 100%; height: 100%; background: #000; }
        .player-overlay { 
            position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; 
            background: linear-gradient(transparent, rgba(0,0,0,0.9)); pointer-events: none;
        }
        #current-chan-title { font-size: 20px; font-weight: bold; color: #00d4ff; }
        #current-chan-grp { font-size: 12px; color: #888; }

        /* Custom URL Form */
        .custom-form { padding: 10px; background: #0a0d16; margin: 10px; border-radius: 8px; border: 1px solid #2a2f3e; }
        .custom-form input { width: 100%; padding: 8px; background: #111625; border: 1px solid #2a2f3e; color: #fff; border-radius: 4px; margin-bottom: 8px; font-size: 12px; }
        .custom-form button { width: 100%; padding: 8px; background: #1a6bb3; color: #fff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }

        /* Mobile / Vita Toggles */
        .menu-toggle { display: none; position: fixed; top: 10px; left: 10px; z-index: 1000; background: #00d4ff; color: #000; padding: 10px; border-radius: 4px; font-weight: bold; }

        /* Device Specific Fixes */
        .is-vita .sidebar { width: 100%; position: absolute; top: 0; left: 0; height: 100%; }
        .is-vita .main-content { display: none; }
        .is-vita .channel-card { padding: 20px; }
        .is-vita .chan-name { font-size: 18px; }
        
        .is-retro .sidebar { width: 100%; position: static; height: auto; }
        .is-retro .main-content { display: none; }
        .is-retro .app-container { flex-direction: column; overflow-y: auto; }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; position: absolute; transform: translateX(-100%); transition: 0.3s; }
            .sidebar.active { transform: translateX(0); }
            .menu-toggle { display: block; }
        }
    </style>
</head>
<body id="app-body">
    <div class="menu-toggle" onclick="toggleSidebar()">☰ MENU</div>

    <div class="app-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">📺 FinnTV</div>
            </div>

            <div class="menu-section">
                <div class="menu-title">Select Playlist</div>
                <div class="playlist-item active" onclick="loadM3U('/m3u/world.m3u', this)">🌍 World Channels</div>
                <div class="playlist-item" onclick="loadM3U('/m3u/asia.m3u', this)">🌏 Asia Channels</div>
                <div class="playlist-item" onclick="loadM3U('/m3u/egypt.m3u', this)">🦅 Egypt Channels</div>
                <div class="playlist-item" onclick="loadM3U('/m3u/india.m3u', this)">🇮🇳 India Channels</div>
                <div class="playlist-item" onclick="loadM3U('/m3u/sport.m3u', this)">⚽ Sport Channels</div>
            </div>

            <div class="custom-form">
                <div class="menu-title">Custom M3U</div>
                <input type="text" id="custom-url" placeholder="Paste .m3u URL">
                <button onclick="loadCustomM3U()">Load Playlist</button>
            </div>

            <div class="search-box">
                <input type="text" id="search" placeholder="Search channels..." oninput="filterChannels()">
            </div>

            <div class="channel-list" id="channel-list">
                <div style="padding:20px; text-align:center; color:#555;">Loading...</div>
            </div>
        </div>

        <div class="main-content">
            <video id="video-player" controls playsinline></video>
            <div class="player-overlay">
                <div id="current-chan-title">Welcome to FinnTV</div>
                <div id="current-chan-grp">Select a channel to begin</div>
            </div>
        </div>
    </div>

    <script>
        var channels = [], filtered = [];
        var isVita = navigator.userAgent.indexOf('PlayStation Vita') !== -1;
        var isRetro = navigator.userAgent.indexOf('Nintendo') !== -1 || navigator.userAgent.indexOf('PlayStation Portable') !== -1;
        
        if (isVita) document.getElementById("app-body").classList.add("is-vita");
        if (isRetro) document.getElementById("app-body").classList.add("is-retro");

        function toggleSidebar() {
            var sidebar = document.getElementById("sidebar");
            if (sidebar.classList.contains("active")) {
                sidebar.classList.remove("active");
            } else {
                sidebar.classList.add("active");
            }
        }

        function safeBtoa(str) {
            try { return btoa(str); } catch (e) {
                try { return btoa(unescape(encodeURIComponent(str))); } catch (e2) { return ''; }
            }
        }

        function loadM3U(url, el) {
            if (el) {
                var items = document.querySelectorAll('.playlist-item');
                for (var i = 0; i < items.length; i++) {
                    items[i].classList.remove('active');
                }
                el.classList.add('active');
            }
            if (window.innerWidth <= 768) {
                var sidebar = document.getElementById("sidebar");
                if (sidebar.classList.contains("active")) toggleSidebar();
            }

            document.getElementById("channel-list").innerHTML = '<div style="padding:20px; text-align:center; color:#00d4ff;">🔄 Fetching Playlist...</div>';
            
            var xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        parseM3U(xhr.responseText);
                    } else {
                        document.getElementById("channel-list").innerHTML = '<div style="padding:20px; text-align:center; color:#ff4444;">❌ Load Failed (' + xhr.status + ')</div>';
                    }
                }
            };
            xhr.send();
        }

        function loadCustomM3U() {
            var url = document.getElementById("custom-url").value;
            if (!url) return;
            loadM3U(url);
        }

        function parseM3U(txt) {
            var lines = txt.replace(/\r/g, '').split("\n"), cur = null;
            channels = [];
            for (var i=0; i<lines.length; i++) {
                var l = lines[i].trim();
                if (l.indexOf("#EXTINF:") === 0) {
                    cur = { t: "Unknown", g: "General", u: "" };
                    var g = l.match(/group-title=\"([^\"]+)\"/);
                    if (g) cur.g = g[1];
                    var p = l.split(",");
                    if (p.length > 1) cur.t = p[p.length-1].trim();
                } else if (l.indexOf("http") === 0 && cur) {
                    cur.u = l; channels.push(cur); cur = null;
                }
            }
            filtered = channels;
            renderChannels();
        }

        function renderChannels() {
            var list = document.getElementById("channel-list");
            list.innerHTML = "";
            var frag = document.createDocumentFragment();
            var limit = Math.min(filtered.length, 500);

            for (var i=0; i<limit; i++) {
                (function(c) {
                    var card = document.createElement("div");
                    card.className = "channel-card";
                    card.innerHTML = '<span class="chan-name">' + c.t + '</span><span class="chan-grp">' + c.g + '</span>';
                    card.onclick = function() {
                        playChannel(c);
                        var active = list.querySelector(".channel-card.active");
                        if (active) active.classList.remove("active");
                        card.classList.add("active");
                    };
                    frag.appendChild(card);
                })(filtered[i]);
            }
            list.appendChild(frag);
        }

        function filterChannels() {
            var q = document.getElementById("search").value.toLowerCase();
            filtered = [];
            for (var i = 0; i < channels.length; i++) {
                var c = channels[i];
                if ((c.t + c.g).toLowerCase().indexOf(q) !== -1) {
                    filtered.push(c);
                }
            }
            renderChannels();
        }

        function playChannel(c) {
            var lowerUrl = c.u.toLowerCase();
            var needsProxy = (window.location.protocol === 'https:' && c.u.indexOf('http:') !== -1) || 
                           lowerUrl.indexOf('.ts') !== -1 || isVita;
            var finalUrl = needsProxy ? ('/api/stream_proxy.php?url=' + encodeURIComponent(safeBtoa(c.u))) : c.u;
            
            if (isVita || isRetro) {
                // High-stability mode for Vita: Jump straight to system player
                window.location.href = finalUrl;
                return;
            }

            document.getElementById("current-chan-title").innerText = c.t;
            document.getElementById("current-chan-grp").innerText = c.g;
            var v = document.getElementById("video-player");
            v.src = finalUrl;
            v.play().catch(function(e) {});
        }

        // Auto-load first playlist
        loadM3U('/m3u/world.m3u');
    </script>
</body>
</html>
