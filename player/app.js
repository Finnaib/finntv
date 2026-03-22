// app.js v17 - Cleaned & Restructured VLC-Engine Logic

document.addEventListener('DOMContentLoaded', function() {
    // 1. DOM Elements
    var m3uInput = document.getElementById('m3u-url');
    var searchInput = document.getElementById('search-input');
    var loadBtn = document.getElementById('load-btn');
    var channelListContainer = document.getElementById('channel-list');
    var paginationContainer = document.getElementById('pagination-container');
    var videoPlayer = document.getElementById('video-player');
    var channelNameHeader = document.getElementById('channel-name');
    var channelGroupText = document.getElementById('status-text');
    var channelCountText = document.getElementById('channel-count');

    // 2. State Variables
    var channels = [];
    var filteredChannels = [];
    var activeElement = null;
    var hls = null;
    var flvPlayer = null;
    var isVita = navigator.userAgent.indexOf('PlayStation Vita') !== -1;
    var renderIndex = 0;
    var RENDER_CHUNK_SIZE = 50; 

    // 3. Helper Functions
    function safeBtoa(str) {
        try {
            return btoa(str);
        } catch (e) {
            try { return btoa(unescape(encodeURIComponent(str))); } catch (e2) { return ''; }
        }
    }

    // 4. Data Loading & Parsing
    function initLoad() {
        var url = m3uInput.value.replace(/^\s+|\s+$/g, '');
        if (url !== "") fetchM3U(url);
    }

    function fetchM3U(url) {
        channelListContainer.innerHTML = '<div class="message">Connecting...</div>';
        paginationContainer.innerHTML = '';
        searchInput.value = '';
        channelCountText.innerText = '0';
        
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200 || xhr.status === 0) {
                    if (xhr.responseText) parseM3UAndRender(xhr.responseText);
                    else channelListContainer.innerHTML = '<div class="message">Security Block / Empty.</div>';
                } else {
                    channelListContainer.innerHTML = '<div class="message">HTTP Error: ' + xhr.status + '</div>';
                }
            }
        };
        xhr.onerror = function() {
            channelListContainer.innerHTML = '<div class="message">Network Failed. CORS restriction?</div>';
        };
        xhr.send();
    }

    function parseM3UAndRender(content) {
        channelListContainer.innerHTML = '<div class="message">Analyzing channels...</div>';
        setTimeout(function() {
            var lines = content.split('\n');
            channels = [];
            var currentChannel = null;

            for (var i = 0; i < lines.length; i++) {
                var line = lines[i].replace(/^\s+|\s+$/g, '');
                if (line.indexOf('#EXTINF:') === 0) {
                    currentChannel = { title: 'Unknown', group: 'Live TV', url: '' };
                    var groupMatch = line.match(/group-title="([^"]+)"/);
                    if (groupMatch) currentChannel.group = groupMatch[1];
                    var commaIndex = line.lastIndexOf(',');
                    if (commaIndex !== -1) {
                        var t = line.substring(commaIndex+1).replace(/^\s+|\s+$/g, '');
                        if (t) currentChannel.title = t;
                    }
                } else if (line.indexOf('http') === 0 && currentChannel) {
                    currentChannel.url = line;
                    channels.push(currentChannel);
                    currentChannel = null;
                }
            }

            if (channels.length === 0) {
                channelListContainer.innerHTML = '<div class="message">Empty Playlist.</div>';
                return;
            }

            filteredChannels = channels.slice(0);
            channelCountText.innerText = filteredChannels.length;
            channelListContainer.innerHTML = '';
            renderIndex = 0;
            renderNextChunk();
        }, 50);
    }

    // 5. UI Rendering & Search
    function renderNextChunk() {
        var fragment = document.createDocumentFragment();
        var limit = Math.min(renderIndex + RENDER_CHUNK_SIZE, filteredChannels.length);

        for (var i = renderIndex; i < limit; i++) {
            (function(idx) {
                var c = filteredChannels[idx];
                var el = document.createElement('div');
                el.className = 'channel-card';
                el.innerHTML = '<div class="card-title">'+c.title+'</div><div class="card-group">'+c.group+'</div>';
                el.addEventListener('click', function() { playChannel(c, el); }, false);
                fragment.appendChild(el);
            })(i);
        }

        renderIndex = limit;
        paginationContainer.innerHTML = '';
        channelListContainer.appendChild(fragment);

        if (renderIndex < filteredChannels.length) {
            var loadMoreBtn = document.createElement('button');
            loadMoreBtn.id = 'load-more-btn';
            loadMoreBtn.innerText = 'Load More (' + (filteredChannels.length - renderIndex) + ')';
            loadMoreBtn.addEventListener('click', renderNextChunk, false);
            paginationContainer.appendChild(loadMoreBtn);
        }
    }

    function runSearch() {
        var query = searchInput.value.toLowerCase().replace(/^\s+|\s+$/g, '');
        if (query === '') {
            filteredChannels = channels.slice(0);
        } else {
            filteredChannels = [];
            for (var i = 0; i < channels.length; i++) {
                if (channels[i].title.toLowerCase().indexOf(query) !== -1 || channels[i].group.toLowerCase().indexOf(query) !== -1) {
                    filteredChannels.push(channels[i]);
                }
            }
        }
        channelCountText.innerText = filteredChannels.length;
        channelListContainer.innerHTML = '';
        renderIndex = 0;
        renderNextChunk();
    }

    // 6. Playback Engine (VLC-Engine Architecture)
    function playChannel(channel, element) {
        if (activeElement) activeElement.className = 'channel-card';
        element.className = 'channel-card active';
        activeElement = element;

        channelNameHeader.innerText = 'Unlocking Stream...';
        channelGroupText.innerText = channel.group;
        window.scrollTo(0, 0);

        // Cleanup
        if (hls) { hls.destroy(); hls = null; }
        if (flvPlayer) { flvPlayer.destroy(); flvPlayer = null; }
        if (window.flvPlayer) { window.flvPlayer.destroy(); window.flvPlayer = null; }
        videoPlayer.pause();
        videoPlayer.src = "";
        videoPlayer.load();

        var lowerUrl = channel.url.toLowerCase();
        var isTS = lowerUrl.indexOf('.ts') !== -1;
        var isVOD = lowerUrl.indexOf('.mp4') !== -1 || lowerUrl.indexOf('.mkv') !== -1;
        var isXtream = lowerUrl.indexOf('/live/') !== -1;

        var proxiedUrl = '/api/stream_proxy.php?url=' + encodeURIComponent(safeBtoa(channel.url));
        var finalUrl = (isVita && !isXtream) ? channel.url : proxiedUrl;

        // Path 1: VLC-ENGINE (MPEGTS.js for raw .ts)
        if (isTS && typeof mpegts !== 'undefined' && mpegts.getFeatureList().mse) {
            try {
                flvPlayer = mpegts.createPlayer({
                    type: 'mse',
                    isLive: true,
                    url: finalUrl,
                    cors: true,
                    enableWorker: true,
                    enableStashBuffer: false,
                    lazyLoad: false
                }, {
                    reusePlayer: true,
                    autoCleanupSourceBuffer: true
                });
                flvPlayer.attachMediaElement(videoPlayer);
                flvPlayer.load();
                var p = flvPlayer.play();
                if (p && p.catch) p.catch(function(){});
                channelNameHeader.innerText = '📺 (VLC-Mode) ' + channel.title;
            } catch (e) {
                console.error("MPEGTS Crash:", e);
                videoPlayer.src = finalUrl;
                videoPlayer.play();
            }
        } 
        // PATH 2: HLS-ENGINE (HLS.js for .m3u8)
        else if (!isTS && !isVOD && window.Hls && Hls.isSupported() && !isVita) {
            hls = new Hls({
                enableWorker: true,
                xhrSetup: function(xhr, url) {
                    if (isXtream) {
                        var enc = encodeURIComponent(safeBtoa(url));
                        xhr.open('GET', '/api/stream_proxy.php?url=' + enc, true);
                    }
                }
            });
            hls.loadSource(channel.url);
            hls.attachMedia(videoPlayer);
            hls.on(Hls.Events.MANIFEST_PARSED, function() { videoPlayer.play(); });
            channelNameHeader.innerText = '📡 (HLS) ' + channel.title;
        }
        // PATH 3: NATIVE HARDWARE (Vita/Safari/MP4)
        else {
            videoPlayer.src = finalUrl;
            videoPlayer.load();
            var p = videoPlayer.play();
            if (p && p.catch) p.catch(function(){});
            channelNameHeader.innerText = (isVOD ? '🎬 ' : '📺 ') + channel.title;
        }

        channelGroupText.innerHTML = isXtream ? 'VLC-Bridge unblocking active' : 'Stable direct path';
    }

    // 7. Event Listeners
    loadBtn.addEventListener('click', initLoad, false);
    searchInput.addEventListener('input', runSearch, false);
    
    videoPlayer.addEventListener('error', function(e) {
        var err = videoPlayer.error ? videoPlayer.error.code : 'Unknown';
        var msg = 'Network Error / Blocked';
        if (err === 3 || err === 4) {
            msg = 'Codec Unsupported';
            if (isVita) msg = 'Hardware Limit: Use SD/H.264 (H265/4K blocked)';
        }
        channelNameHeader.innerText = 'Player Error ' + err;
        channelGroupText.innerText = msg;
    }, true);

    // Params init logic
    function checkUrlParams() {
        var query = window.location.search;
        if (query && query.indexOf('?m3u=') !== -1) {
            var m3uParam = query.split('?m3u=')[1].split('&')[0];
            if (m3uParam) {
                m3uInput.value = decodeURIComponent(m3uParam);
                initLoad();
            }
        }
    }
    setTimeout(checkUrlParams, 200);
});
