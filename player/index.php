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

    <!-- Video.js v10 Core -->
    <link href="https://cdn.jsdelivr.net/npm/video.js@10/dist/video-js.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/video.js@10/dist/video.min.js"></script>
    <!-- HLS.js for HLS streams -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.13/dist/hls.min.js"></script>
    <!-- mpegts.js for raw TS streams -->
    <script src="https://cdn.jsdelivr.net/npm/mpegts.js@1.7.3/dist/mpegts.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-deep:    #050b18;
            --bg-card:    #0d1a2e;
            --bg-nav:     #07111f;
            --accent:     #3b82f6;
            --accent-glow: rgba(59,130,246,0.35);
            --text:       #e2e8f0;
            --text-muted: #64748b;
            --border:     rgba(255,255,255,0.07);
            --radius:     12px;
        }

        body, html {
            background: var(--bg-deep);
            color: var(--text);
            font-family: 'Inter', 'Segoe UI', sans-serif;
            overflow: hidden;
            height: 100%;
        }

        /* ── Top Nav ── */
        .top-nav {
            height: 64px;
            background: var(--bg-nav);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 16px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.5);
            position: relative;
            z-index: 100;
        }

        .nav-brand {
            font-size: 22px;
            font-weight: 800;
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            letter-spacing: 1px;
            white-space: nowrap;
        }

        .nav-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        #playlist-select {
            padding: 9px 14px;
            width: 220px;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            border-radius: var(--radius);
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        #playlist-select:focus { border-color: var(--accent); }
        #playlist-select option { background: #0d1a2e; }

        #custom-url {
            flex: 1;
            padding: 9px 14px;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            color: #fff;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            border-radius: var(--radius);
            outline: none;
            transition: border-color 0.2s;
            display: none;
        }
        #custom-url:focus { border-color: var(--accent); }

        #play-custom {
            padding: 9px 18px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            display: none;
        }
        #play-custom:hover { background: #2563eb; box-shadow: 0 0 14px var(--accent-glow); }

        .search-wrap {
            position: relative;
            flex: 1;
            max-width: 340px;
        }
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }
        #search {
            width: 100%;
            padding: 9px 14px 9px 36px;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            color: #fff;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            border-radius: var(--radius);
            outline: none;
            transition: border-color 0.2s;
        }
        #search:focus { border-color: var(--accent); }
        #search::placeholder { color: var(--text-muted); }

        /* ── Layout ── */
        .app-layout {
            display: flex;
            height: calc(100vh - 64px);
        }

        /* ── Video Area ── */
        .video-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #000;
            position: relative;
        }

        .video-wrapper {
            flex: 1;
            position: relative;
            background: #000;
            overflow: hidden;
        }

        .video-placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: radial-gradient(ellipse at center, #0d1a2e 0%, #050b18 100%);
            z-index: 5;
            transition: opacity 0.4s ease;
            gap: 16px;
        }
        .placeholder-icon { font-size: 64px; opacity: 0.2; }
        .placeholder-text { color: rgba(255,255,255,0.3); font-size: 1.1rem; font-weight: 400; }

        /* Video.js overrides */
        #finntv-player {
            width: 100% !important;
            height: 100% !important;
            position: absolute;
            inset: 0;
        }
        .video-js {
            width: 100%;
            height: 100%;
            background: #000;
        }
        .video-js .vjs-big-play-button {
            background: rgba(59,130,246,0.85);
            border: 2px solid #60a5fa;
            border-radius: 50%;
            width: 70px;
            height: 70px;
            line-height: 70px;
            font-size: 28px;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.2s;
        }
        .video-js:hover .vjs-big-play-button {
            background: rgba(59,130,246,1);
            box-shadow: 0 0 30px var(--accent-glow);
        }
        .video-js .vjs-control-bar {
            background: linear-gradient(transparent, rgba(0,0,0,0.85));
            height: 48px;
            padding: 0 10px;
        }
        .video-js .vjs-progress-control .vjs-play-progress { background: var(--accent); }
        .video-js .vjs-play-progress:before { color: var(--accent); }
        .video-js .vjs-volume-level { background: var(--accent); }
        .video-js .vjs-slider { background: rgba(255,255,255,0.2); }
        .video-js .vjs-load-progress { background: rgba(59,130,246,0.3); }
        .video-js .vjs-button > .vjs-icon-placeholder:before,
        .video-js .vjs-time-control,
        .video-js .vjs-remaining-time { color: #e2e8f0; }
        .video-js .vjs-menu-button-popup .vjs-menu { bottom: 3em; }

        /* ── Footer Info ── */
        .video-footer {
            padding: 18px 28px;
            background: var(--bg-card);
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .footer-info { flex: 1; min-width: 0; }
        #ch-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        #ch-grp {
            font-size: 0.8rem;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-top: 4px;
        }
        #ch-status {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 4px;
            font-family: monospace;
        }

        /* ── Sidebar ── */
        .list-pane {
            width: 380px;
            background: var(--bg-card);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }

        .list-header {
            padding: 14px 18px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .ch-count {
            background: rgba(59,130,246,0.15);
            color: var(--accent);
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .scroller {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            -webkit-overflow-scrolling: touch;
        }
        .scroller::-webkit-scrollbar { width: 4px; }
        .scroller::-webkit-scrollbar-track { background: transparent; }
        .scroller::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }

        .card {
            padding: 12px 14px;
            cursor: pointer;
            border-radius: 10px;
            margin-bottom: 6px;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .card:hover { background: rgba(255,255,255,0.05); border-color: rgba(59,130,246,0.2); }
        .card:active { transform: scale(0.98); }
        .card.active {
            background: rgba(59,130,246,0.12);
            border-color: rgba(59,130,246,0.5);
        }

        .card-logo {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            object-fit: contain;
            background: rgba(255,255,255,0.05);
            flex-shrink: 0;
        }
        .card-logo-placeholder {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: rgba(59,130,246,0.15);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: var(--accent);
            font-weight: 700;
        }

        .card-info { flex: 1; min-width: 0; }
        .v-name {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card.active .v-name { color: #60a5fa; }
        .v-grp {
            display: block;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Spinner */
        .spinner {
            width: 36px; height: 36px;
            border: 3px solid rgba(255,255,255,0.08);
            border-left-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
            margin: 0 auto 12px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .loading-msg {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-muted);
        }

        /* Status Badge */
        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(239,68,68,0.15);
            color: #f87171;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .live-dot {
            width: 6px; height: 6px;
            background: #ef4444;
            border-radius: 50%;
            animation: pulse 1.5s ease infinite;
        }
        @keyframes pulse {
            0%,100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }

        /* Error Toast */
        #error-toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(239,68,68,0.9);
            color: #fff;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            display: none;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            backdrop-filter: blur(10px);
        }

        /* Mobile */
        @media (max-width: 900px) {
            .app-layout { flex-direction: column; }
            .video-container { height: 45vh; flex: none; }
            .list-pane { width: 100%; border-left: none; border-top: 1px solid var(--border); }
            .top-nav { height: auto; flex-wrap: wrap; padding: 10px; gap: 8px; }
            .nav-controls { flex-wrap: wrap; }
            #playlist-select { width: 160px; }
            .video-footer { padding: 12px 16px; }
            #ch-title { font-size: 1.1rem; }
        }
    </style>
</head>
<body id="app-body">

    <!-- Top Navigation -->
    <div class="top-nav">
        <a href="../" class="nav-brand">FinnTV</a>
        <div class="nav-controls">
            <select id="playlist-select" onchange="handlePlaylistChange()">
                <option value="../m3u/world.m3u">🌍 World</option>
                <option value="../m3u/asia.m3u">🌏 Asia</option>
                <option value="../m3u/egypt.m3u">🇪🇬 Egypt</option>
                <option value="../m3u/india.m3u">🇮🇳 India</option>
                <option value="../m3u/spanish.m3u">🇪🇸 Spain</option>
                <option value="../m3u/sport.m3u">⚽ Sport</option>
                <option value="custom">🔗 Custom URL...</option>
            </select>
            <input type="text" id="custom-url" placeholder="Paste M3U or stream URL...">
            <button id="play-custom" onclick="playCustom()">▶ Play</button>
            <div class="search-wrap">
                <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="text" id="search" placeholder="Search channels...">
            </div>
        </div>
    </div>

    <div class="app-layout">
        <!-- Main Video Area -->
        <div class="video-container">
            <div class="video-wrapper">
                <div class="video-placeholder" id="placeholder">
                    <div class="placeholder-icon">📺</div>
                    <div class="placeholder-text">Select a channel to start watching</div>
                </div>
                <video id="finntv-player" class="video-js vjs-default-skin" playsinline></video>
            </div>
            <div class="video-footer">
                <div class="footer-info">
                    <div id="ch-title">FinnTV Premium</div>
                    <div id="ch-grp">Ready to play</div>
                    <div id="ch-status"></div>
                </div>
                <div id="live-badge-wrap" style="display:none">
                    <span class="live-badge"><span class="live-dot"></span> LIVE</span>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="list-pane">
            <div class="list-header">
                Channels
                <span class="ch-count" id="ch-count">0</span>
            </div>
            <div class="scroller" id="list">
                <div class="loading-msg">
                    <div class="spinner"></div>
                    Loading playlist...
                </div>
            </div>
        </div>
    </div>

    <div id="error-toast"></div>

    <script>
    // ── State ──────────────────────────────────────────────
    var channels = [], filtered = [];
    var player = null;
    var mpegtsPlayer = null;
    var currentChannel = null;

    var ua = navigator.userAgent;
    var isVita = ua.indexOf('PlayStation Vita') !== -1;
    var isRetro = ua.indexOf('Nintendo') !== -1 || ua.indexOf('PlayStation Portable') !== -1 ||
                  ua.indexOf('PlayStation 3') !== -1 || ua.indexOf('Xbox') !== -1;

    // ── DOM refs ──────────────────────────────────────────
    var listEl    = document.getElementById('list');
    var titleEl   = document.getElementById('ch-title');
    var grpEl     = document.getElementById('ch-grp');
    var statusEl  = document.getElementById('ch-status');
    var searchEl  = document.getElementById('search');
    var placeholder = document.getElementById('placeholder');
    var countEl   = document.getElementById('ch-count');
    var badgeWrap = document.getElementById('live-badge-wrap');

    // ── Utilities ─────────────────────────────────────────
    function showError(msg, duration) {
        var toast = document.getElementById('error-toast');
        toast.textContent = msg;
        toast.style.display = 'block';
        setTimeout(function() { toast.style.display = 'none'; }, duration || 4000);
    }

    function isLiveStream(url) {
        var u = url.toLowerCase();
        return !(u.indexOf('.mp4') !== -1 || u.indexOf('.mkv') !== -1 || u.indexOf('.avi') !== -1
                 || u.indexOf('/movie/') !== -1 || u.indexOf('/vod/') !== -1);
    }

    function detectStreamType(url) {
        var u = url.toLowerCase().split('?')[0];
        if (u.indexOf('.m3u8') !== -1) return 'm3u8';
        if (u.indexOf('.ts')   !== -1) return 'ts';
        if (u.indexOf('.mp4')  !== -1) return 'mp4';
        return 'auto'; // Try HLS first
    }

    function buildProxyUrl(rawUrl) {
        // Always proxy through our server to bypass ISP blocks
        return '../api/stream_proxy.php?url=' + encodeURIComponent(btoa(unescape(encodeURIComponent(rawUrl))));
    }

    // ── Player Init ───────────────────────────────────────
    function destroyPlayers() {
        if (mpegtsPlayer) {
            try { mpegtsPlayer.destroy(); } catch(e) {}
            mpegtsPlayer = null;
        }
        if (player) {
            try {
                player.pause();
                player.src('');
            } catch(e) {}
        }
    }

    function initVideoJS() {
        if (player) return; // Already initialized

        player = videojs('finntv-player', {
            controls: true,
            autoplay: true,
            preload: 'auto',
            fluid: false,
            responsive: false,
            playbackRates: [0.5, 1, 1.25, 1.5, 2],
            html5: {
                vhs: {
                    overrideNative: true,
                    enableLowInitialPlaylist: true,
                    handleManifestRedirects: true,
                    limitRenditionByPlayerDimensions: false
                },
                nativeAudioTracks: false,
                nativeVideoTracks: false
            },
            liveui: true,
        });

        player.on('error', function() {
            var err = player.error();
            showError('⚠️ Stream unavailable. Trying fallback...');
            statusEl.textContent = 'Error: ' + (err ? err.message : 'Unknown');
        });
    }

    function playStream(rawUrl, channelName, groupName, logo) {
        if (isVita || isRetro) {
            window.location.href = rawUrl;
            return;
        }

        // Hide placeholder
        placeholder.style.opacity = '0';
        setTimeout(function() { placeholder.style.display = 'none'; }, 400);

        // Update footer
        titleEl.textContent = channelName || 'Unknown';
        grpEl.textContent   = groupName || '';

        var live = isLiveStream(rawUrl);
        badgeWrap.style.display = live ? 'block' : 'none';

        var proxyUrl = buildProxyUrl(rawUrl);
        var stype = detectStreamType(rawUrl);

        statusEl.textContent = 'Connecting...';

        // Initialize Video.js if not already done
        initVideoJS();

        destroyPlayers();

        if (stype === 'ts') {
            // Use mpegts.js for raw TS streams, attach to video element
            statusEl.textContent = 'TS stream (mpegts.js)';
            if (mpegts.getFeatureList().mseLivePlayback) {
                mpegtsPlayer = mpegts.createPlayer({
                    type: 'mse',
                    isLive: live,
                    url: proxyUrl,
                    cors: true
                }, {
                    enableWorker: true,
                    liveBufferLatencyChasing: live,
                    liveBufferLatencyMaxLatency: live ? 10 : undefined,
                    liveBufferLatencyMinRemain: live ? 2 : undefined,
                });
                var videoEl = player.el().querySelector('video');
                mpegtsPlayer.attachMediaElement(videoEl);
                mpegtsPlayer.load();
                mpegtsPlayer.on(mpegts.Events.ERROR, function(type, detail) {
                    showError('⚠️ TS Stream error: ' + type);
                    statusEl.textContent = 'Error: ' + type;
                });
                mpegtsPlayer.play().catch(function() {});
            } else {
                // Fallback native
                player.src({ src: proxyUrl, type: 'video/mp2t' });
                player.play();
            }
        } else if (stype === 'm3u8' || stype === 'auto') {
            // Use hls.js if supported (most reliable for IPTV)
            if (Hls.isSupported()) {
                statusEl.textContent = 'HLS stream (hls.js)';
                // Use VHS from Video.js with custom loader
                var videoEl = player.el().querySelector('video');

                var hls = new Hls({
                    enableWorker: true,
                    lowLatencyMode: live,
                    backBufferLength: live ? 30 : 90,
                    maxBufferLength: live ? 15 : 60,
                    maxMaxBufferLength: live ? 30 : 120,
                    debug: false,
                    xhrSetup: function(xhr) {
                        xhr.withCredentials = false;
                    }
                });
                hls.loadSource(proxyUrl);
                hls.attachMedia(videoEl);
                player._hlsInstance = hls;

                hls.on(Hls.Events.MANIFEST_PARSED, function() {
                    statusEl.textContent = '▶ Playing (HLS)';
                    videoEl.play().catch(function(e) {
                        showError('Autoplay blocked — click to play');
                    });
                });
                hls.on(Hls.Events.ERROR, function(event, data) {
                    if (data.fatal) {
                        if (data.type === Hls.ErrorTypes.NETWORK_ERROR) {
                            showError('⚠️ Network error — retrying...');
                            hls.startLoad();
                        } else if (data.type === Hls.ErrorTypes.MEDIA_ERROR) {
                            showError('⚠️ Media error — recovering...');
                            hls.recoverMediaError();
                        } else {
                            showError('⚠️ Stream failed. Try a different channel.');
                            statusEl.textContent = 'Stream unavailable';
                        }
                    }
                });

                // Cleanup old hls instance on next play
                player.one('dispose', function() {
                    hls.destroy();
                });

            } else if (document.querySelector('video').canPlayType('application/vnd.apple.mpegurl')) {
                // Native HLS (Safari/iOS)
                statusEl.textContent = 'HLS stream (native)';
                player.src({ src: proxyUrl, type: 'application/x-mpegURL' });
                player.play();
            } else {
                showError('HLS is not supported in this browser');
            }
        } else {
            // MP4 / direct
            statusEl.textContent = '▶ Playing (MP4)';
            player.src({ src: proxyUrl, type: 'video/mp4' });
            player.play();
        }
    }

    // ── Playlist Loading ──────────────────────────────────
    function loadPlaylist(url) {
        listEl.innerHTML = '<div class="loading-msg"><div class="spinner"></div>Loading playlist...</div>';
        channels = []; filtered = [];
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) parseM3U(xhr.responseText);
                else showError('Failed to load playlist: HTTP ' + xhr.status);
            }
        };
        xhr.send();
    }

    function parseM3U(txt) {
        var lines = txt.replace(/\r/g, '').split('\n');
        channels = [];
        var cur = null;
        for (var i = 0; i < lines.length; i++) {
            var l = lines[i].trim();
            if (l.indexOf('#EXTINF:') === 0) {
                cur = { t: 'Unknown', g: 'Live TV', u: '', logo: '' };
                var g = l.match(/group-title="([^"]+)"/);
                if (g) cur.g = g[1];
                var logo = l.match(/tvg-logo="([^"]+)"/);
                if (logo) cur.logo = logo[1];
                var p = l.split(',');
                if (p.length > 1) cur.t = p[p.length - 1].trim();
            } else if (l.indexOf('http') === 0 && cur) {
                cur.u = l;
                channels.push(cur);
                cur = null;
            }
        }
        filtered = channels;
        render();

        // Auto-play first channel
        if (channels.length > 0 && !currentChannel) {
            var first = channels[0];
            playChannel(first, 0);
        }
    }

    function render() {
        listEl.innerHTML = '';
        countEl.textContent = filtered.length.toLocaleString();
        var frag = document.createDocumentFragment();
        var limit = Math.min(filtered.length, 5000);
        for (var i = 0; i < limit; i++) {
            (function(c, idx) {
                var el = document.createElement('div');
                el.className = 'card';
                el.dataset.idx = idx;

                var logoHTML;
                if (c.logo) {
                    logoHTML = '<img class="card-logo" src="' + c.logo + '" onerror="this.style.display=\'none\';this.nextSibling.style.display=\'flex\'">' +
                               '<div class="card-logo-placeholder" style="display:none">' + (c.t[0] || '?') + '</div>';
                } else {
                    logoHTML = '<div class="card-logo-placeholder">' + (c.t[0] || '?') + '</div>';
                }

                el.innerHTML = logoHTML +
                    '<div class="card-info">' +
                        '<span class="v-name">' + escapeHtml(c.t) + '</span>' +
                        '<span class="v-grp">' + escapeHtml(c.g) + '</span>' +
                    '</div>';

                el.onclick = function() {
                    // Clear active
                    var active = listEl.querySelectorAll('.card.active');
                    active.forEach(function(a) { a.classList.remove('active'); });
                    el.classList.add('active');
                    playChannel(c, idx);
                };
                frag.appendChild(el);
            })(filtered[i], i);
        }
        listEl.appendChild(frag);
    }

    function playChannel(c, idx) {
        currentChannel = c;

        // Clean up old HLS instance if any
        if (player && player._hlsInstance) {
            try { player._hlsInstance.destroy(); } catch(e) {}
            player._hlsInstance = null;
        }

        playStream(c.u, c.t, c.g, c.logo);
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ── Playlist Switcher ─────────────────────────────────
    function handlePlaylistChange() {
        var select = document.getElementById('playlist-select');
        var customUrl = document.getElementById('custom-url');
        var playBtn   = document.getElementById('play-custom');

        if (select.value === 'custom') {
            customUrl.style.display = 'block';
            playBtn.style.display   = 'block';
        } else {
            customUrl.style.display = 'none';
            playBtn.style.display   = 'none';
            currentChannel = null;
            loadPlaylist(select.value);
        }
    }

    function playCustom() {
        var url = document.getElementById('custom-url').value.trim();
        if (!url) return;

        // If it's a direct stream URL (not a playlist), play it directly
        var u = url.toLowerCase().split('?')[0];
        var isDirectStream = (u.indexOf('.m3u8') !== -1 || u.indexOf('.ts') !== -1 || u.indexOf('.mp4') !== -1) && u.indexOf('.m3u') === -1;

        if (isDirectStream) {
            currentChannel = null;
            placeholder.style.display = 'none';
            playStream(url, 'Custom Stream', 'Custom', '');
        } else {
            currentChannel = null;
            loadPlaylist(url);
        }
    }

    // ── Search ────────────────────────────────────────────
    searchEl.oninput = function() {
        var q = searchEl.value.toLowerCase();
        if (!q) {
            filtered = channels;
        } else {
            filtered = channels.filter(function(c) {
                return (c.t + ' ' + c.g).toLowerCase().indexOf(q) !== -1;
            });
        }
        render();
    };

    // ── Init ──────────────────────────────────────────────
    (function init() {
        var m3u = '../m3u/world.m3u';
        var query = window.location.search.substring(1);
        if (query) {
            var vars = query.split('&');
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split('=');
                if (pair[0] === 'm3u') m3u = decodeURIComponent(pair[1]);
            }
        }

        var select = document.getElementById('playlist-select');
        var found = false;
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].value === m3u) {
                select.selectedIndex = i;
                found = true;
                break;
            }
        }
        if (!found) {
            select.value = 'custom';
            document.getElementById('custom-url').value = m3u;
            document.getElementById('custom-url').style.display = 'block';
            document.getElementById('play-custom').style.display = 'block';
        }

        loadPlaylist(m3u);
    })();
    </script>
</body>
</html>
