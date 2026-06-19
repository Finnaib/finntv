import { XtreamAPI, M3UParser } from './api.js';

class App {
    constructor() {
        this.appEl = document.getElementById('app');
        this.api = null; // XtreamAPI instance
        this.m3uChannels = []; // Array of M3U channels
        this.authMode = 'xtream'; // 'xtream' or 'm3u'
        this.currentView = 'live'; // 'live', 'vod', 'series'
        
        this.categories = [];
        this.streams = [];
        this.filteredStreams = [];
        this.currentCategory = null;

        this.player = null; // Video.js instance
        this.hls = null;
        this.mpegts = null;

        this.init();
    }

    init() {
        this.renderShell();
        this.showLogin();
        
        // Auto-login logic for M3U links passed from the main site
        const urlParams = new URLSearchParams(window.location.search);
        const m3uUrl = urlParams.get('m3u');
        if (m3uUrl) {
            // Switch to M3U tab
            const tabs = document.querySelectorAll('.auth-tab');
            tabs.forEach(t => t.classList.remove('active'));
            const m3uTab = document.querySelector('.auth-tab[data-mode="m3u"]');
            if (m3uTab) m3uTab.classList.add('active');
            
            this.authMode = 'm3u';
            document.getElementById('xtream-fields').style.display = 'none';
            document.getElementById('m3u-fields').style.display = 'block';
            
            // Fill input and submit
            document.getElementById('m-url').value = m3uUrl;
            this.handleLogin();
        }

        // Initialize Lucide icons
        lucide.createIcons();
    }

