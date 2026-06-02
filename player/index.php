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

    <!-- HLS.js for HLS (.m3u8) streams -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.13/dist/hls.min.js"></script>
    <!-- mpegts.js for raw TS streams -->
    <script src="https://cdn.jsdelivr.net/npm/mpegts.js@1.7.3/dist/mpegts.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:      #060c18;
            --card:    #0c1625;
            --nav:     #070e1c;
            --accent:  #3b82f6;
            --glow:    rgba(59,130,246,0.3);
            --text:    #e2e8f0;
            --muted:   #556070;
            --border:  rgba(255,255,255,0.06);
        }

        body, html {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', 'Segoe UI', sans-serif;
            overflow: hidden;
            height: 100%;
        }

        /* ── Top Nav ── */
        .top-nav {
            height: 62px;
            background: var(--nav);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 18px;
            gap: 14px;
            box-shadow: 0 2px 24px rgba(0,0,0,0.6);
            z-index: 100;
            position: relative;
        }
        .nav-brand {
            font-size: 21px;
            font-weight: 800;
            background: linear-gradient(135deg, #93c5fd, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            letter-spacing: 1px;
            white-space: nowrap;
        }
        .nav-controls { display: flex; align-items: center; gap: 8px; flex: 1; }

        select, input[type=text] {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 13.5px;
            border-radius: 9px;
            outline: none;
            padding: 8px 12px;
            transition: border-color 0.2s;
        }
        select:focus, input[type=text]:focus { border-color: var(--accent); }
        select option { background: #0c1625; }
        #playlist-select { width: 210px; font-weight: 500; }
        #custom-url { flex: 1; display: none; }
        #custom-url::placeholder { color: var(--muted); }

        #play-custom {
            display: none;
            padding: 8px 16px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 9px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13.5px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            white-space: nowrap;
        }
        #play-custom:hover { background: #2563eb; box-shadow: 0 0 16px var(--glow); }

        .search-wrap { position: relative; flex: 1; max-width: 300px; }
        .search-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
        #search { width: 100%; padding-left: 32px; }
        #search::placeholder { color: var(--muted); }

        /* ── Layout ── */
        .app-layout { display: flex; height: calc(100vh - 62px); }

        /* ── Video Section ── */
        .video-container { flex: 1; display: flex; flex-direction: column; background: #000; }

        .video-wrapper { flex: 1; position: relative; background: #000; overflow: hidden; }

        /* Placeholder */
        .placeholder {
            position: absolute; inset: 0;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            background: radial-gradient(ellipse at center, #0d1b2e 0%, #040a14 100%);
            z-index: 5;
            transition: opacity 0.4s;
            gap: 14px;
            pointer-events: none;
        }
        .placeholder-icon { font-size: 60px; opacity: 0.18; }
        .placeholder-text { color: rgba(255,255,255,0.25); font-size: 1rem; }

        /* Native video */
        #tv { width: 100%; height: 100%; background: #000; display: block; outline: none; }

        /* ── Custom Controls ── */
        .controls {
            position: absolute; bottom: 0; left: 0; right: 0;
            padding: 32px 16px 12px;
            background: linear-gradient(transparent, rgba(0,0,0,0.88));
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 10;
        }
        .video-wrapper:hover .controls,
        .video-wrapper.show-controls .controls { opacity: 1; }

        .progress-wrap {
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.15);
            border-radius: 2px;
            cursor: pointer;
            margin-bottom: 10px;
            position: relative;
        }
        .progress-fill {
            height: 100%;
            background: var(--accent);
            border-radius: 2px;
            width: 0%;
            pointer-events: none;
        }
        .progress-buffer {
            position: absolute; top: 0; left: 0; height: 100%;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            pointer-events: none;
        }

        .ctrl-row { display: flex; align-items: center; gap: 10px; }

        .ctrl-btn {
            background: none; border: none; color: #fff; cursor: pointer;
            font-size: 18px; padding: 4px 6px; border-radius: 6px;
            transition: background 0.15s; line-height: 1;
            display: flex; align-items: center; justify-content: center;
        }
        .ctrl-btn:hover { background: rgba(255,255,255,0.12); }
        .ctrl-btn svg { width: 20px; height: 20px; fill: currentColor; }

        .vol-wrap { display: flex; align-items: center; gap: 6px; }
        #vol-slider {
            -webkit-appearance: none;
            width: 70px; height: 3px;
            background: rgba(255,255,255,0.2);
            border-radius: 2px; outline: none; cursor: pointer;
        }
        #vol-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 12px; height: 12px;
            border-radius: 50%;
            background: #fff; cursor: pointer;
        }

        .time-display { color: rgba(255,255,255,0.7); font-size: 12px; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .ctrl-spacer { flex: 1; }

        /* Live badge */
        .live-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(239,68,68,0.2);
            color: #f87171; padding: 2px 9px;
            border-radius: 20px; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .live-dot {
            width: 6px; height: 6px; background: #ef4444;
            border-radius: 50%; animation: pulse 1.4s ease infinite;
        }
        @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.5;transform:scale(0.8)} }

        /* Big play button overlay */
        .big-play {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            z-index: 8; cursor: pointer; pointer-events: none;
            opacity: 0; transition: opacity 0.2s;
        }
        .big-play.show { opacity: 1; pointer-events: auto; }
        .big-play-btn {
            width: 72px; height: 72px; border-radius: 50%;
            background: rgba(59,130,246,0.85);
            border: 2px solid rgba(147,197,253,0.6);
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s; box-shadow: 0 0 40px var(--glow);
        }
        .big-play-btn:hover { transform: scale(1.08); background: var(--accent); }

        /* Status overlay */
        #status-overlay {
            position: absolute; top: 14px; left: 14px;
            background: rgba(0,0,0,0.65); backdrop-filter: blur(8px);
            padding: 6px 12px; border-radius: 8px;
            font-size: 12px; color: rgba(255,255,255,0.6);
            z-index: 12; display: none;
            border: 1px solid rgba(255,255,255,0.08);
        }

        /* Buffering spinner */
        #buffering {
            position: absolute; inset: 0;
            display: none; align-items: center; justify-content: center;
            z-index: 9; pointer-events: none;
        }
        .buf-spinner {
            width: 44px; height: 44px;
            border: 3px solid rgba(255,255,255,0.1);
            border-left-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Footer ── */
        .video-footer {
            padding: 14px 24px;
            background: var(--card);
            border-top: 1px solid var(--border);
            display: flex; align-items: center; gap: 14px;
            min-height: 70px;
        }
        .footer-info { flex: 1; min-width: 0; }
        #ch-title { font-size: 1.25rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #ch-grp { font-size: 0.75rem; color: var(--accent); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-top: 3px; }

        /* ── Sidebar ── */
        .list-pane {
            width: 370px;
            background: var(--card);
            border-left: 1px solid var(--border);
            display: flex; flex-direction: column;
        }
        .list-header {
            padding: 12px 16px;
            font-size: 12px; font-weight: 600;
            color: var(--muted); text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .ch-count {
            background: rgba(59,130,246,0.12); color: var(--accent);
            padding: 2px 9px; border-radius: 20px; font-size: 11px;
        }
        .scroller { flex: 1; overflow-y: auto; padding: 8px; }
        .scroller::-webkit-scrollbar { width: 4px; }
        .scroller::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 4px; }

        .card {
            padding: 10px 12px; cursor: pointer; border-radius: 9px;
            margin-bottom: 4px; border: 1px solid transparent;
            transition: all 0.13s; display: flex; align-items: center; gap: 10px;
        }
        .card:hover { background: rgba(255,255,255,0.04); border-color: rgba(59,130,246,0.18); }
        .card:active { transform: scale(0.98); }
        .card.active { background: rgba(59,130,246,0.1); border-color: rgba(59,130,246,0.45); }

        .card-logo {
            width: 34px; height: 34px; border-radius: 7px;
            object-fit: contain; background: rgba(255,255,255,0.04); flex-shrink: 0;
        }
        .card-icon {
            width: 34px; height: 34px; border-radius: 7px;
            background: rgba(59,130,246,0.12); flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: var(--accent);
        }
        .card-info { flex: 1; min-width: 0; }
        .v-name {
            display: block; font-weight: 600; font-size: 0.85rem; color: #dde5f0;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .card.active .v-name { color: #93c5fd; }
        .v-grp {
            display: block; font-size: 0.72rem; color: var(--muted); margin-top: 2px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        .loading-msg { text-align: center; padding: 50px 20px; color: var(--muted); }
        .spinner {
            width: 32px; height: 32px; margin: 0 auto 12px;
            border: 3px solid rgba(255,255,255,0.07);
            border-left-color: var(--accent);
            border-radius: 50%; animation: spin 0.9s linear infinite;
        }

        /* Toast */
        #toast {
            position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
            background: rgba(15,23,42,0.95); backdrop-filter: blur(12px);
            color: #fff; padding: 10px 20px; border-radius: 10px;
            font-size: 13px; font-weight: 500; z-index: 9999;
            display: none; box-shadow: 0 8px 32px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.08);
            white-space: nowrap; max-width: 90vw;
        }

        /* Mobile */
        @media (max-width: 860px) {
            .app-layout { flex-direction: column; }
            .video-container { height: 46vh; flex: none; }
            .list-pane { width: 100%; border-left: none; border-top: 1px solid var(--border); }
            .top-nav { height: auto; flex-wrap: wrap; padding: 10px; gap: 8px; }
            .nav-controls { flex-wrap: wrap; }
            #playlist-select { width: 155px; }
        }
    </style>
</head>
<body>

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
            <option value="custom">🔗 Custom...</option>
        </select>
        <input type="text" id="custom-url" placeholder="Paste M3U or stream URL...">
        <button id="play-custom" onclick="playCustom()">▶ Play</button>
        <div class="search-wrap">
            <svg class="search-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" id="search" placeholder="Search channels...">
        </div>
    </div>
</div>

<div class="app-layout">
    <div class="video-container">
        <div class="video-wrapper" id="vwrap">

            <div class="placeholder" id="placeholder">
                <div class="placeholder-icon">📺</div>
                <div class="placeholder-text">Select a channel to start</div>
            </div>

            <video id="tv" playsinline></video>

            <div id="buffering"><div class="buf-spinner"></div></div>
            <div id="status-overlay"></div>

            <div class="big-play show" id="big-play" onclick="togglePlay()">
                <div class="big-play-btn">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>
                </div>
            </div>

            <div class="controls" id="controls">
                <div class="progress-wrap" id="progress-wrap">
                    <div class="progress-buffer" id="progress-buffer"></div>
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="ctrl-row">
                    <button class="ctrl-btn" id="play-btn" onclick="togglePlay()" title="Play/Pause">
                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </button>
                    <div class="vol-wrap">
                        <button class="ctrl-btn" id="mute-btn" onclick="toggleMute()" title="Mute">
                            <svg viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3A4.5 4.5 0 0 0 14 7.97v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>
                        </button>
                        <input type="range" id="vol-slider" min="0" max="1" step="0.05" value="0.85" oninput="setVolume(this.value)">
                    </div>
                    <div class="time-display" id="time-display">Live</div>
                    <div class="ctrl-spacer"></div>
                    <div id="live-badge-ctrl" style="display:none">
                        <span class="live-badge"><span class="live-dot"></span>LIVE</span>
                    </div>
                    <button class="ctrl-btn" onclick="toggleFS()" title="Fullscreen">
                        <svg viewBox="0 0 24 24"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="video-footer">
            <div class="footer-info">
                <div id="ch-title">FinnTV Premium</div>
                <div id="ch-grp">Select a channel</div>
            </div>
        </div>
    </div>

    <div class="list-pane">
        <div class="list-header">
            Channels <span class="ch-count" id="ch-count">0</span>
        </div>
        <div class="scroller" id="list">
            <div class="loading-msg"><div class="spinner"></div>Loading...</div>
        </div>
    </div>
</div>

<div id="toast"></div>

<script>
// ── DOM ─────────────────────────────────────────────────
var tv          = document.getElementById('tv');
var listEl      = document.getElementById('list');
var placeholder = document.getElementById('placeholder');
var bigPlay     = document.getElementById('big-play');
var buffering   = document.getElementById('buffering');
var statusEl    = document.getElementById('status-overlay');
var progressFill= document.getElementById('progress-fill');
var progressBuf = document.getElementById('progress-buffer');
var timeDisplay = document.getElementById('time-display');
var playBtn     = document.getElementById('play-btn');
var muteBtn     = document.getElementById('mute-btn');
var liveBadge   = document.getElementById('live-badge-ctrl');

// ── State ────────────────────────────────────────────────
var channels = [], filtered = [];
var hlsInstance    = null;
var mpegtsInstance = null;
var currentIsLive  = false;

// ── Utilities ────────────────────────────────────────────
function toast(msg, ms) {
    var el = document.getElementById('toast');
    el.textContent = msg;
    el.style.display = 'block';
    clearTimeout(el._t);
    el._t = setTimeout(function(){ el.style.display = 'none'; }, ms || 4000);
}

function setStatus(msg) {
    if (msg) {
        statusEl.textContent = msg;
        statusEl.style.display = 'block';
        clearTimeout(statusEl._t);
        statusEl._t = setTimeout(function(){ statusEl.style.display = 'none'; }, 6000);
    } else {
        statusEl.style.display = 'none';
    }
}

function showBuffering(v) {
    buffering.style.display = v ? 'flex' : 'none';
}

function setPlaceholderVisible(v) {
    placeholder.style.opacity = v ? '1' : '0';
    placeholder.style.pointerEvents = v ? 'auto' : 'none';
}

function isLive(url) {
    var u = url.toLowerCase();
    return !(u.indexOf('.mp4') !== -1 || u.indexOf('.mkv') !== -1 || u.indexOf('.avi') !== -1
             || u.indexOf('/movie/') !== -1 || u.indexOf('/vod/') !== -1);
}

function streamType(url) {
    var u = url.toLowerCase().split('?')[0];
    if (u.indexOf('.m3u8') !== -1) return 'm3u8';
    if (u.indexOf('.ts')   !== -1) return 'ts';
    if (u.indexOf('.mp4')  !== -1) return 'mp4';
    return 'auto'; // default: try HLS
}

function proxyUrl(rawUrl) {
    try {
        var b64 = btoa(unescape(encodeURIComponent(rawUrl)));
        return '../api/stream_proxy.php?url=' + encodeURIComponent(b64);
    } catch(e) {
        return rawUrl;
    }
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Player ───────────────────────────────────────────────
function destroyAll() {
    if (hlsInstance) {
        try { hlsInstance.destroy(); } catch(e) {}
        hlsInstance = null;
    }
    if (mpegtsInstance) {
        try { mpegtsInstance.unload(); mpegtsInstance.detachMediaElement(); mpegtsInstance.destroy(); } catch(e) {}
        mpegtsInstance = null;
    }
    tv.pause();
    tv.removeAttribute('src');
    tv.load();
}

function playUrl(rawUrl, name, group) {
    setPlaceholderVisible(false);
    document.getElementById('ch-title').textContent = name || '';
    document.getElementById('ch-grp').textContent   = group || '';

    currentIsLive = isLive(rawUrl);
    liveBadge.style.display = currentIsLive ? 'block' : 'none';
    timeDisplay.textContent = currentIsLive ? 'LIVE' : '0:00 / 0:00';
    progressFill.style.width = '0%';

    var stype = streamType(rawUrl);
    var purl  = proxyUrl(rawUrl);

    setStatus('Connecting...');
    showBuffering(true);
    destroyAll();

    if (stype === 'ts') {
        // mpegts.js handles raw TS
        setStatus('TS stream');
        if (mpegts.getFeatureList().mseLivePlayback) {
            mpegtsInstance = mpegts.createPlayer({
                type: 'mse',
                isLive: currentIsLive,
                url: purl,
                cors: true
            }, {
                enableWorker: true,
                liveBufferLatencyChasing: currentIsLive,
            });
            mpegtsInstance.attachMediaElement(tv);
            mpegtsInstance.load();
            mpegtsInstance.on(mpegts.Events.ERROR, function(type, detail) {
                showBuffering(false);
                toast('⚠ Stream error: ' + type);
                setStatus('Error: ' + type);
            });
            tv.volume = document.getElementById('vol-slider').value;
            tv.play().catch(function(){});
        } else {
            tv.src = purl;
            tv.play().catch(function(){});
        }

    } else if (stype === 'm3u8' || stype === 'auto') {
        // hls.js for HLS streams
        if (Hls.isSupported()) {
            setStatus('HLS stream');
            hlsInstance = new Hls({
                enableWorker: true,
                lowLatencyMode: currentIsLive,
                backBufferLength: currentIsLive ? 30 : 90,
                maxBufferLength: currentIsLive ? 20 : 60,
                debug: false
            });
            hlsInstance.loadSource(purl);
            hlsInstance.attachMedia(tv);

            hlsInstance.on(Hls.Events.MANIFEST_PARSED, function() {
                setStatus('');
                showBuffering(false);
                tv.volume = document.getElementById('vol-slider').value;
                tv.play().catch(function(){
                    bigPlay.classList.add('show');
                    toast('Click ▶ to start playback');
                });
            });

            hlsInstance.on(Hls.Events.ERROR, function(evt, data) {
                if (data.fatal) {
                    showBuffering(false);
                    if (data.type === Hls.ErrorTypes.NETWORK_ERROR) {
                        toast('⚠ Network error — retrying...');
                        setStatus('Retrying...');
                        hlsInstance.startLoad();
                    } else if (data.type === Hls.ErrorTypes.MEDIA_ERROR) {
                        toast('⚠ Media error — recovering...');
                        hlsInstance.recoverMediaError();
                    } else {
                        toast('⚠ Stream failed');
                        setStatus('Stream unavailable');
                    }
                }
            });

        } else if (tv.canPlayType('application/vnd.apple.mpegurl')) {
            // Native Safari HLS
            setStatus('Native HLS');
            tv.src = purl;
            tv.play().catch(function(){});
        } else {
            toast('HLS not supported in this browser');
        }

    } else {
        // MP4 / direct
        setStatus('');
        tv.src = purl;
        tv.volume = document.getElementById('vol-slider').value;
        tv.play().catch(function(){});
    }
}

// ── Video Element Events ──────────────────────────────────
tv.addEventListener('waiting',  function(){ showBuffering(true); });
tv.addEventListener('playing',  function(){ showBuffering(false); setStatus(''); updatePlayBtn(); });
tv.addEventListener('pause',    function(){ updatePlayBtn(); bigPlay.classList.add('show'); });
tv.addEventListener('play',     function(){ updatePlayBtn(); bigPlay.classList.remove('show'); });
tv.addEventListener('error',    function(){
    showBuffering(false);
    if (tv.error) toast('⚠ Playback error: ' + tv.error.message);
});
tv.addEventListener('timeupdate', function(){
    if (!currentIsLive && tv.duration && isFinite(tv.duration)) {
        var pct = (tv.currentTime / tv.duration) * 100;
        progressFill.style.width = pct + '%';
        timeDisplay.textContent = fmt(tv.currentTime) + ' / ' + fmt(tv.duration);
    }
    if (tv.buffered.length > 0 && tv.duration) {
        var bpct = (tv.buffered.end(tv.buffered.length-1) / tv.duration) * 100;
        progressBuf.style.width = bpct + '%';
    }
});

function fmt(s) {
    var m = Math.floor(s/60), ss = Math.floor(s%60);
    return m + ':' + (ss<10?'0':'')+ss;
}

// ── Controls ─────────────────────────────────────────────
function updatePlayBtn() {
    playBtn.innerHTML = tv.paused
        ? '<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>'
        : '<svg viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>';
}

function togglePlay() {
    if (tv.paused) { tv.play(); bigPlay.classList.remove('show'); }
    else           { tv.pause(); }
}

function toggleMute() {
    tv.muted = !tv.muted;
    muteBtn.innerHTML = tv.muted
        ? '<svg viewBox="0 0 24 24"><path d="M16.5 12A4.5 4.5 0 0 0 14 7.97v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51A8.796 8.796 0 0 0 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zm-13.27-8L4 5.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06A8.82 8.82 0 0 0 17.73 18l1.99 2L21 18.73 4.27 2 5.73.54z"/></svg>'
        : '<svg viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3A4.5 4.5 0 0 0 14 7.97v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>';
}

function setVolume(v) {
    tv.volume = v;
    tv.muted = (v == 0);
}

function toggleFS() {
    var wrap = document.querySelector('.video-container');
    if (document.fullscreenElement) document.exitFullscreen();
    else wrap.requestFullscreen && wrap.requestFullscreen();
}

// Progress bar click
document.getElementById('progress-wrap').addEventListener('click', function(e) {
    if (!tv.duration || currentIsLive) return;
    var rect = this.getBoundingClientRect();
    var pct = (e.clientX - rect.left) / rect.width;
    tv.currentTime = pct * tv.duration;
});

// Click on video = toggle controls show
var vwrap = document.getElementById('vwrap');
var controlsTimeout;
vwrap.addEventListener('mousemove', function() {
    vwrap.classList.add('show-controls');
    clearTimeout(controlsTimeout);
    controlsTimeout = setTimeout(function(){ vwrap.classList.remove('show-controls'); }, 3000);
});
vwrap.addEventListener('click', function(e) {
    if (e.target === tv) togglePlay();
});

// ── Playlist ─────────────────────────────────────────────
function loadPlaylist(url) {
    listEl.innerHTML = '<div class="loading-msg"><div class="spinner"></div>Loading playlist...</div>';
    channels = []; filtered = [];
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) parseM3U(xhr.responseText);
            else { listEl.innerHTML = '<div class="loading-msg">Error loading playlist (HTTP '+xhr.status+')</div>'; }
        }
    };
    xhr.send();
}

