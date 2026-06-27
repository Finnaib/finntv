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
                    <button class="btn-close" id="btn-close-player"><i-lucide name="arrow-left"></i-lucide></button>
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

            <!-- Movie Info Overlay -->
            <div id="movie-info-overlay" class="screen" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:1000; align-items:center; justify-content:center;">
                <div class="movie-info-card" style="display:flex; background:#111; border:1px solid #333; border-radius:12px; width:90%; max-width:900px; height:600px; overflow:hidden; position:relative;">
                    <button class="btn-close" id="btn-close-mi" style="position:absolute; top:20px; right:20px; background:rgba(0,0,0,0.5); border:none; color:white; width:40px; height:40px; border-radius:50%; cursor:pointer;"><i-lucide name="arrow-left"></i-lucide></button>
                    <div class="mi-poster" id="mi-poster" style="width:350px; background:#000;"></div>
                    <div class="mi-details" style="flex:1; padding:40px; display:flex; flex-direction:column; overflow-y:auto;">
                        <h1 class="mi-title brand-font" id="mi-title" style="font-size:2.5rem; margin-bottom:10px;">Movie Title</h1>
                        <div class="mi-meta" id="mi-meta" style="color:#aaa; font-size:1rem; margin-bottom:24px; display:flex; gap:16px;"></div>
                        <p class="mi-plot" id="mi-plot" style="font-size:1.1rem; line-height:1.6; margin-bottom:30px; color:#e2e8f0;">Plot description goes here...</p>
                        <div class="mi-cast" id="mi-cast" style="margin-bottom:30px; color:#aaa;"></div>
                        <div class="mi-buttons" style="display:flex; gap:16px; margin-top:auto;">
                            <button class="btn btn-primary" id="btn-mi-play"><i-lucide name="play"></i-lucide> Play Now</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Series Chooser Overlay -->
            <div id="series-overlay" class="screen" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:var(--bg-dark); z-index:900; flex-direction:column;">
                <div class="series-header" style="display:flex; align-items:center; padding:24px 40px; background:rgba(0,0,0,0.4); border-bottom:1px solid #333; gap:20px;">
                    <button class="btn btn-outline" id="btn-close-series"><i-lucide name="arrow-left"></i-lucide> Back</button>
                    <h2 class="brand-font" id="series-title" style="font-size:2rem">Series Name</h2>
                </div>
                <div class="series-content" style="display:flex; flex:1; overflow:hidden;">
                    <div class="series-seasons" id="series-seasons" style="width:250px; background:rgba(0,0,0,0.2); border-right:1px solid #333; overflow-y:auto; padding:20px 0;"></div>
                    <div class="series-episodes" id="series-episodes" style="flex:1; overflow-y:auto; padding:40px; display:flex; flex-direction:column; gap:16px;"></div>
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
            if (window.location.hash === '#playing') {
                history.back();
            } else {
                this.stopPlayer();
            }
        });
        
        document.getElementById('btn-close-mi').addEventListener('click', () => {
            if (window.location.hash === '#movie-info') {
                history.back();
            } else {
                document.getElementById('movie-info-overlay').style.display = 'none';
            }
        });
        
        document.getElementById('btn-close-series').addEventListener('click', () => {
            if (window.location.hash === '#series') {
                history.back();
            } else {
                document.getElementById('series-overlay').style.display = 'none';
            }
        });

        // History API popstate event to handle browser back button
        window.addEventListener('popstate', (e) => {
            const hash = window.location.hash;
            
            // If we're no longer playing a video, but the player overlay is active, stop it
            if (hash !== '#playing' && document.getElementById('player-overlay').classList.contains('active')) {
                this.stopPlayer();
            }
            
            // Handle movie info overlay
            if (hash !== '#movie-info' && document.getElementById('movie-info-overlay').style.display === 'flex') {
                document.getElementById('movie-info-overlay').style.display = 'none';
            }
            
            // Handle series overlay
            if (hash !== '#series' && document.getElementById('series-overlay').style.display === 'flex') {
                document.getElementById('series-overlay').style.display = 'none';
            }
        });

        // Make sure header is always visible
        const playerOverlay = document.getElementById('player-overlay');
        const playerHeader = playerOverlay.querySelector('.player-header');
        playerHeader.classList.remove('idle');
        
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
        lucide.createIcons({ root: toast });
        
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
                    } else if (this.currentView === 'vod') {
                        this.showMovieDetails(streamId, name, stream);
                    } else {
                        const url = this.api.buildStreamUrl(streamId, 'live');
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
        lucide.createIcons({ root: overlay });

        // Push state to history for back button support
        if (window.location.hash !== '#playing') {
            history.pushState({overlay: 'player'}, '', '#playing');
        }

        this.initVideoJS();

        this.player.ready(() => {
            this.teardownSubPlayers();

            // Convert raw live .ts streams to .m3u8 HLS playlists where possible.
            let streamUrl = url;
            if (streamUrl.includes('/live/') && streamUrl.split('?')[0].endsWith('.ts')) {
                streamUrl = streamUrl.replace(/\.ts(\?|$)/, '.m3u8$1');
            }

            const isLive = this.currentView === 'live' || this.authMode === 'm3u';
            
            // Use stream_proxy.php for Live TV to parse tiny text playlists
            // Use video_proxy.js (Edge function) for VOD/Series to stream huge MP4/MKV files without freezing
            let proxyUrl;
            if (isLive) {
                proxyUrl = `../api/stream_proxy.php?url=${encodeURIComponent(btoa(unescape(encodeURIComponent(streamUrl))))}`;
                if (streamUrl.includes('jmp2.uk') || streamUrl.includes('samsungtvplus')) {
                    proxyUrl += '&noprx=1';
                }
            } else {
                // VOD/Series (MP4/MKV) are too large for Vercel Edge/Serverless limits to proxy continuously.
                // We MUST stream them directly to the browser.
                proxyUrl = streamUrl;
            }
            
            const stype = this.getStreamType(streamUrl);
            const vidEl = this.player.tech(true) ? this.player.tech(true).el() : null;

            if (!vidEl) {
                console.error("Video element not ready");
                return;
            }

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
                this.player.src({ src: proxyUrl, type: 'application/x-mpegURL' });
                this.player.play().catch(e => console.warn(e));
            } else {
                this.player.src({ src: proxyUrl, type: 'video/mp4' });
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
        if (u.includes('.m3u8') || u.includes('jmp2.uk') || u.includes('samsungtvplus')) return 'm3u8';
        if (u.includes('.ts')) return 'ts';
        if (u.includes('.mp4') || u.includes('.mkv')) return 'mp4';
        return 'm3u8'; // Defaulting to HLS since it's the standard for most modern web playlists
    }

    async showMovieDetails(streamId, name, streamObj) {
        this.setLoading(true);
        try {
            const data = await this.api._fetchAction(`get_vod_info&vod_id=${streamId}`);
            const info = data.info || streamObj || {};
            
            document.getElementById('movie-info-overlay').style.display = 'flex';
            
            // Push state for back button
            if (window.location.hash !== '#movie-info') {
                history.pushState({overlay: 'movie-info'}, '', '#movie-info');
            }
            
            const posterImg = info.movie_image || info.cover || streamObj.stream_icon;
            document.getElementById('mi-poster').innerHTML = posterImg ? `<img src="${posterImg}" style="width:100%; height:100%; object-fit:cover;">` : '';
            document.getElementById('mi-title').innerText = info.name || name;
            
            document.getElementById('mi-meta').innerHTML = `
                ${info.rating ? `<span>⭐ ${info.rating}</span>` : ''}
                ${info.year ? `<span>📅 ${info.year}</span>` : ''}
                ${info.duration ? `<span>⏱ ${info.duration}</span>` : ''}
            `;
            document.getElementById('mi-plot').innerText = info.plot || 'No description available.';
            document.getElementById('mi-cast').innerHTML = `
                ${info.director ? `<strong>Director:</strong> ${info.director}<br>` : ''}
                ${info.cast ? `<strong>Cast:</strong> ${info.cast}` : ''}
            `;

            const playBtn = document.getElementById('btn-mi-play');
            playBtn.onclick = () => {
                document.getElementById('movie-info-overlay').style.display = 'none';
                const ext = streamObj.container_extension || 'mp4';
                const url = this.api.buildStreamUrl(streamId, 'movie', ext);
                this.playVideo(url, name, 'Movie');
            };
            
            lucide.createIcons({ root: document.getElementById('movie-info-overlay') });
        } catch (e) {
            this.showToast('Failed to load movie info', 'error');
        }
        this.setLoading(false);
    }

    async showSeriesDetails(seriesId, seriesName, seriesObj) {
        this.setLoading(true);
        try {
            const data = await this.api.getSeriesInfo(seriesId);
            if (!data || (!data.episodes && !Array.isArray(data))) throw new Error("Could not load series info");
            
            document.getElementById('series-overlay').style.display = 'flex';
            document.getElementById('series-title').innerText = seriesName;
            
            // Push state for back button
            if (window.location.hash !== '#series') {
                history.pushState({overlay: 'series'}, '', '#series');
            }
            
            const seasonsContainer = document.getElementById('series-seasons');
            const episodesContainer = document.getElementById('series-episodes');
            seasonsContainer.innerHTML = '';
            episodesContainer.innerHTML = '';
            
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
            
            seasons.forEach((s, idx) => {
                const el = document.createElement('div');
                el.style.cssText = `padding: 16px 24px; font-size: 1.2rem; font-weight: 600; cursor: pointer; transition: 0.2s; border-left: 4px solid ${idx === 0 ? 'var(--primary)' : 'transparent'}; background: ${idx === 0 ? 'rgba(59, 130, 246, 0.15)' : 'transparent'}; color: ${idx === 0 ? 'var(--primary)' : 'inherit'};`;
                el.innerText = `Season ${s}`;
                el.onclick = () => {
                    Array.from(seasonsContainer.children).forEach(x => {
                        x.style.borderLeftColor = 'transparent';
                        x.style.background = 'transparent';
                        x.style.color = 'inherit';
                    });
                    el.style.borderLeftColor = 'var(--primary)';
                    el.style.background = 'rgba(59, 130, 246, 0.15)';
                    el.style.color = 'var(--primary)';
                    this.renderSmartersEpisodes(groupedEps[s], s);
                };
                seasonsContainer.appendChild(el);
            });

            if (seasons.length > 0) {
                this.renderSmartersEpisodes(groupedEps[seasons[0]], seasons[0]);
            }
        } catch (e) {
            this.showToast('Failed to load series', 'error');
        }
        this.setLoading(false);
    }

    renderSmartersEpisodes(episodes, season) {
        const container = document.getElementById('series-episodes');
        container.innerHTML = '';
        
        // Prevent browser freeze if a season has thousands of episodes
        const safeEpisodes = episodes.slice(0, 500);
        
        safeEpisodes.forEach(ep => {
            const card = document.createElement('div');
            card.style.cssText = 'display: flex; background: rgba(30, 42, 71, 0.7); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; overflow: hidden; cursor: pointer; transition: 0.2s; height: 120px;';
            card.onmouseenter = () => { card.style.borderColor = 'var(--primary)'; card.style.transform = 'translateX(10px)'; };
            card.onmouseleave = () => { card.style.borderColor = 'rgba(255,255,255,0.1)'; card.style.transform = 'translateX(0)'; };
            
            const img = ep.info?.movie_image || ep.info?.cover || '';
            const imgHtml = img ? `<img src="${img}" style="width:100%; height:100%; object-fit:cover;">` : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#444;background:#000;">No Image</div>`;
            
            card.innerHTML = `
                <div style="width: 200px; background: #000;">${imgHtml}</div>
                <div style="padding: 20px; flex: 1; display: flex; flex-direction: column; justify-content: center;">
                    <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 8px;">${ep.title || `Episode ${ep.episode_num}`}</div>
                    <div style="color: #aaa; font-size: 0.9rem;">Season ${season} • Episode ${ep.episode_num}</div>
                </div>
                <div style="margin-left: auto; padding: 0 30px; display: flex; align-items: center; color: var(--primary);">
                    <i-lucide name="play-circle" size="32"></i-lucide>
                </div>
            `;
            
            card.onclick = () => {
                const ext = ep.container_extension || 'mp4';
                const url = this.api.buildStreamUrl(ep.id, 'series', ext);
                this.playVideo(url, ep.title || `S${season} E${ep.episode_num}`, 'Series');
            };
            container.appendChild(card);
        });
        lucide.createIcons({ root: container });
    }
}

// Start App
document.addEventListener('DOMContentLoaded', () => {
    window.finntvApp = new App();
});