    renderShell() {
        this.appEl.innerHTML = `
            <div class="app-bg"></div>
            
            <!-- Login Screen -->
            <div id="login-screen" class="screen">
                <div class="login-card">
                    <div class="login-header">
                        <h1 class="brand-font">FinnTV Player</h1>
                        <p>Sign in to your premium IPTV account</p>
                    </div>
                    
                    <div class="auth-tabs">
                        <div class="auth-tab active" data-mode="xtream">Xtream Codes</div>
                        <div class="auth-tab" data-mode="m3u">M3U Playlist</div>
                    </div>
                    
                    <form id="login-form">
                        <div id="xtream-fields">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" id="x-user" class="form-input" placeholder="Enter username">
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" id="x-pass" class="form-input" placeholder="Enter password">
                            </div>
                            <div class="form-group">
                                <label>Server URL</label>
                                <input type="url" id="x-url" class="form-input" placeholder="http://domain.com:port">
                            </div>
                        </div>
                        
                        <div id="m3u-fields" style="display: none;">
                            <div class="form-group">
                                <label>M3U URL</label>
                                <input type="url" id="m-url" class="form-input" placeholder="http://domain.com/playlist.m3u">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                            <i-lucide name="log-in"></i-lucide> Login
                        </button>
                    </form>
                </div>
            </div>

            <!-- Dashboard Screen -->
            <div id="dashboard-screen" class="screen">
                <nav class="top-nav">
                    <div class="nav-brand">FinnTV</div>
                    <div class="nav-links">
                        <button class="nav-btn active" data-view="live">Live TV</button>
                        <button class="nav-btn" data-view="vod">Movies</button>
                        <button class="nav-btn" data-view="series">Series</button>
                    </div>
                    <div class="nav-user">
                        <a href="../index.html" class="btn-home"><i-lucide name="home"></i-lucide> <span>Back to Home</span></a>
                        <button id="btn-logout" class="btn-logout"><i-lucide name="log-out"></i-lucide> <span>Logout</span></button>
                    </div>
                </nav>
                <div class="main-content">
                    <aside class="sidebar">
                        <div class="sidebar-header">
                            <div class="search-box">
                                <i-lucide name="search"></i-lucide>
                                <input type="text" id="search-input" placeholder="Search channels...">
                            </div>
                        </div>
                        <div class="category-list" id="category-list"></div>
                    </aside>
                    <main class="content-area">
                        <div class="grid-container" id="content-grid"></div>
                    </main>
                </div>
            </div>

            <!-- Video Player Overlay -->
            <div id="player-overlay">
                <div class="player-header">
                    <button class="btn-close" id="btn-close-player"><i-lucide name="x"></i-lucide></button>
                    <div class="now-playing-info">
                        <h2 id="np-title">Loading...</h2>
                        <p id="np-category">Please wait</p>
                    </div>
                </div>
                <div class="video-wrapper">
                    <video id="vjs-player" class="video-js vjs-default-skin vjs-big-play-centered" playsinline></video>
                </div>
            </div>
            
            <div id="loader" class="loader-screen" style="display: none;">
                <div class="spinner"></div>
                <h2>Loading...</h2>
            </div>

            <!-- Series Details Overlay (Netflix Style) -->
            <div id="series-overlay" class="screen" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:var(--bg-base); z-index:900; overflow-y:auto;">
                <button class="btn btn-primary" id="btn-close-series"><i-lucide name="x"></i-lucide></button>
                <div class="series-hero" id="series-hero">
                    <div class="series-hero-content">
                        <h2 id="series-title" class="series-hero-title brand-font">Series Name</h2>
                        <div class="series-hero-meta" id="series-meta">
                            <!-- Meta like year, rating goes here -->
                        </div>
                    </div>
                </div>
                <div class="series-body">
                    <div class="season-selector">
                        <h3 class="brand-font" style="font-size:1.5rem">Episodes</h3>
                        <select id="season-select"></select>
                    </div>
                    <div id="series-episodes" class="episode-list"></div>
                </div>
            </div>
        `;

        // Bind Login Events
        const tabs = document.querySelectorAll('.auth-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                tabs.forEach(t => t.classList.remove('active'));
                e.target.classList.add('active');
                this.authMode = e.target.dataset.mode;
                document.getElementById('xtream-fields').style.display = this.authMode === 'xtream' ? 'block' : 'none';
                document.getElementById('m3u-fields').style.display = this.authMode === 'm3u' ? 'block' : 'none';
            });
        });

        document.getElementById('login-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });

        // Bind Nav Events
        const navBtns = document.querySelectorAll('.nav-btn');
        navBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (this.authMode === 'm3u') {
                    this.showToast('M3U Playlists only support Live TV view', 'info');
                    return;
                }
                navBtns.forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.currentView = e.target.dataset.view;
                this.loadViewData();
            });
        });

        document.getElementById('btn-logout').addEventListener('click', () => {
            this.api = null;
            this.m3uChannels = [];
            this.showLogin();
        });

        document.getElementById('search-input').addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            this.filteredStreams = this.streams.filter(s => {
                const name = s.name || s.title || '';
                return name.toLowerCase().includes(query);
            });
            this.renderStreams();
        });

        document.getElementById('btn-close-player').addEventListener('click', () => {
            this.stopPlayer();
        });
        
        // Make sure header is always visible
        const playerOverlay = document.getElementById('player-overlay');
        const playerHeader = playerOverlay.querySelector('.player-header');
        playerHeader.classList.remove('idle');

        document.getElementById('btn-close-series').addEventListener('click', () => {
            document.getElementById('series-overlay').style.display = 'none';
        });
        
        // Infinite scroll listener
        const contentArea = document.querySelector('.content-area');
        if (contentArea) {
            contentArea.addEventListener('scroll', (e) => {
                const { scrollTop, scrollHeight, clientHeight } = e.target;
                if (scrollTop + clientHeight >= scrollHeight - 200) {
                    if (this.currentRenderLimit < this.filteredStreams.length) {
                        this.currentRenderLimit += 150;
                        this.renderStreams(true);
                    }
                }
            });
        }
    }

    showLogin() {
        document.getElementById('dashboard-screen').classList.remove('active');
        document.getElementById('login-screen').classList.add('active');
    }

    showDashboard() {
        document.getElementById('login-screen').classList.remove('active');
        document.getElementById('dashboard-screen').classList.add('active');
        lucide.createIcons();
    }

    setLoading(loading) {
        document.getElementById('loader').style.display = loading ? 'flex' : 'none';
    }

    showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        let icon = 'info';
        if (type === 'error') icon = 'alert-circle';
        if (type === 'success') icon = 'check-circle';
        
        toast.innerHTML = `<i-lucide name="${icon}"></i-lucide> <span>${message}</span>`;
        container.appendChild(toast);
        lucide.createIcons();
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    async handleLogin() {
        this.setLoading(true);
        try {
            if (this.authMode === 'xtream') {
                const u = document.getElementById('x-user').value.trim();
                const p = document.getElementById('x-pass').value.trim();
                const url = document.getElementById('x-url').value.trim();
                
                if (!u || !p || !url) throw new Error("Please fill all fields");
                
                this.api = new XtreamAPI(url, u, p);
                const res = await this.api.authenticate();
                
                if (res.success) {
                    this.showToast(`Welcome, ${u}`, 'success');
                    this.currentView = 'live';
                    this.showDashboard();
                    await this.loadViewData();
                } else {
                    throw new Error(res.error);
                }
            } else {
                const url = document.getElementById('m-url').value.trim();
                if (!url) throw new Error("Please enter M3U URL");
                
                // First try fetching directly (works for local files like ../m3u/sport.m3u)
                try {
                    this.m3uChannels = await M3UParser.fetchAndParse(url);
                } catch (err) {
                    console.warn("Direct fetch failed, attempting proxy...", err);
                    const proxyUrl = `../api/m3u_proxy.php?url=${encodeURIComponent(url)}`;
                    this.m3uChannels = await M3UParser.fetchAndParse(proxyUrl);
                }
                
                if (this.m3uChannels.length > 0) {
                    this.showToast(`Loaded ${this.m3uChannels.length} channels`, 'success');
                    this.currentView = 'live';
                    this.showDashboard();
                    this.buildM3UDashboard();
                } else {
                    throw new Error("No channels found in playlist");
                }
            }
        } catch (e) {
            this.showToast(e.message, 'error');
        }
        this.setLoading(false);
    }

    async loadViewData() {
        if (!this.api) return;
        this.setLoading(true);
        this.categories = [];
        this.streams = [];
        this.filteredStreams = [];
        
        try {
            if (this.currentView === 'live') {
                this.categories = await this.api.getLiveCategories();
            } else if (this.currentView === 'vod') {
                this.categories = await this.api.getVodCategories();
            } else if (this.currentView === 'series') {
                this.categories = await this.api.getSeriesCategories();
            }
            
            // Add 'All' category
            this.categories.unshift({ category_id: 'all', category_name: 'All Categories' });
            this.currentCategory = 'all';
            this.renderCategories();
            await this.loadCategoryStreams('all');
            
        } catch(e) {
            this.showToast('Failed to load categories', 'error');
        }
        this.setLoading(false);
    }

    async loadCategoryStreams(categoryId) {
        this.setLoading(true);
        try {
            const fetchCatId = categoryId === 'all' ? null : categoryId;
            if (this.currentView === 'live') {
                this.streams = await this.api.getLiveStreams(fetchCatId);
            } else if (this.currentView === 'vod') {
                this.streams = await this.api.getVodStreams(fetchCatId);
            } else if (this.currentView === 'series') {
                this.streams = await this.api.getSeries(fetchCatId);
            }
            this.currentCategory = categoryId;
            this.filteredStreams = [...this.streams];
            this.renderStreams();
        } catch (e) {
            this.showToast("Failed to load streams", "error");
        }
        this.setLoading(false);
    }

    buildM3UDashboard() {
        // Build categories based on groups
        const groups = new Set();
        this.m3uChannels.forEach(c => groups.add(c.group));
        
        this.categories = [{category_id: 'all', category_name: 'All Channels'}];
        Array.from(groups).sort().forEach(g => {
            if (g) this.categories.push({ category_id: g, category_name: g });
        });
        
        this.currentCategory = 'all';
        this.renderCategories();
        this.loadM3UCategory('all');
    }

    loadM3UCategory(group) {
        this.currentCategory = group;
        this.renderCategories();
        if (group === 'all') {
            this.streams = this.m3uChannels;
        } else {
            this.streams = this.m3uChannels.filter(c => c.group === group);
        }
        this.filteredStreams = [...this.streams];
        this.renderStreams();
    }

    renderCategories() {
        const list = document.getElementById('category-list');
        list.innerHTML = '';
        
        this.categories.forEach(cat => {
            const div = document.createElement('div');
            div.className = `cat-item ${this.currentCategory === cat.category_id ? 'active' : ''}`;
            div.innerHTML = `<span>${cat.category_name}</span>`;
            
            div.onclick = () => {
                if (this.authMode === 'xtream') {
                    this.loadCategoryStreams(cat.category_id);
                } else {
                    this.loadM3UCategory(cat.category_id);
                }
            };
            list.appendChild(div);
        });
    }

    renderStreams(append = false) {
        const grid = document.getElementById('content-grid');
        
        if (!append) {
            grid.innerHTML = '';
            this.currentRenderLimit = 150; // Start with 150
            // Reset scroll position on the container
            const container = document.querySelector('.content-area');
            if (container) container.scrollTop = 0;
        }
        
        let toRender = this.filteredStreams;
        
        if (toRender.length === 0) {
            if (!append) grid.innerHTML = '<div class="empty-state">No content found</div>';
            return;
        }
        
        // Slice for infinite scroll
        const startIndex = append ? this.currentRenderLimit - 150 : 0;
        const renderList = toRender.slice(startIndex, this.currentRenderLimit);
        
        renderList.forEach(stream => {
            const card = document.createElement('div');
            card.className = `media-card ${this.currentView === 'vod' || this.currentView === 'series' ? 'vod' : ''}`;
            
            const name = stream.name || stream.title || 'Unknown';
            const logo = stream.stream_icon || stream.cover || stream.logo || '';
            const streamId = stream.stream_id || stream.series_id || null;
            
            let imgHtml = logo ? `<img src="${logo}" class="card-img" onerror="this.style.display='none'">` 
                               : `<div class="card-fallback">${name.substring(0,2)}</div>`;
                               
            card.innerHTML = `
                <div class="card-img-wrapper">
                    ${imgHtml}
                </div>
                <div class="card-info">
                    <div class="card-title">${name}</div>
                    <div class="card-meta">
                        <span>${this.currentView === 'live' ? 'LIVE' : 'VOD'}</span>
                    </div>
                </div>
            `;
            
            card.onclick = () => {
                if (this.authMode === 'xtream') {
                    if (this.currentView === 'series') {
                        this.showSeriesDetails(streamId, name, stream);
                    } else {
                        const ext = stream.container_extension || (this.currentView === 'vod' ? 'mp4' : 'ts');
                        const url = this.api.buildStreamUrl(streamId, this.currentView === 'vod' ? 'movie' : 'live', ext);
                        this.playVideo(url, name, stream.category_name || 'Channel');
                    }
                } else {
                    // M3U stream
                    this.playVideo(stream.url, name, stream.group);
                }
            };
            grid.appendChild(card);
        });
    }

    // Video Player Logic
    initVideoJS() {
        if (!this.player) {
            this.player = videojs('vjs-player', {
                controls: true,
                autoplay: true,
                preload: 'auto',
                liveui: true,
                playbackRates: [1, 1.25, 1.5, 2]
            });
            this.player.on('error', () => {
                this.showToast('Playback error occurred', 'error');
            });
        }
    }

    teardownSubPlayers() {
        if (this.hls) {
            this.hls.destroy();
            this.hls = null;
        }
        if (this.mpegts) {
            this.mpegts.unload();
            this.mpegts.detachMediaElement();
            this.mpegts.destroy();
            this.mpegts = null;
        }
        if (this.player) {
            this.player.pause();
            this.player.reset();
        }
    }

    playVideo(url, title, category) {
        document.getElementById('np-title').innerText = title;
        document.getElementById('np-category').innerText = category;
        const overlay = document.getElementById('player-overlay');
        overlay.classList.add('active');
        lucide.createIcons();

        this.initVideoJS();

        this.player.ready(() => {
            this.teardownSubPlayers();

            // Convert raw live .ts streams to .m3u8 HLS playlists.
            // Raw .ts streams are infinite and will cause timeouts on serverless proxies (Vercel).
            // HLS chunks are small and finite, allowing the proxy to handle them successfully.
            let streamUrl = url;
            if (streamUrl.includes('/live/') && streamUrl.split('?')[0].endsWith('.ts')) {
                streamUrl = streamUrl.replace(/\.ts(\?|$)/, '.m3u8$1');
            }

            // Use Proxy to bypass CORS
            const proxyUrl = `../api/stream_proxy.php?url=${encodeURIComponent(btoa(unescape(encodeURIComponent(streamUrl))))}`;
            
            const isLive = this.currentView === 'live' || this.authMode === 'm3u';
            const stype = this.getStreamType(streamUrl);
            const vidEl = this.player.tech(true) ? this.player.tech(true).el() : null;

            if (!vidEl) {
                console.error("Video element not ready");
                return;
            }

            // Always clear previous errors
            this.player.error(null);

            if (stype === 'ts') {
                if (typeof mpegts !== 'undefined' && mpegts.getFeatureList().mseLivePlayback) {
                    this.mpegts = mpegts.createPlayer({ type: 'mse', isLive: isLive, url: proxyUrl, cors: true });
                    this.mpegts.attachMediaElement(vidEl);
                    this.mpegts.load();
                    this.mpegts.on(mpegts.Events.ERROR, (t) => { 
                        console.error('MPEG-TS Error:', t); 
                        this.showToast('Stream Error: ' + t, 'error');
                    });
                    this.player.play().catch(e => console.warn(e));
                } else {
                    this.player.src({ src: proxyUrl, type: 'video/mp2t' });
                    this.player.play().catch(e => console.warn(e));
                }
            } else if (stype === 'm3u8' || stype === 'hls') {
                // Trust video.js VHS engine for HLS, it is much more stable and won't throw SRC_NOT_SUPPORTED conflicts
                this.player.src({ src: proxyUrl, type: 'application/x-mpegURL' });
                this.player.play().catch(e => console.warn(e));
            } else {
                // Omit the type so the browser can sniff MKV vs MP4 natively
                this.player.src({ src: streamUrl });
                this.player.play().catch(e => console.warn(e));
            }
        });
    }

    stopPlayer() {
        const overlay = document.getElementById('player-overlay');
        overlay.classList.remove('active');
        this.teardownSubPlayers();
    }

    getStreamType(url) {
        const u = url.toLowerCase().split('?')[0];
        if (u.includes('.m3u8')) return 'm3u8';
        if (u.includes('.ts')) return 'ts';
        if (u.includes('.mp4') || u.includes('.mkv') || u.includes('.avi')) return 'mp4';
        return 'ts'; // Default assumption for IPTV
    }

    async showSeriesDetails(seriesId, seriesName, seriesObj) {
        this.setLoading(true);
        try {
            const data = await this.api.getSeriesInfo(seriesId);
            if (!data || (!data.episodes && !Array.isArray(data))) throw new Error("Could not load series info");
            
            const info = data.info || seriesObj || {};
            const backdrop = info.backdrop_path && info.backdrop_path.length > 0 ? info.backdrop_path[0] : (info.cover || '');
            
            const hero = document.getElementById('series-hero');
            if (backdrop) {
                hero.style.backgroundImage = `url('${backdrop}')`;
            } else {
                hero.style.background = 'linear-gradient(to right, var(--primary), var(--bg-base))';
            }
            
            document.getElementById('series-title').innerText = info.name || seriesName;
            
            const metaContainer = document.getElementById('series-meta');
            metaContainer.innerHTML = '';
            if (info.releaseDate) metaContainer.innerHTML += `<span>${info.releaseDate.substring(0,4)}</span>`;
            if (info.rating) metaContainer.innerHTML += `<span>⭐ ${info.rating}</span>`;
            if (info.genre) metaContainer.innerHTML += `<span>${info.genre}</span>`;
            
            const seasonSelect = document.getElementById('season-select');
            const episodesContainer = document.getElementById('series-episodes');
            
            seasonSelect.innerHTML = '';
            episodesContainer.innerHTML = '';
            
            // Normalize episodes data into { "Season X": [eps] }
            let groupedEps = {};
            let rawEpisodes = data.episodes || data;
            
            if (Array.isArray(rawEpisodes)) {
                rawEpisodes.forEach(ep => {
                    const s = ep.season || 1;
                    if (!groupedEps[s]) groupedEps[s] = [];
                    groupedEps[s].push(ep);
                });
            } else if (typeof rawEpisodes === 'object') {
                groupedEps = rawEpisodes;
            }
            
            const seasons = Object.keys(groupedEps).sort((a,b) => parseInt(a) - parseInt(b));
            if (seasons.length === 0) throw new Error("No episodes found");
            
            let currentSeason = seasons[0];
            
            const renderEpisodes = (season) => {
                episodesContainer.innerHTML = '';
                const eps = groupedEps[season] || [];
                eps.forEach(ep => {
                    const row = document.createElement('div');
                    row.className = 'episode-row';
                    
                    const epInfo = ep.info || {};
                    const imgHtml = epInfo.movie_image ? `<img src="${epInfo.movie_image}">` : '';
                    const duration = epInfo.duration ? `<div class="episode-duration">${epInfo.duration}</div>` : '';
                    const plot = epInfo.plot ? `<div class="episode-desc">${epInfo.plot}</div>` : '';
                    
                    row.innerHTML = `
                        <div class="episode-num">${ep.episode_num}</div>
                        <div class="episode-thumb">
                            ${imgHtml}
                            <div class="play-icon"><i-lucide name="play" color="white" size="32"></i-lucide></div>
                        </div>
                        <div class="episode-details">
                            <div class="episode-title-row">
                                <div class="episode-title">${ep.title || 'Episode ' + ep.episode_num}</div>
                                ${duration}
                            </div>
                            ${plot}
                        </div>
                    `;
                    
                    row.onclick = () => {
                        const ext = ep.container_extension || 'mp4';
                        const url = this.api.buildStreamUrl(ep.id, 'series', ext);
                        this.playVideo(url, ep.title || `S${season} E${ep.episode_num}`, seriesName);
                    };
                    episodesContainer.appendChild(row);
                });
                lucide.createIcons();
            };
            
            seasons.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s;
                opt.innerText = `Season ${s}`;
                seasonSelect.appendChild(opt);
            });
            
            seasonSelect.onchange = (e) => {
                renderEpisodes(e.target.value);
            };
            
            if (currentSeason) renderEpisodes(currentSeason);
            
            document.getElementById('series-overlay').style.display = 'flex';
            lucide.createIcons();
        } catch (e) {
            console.error("Series error:", e);
            this.showToast(e.message, 'error');
        }
        this.setLoading(false);
    }
}

// Start App
document.addEventListener('DOMContentLoaded', () => {
    window.finntvApp = new App();
});