function parseM3U(txt) {
    var lines = txt.replace(/\r/g,'').split('\n');
    channels = [];
    var cur = null;
    for (var i = 0; i < lines.length; i++) {
        var l = lines[i].trim();
        if (l.indexOf('#EXTINF:') === 0) {
            cur = { t: 'Unknown', g: 'Live TV', u: '', logo: '' };
            var gm = l.match(/group-title="([^"]+)"/); if (gm) cur.g = gm[1];
            var lm = l.match(/tvg-logo="([^"]+)"/);   if (lm) cur.logo = lm[1];
            var p = l.split(','); if (p.length > 1) cur.t = p[p.length-1].trim();
        } else if (l.indexOf('http') === 0 && cur) {
            cur.u = l; channels.push(cur); cur = null;
        }
    }
    filtered = channels;
    render();
}

function render() {
    listEl.innerHTML = '';
    document.getElementById('ch-count').textContent = filtered.length.toLocaleString();
    var frag = document.createDocumentFragment();
    var lim = Math.min(filtered.length, 5000);
    for (var i = 0; i < lim; i++) {
        (function(c) {
            var el = document.createElement('div');
            el.className = 'card';
            var logoHtml = c.logo
                ? '<img class="card-logo" src="'+escHtml(c.logo)+'" onerror="this.style.display=\'none\'">'
                : '<div class="card-icon">'+(c.t[0]||'?')+'</div>';
            el.innerHTML = logoHtml +
                '<div class="card-info"><span class="v-name">'+escHtml(c.t)+'</span><span class="v-grp">'+escHtml(c.g)+'</span></div>';
            el.onclick = function() {
                listEl.querySelectorAll('.card.active').forEach(function(a){ a.classList.remove('active'); });
                el.classList.add('active');
                playUrl(c.u, c.t, c.g);
            };
            frag.appendChild(el);
        })(filtered[i]);
    }
    listEl.appendChild(frag);
}

