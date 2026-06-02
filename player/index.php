<?php
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-cache, no-store, must-revalidate");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>FinnTV Player</title>

    <!-- Video.js v8 (stable CDN, same look as v10 React demo) -->
    <link href="https://cdn.jsdelivr.net/npm/video.js@8.21.1/dist/video-js.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/video.js@8.21.1/dist/video.min.js"></script>
    <!-- HLS.js (for ISP-blocked streams that can't use VHS natively) -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.13/dist/hls.min.js"></script>
    <!-- mpegts.js for raw TS streams -->
    <script src="https://cdn.jsdelivr.net/npm/mpegts.js@1.7.3/dist/mpegts.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:     #0a0f1e;
            --card:   #0d1628;
            --nav:    #060d1c;
            --acc:    #f97316;
            --acc2:   #3b82f6;
            --txt:    #e2e8f0;
            --muted:  #4e5d70;
            --border: rgba(255,255,255,0.06);
        }

        html, body { height: 100%; background: var(--bg); color: var(--txt); font-family: 'Inter', sans-serif; overflow: hidden; }

        /* ── Nav ── */
        .top-nav {
            height: 58px; background: var(--nav);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; padding: 0 16px; gap: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.6); position: relative; z-index: 100;
        }
        .nav-brand {
            font-size: 19px; font-weight: 800; letter-spacing: 1px; text-decoration: none;
            background: linear-gradient(135deg, #fb923c, #f97316);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .nav-controls { display: flex; align-items: center; gap: 8px; flex: 1; }

        select, input[type=text] {
            background: rgba(255,255,255,0.05); border: 1px solid var(--border);
            color: #fff; font-family: 'Inter', sans-serif; font-size: 13px;
            border-radius: 8px; outline: none; padding: 7px 11px; transition: border-color .2s;
        }
        select:focus, input[type=text]:focus { border-color: var(--acc); }
        select option { background: #0d1628; }
        #playlist-select { width: 190px; font-weight: 500; cursor: pointer; }
        #custom-url { flex: 1; display: none; }
        #custom-url::placeholder { color: var(--muted); }
        #play-custom {
            display: none; padding: 7px 14px; background: var(--acc); color: #fff;
            border: none; border-radius: 8px; cursor: pointer; font-weight: 600;
            font-size: 13px; font-family: 'Inter', sans-serif; transition: background .2s;
        }
        #play-custom:hover { background: #ea6c0a; }
        .search-wrap { position: relative; flex: 1; max-width: 270px; }
        .s-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
        #search { width: 100%; padding-left: 30px; }
        #search::placeholder { color: var(--muted); }

        /* ── Layout ── */
        .app { display: flex; height: calc(100vh - 58px); }

        /* ── Video pane ── */
        .v-pane { flex: 1; min-width: 0; display: flex; flex-direction: column; background: #000; }

        .v-wrap {
            flex: 1; position: relative; background: #070707;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }

        /* Placeholder */
        .ph {
            position: absolute; inset: 0; z-index: 5;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px;
            background: radial-gradient(ellipse at 50% 40%, #0d1b30 0%, #030810 100%);
            transition: opacity .4s;
        }
        .ph-icon { font-size: 54px; opacity: .14; }
        .ph-txt  { color: rgba(255,255,255,.18); font-size: .93rem; }

        /* Video.js fills wrapper */
        .video-js { width: 100% !important; height: 100% !important; }
        #finntv-vjs { position: absolute; inset: 0; }

        /* ── VJS skin overrides ── */
        .video-js .vjs-big-play-button {
            width: 66px; height: 66px; border-radius: 50%;
            border: 2px solid rgba(249,115,22,.7);
            background: rgba(249,115,22,.88);
            top: 50%; left: 50%; margin: 0;
            transform: translate(-50%, -50%);
            line-height: 66px;
            transition: all .2s;
        }
        .video-js:hover .vjs-big-play-button {
            background: #f97316;
            box-shadow: 0 0 32px rgba(249,115,22,.5);
            transform: translate(-50%, -50%) scale(1.07);
        }
        .vjs-big-play-button .vjs-icon-placeholder::before { line-height: 66px; font-size: 28px; }

        .video-js .vjs-control-bar {
            background: linear-gradient(transparent, rgba(0,0,0,.88));
            height: 50px; padding: 0 8px; font-family: 'Inter', sans-serif;
        }
        .video-js .vjs-progress-holder { height: 4px; border-radius: 2px; }
        .video-js .vjs-play-progress { background: #f97316; border-radius: 2px; }
        .video-js .vjs-play-progress::before { color: #f97316; }
        .video-js .vjs-load-progress,
        .video-js .vjs-load-progress div { background: rgba(255,255,255,.2); border-radius: 2px; }
        .video-js .vjs-slider { background: rgba(255,255,255,.15); border-radius: 2px; }
        .video-js .vjs-volume-level { background: #f97316; border-radius: 2px; }
        .video-js .vjs-volume-level::before { color: #f97316; }
        .video-js .vjs-progress-control:hover .vjs-progress-holder { height: 6px; }

        .video-js .vjs-time-control { font-size: 13px; font-weight: 500; padding: 0 4px; min-width: auto; }
        .video-js .vjs-current-time,
        .video-js .vjs-time-divider,
        .video-js .vjs-duration { display: flex; align-items: center; }
        .video-js .vjs-playback-rate .vjs-playback-rate-value { font-size: 13px; font-weight: 600; }
        .video-js .vjs-button > .vjs-icon-placeholder::before { font-size: 20px; }
        .video-js .vjs-control { color: rgba(255,255,255,.88); }
        .video-js .vjs-control:hover { color: #fff; }

        /* Speed menu */
        .video-js .vjs-menu-button-popup .vjs-menu { bottom: 2.4em; }
        .video-js .vjs-menu-content {
            background: rgba(8,14,28,.96); border: 1px solid var(--border);
            border-radius: 8px; backdrop-filter: blur(10px);
        }
        .video-js .vjs-menu li.vjs-menu-item { font-size: 13px; font-family: 'Inter', sans-serif; }
        .video-js .vjs-menu li.vjs-menu-item:hover,
        .video-js .vjs-menu li.vjs-selected { background: rgba(249,115,22,.15); color: #f97316; }

        /* Loading spinner */
        .video-js .vjs-loading-spinner { border-color: rgba(249,115,22,.3); }
        .video-js .vjs-loading-spinner::before,
        .video-js .vjs-loading-spinner::after { border-top-color: #f97316; }

        /* ── Footer ── */
        .v-footer {
            padding: 11px 20px; background: var(--card);
            border-top: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px; min-height: 62px;
        }
        .fi { flex: 1; min-width: 0; }
        #ch-name { font-size: 1.15rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #ch-grp  { font-size: .72rem; color: var(--acc); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-top: 2px; }
        .live-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(239,68,68,.13); color: #f87171;
            padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
        }
        .ldot { width: 6px; height: 6px; background: #ef4444; border-radius: 50%; animation: pulse 1.4s ease infinite; }
        @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.8)} }

        /* ── Sidebar ── */
        .sidebar {
            width: 355px; flex-shrink: 0;
            background: var(--card); border-left: 1px solid var(--border);
            display: flex; flex-direction: column;
        }
        .sb-head {
            padding: 11px 15px; font-size: 11px; font-weight: 700;
            color: var(--muted); text-transform: uppercase; letter-spacing: 1.5px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .ch-count { background: rgba(249,115,22,.1); color: var(--acc); padding: 2px 9px; border-radius: 20px; font-size: 11px; }
        .scroller { flex: 1; overflow-y: auto; padding: 7px; }
        .scroller::-webkit-scrollbar { width: 3px; }
        .scroller::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 3px; }

        .card {
            display: flex; align-items: center; gap: 9px;
            padding: 9px 11px; border-radius: 9px; cursor: pointer;
            border: 1px solid transparent; margin-bottom: 3px; transition: all .12s;
        }
        .card:hover { background: rgba(255,255,255,.04); border-color: rgba(249,115,22,.18); }
        .card:active { transform: scale(.985); }
        .card.active { background: rgba(249,115,22,.08); border-color: rgba(249,115,22,.4); }
        .card-logo { width: 33px; height: 33px; border-radius: 7px; object-fit: contain; background: rgba(255,255,255,.04); flex-shrink: 0; }
        .card-icon { width: 33px; height: 33px; border-radius: 7px; background: rgba(249,115,22,.1); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: var(--acc); }
        .ci { flex: 1; min-width: 0; }
        .cn { display: block; font-weight: 600; font-size: .82rem; color: #cdd6e6; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card.active .cn { color: #fb923c; }
        .cg { display: block; font-size: .7rem; color: var(--muted); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .loading-msg { text-align: center; padding: 50px 20px; color: var(--muted); }
        .spin { width: 28px; height: 28px; margin: 0 auto 10px; border: 3px solid rgba(255,255,255,.06); border-left-color: var(--acc); border-radius: 50%; animation: rot .9s linear infinite; }
        @keyframes rot { to { transform: rotate(360deg); } }

        #toast {
            position: fixed; bottom: 22px; left: 50%; transform: translateX(-50%);
            background: rgba(8,14,28,.95); backdrop-filter: blur(12px); color: #fff;
            padding: 9px 18px; border-radius: 10px; font-size: 13px; font-weight: 500;
            z-index: 9999; display: none; border: 1px solid rgba(255,255,255,.08);
            box-shadow: 0 8px 30px rgba(0,0,0,.5); max-width: 90vw; white-space: nowrap;
        }

        @media (max-width: 860px) {
            .app { flex-direction: column; }
            .v-pane { height: 45vh; flex: none; }
            .sidebar { width: 100%; border-left: none; border-top: 1px solid var(--border); }
            .top-nav { height: auto; flex-wrap: wrap; padding: 10px; gap: 8px; }
            .nav-controls { flex-wrap: wrap; }
            #playlist-select { width: 145px; }
        }
    </style>
</head>
<body>

<div class="top-nav">
    <a href="../" class="nav-brand">FinnTV</a>
    <div class="nav-controls">
        <select id="playlist-select" onchange="onPlaylistChange()">
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
            <svg class="s-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" id="search" placeholder="Search channels...">
        </div>
    </div>
</div>

<div class="app">
    <div class="v-pane">
        <div class="v-wrap" id="vwrap">
            <div class="ph" id="ph">
                <div class="ph-icon">📺</div>
                <div class="ph-txt">Select a channel to start</div>
            </div>
            <video id="finntv-vjs"
                   class="video-js vjs-default-skin vjs-big-play-centered"
                   playsinline preload="auto"></video>
        </div>
        <div class="v-footer">
            <div class="fi">
                <div id="ch-name">FinnTV Premium</div>
                <div id="ch-grp">Select a channel</div>
            </div>
            <div id="live-wrap" style="display:none">
                <span class="live-badge"><span class="ldot"></span>LIVE</span>
            </div>
        </div>
    </div>

    <div class="sidebar">
        <div class="sb-head">Channels <span class="ch-count" id="ch-count">0</span></div>
        <div class="scroller" id="list">
            <div class="loading-msg"><div class="spin"></div>Loading...</div>
        </div>
    </div>
</div>
<div id="toast"></div>

<script>
// ── State ────────────────────────────────────────────────
var channels = [], filtered = [];
var vjs      = null;   // Video.js player
var hlsInst  = null;   // hls.js
var mpInst   = null;   // mpegts.js
var isLiveStream = false;

// ── Helpers ──────────────────────────────────────────────
function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function toast(msg, ms) {
    var el = document.getElementById('toast');
    el.textContent = msg; el.style.display = 'block';
    clearTimeout(el._t);
    el._t = setTimeout(function(){ el.style.display='none'; }, ms||4000);
}

function proxyUrl(raw) {
    try { return '../api/stream_proxy.php?url=' + encodeURIComponent(btoa(unescape(encodeURIComponent(raw)))); }
    catch(e) { return raw; }
}

function streamType(url) {
    var u = url.toLowerCase().split('?')[0];
    if (u.indexOf('.m3u8') !== -1) return 'm3u8';
    if (u.indexOf('.ts')   !== -1) return 'ts';
    if (u.indexOf('.mp4')  !== -1) return 'mp4';
    return 'hls'; // IPTV default
}

function liveCheck(url) {
    var u = url.toLowerCase();
    return !(u.indexOf('/movie/') !== -1 || u.indexOf('/vod/') !== -1 || u.indexOf('.mp4') !== -1);
}

// ── Video.js Init ────────────────────────────────────────
function initVJS() {
    if (vjs) return;
    vjs = videojs('finntv-vjs', {
        controls: true,
        preload:  'auto',
        fluid:    false,
        playbackRates: [0.5, 0.75, 1, 1.25, 1.5, 2],
        liveui: true,
        html5: {
            vhs: {
                overrideNative: !videojs.browser.IS_ANY_SAFARI,
                enableLowInitialPlaylist: true,
                smoothQualityChange: true
            },
            nativeAudioTracks: false,
            nativeVideoTracks: false
        },
        controlBar: {
            children: [
                'playToggle',
                'skipBackward',
                'skipForward',
                'currentTimeDisplay',
                'timeDivider',
                'durationDisplay',
                'progressControl',
                'playbackRateMenuButton',
                'volumePanel',
                'pictureInPictureToggle',
                'fullscreenToggle'
            ],
            skipButtons: { backward: 10, forward: 10 },
            volumePanel: { inline: true }
        }
    });

    vjs.on('error', function() {
        var e = vjs.error();
        toast('⚠ ' + (e ? e.message : 'Playback error'));
    });
}

// ── Tear-down sub-players ────────────────────────────────
function teardown() {
    if (hlsInst) { try { hlsInst.destroy(); } catch(e){} hlsInst = null; }
    if (mpInst)  { try { mpInst.unload(); mpInst.detachMediaElement(); mpInst.destroy(); } catch(e){} mpInst = null; }
    if (vjs) { vjs.pause(); vjs.reset(); }
}

// ── Play ─────────────────────────────────────────────────
function playUrl(rawUrl, name, group) {
    document.getElementById('ph').style.cssText = 'opacity:0;pointer-events:none';
    document.getElementById('ch-name').textContent = name  || '';
    document.getElementById('ch-grp').textContent  = group || '';
    isLiveStream = liveCheck(rawUrl);
    document.getElementById('live-wrap').style.display = isLiveStream ? 'block' : 'none';

    initVJS();
    teardown();

    var stype = streamType(rawUrl);
    var purl  = proxyUrl(rawUrl);
    // Get the actual <video> element from Video.js tech
    var vid   = vjs.tech(true).el();

    if (stype === 'ts') {
        // mpegts.js handles raw MPEG-TS
        if (mpegts.getFeatureList().mseLivePlayback) {
            mpInst = mpegts.createPlayer({ type: 'mse', isLive: isLiveStream, url: purl, cors: true },
                { enableWorker: true, liveBufferLatencyChasing: isLiveStream });
            mpInst.attachMediaElement(vid);
            mpInst.load();
            mpInst.on(mpegts.Events.ERROR, function(t){ toast('⚠ Stream error: ' + t); });
            vjs.play();
        } else {
            vjs.src({ src: purl, type: 'video/mp2t' });
            vjs.play();
        }

    } else if (stype === 'm3u8' || stype === 'hls') {
        if (Hls.isSupported()) {
            // hls.js on the raw video element, Video.js just renders controls
            hlsInst = new Hls({
                enableWorker:      true,
                lowLatencyMode:    isLiveStream,
                backBufferLength:  isLiveStream ? 30 : 90,
                maxBufferLength:   isLiveStream ? 20 : 60
            });
            hlsInst.loadSource(purl);
            hlsInst.attachMedia(vid);
            hlsInst.on(Hls.Events.MANIFEST_PARSED, function() { vjs.play().catch(function(){}); });
            hlsInst.on(Hls.Events.ERROR, function(e, d) {
                if (!d.fatal) return;
                if (d.type === Hls.ErrorTypes.NETWORK_ERROR) { toast('⚠ Network error — retrying…'); hlsInst.startLoad(); }
                else if (d.type === Hls.ErrorTypes.MEDIA_ERROR) hlsInst.recoverMediaError();
                else toast('⚠ Stream failed');
            });
        } else if (vid.canPlayType('application/vnd.apple.mpegurl')) {
            vjs.src({ src: purl, type: 'application/x-mpegURL' });
            vjs.play();
        } else {
            toast('HLS not supported in this browser');
        }

    } else {
        // MP4 / direct
        vjs.src({ src: purl, type: 'video/mp4' });
        vjs.play();
    }
}

// ── Playlist ─────────────────────────────────────────────
function loadPlaylist(url) {
    document.getElementById('list').innerHTML = '<div class="loading-msg"><div class="spin"></div>Loading...</div>';
    channels = []; filtered = [];
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status === 200) parseM3U(xhr.responseText);
        else document.getElementById('list').innerHTML = '<div class="loading-msg">Failed (HTTP '+xhr.status+')</div>';
    };
    xhr.send();
}

function parseM3U(txt) {
    var lines = txt.replace(/\r/g, '').split('\n');
    channels = []; var cur = null;
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
    filtered = channels; render();
}

function render() {
    var el = document.getElementById('list');
    el.innerHTML = '';
    document.getElementById('ch-count').textContent = filtered.length.toLocaleString();
    var frag = document.createDocumentFragment();
    for (var i = 0, lim = Math.min(filtered.length, 5000); i < lim; i++) {
        (function(c) {
            var d = document.createElement('div');
            d.className = 'card';
            d.innerHTML = (c.logo
                ? '<img class="card-logo" src="'+esc(c.logo)+'" onerror="this.style.display=\'none\'">'
                : '<div class="card-icon">'+esc(c.t[0]||'?')+'</div>') +
                '<div class="ci"><span class="cn">'+esc(c.t)+'</span><span class="cg">'+esc(c.g)+'</span></div>';
            d.onclick = function() {
                el.querySelectorAll('.card.active').forEach(function(a){ a.classList.remove('active'); });
                d.classList.add('active');
                playUrl(c.u, c.t, c.g);
            };
            frag.appendChild(d);
        })(filtered[i]);
    }
    el.appendChild(frag);
}

function onPlaylistChange() {
    var v = document.getElementById('playlist-select').value;
    document.getElementById('custom-url').style.display = v==='custom'?'block':'none';
    document.getElementById('play-custom').style.display = v==='custom'?'block':'none';
    if (v !== 'custom') loadPlaylist(v);
}

function playCustom() {
    var url = document.getElementById('custom-url').value.trim();
    if (!url) return;
    var u = url.toLowerCase().split('?')[0];
    var direct = u.indexOf('.m3u8')!==-1||u.indexOf('.ts')!==-1||u.indexOf('.mp4')!==-1;
    if (direct && u.indexOf('.m3u')===-1) playUrl(url, 'Custom Stream', 'Custom');
    else loadPlaylist(url);
}

document.getElementById('search').oninput = function() {
    var q = this.value.toLowerCase();
    filtered = q ? channels.filter(function(c){ return (c.t+c.g).toLowerCase().indexOf(q)!==-1; }) : channels;
    render();
};

// ── Boot ─────────────────────────────────────────────────
(function() {
    var m3u = '../m3u/world.m3u';
    window.location.search.substring(1).split('&').forEach(function(p) {
        var kv = p.split('='); if (kv[0]==='m3u') m3u = decodeURIComponent(kv[1]);
    });
    var sel = document.getElementById('playlist-select'), found = false;
    for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === m3u) { sel.selectedIndex = i; found = true; break; }
    }
    if (!found) {
        sel.value='custom';
        document.getElementById('custom-url').style.display='block';
        document.getElementById('custom-url').value=m3u;
        document.getElementById('play-custom').style.display='block';
    }
    loadPlaylist(m3u);
})();
</script>
</body>
</html>
