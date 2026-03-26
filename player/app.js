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
    var nativeLink = document.getElementById('vita-native-link');

    var channels = [];
    var filteredChannels = [];
    var activeGroup = 'All';
    var activeElement = null;
    var hls = null;
    var flvPlayer = null;
    var isVita = navigator.userAgent.indexOf('Vita') !== -1;
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

    window.loadPreset = function(url, group) {
        m3uInput.value = url;
        if (group) activeGroup = group;
        else activeGroup = 'All';
        initLoad();
    };

    function fetchM3U(url) {
        channelListContainer.innerHTML = '<div class="message">Building Bridge...</div>';
        paginationContainer.innerHTML = '';
        var proxyUrl = '/api/m3u_proxy.php?url=' + encodeURIComponent(safeBtoa(url));
        var xhr = new XMLHttpRequest();
        xhr.open('GET', proxyUrl, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && (xhr.status === 200 || xhr.status === 0)) {
                parseM3UAndRender(xhr.responseText || "");
            } else if (xhr.readyState === 4) {
               console.warn("Proxy failed, trying direct...");
               var xhr2 = new XMLHttpRequest();
               xhr2.open('GET', url, true);
               xhr2.onreadystatechange = function() {
                   if (xhr2.readyState === 4 && (xhr2.status === 200 || xhr2.status === 0)) {
                       parseM3UAndRender(xhr2.responseText || "");
                   } else if (xhr2.readyState === 4) {
                       channelListContainer.innerHTML = '<div class="message">Source Unavailable. Check Link.</div>';
                   }
               };
               xhr2.send();
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
                var streamUrl = line;
                var lowStreamUrl = streamUrl.toLowerCase();
                // Universal Optimization: Convert raw .ts Xtream links to HLS (.m3u8) for native broad support
                if ((lowStreamUrl.indexOf('/live/') !== -1 || lowStreamUrl.indexOf('/movie/') !== -1 || lowStreamUrl.indexOf('/series/') !== -1) && lowStreamUrl.indexOf('.ts') !== -1) {
                    streamUrl = streamUrl.substring(0, streamUrl.length - 3) + '.m3u8';
                }
                currentChannel.url = streamUrl;
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
        // Keep pagination container clean but present
        channelListContainer.innerHTML = '';
        channelListContainer.appendChild(paginationContainer);
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
        channelListContainer.insertBefore(fragment, paginationContainer); // Insert before pagination container

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

        channelNameHeader.innerText = 'Unlocking...';
        channelGroupText.innerText = channel.group;
        
        // Mobile-friendly scroll: Scroll the container to top to show the player
        var splitLayout = document.querySelector('.split-layout');
        if (splitLayout) splitLayout.scrollTop = 0;
        else window.scrollTo(0, 0);

        if (hls) { hls.destroy(); hls = null; }
        if (flvPlayer) { flvPlayer.destroy(); flvPlayer = null; }
        videoPlayer.pause();
        videoPlayer.src = "";

        var lowerUrl = channel.url.toLowerCase();
        var isTS = lowerUrl.indexOf('.ts') !== -1 || lowerUrl.indexOf('.m2t') !== -1;
        var isVOD = lowerUrl.indexOf('.mp4') !== -1 || lowerUrl.indexOf('.mkv') !== -1;
        var isXtream = lowerUrl.indexOf('/live/') !== -1 || lowerUrl.indexOf('/movie/') !== -1 || lowerUrl.indexOf('/series/') !== -1;
        
        // Smart Proxy Logic: Only proxy if strictly needed (Insecure on HTTPS, Xtream, or forced TS)
        var isHttps = window.location.protocol === 'https:';
        var isInsecure = channel.url.indexOf('http:') !== -1;
        var needsProxy = (isInsecure && isHttps) || isTS || isXtream; // PC/Mobile need proxy for CORS, Vita doesn't via Native Player
        var finalUrl = needsProxy ? ('/api/stream_proxy.php?url=' + encodeURIComponent(safeBtoa(channel.url))) : channel.url;

        // Path 1: MPEG-TS (VLC Mode) - Disable on Vita if native player can't handle it
        if (isTS && !isVita && typeof mpegts !== 'undefined' && mpegts.getFeatureList().mse) {
            flvPlayer = mpegts.createPlayer({ type: 'mse', isLive: !isVOD, url: finalUrl, enableWorker: true, enableStashBuffer: false });
            flvPlayer.attachMediaElement(videoPlayer);
            flvPlayer.load();
            var promise = flvPlayer.play();
            if (promise !== undefined) {
                promise.catch(function() {
                    console.error("MPEG-TS Playback failed. Attempting native fallback.");
                    videoPlayer.src = finalUrl;
                    videoPlayer.play().catch(function(){});
                });
            }
            channelNameHeader.innerText = channel.title;
        } 
        // Path 2: HLS (m3u8)
        else if (!isTS && !isVOD && window.Hls && Hls.isSupported() && !isVita) {
            hls = new Hls({ enableWorker: true, lowLatencyMode: true });
            hls.loadSource(finalUrl);
            hls.attachMedia(videoPlayer);
            hls.on(Hls.Events.MANIFEST_PARSED, function() { videoPlayer.play().catch(function(){}); });
            hls.on(Hls.Events.ERROR, function(event, data) {
                if (data.fatal) {
                    console.warn("HLS Error encountered. Falling back to native/alternate...");
                    if (finalUrl !== channel.url) {
                        hls.loadSource(channel.url);
                        hls.startLoad();
                    }
                }
            });
            channelNameHeader.innerText = channel.title;
        }
        // Path 3: Native (PS Vita / Mobile / MP4)
        else {
            videoPlayer.pause();
            videoPlayer.innerHTML = "";
            videoPlayer.removeAttribute("src");
            
            var sourceUrl = finalUrl;
            if (isVita && !isVOD && sourceUrl.indexOf('.mp4') === -1 && sourceUrl.indexOf('&ext=') === -1) {
                sourceUrl += '&ext=.m3u8';
            }
            
            if (isVita) {
                // Verified working pattern from psvita/app.js reference
                videoPlayer.innerHTML = 
                    '<source src="' + sourceUrl + '" type="application/vnd.apple.mpegurl">' +
                    '<source src="' + sourceUrl + '" type="application/x-mpegURL">';
                
                videoPlayer.load();
                
                if (nativeLink) {
                    nativeLink.style.display = 'inline-block';
                    nativeLink.href = sourceUrl;
                }
                
                setTimeout(function() {
                    try {
                        var p = videoPlayer.play();
                        if (p && p.catch) p.catch(function(){});
                    } catch(e) {}
                }, 300);
            } else {
                if (nativeLink) nativeLink.style.display = 'none';
                videoPlayer.src = sourceUrl;
                if (lowerUrl.indexOf('.mp4') !== -1) videoPlayer.setAttribute('type', 'video/mp4');
                
                var nativePromise = videoPlayer.play();
                if (nativePromise && nativePromise.catch) {
                    nativePromise.catch(function() {
                        if (finalUrl !== channel.url) {
                            videoPlayer.src = channel.url;
                            videoPlayer.load();
                            videoPlayer.play();
                        }
                    });
                }
            }
            channelNameHeader.innerText = channel.title;
        }
    }

    loadBtn.addEventListener('click', initLoad);
    searchInput.addEventListener('input', applyFilter);
    
    // PS Vita Scroll Polyfill - Fixes broken overflow (Touch + Mouse Drag)
    function applyVitaScrollFix(el, isX) {
        if (!el || !isVita) return;
        var startPos = 0, startScroll = 0, isDown = false;

        var startHandler = function(e) {
            isDown = true;
            startPos = isX ? (e.touches ? e.touches[0].pageX : e.pageX) : (e.touches ? e.touches[0].pageY : e.pageY);
            startScroll = isX ? el.scrollLeft : el.scrollTop;
        };

        var moveHandler = function(e) {
            if (!isDown) return;
            var currentPos = isX ? (e.touches ? e.touches[0].pageX : e.pageX) : (e.touches ? e.touches[0].pageY : e.pageY);
            var diff = startPos - currentPos;
            if (isX) el.scrollLeft = startScroll + diff;
            else el.scrollTop = startScroll + diff;
            if (e.cancelable) e.preventDefault();
        };

        var endHandler = function() { isDown = false; };

        el.addEventListener('touchstart', startHandler, { passive: true });
        el.addEventListener('touchmove', moveHandler, { passive: false });
        el.addEventListener('touchend', endHandler);
        el.addEventListener('mousedown', startHandler);
        window.addEventListener('mousemove', moveHandler);
        window.addEventListener('mouseup', endHandler);
    }

    applyFilter();
    applyVitaScrollFix(channelListContainer, false);
    applyVitaScrollFix(groupFilterContainer, true);
    
    videoPlayer.addEventListener('error', function() {
        var err = videoPlayer.error ? videoPlayer.error.code : 'Unknown';
        channelNameHeader.innerText = 'Cannot play this video';
        channelGroupText.innerText = isVita && (err==3||err==4) ? 'Format Unsupported (Use SD/H264)' : 'Check stream link or try another channel';
    }, true);

    setTimeout(function() {
        var q = window.location.search;
        if (q.indexOf('?m3u=') !== -1) { 
            m3uInput.value = decodeURIComponent(q.split('?m3u=')[1].split('&')[0]); 
        }
        if (m3uInput.value) initLoad();
    }, 200);
});