// ── Playlist Switcher ─────────────────────────────────────
function handlePlaylistChange() {
    var sel = document.getElementById('playlist-select');
    var cu  = document.getElementById('custom-url');
    var pb  = document.getElementById('play-custom');
    if (sel.value === 'custom') {
        cu.style.display = 'block'; pb.style.display = 'block';
    } else {
        cu.style.display = 'none'; pb.style.display = 'none';
        loadPlaylist(sel.value);
    }
}

function playCustom() {
    var url = document.getElementById('custom-url').value.trim();
    if (!url) return;
    var u = url.toLowerCase().split('?')[0];
    var isDirect = (u.indexOf('.m3u8')!==-1||u.indexOf('.ts')!==-1||u.indexOf('.mp4')!==-1) && u.indexOf('.m3u')===-1;
    if (isDirect) { playUrl(url, 'Custom Stream', 'Custom'); }
    else { loadPlaylist(url); }
}

// ── Search ────────────────────────────────────────────────
document.getElementById('search').oninput = function() {
    var q = this.value.toLowerCase();
    filtered = q ? channels.filter(function(c){ return (c.t+' '+c.g).toLowerCase().indexOf(q)!==-1; }) : channels;
    render();
};

// ── Init ──────────────────────────────────────────────────
(function() {
    var m3u = '../m3u/world.m3u';
    var qs = window.location.search.substring(1).split('&');
    for (var i = 0; i < qs.length; i++) {
        var p = qs[i].split('=');
        if (p[0]==='m3u') m3u = decodeURIComponent(p[1]);
    }
    var sel = document.getElementById('playlist-select');
    var found = false;
    for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === m3u) { sel.selectedIndex = i; found = true; break; }
    }
    if (!found) {
        sel.value = 'custom';
        document.getElementById('custom-url').value = m3u;
        document.getElementById('custom-url').style.display = 'block';
        document.getElementById('play-custom').style.display = 'block';
    }
    loadPlaylist(m3u);
})();
</script>
</body>
</html>
