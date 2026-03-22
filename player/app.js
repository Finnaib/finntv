// app.js v7 - Search & Fixed Scrolling Logic

document.addEventListener('DOMContentLoaded', function() {
    var m3uInput = document.getElementById('m3u-url');
    var searchInput = document.getElementById('search-input');
    var loadBtn = document.getElementById('load-btn');
    var channelListContainer = document.getElementById('channel-list');
    var paginationContainer = document.getElementById('pagination-container');
    var videoPlayer = document.getElementById('video-player');
    var channelNameHeader = document.getElementById('channel-name');
    var channelGroupText = document.getElementById('status-text');
    var channelCountText = document.getElementById('channel-count');

    var channels = [];
    var filteredChannels = [];
    var activeElement = null;
    var hls = null;
    var isVita = navigator.userAgent.indexOf('PlayStation Vita') !== -1;
    var renderIndex = 0;
    var RENDER_CHUNK_SIZE = 50; 

    // Dynamic HLS for Desktop
    if (!isVita) {
        var script = document.createElement('script');
        script.src = "https://cdn.jsdelivr.net/npm/hls.js@latest";
        document.body.appendChild(script);
    }

    function initLoad() {
        var url = m3uInput.value.replace(/^\s+|\s+$/g, '');
        if (url !== "") {
            fetchM3U(url);
        }
    }

    // Auto-load M3U from URL parameter for seamless handoffs
    function checkUrlParams() {
        var query = window.location.search;
        if (query && query.indexOf('?m3u=') !== -1) {
            var m3uParam = query.split('?m3u=')[1].split('&')[0];
            if (m3uParam) {
                var decoded = decodeURIComponent(m3uParam);
                m3uInput.value = decoded;
                initLoad();
            }
        }
    }
    
    // Check parameters shortly after load
    setTimeout(checkUrlParams, 200);

    // Fixed touch scrolling issue: removed touchstart bindings. Standard clicks are fluid with the viewport meta tag.
    loadBtn.addEventListener('click', initLoad, false);

    // SEARCH FUNCTIONALITY
    function runSearch() {
        var query = searchInput.value.toLowerCase().replace(/^\s+|\s+$/g, '');
        if (query === '') {
            filteredChannels = channels.slice(0); // copy all
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
        
        if (filteredChannels.length === 0) {
            channelListContainer.innerHTML = '<div class="message">No channels match "' + query + '"</div>';
            paginationContainer.innerHTML = '';
        } else {
            renderNextChunk();
        }
    }

    // Bind Search Input
    searchInput.addEventListener('keyup', runSearch, false);
    searchInput.addEventListener('input', runSearch, false);

    // Video Error Handling - Smarter detection
    videoPlayer.addEventListener('error', function(e) {
        var err = videoPlayer.error ? videoPlayer.error.code : 'Unknown';
        var msg = 'Network or Sync Error';
        
        if (err === 3 || err === 4) {
            msg = 'Unsupported Codec/Format';
            if (isVita) {
                // Special hardware warning for Vita users
                msg = 'Hardware Limit: Use SD/H.264. (H.265/4K blocked)';
            }
        }
        
        channelNameHeader.innerText = 'Player Error ' + err;
        channelGroupText.innerText = msg;
    }, true);

    function safeBtoa(str) {
        try {
            return btoa(str);
        } catch (e) {
            try {
                return btoa(unescape(encodeURIComponent(str)));
            } catch (e2) {
                return '';
            }
        }
    }

    function fetchM3U(url) {
        channelListContainer.innerHTML = '<div class="message">Connecting...</div>';
        paginationContainer.innerHTML = '';
        searchInput.value = ''; // Reset search
        channelCountText.innerText = '0';
        
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200 || xhr.status === 0) {
                    if (xhr.responseText) {
                        parseM3UAndRender(xhr.responseText);
                    } else {
                        channelListContainer.innerHTML = '<div class="message">Security Block / Empty.</div>';
                    }
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
                    currentChannel = {
                        title: 'Unknown Channel',
                        group: 'Live TV',
                        url: ''
                    };
                    
                    var groupMatch = line.match(/group-title="([^"]+)"/);
                    if (groupMatch) {
                        currentChannel.group = groupMatch[1];
                    }

                    var commaIndex = line.lastIndexOf(',');
                    if (commaIndex !== -1) {
                        var t = line.substring(commaIndex + 1).replace(/^\s+|\s+$/g, '');
                        if (t) currentChannel.title = t;
                    }
                } else if (line.indexOf('http') === 0 && currentChannel) {
                    currentChannel.url = line; // Preserve original link (.ts or .m3u8)
                    channels.push(currentChannel);
                    currentChannel = null;
                }
            }

            if (channels.length === 0) {
                channelListContainer.innerHTML = '<div class="message">Empty Playlist.</div>';
                return;
            }

            filteredChannels = channels.slice(0); // Initialize filtered array
            channelCountText.innerText = filteredChannels.length;
            
            channelListContainer.innerHTML = '';
            renderIndex = 0;
            renderNextChunk();
        }, 50);
    }

    function renderNextChunk() {
        var fragment = document.createDocumentFragment();
        var limit = Math.min(renderIndex + RENDER_CHUNK_SIZE, filteredChannels.length);

        for (var i = renderIndex; i < limit; i++) {
            (function(idx) {
                var c = filteredChannels[idx];
                
                var el = document.createElement('div');
                el.className = 'channel-card';
                
                var titleEl = document.createElement('div');
                titleEl.className = 'card-title';
                titleEl.innerText = c.title;
                
                var groupEl = document.createElement('div');
                groupEl.className = 'card-group';
                groupEl.innerText = c.group;

                el.appendChild(titleEl);
                el.appendChild(groupEl);

                // Reverted back exclusively to click bindings to FIX touch scrolling behavior!
                // Touchstart fires immediately upon the finger hitting the screen, blocking scrolling.
                el.addEventListener('click', function() {
                    playChannel(c, el);
                }, false);

                fragment.appendChild(el);
            })(i);
        }

        renderIndex = limit;
        paginationContainer.innerHTML = ''; // reset load more btn
        channelListContainer.appendChild(fragment);

        if (renderIndex < filteredChannels.length) {
            var loadMoreBtn = document.createElement('button');
            loadMoreBtn.id = 'load-more-btn';
            loadMoreBtn.innerText = 'Load Next ' + RENDER_CHUNK_SIZE + ' (' + (filteredChannels.length - renderIndex) + ' left)';
            
            // Just click! No touchstart locking!
            loadMoreBtn.addEventListener('click', renderNextChunk, false);
            
            paginationContainer.appendChild(loadMoreBtn);
        }
    }

    function playChannel(channel, element) {
        if (activeElement) {
            activeElement.className = 'channel-card';
        }
        element.className = 'channel-card active';
        activeElement = element;

        channelNameHeader.innerText = 'Connecting...';
        channelGroupText.innerText = channel.group;

        window.scrollTo(0, 0);

        if (hls) {
            hls.destroy();
            hls = null;
        }

        // Format-Agnostic Detection
        var lowerUrl = channel.url.toLowerCase();
        var isTS = lowerUrl.indexOf('.ts') !== -1;
        var isVOD = lowerUrl.indexOf('.mp4') !== -1 || lowerUrl.indexOf('.mkv') !== -1 || lowerUrl.indexOf('.avi') !== -1;
        var isXtream = lowerUrl.indexOf('/live/') !== -1;

        // Determine the ultimate player source
        var proxiedUrl = '/api/stream_proxy.php?url=' + encodeURIComponent(safeBtoa(channel.url));
        var finalUrl = (isVita && !isXtream) ? channel.url : proxiedUrl;
        
        // Final "VLC Mode" Initialization - Professional Grade Player
        if (typeof videojs !== 'undefined') {
            try {
                // If a player exists, dispose of it properly to reset hardware resources
                var oldPlayer = videojs.getPlayer('video-player');
                if (oldPlayer) {
                    oldPlayer.dispose();
                    // Reinject the video element into the DOM (Dispose removes it)
                    var wrapper = document.querySelector('.player-wrapper');
                    var newVideo = document.createElement('video');
                    newVideo.id = 'video-player';
                    newVideo.className = 'video-js vjs-default-skin vjs-big-play-centered';
                    newVideo.setAttribute('controls', 'true');
                    newVideo.setAttribute('preload', 'auto');
                    newVideo.style.position = 'absolute';
                    newVideo.style.top = '0';
                    newVideo.style.left = '0';
                    newVideo.style.width = '100%';
                    newVideo.style.height = '100%';
                    wrapper.appendChild(newVideo);
                }

                var player = videojs('video-player', {
                    fluid: true,
                    responsive: true,
                    html5: {
                        vhs: { overrideNative: !isVita }, // Force JS unblocking on non-Vita, use hardware on Vita
                        nativeAudioTracks: false,
                        nativeVideoTracks: false
                    }
                });

                player.src({
                    src: finalUrl,
                    type: isTS ? 'video/mp2t' : 'application/x-mpegURL'
                });

                player.on('error', function() {
                    channelNameHeader.innerText = 'Sync Crash';
                    channelGroupText.innerHTML = 'Bridge failed | <a href="' + channel.url + '" target="_blank" style="color:#00e1ff">TRY VLC DIRECT</a>';
                });

                player.play();
                channelNameHeader.innerText = (isVOD ? '🎬 ' : '📺 ') + channel.title;
                channelGroupText.innerHTML = isXtream ? 'VLC-Bridge unblocking active' : 'Stable hardware path active';
                
            } catch (e) {
                console.error("VideoJS Crash:", e);
                videoPlayer.src = finalUrl;
                videoPlayer.play();
            }
        } else {
            // Emergency fallback if library fails to load
            videoPlayer.src = finalUrl;
            videoPlayer.play();
        }
    }
});
