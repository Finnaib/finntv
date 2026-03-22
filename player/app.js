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

        window.scrollTo(0, 0);

        if (hls) {
            hls.destroy();
            hls = null;
        }

        if (window.Hls && Hls.isSupported()) {
            hls = new Hls();
            hls.on(Hls.Events.ERROR, function(evt, data) {
                if (data.fatal) {
                    channelNameHeader.innerText = 'CORS Blocked';
                    channelGroupText.innerText = data.type;
                }
            });
            hls.loadSource(channel.url);
            hls.attachMedia(videoPlayer);
            hls.on(Hls.Events.MANIFEST_PARSED, function() {
                var p = videoPlayer.play();
                if (p && p.catch) p.catch(function(){});
            });
        } 
        else {
            videoPlayer.innerHTML = 
                '<source src="' + channel.url + '" type="application/vnd.apple.mpegurl">' +
                '<source src="' + channel.url + '" type="application/x-mpegURL">';
            videoPlayer.load();
            var p = videoPlayer.play();
            if (p && p.catch) p.catch(function(){});
        }

        channelNameHeader.innerText = channel.title;
        channelGroupText.innerText = channel.group;
    }
});
