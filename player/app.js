// app.js v18 - Pro IPTV Architecture (VLC-Engine + Group Filtering)

document.addEventListener('DOMContentLoaded', function() {
    var m3uInput = document.getElementById('m3u-url');
    var searchInput = document.getElementById('search-input');
    var loadBtn = document.getElementById('load-btn');
    var channelListContainer = document.getElementById('channel-list');
    var paginationContainer = document.getElementById('pagination-container');
    var groupFilterContainer = document.getElementById('group-filter');
    var videoPlayer = document.getElementById('video-player');
    var channelNameHeader = document.getElementById('channel-name');
    var channelGroupText = document.getElementById('status-text');
    var channelCountText = document.getElementById('channel-count');

    var channels = [];
    var filteredChannels = [];
    var activeGroup = 'All';
    var activeElement = null;
    var hls = null;
    var flvPlayer = null;
    var isVita = navigator.userAgent.indexOf('PlayStation Vita') !== -1;
    var renderIndex = 0;
    var RENDER_CHUNK_SIZE = 50; 

    function safeBtoa(str) {
        try { return btoa(str); } catch (e) {
            try { return btoa(unescape(encodeURIComponent(str))); } catch (e2) { return ''; }
        }
    }

    function initLoad() {
        var url = m3uInput.value.replace(/^\s+|\s+$/g, '');
        if (url !== "") fetchM3U(url);
    }

    function fetchM3U(url) {
        channelListContainer.innerHTML = '<div class="message">Building Bridge...</div>';
        paginationContainer.innerHTML = '';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && (xhr.status === 200 || xhr.status === 0)) {
                parseM3UAndRender(xhr.responseText || "");
            }
        };
        xhr.send();
    }

    function parseM3UAndRender(content) {
        var lines = content.replace(/\r/g, '').split('\n');
        channels = [];
        var groups = {'All': true};
        var currentChannel = null;

        for (var i = 0; i < lines.length; i++) {
            var line = lines[i].replace(/^\s+|\s+$/g, '');
            if (line.indexOf('#EXTINF:') === 0) {
                currentChannel = { title: 'Unknown', group: 'Live TV', url: '', logo: '' };
                var g = line.match(/group-title="([^"]+)"/);
                if (g) { currentChannel.group = g[1]; groups[g[1]] = true; }
                var l = line.match(/tvg-logo="([^"]+)"/);
                if (l) currentChannel.logo = l[1];
                var comma = line.lastIndexOf(',');
                if (comma !== -1) {
                    var t = line.substring(comma+1).replace(/^\s+|\s+$/g, '');
                    if (t) currentChannel.title = t;
                }
            } else if (line.indexOf('http') === 0 && currentChannel) {
                currentChannel.url = line;
                channels.push(currentChannel);
                currentChannel = null;
            }
        }

        renderGroupBar(Object.keys(groups));
        applyFilter();
    }

    function renderGroupBar(groupList) {
        groupFilterContainer.innerHTML = '';
        groupList.sort().forEach(function(g) {
            var btn = document.createElement('div');
            btn.className = 'group-item' + (activeGroup === g ? ' active' : '');
            btn.innerText = g;
            btn.addEventListener('click', function() {
                activeGroup = g;
                applyFilter();
                renderGroupBar(groupList);
            });
            groupFilterContainer.appendChild(btn);
        });
    }

    function applyFilter() {
        var query = searchInput.value.toLowerCase().trim();
        filteredChannels = channels.filter(function(c) {
            var matchGroup = (activeGroup === 'All' || c.group === activeGroup);
            var matchSearch = (c.title.toLowerCase().indexOf(query) !== -1 || c.group.toLowerCase().indexOf(query) !== -1);
            return matchGroup && matchSearch;
        });

        channelCountText.innerText = filteredChannels.length;
        channelListContainer.innerHTML = '';
        renderIndex = 0;
        renderNextChunk();
    }

    function renderNextChunk() {
        var fragment = document.createDocumentFragment();
        var limit = Math.min(renderIndex + RENDER_CHUNK_SIZE, filteredChannels.length);

        for (var i = renderIndex; i < limit; i++) {
            (function(idx) {
                var c = filteredChannels[idx];
                var el = document.createElement('div');
                el.className = 'channel-card';
                el.innerHTML = '<div class="card-title">'+c.title+'</div><div class="card-group">'+c.group+'</div>';
                el.addEventListener('click', function() { playChannel(c, el); });
                fragment.appendChild(el);
            })(i);
        }

        renderIndex = limit;
        paginationContainer.innerHTML = '';
        channelListContainer.appendChild(fragment);

        if (renderIndex < filteredChannels.length) {
            var btn = document.createElement('button');
            btn.id = 'load-more-btn';
            btn.innerText = 'Load More (' + (filteredChannels.length - renderIndex) + ')';
            btn.addEventListener('click', renderNextChunk);
            paginationContainer.appendChild(btn);
        }
    }

    function playChannel(channel, element) {
        if (activeElement) activeElement.classList.remove('active');
        element.classList.add('active');
        activeElement = element;

        channelNameHeader.innerText = '📡 Unlocking...';
        channelGroupText.innerText = channel.group;
        window.scrollTo(0, 0);

        if (hls) { hls.destroy(); hls = null; }
        if (flvPlayer) { flvPlayer.destroy(); flvPlayer = null; }
        videoPlayer.pause();
        videoPlayer.src = "";

        var lowerUrl = channel.url.toLowerCase();
        var isTS = lowerUrl.indexOf('.ts') !== -1;
        var isVOD = lowerUrl.indexOf('.mp4') !== -1 || lowerUrl.indexOf('.mkv') !== -1;
        var isXtream = lowerUrl.indexOf('/live/') !== -1 || lowerUrl.indexOf('/movie/') !== -1 || lowerUrl.indexOf('/series/') !== -1;
        var finalUrl = (isVita && !isXtream) ? channel.url : ('/api/stream_proxy.php?url=' + encodeURIComponent(safeBtoa(channel.url)));

        // Path 1: MPEG-TS (VLC Mode)
        if (isTS && typeof mpegts !== 'undefined' && mpegts.getFeatureList().mse) {
            flvPlayer = mpegts.createPlayer({ type: 'mse', isLive: !isVOD, url: finalUrl, enableWorker: true, enableStashBuffer: false });
            flvPlayer.attachMediaElement(videoPlayer);
            flvPlayer.load();
            flvPlayer.play().catch(function(){});
            channelNameHeader.innerText = '📺 ' + channel.title;
        } 
        // Path 2: HLS
        else if (!isTS && !isVOD && window.Hls && Hls.isSupported() && !isVita) {
            hls = new Hls();
            hls.loadSource(finalUrl); // Use proxied URL for the manifest
            hls.attachMedia(videoPlayer);
            hls.on(Hls.Events.MANIFEST_PARSED, function() { 
                videoPlayer.play().catch(function(){}); 
            });
            channelNameHeader.innerText = '📡 ' + channel.title;
        }
        // Path 3: Native
        else {
            videoPlayer.src = finalUrl;
            videoPlayer.load();
            videoPlayer.play().catch(function(){});
            channelNameHeader.innerText = (isVOD ? '🎬 ' : '📺 ') + channel.title;
        }
    }

    loadBtn.addEventListener('click', initLoad);
    searchInput.addEventListener('input', applyFilter);
    
    videoPlayer.addEventListener('error', function() {
        var err = videoPlayer.error ? videoPlayer.error.code : 'Unknown';
        channelNameHeader.innerText = 'Error ' + err;
        channelGroupText.innerText = isVita && (err==3||err==4) ? 'Format Unsupported (Use SD/H264)' : 'Check stream link';
    }, true);

    setTimeout(function() {
        var q = window.location.search;
        if (q.indexOf('?m3u=') !== -1) { 
            m3uInput.value = decodeURIComponent(q.split('?m3u=')[1].split('&')[0]); 
            initLoad();
        }
    }, 200);
});
