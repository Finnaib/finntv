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
    
    // Core Streaming Engines
    var hls = null;
    var dashPlayer = null;
    var tsPlayer = null;

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

    // Video Error Handling
    videoPlayer.addEventListener('error', function() {
        var err = videoPlayer.error ? videoPlayer.error.code : 'Unknown';
        channelNameHeader.innerText = 'Crash (Code ' + err + ')';
        channelGroupText.innerText = 'Codec unsupported or network block';
    }, true);

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
                    var streamUrl = line;
                    if (streamUrl.indexOf('/live/') !== -1 && streamUrl.slice(-3) === '.ts') {
                        streamUrl = streamUrl.slice(0, -3) + '.m3u8';
                    }
                    currentChannel.url = streamUrl;
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

        channelNameHeader.innerText = 'Tuning...';
        channelGroupText.innerText = channel.group;

        window.scrollTo(0, 0);

        // Core Cleanup - Destroy ALL active streaming engines before loading the new one
        if (hls) { hls.destroy(); hls = null; }
        if (dashPlayer) { dashPlayer.reset(); dashPlayer = null; }
        if (tsPlayer) { tsPlayer.destroy(); tsPlayer = null; }
        
        videoPlayer.removeAttribute('src');
        videoPlayer.load();

        var urlLower = channel.url.split('?')[0].toLowerCase();

        // 1. DASH Native Handling (.mpd)
        if (urlLower.indexOf('.mpd') !== -1) {
            if (!window.dashjs) {
                channelNameHeader.innerText = 'Downloading DASH.js Engine...';
                var script = document.createElement('script');
                script.src = 'https://cdn.dashjs.org/latest/dash.all.min.js';
                script.onload = function() {
                    dashPlayer = dashjs.MediaPlayer().create();
                    dashPlayer.initialize(videoPlayer, channel.url, true);
                    channelNameHeader.innerText = channel.title;
                };
                document.head.appendChild(script);
            } else {
                dashPlayer = dashjs.MediaPlayer().create();
                dashPlayer.initialize(videoPlayer, channel.url, true);
                channelNameHeader.innerText = channel.title;
            }
            return;
        }

        // 2. Raw MPEG-TS Handling (.ts)
        else if (urlLower.indexOf('.ts') !== -1 && !isVita) {
            if (!window.mpegts) {
                channelNameHeader.innerText = 'Downloading MPEG-TS Engine...';
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/mpegts.js@latest/dist/mpegts.min.js';
                script.onload = function() {
                    if (mpegts.getFeatureList().mseLivePlayback) {
                        tsPlayer = mpegts.createPlayer({ type: 'mse', isLive: true, url: channel.url });
                        tsPlayer.attachMediaElement(videoPlayer);
                        tsPlayer.load();
                        tsPlayer.play().catch(function(){});
                        channelNameHeader.innerText = channel.title;
                    } else {
                        channelNameHeader.innerText = 'MPEG-TS Unsupported on Browser';
                    }
                };
                document.head.appendChild(script);
            } else {
                tsPlayer = mpegts.createPlayer({ type: 'mse', isLive: true, url: channel.url });
                tsPlayer.attachMediaElement(videoPlayer);
                tsPlayer.load();
                tsPlayer.play().catch(function(){});
                channelNameHeader.innerText = channel.title;
            }
            return;
        }

        // 3. VOD / MP4 / Native HTML5 Video Handling (.mp4, .mkv, .webm)
        else if (urlLower.indexOf('.mp4') !== -1 || urlLower.indexOf('.mkv') !== -1 || urlLower.indexOf('.webm') !== -1 || urlLower.indexOf('.ogg') !== -1) {
            videoPlayer.src = channel.url;
            videoPlayer.play().catch(function(){});
            channelNameHeader.innerText = channel.title;
            return;
        }

        // 4. Default HLS Handling (.m3u8 & Extensions)
        if (window.Hls && Hls.isSupported()) {
            
            function initHls(useProxy) {
                var config = {};
                
                // If standard fetch fails due to CORS, intercept all XHRs and route via Vercel Stream Proxy
                if (useProxy) {
                    config.xhrSetup = function(xhr, url) {
                        if (url.indexOf('stream_proxy.php') === -1) {
                            var b64Url = btoa(unescape(encodeURIComponent(url)));
                            xhr.open('GET', '/api/stream_proxy.php?url=' + encodeURIComponent(b64Url), true);
                        } else {
                            xhr.open('GET', url, true);
                        }
                    };
                }
                
                hls = new Hls(config);
                
                hls.on(Hls.Events.ERROR, function(evt, data) {
                    if (data.fatal) {
                        // Automatically fall back to Vercel Local Proxy on first Network/CORS Error
                        if (data.type === Hls.ErrorTypes.NETWORK_ERROR && !useProxy) {
                            channelNameHeader.innerText = 'Mixed-Content Blocked. Rerouting...';
                            channelGroupText.innerText = 'Connecting via FINNTV Cloud Proxy';
                            hls.destroy();
                            initHls(true); // Retry with proxy enabled
                            return;
                        }
                        
                        channelNameHeader.innerText = 'Stream Offline / Blocked';
                        channelGroupText.innerText = data.type;
                    }
                });
                
                // If proxying, we also need to route the initial manifest through the proxy
                var sourceUrl = channel.url;
                if (useProxy && sourceUrl.indexOf('stream_proxy.php') === -1) {
                    var initialB64 = btoa(unescape(encodeURIComponent(sourceUrl)));
                    sourceUrl = '/api/stream_proxy.php?url=' + encodeURIComponent(initialB64);
                }
                
                hls.loadSource(sourceUrl);
                hls.attachMedia(videoPlayer);
                hls.on(Hls.Events.MANIFEST_PARSED, function() {
                    channelNameHeader.innerText = channel.title;
                    var p = videoPlayer.play();
                    if (p && p.catch) p.catch(function(){});
                });
            }
            
            // Start without proxy to save latency/bandwidth
            initHls(false);
            
        } 
        else {
            videoPlayer.innerHTML = 
                '<source src="' + channel.url + '" type="application/vnd.apple.mpegurl">' +
                '<source src="' + channel.url + '" type="application/x-mpegURL">';
            videoPlayer.load();
            var p = videoPlayer.play();
            if (p && p.catch) p.catch(function(){});
            
            channelNameHeader.innerText = channel.title;
        }
    }
});
