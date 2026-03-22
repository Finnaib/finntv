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
        if (err === 3 || err === 4) msg = 'Unsupported Codec/Format';
        channelNameHeader.innerText = 'Player Error ' + err;
        channelGroupText.innerText = msg + ' (Check site for updates)';
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

        // Detect H.265 from name
        var isH265 = channel.title.toLowerCase().indexOf('h265') !== -1 || channel.title.toLowerCase().indexOf('hevc') !== -1;

        // Determine if this is a raw .ts link or HLS manifest
        var isTS = channel.url.toLowerCase().indexOf('.ts') !== -1;

        if (window.Hls && Hls.isSupported() && !isTS) {
            
            function initHlsLayer(proxyProvider) {
                var config = {
                    enableWorker: true,
                    xhrSetup: function(xhr, url) {
                        try {
                            if (proxyProvider === 'internal') {
                                var encodedUrl = encodeURIComponent(safeBtoa(url));
                                xhr.open('GET', '/api/stream_proxy.php?url=' + encodedUrl, true);
                            } else if (proxyProvider === 'corsproxy') {
                                if (url.indexOf('corsproxy.io') === -1) {
                                    xhr.open('GET', 'https://corsproxy.io/?' + encodeURIComponent(url), true);
                                }
                            } else if (proxyProvider === 'allorigins') {
                                if (url.indexOf('allorigins.win') === -1) {
                                    xhr.open('GET', 'https://api.allorigins.win/raw?url=' + encodeURIComponent(url), true);
                                }
                            }
                        } catch (e) {
                            console.error("Proxy setup failed:", e);
                        }
                    }
                };
                
                hls = new Hls(config);
                
                hls.on(Hls.Events.ERROR, function(evt, data) {
                    if (data.fatal) {
                        if (data.type === Hls.ErrorTypes.NETWORK_ERROR) {
                            if (!proxyProvider) {
                                channelNameHeader.innerText = 'Unlocking Stream...';
                                hls.destroy();
                                initHlsLayer('internal');
                            } else if (proxyProvider === 'internal') {
                                channelNameHeader.innerText = 'Bypassing Block...';
                                hls.destroy();
                                initHlsLayer('corsproxy');
                            } else if (proxyProvider === 'corsproxy') {
                                channelNameHeader.innerText = 'Final Attempt...';
                                hls.destroy();
                                initHlsLayer('allorigins');
                            } else {
                                channelNameHeader.innerText = 'Stream Blocked / Offline';
                                channelGroupText.innerText = 'VLC-bypass and CORS proxies failed';
                            }
                        } else if (data.type === Hls.ErrorTypes.MEDIA_ERROR) {
                            if (isH265) {
                                channelNameHeader.innerText = 'Codec Error (H.265)';
                                channelGroupText.innerText = 'HEVC not supported in browser';
                            } else {
                                channelNameHeader.innerText = 'Media Format Error';
                                channelGroupText.innerText = 'Unsupported stream codec';
                            }
                            hls.destroy();
                        } else {
                            channelNameHeader.innerText = 'Fatal Player Error';
                            channelGroupText.innerText = data.type;
                            hls.destroy();
                        }
                    }
                });
                
                hls.loadSource(channel.url);
                hls.attachMedia(videoPlayer);
                hls.on(Hls.Events.MANIFEST_PARSED, function() {
                    channelNameHeader.innerText = channel.title;
                    var p = videoPlayer.play();
                    if (p && p.catch) p.catch(function(){});
                });
            }
            
            // Start direct
            initHlsLayer(null);
            
        } 
        else {
            // NATIVE FALLBACK (.ts links or browsers like Vita/Safari)
            var finalNativeUrl = '/api/stream_proxy.php?url=' + encodeURIComponent(safeBtoa(channel.url));
            
            videoPlayer.innerHTML = 
                '<source src="' + finalNativeUrl + '" type="video/mp2t">' +
                '<source src="' + finalNativeUrl + '" type="application/vnd.apple.mpegurl">';
            videoPlayer.load();
            var p = videoPlayer.play();
            if (p && p.catch) p.catch(function(){});
            
            channelNameHeader.innerText = (isTS ? '📺 ' : '') + channel.title;
            if (isTS && !isVita) {
                channelGroupText.innerText = 'RAW .TS link: May require Safari or Vita';
            }
        }
    }
});
