import { XtreamAPI, M3UParser } from './api.js';

class App {
    constructor() {
        this.appEl = document.getElementById('app');
        this.api = null;
        this.authMode = 'xtream';
        this.currentView = 'live'; 
        
        this.categories = [];
        this.streams = [];
        this.filteredStreams = [];
        this.currentCategory = null;
        this.currentRenderLimit = 150;

        this.player = null; // video.js instance
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
            this.authMode = 'm3u';
            document.getElementById('xtream-fields').style.display = 'none';
            document.getElementById('m3u-fields').style.display = 'block';
            document.getElementById('m-url').value = m3uUrl;
            this.handleLogin();
        }

        lucide.createIcons();
    }

    renderShell() {
        this.appEl.innerHTML = `
            <!-- Login Screen -->
            <div id="login-screen" class="screen">
                <div class="login-card">
                    <div class="login-header">
                        <h1 class="brand-font" style="color:white; font-size:2.5rem; margin-bottom:10px">FinnTV</h1>
                        <p style="color:var(--text-muted)">Premium IPTV Player</p>
                    </div>
                    
                    <div class="auth-tabs">
                        <div class="auth-tab active" data-mode="xtream">Xtream Codes</div>
                        <div class="auth-tab" data-mode="m3u">M3U Playlist</div>
                    </div>
                    
                    <form id="login-form">
                        <div id="xtream-fields">
                            <input type="text" id="x-user" class="form-input" placeholder="Username">
                            <input type="password" id="x-pass" class="form-input" placeholder="Password">
                            <input type="url" id="x-url" class="form-input" placeholder="http://domain.com:port">
                        </div>
                        <div id="m3u-fields" style="display: none;">
                            <input type="url" id="m-url" class="form-input" placeholder="http://domain.com/playlist.m3u">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                            <i-lucide name="log-in"></i-lucide> Login
                        </button>
                    </form>
                </div>
            </div>

            <!-- Home Dashboard (Smarters Style) -->
            <div id="home-screen" class="screen">
                <div class="home-header">
                    <h1 class="brand-font" style="color:white; font-size:2rem">FinnTV</h1>
                    <div class="home-user-info">
                        <div>
                            <div id="h-user" style="font-weight:bold; font-size:1.1rem">Username</div>
                            <div id="h-exp" style="color:var(--text-muted); font-size:0.9rem">Active</div>
                        </div>
                        <div class="home-avatar"><i-lucide name="user" size="28" color="white"></i-lucide></div>
                        <button id="btn-logout" class="btn btn-outline" style="margin-left:20px"><i-lucide name="log-out"></i-lucide></button>
                    </div>
                </div>
                <div class="home-grid">
                    <div class="home-tile" data-target="live">
                        <i-lucide name="tv" size="64"></i-lucide>
                        <h2 class="brand-font">Live TV</h2>
                    </div>
                    <div class="home-tile" data-target="vod">
                        <i-lucide name="film" size="64"></i-lucide>
                        <h2 class="brand-font">Movies</h2>
                    </div>
                    <div class="home-tile" data-target="series">
                        <i-lucide name="clapperboard" size="64"></i-lucide>
                        <h2 class="brand-font">Series</h2>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard (Sidebar + Grid) -->
            <div id="dashboard-screen" class="screen">
                <div class="dash-header">
                    <button class="btn btn-outline" id="btn-back-home"><i-lucide name="arrow-left"></i-lucide> Back</button>
                    <h2 id="dash-title" class="brand-font">Live TV</h2>
                    <div style="width:100px"></div>
                </div>
                <div class="dash-layout">
                    <aside class="sidebar">
                        <div class="search-box">
                            <input type="text" id="search-input" placeholder="Search...">
                        </div>
                        <div class="category-list" id="category-list"></div>
                    </aside>
                    <main class="content-area">
                        <div class="grid-container" id="content-grid"></div>
                    </main>
                </div>
            </div>

            <!-- Movie Info Overlay (Smarters Style) -->
            <div id="movie-info-overlay">
                <div class="movie-info-card">
                    <button class="mi-close" id="btn-close-mi"><i-lucide name="x"></i-lucide></button>
                    <div class="mi-poster" id="mi-poster"></div>
                    <div class="mi-details">
                        <h1 class="mi-title brand-font" id="mi-title">Movie Title</h1>
                        <div class="mi-meta" id="mi-meta"></div>
                        <p class="mi-plot" id="mi-plot">Plot description goes here...</p>
                        <div class="mi-cast" id="mi-cast"></div>
                        <div class="mi-buttons">
                            <button class="btn btn-primary" id="btn-mi-play"><i-lucide name="play"></i-lucide> Play Now</button>
                            <button class="btn btn-outline" id="btn-mi-trailer" style="display:none"><i-lucide name="youtube"></i-lucide> Trailer</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Series Chooser Overlay (Smarters Style) -->
            <div id="series-overlay">
                <div class="series-header">
                    <button class="btn btn-outline" id="btn-close-series"><i-lucide name="arrow-left"></i-lucide> Back</button>
                    <h2 class="brand-font" id="series-title" style="font-size:2rem">Series Name</h2>
                </div>
                <div class="series-content">
                    <div class="series-seasons" id="series-seasons"></div>
                    <div class="series-episodes" id="series-episodes"></div>
                </div>
            </div>

            <!-- Video Player Overlay -->
            <div id="player-overlay">
                <div class="player-header">
                    <button class="btn btn-outline" id="btn-close-player" style="border:none; background:transparent"><i-lucide name="arrow-left" size="32"></i-lucide></button>
                    <div class="now-playing-info">
                        <h2 id="np-title" style="margin:0">Loading...</h2>
                        <p id="np-category" style="margin:0; color:#aaa">Please wait</p>
                    </div>
                </div>
                <div class="video-wrapper" style="flex:1; width:100%; position:relative; background:black;">
                    <video id="vjs-player" class="video-js vjs-default-skin vjs-big-play-centered" playsinline style="width:100%; height:100%"></video>
                    
                    <!-- VLC Fallback Overlay -->
                    <div id="vlc-fallback" style="display:none; position:absolute; inset:0; z-index:100; text-align:center; background:rgba(0,0,0,0.8); flex-direction:column; align-items:center; justify-content:center; color:white">
                        <i-lucide name="alert-triangle" color="var(--primary)" size="48" style="margin-bottom:16px"></i-lucide>
                        <h2>Format Not Supported by Browser</h2>
                        <p style="color:#aaa; margin:16px 0; max-width:400px">This video uses an MKV/HEVC codec which browsers cannot natively play. You can play it using an external player.</p>
                        <button id="btn-open-vlc" class="btn btn-primary" style="width:auto; padding:12px 24px; font-size:1.1rem">Open in VLC Media Player</button>
                    </div>
                </div>
            </div>
            
            <div id="loader" class="loader-screen" style="display: none;">
                <div class="spinner"></div>
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

        // Home screen tiles
        document.querySelectorAll('.home-tile').forEach(tile => {
            tile.addEventListener('click', (e) => {
                if (this.authMode === 'm3u' && e.currentTarget.dataset.target !== 'live') {
                    this.showToast('M3U Playlists only support Live TV view', 'info');
                    return;
                }
                this.currentView = e.currentTarget.dataset.target;
                document.getElementById('dash-title').innerText = this.currentView === 'live' ? 'Live TV' : (this.currentView === 'vod' ? 'Movies' : 'Series');
                this.showDashboard();
                this.loadViewData();
            });
        });

        document.getElementById('btn-back-home').addEventListener('click', () => {
            this.showHome();
        });

        document.getElementById('btn-logout').addEventListener('click', () => {
            this.api = null;
            this.showLogin();
        });

        document.getElementById('search-input').addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            this.filteredStreams = this.streams.filter(s => {
                const name = s.name || s.title || '';
                return name.toLowerCase().includes(query);
            });
            this.currentRenderLimit = 150;
            this.renderStreams();
        });

        document.getElementById('btn-close-player').addEventListener('click', () => this.stopPlayer());
        document.getElementById('btn-close-mi').addEventListener('click', () => {
            document.getElementById('movie-info-overlay').style.display = 'none';
        });
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
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById('login-screen').classList.add('active');
    }

    showHome() {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById('home-screen').classList.add('active');
        lucide.createIcons();
    }

    showDashboard() {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
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
                    document.getElementById('h-user').innerText = res.info.username || u;
                    const exp = res.info.exp_date ? new Date(res.info.exp_date * 1000).toLocaleDateString() : 'Active';
                    document.getElementById('h-exp').innerText = `Expires: ${exp}`;
                    this.showToast(`Welcome, ${u}`, 'success');
                    this.showHome();
                } else {
                    throw new Error(res.error || "Invalid credentials");
                }
            } else {
                const url = document.getElementById('m-url').value.trim();
                if (!url) throw new Error("Please enter M3U URL");
                
                this.m3uChannels = await M3UParser.fetchAndParse(url);
                this.api = null;
                document.getElementById('h-user').innerText = 'M3U User';
                document.getElementById('h-exp').innerText = `${this.m3uChannels.length} Channels`;
                this.showToast('Playlist loaded', 'success');
                this.showHome();
            }
        } catch (e) {
            this.showToast(e.message, 'error');
        }
        this.setLoading(false);
    }

    async loadViewData() {
        this.setLoading(true);
        try {
            if (this.authMode === 'm3u') {
                this.categories = [];
                this.streams = this.m3uChannels.map(ch => ({
                    stream_id: ch.url,
                    name: ch.name,
                    stream_icon: ch.logo,
                    category_name: ch.group || 'All',
                    url: ch.url
                }));
                
                // Extract unique groups
                const groups = [...new Set(this.streams.map(s => s.category_name))];
                this.categories = groups.map((g, i) => ({ category_id: `m3u_${i}`, category_name: g }));
            } else {
                if (this.currentView === 'live') {
                    this.categories = await this.api.getLiveCategories();
                    this.streams = await this.api.getLiveStreams();
                } else if (this.currentView === 'vod') {
                    this.categories = await this.api.getVodCategories();
                    this.streams = await this.api.getVodStreams();
                } else if (this.currentView === 'series') {
                    this.categories = await this.api.getSeriesCategories();
                    this.streams = await this.api.getSeries();
                }
            }

            // Always add "All" category
            this.categories.unshift({ category_id: 'all', category_name: 'All Channels' });
            this.renderCategories();
            
            // Select first category by default
            if (this.categories.length > 0) {
                this.selectCategory(this.categories[0].category_id);
            }
        } catch (e) {
            this.showToast('Failed to load data', 'error');
            console.error(e);
        }
        this.setLoading(false);
    }

    renderCategories() {
        const list = document.getElementById('category-list');
        list.innerHTML = '';
        
        this.categories.forEach(cat => {
            const el = document.createElement('div');
            el.className = 'cat-item';
            el.innerText = cat.category_name;
            el.dataset.id = cat.category_id;
            
            el.onclick = () => this.selectCategory(cat.category_id);
            list.appendChild(el);
        });
    }

    selectCategory(catId) {
        document.querySelectorAll('.cat-item').forEach(el => el.classList.remove('active'));
        const activeEl = document.querySelector(`.cat-item[data-id="${catId}"]`);
        if (activeEl) activeEl.classList.add('active');

        this.currentCategory = catId;
        document.getElementById('search-input').value = '';
        
        if (catId === 'all') {
            this.filteredStreams = this.streams;
        } else {
            if (this.authMode === 'm3u') {
                const catName = this.categories.find(c => c.category_id === catId)?.category_name;
                this.filteredStreams = this.streams.filter(s => s.category_name === catName);
            } else {
                this.filteredStreams = this.streams.filter(s => String(s.category_id) === String(catId));
            }
        }
        
        this.currentRenderLimit = 150;
        this.renderStreams();
    }

    renderStreams(append = false) {
        const grid = document.getElementById('content-grid');
        if (!append) grid.innerHTML = '';
        
        const start = append ? this.currentRenderLimit - 150 : 0;
        const toRender = this.filteredStreams.slice(start, this.currentRenderLimit);

        toRender.forEach(stream => {
            const card = document.createElement('div');
            card.className = `card ${this.currentView === 'live' ? 'live' : ''}`;
            
            const name = stream.name || stream.title;
            const logo = stream.stream_icon || stream.cover || '';
            const streamId = stream.stream_id || stream.series_id;
            
            let imgHtml = logo ? `<img src="${logo}" class="card-img" onerror="this.style.display='none'">` 
                               : `<div class="card-fallback">${name.substring(0,2)}</div>`;
                               
            card.innerHTML = `
                <div class="card-img-wrapper">
                    ${imgHtml}
                </div>
                <div class="card-info">
                    <div class="card-title">${name}</div>
                </div>
            `;
            
            card.onclick = () => {
                if (this.authMode === 'xtream') {
                    if (this.currentView === 'series') {
                        this.showSeriesDetails(streamId, name, stream);
                    } else if (this.currentView === 'vod') {
                        this.showMovieDetails(streamId, name, stream);
                    } else {
                        const ext = stream.container_extension || 'ts';
                        const url = this.api.buildStreamUrl(streamId, 'live', ext);
                        this.playVideo(url, name, stream.category_name || 'Channel');
                    }
                } else {
                    this.playVideo(stream.url, name, stream.category_name);
                }
            };
            grid.appendChild(card);
        });
    }

    // --- Smarters Details Screens ---

    async showMovieDetails(streamId, name, streamObj) {
        this.setLoading(true);
        try {
            const data = await this.api._fetchAction(`get_vod_info&vod_id=${streamId}`);
            const info = data.info || streamObj || {};
            
            document.getElementById('movie-info-overlay').style.display = 'flex';
            
            const posterImg = info.movie_image || info.cover || streamObj.stream_icon;
            document.getElementById('mi-poster').innerHTML = posterImg ? `<img src="${posterImg}">` : '';
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
            
            lucide.createIcons();
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
                el.className = `season-item ${idx === 0 ? 'active' : ''}`;
                el.innerText = `Season ${s}`;
                el.onclick = () => {
                    document.querySelectorAll('.season-item').forEach(x => x.classList.remove('active'));
                    el.classList.add('active');
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
        
        episodes.forEach(ep => {
            const card = document.createElement('div');
            card.className = 'ep-card';
            
            const img = ep.info?.movie_image || ep.info?.cover || '';
            const imgHtml = img ? `<img src="${img}">` : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#444">No Image</div>`;
            
            card.innerHTML = `
                <div class="ep-img">${imgHtml}</div>
                <div class="ep-info">
                    <div class="ep-title">${ep.title || `Episode ${ep.episode_num}`}</div>
                    <div style="color:var(--text-muted); font-size:0.9rem">Season ${season} • Episode ${ep.episode_num}</div>
                </div>
                <div class="ep-play">
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
        lucide.createIcons();
    }

    // --- Video Player Logic ---

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
                const error = this.player.error();
                if (error && error.code === 4) {
                    this.showVlcFallback(this.player.currentSrc());
                } else {
                    this.showToast('Playback error occurred', 'error');
                }
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
        document.getElementById('vlc-fallback').style.display = 'none';
        
        const overlay = document.getElementById('player-overlay');
        overlay.style.display = 'flex';
        lucide.createIcons();

        this.initVideoJS();

        this.player.ready(() => {
            this.teardownSubPlayers();

            let streamUrl = url;
            if (window.location.protocol === 'https:' && streamUrl.startsWith('http://')) {
                streamUrl = streamUrl.replace('http://', 'https://');
            }

            const isLive = this.currentView === 'live' || this.authMode === 'm3u';
            const stype = this.getStreamType(streamUrl);
            const vidEl = this.player.tech(true) ? this.player.tech(true).el() : null;

            if (!vidEl) {
                console.error("Video element not ready");
                return;
            }

            this.player.error(null);

            if (isLive) {
                // Route through Edge proxy for CORS without Vercel timeouts
                const proxyUrl = `../api/video_proxy.js?url=${encodeURIComponent(btoa(streamUrl))}`;
                
                if (stype === 'ts') {
                    if (typeof mpegts !== 'undefined' && mpegts.getFeatureList().mseLivePlayback) {
                        this.mpegts = mpegts.createPlayer({ type: 'mse', isLive: true, url: proxyUrl, cors: true });
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
                } else {
                    this.player.src({ src: proxyUrl, type: 'application/x-mpegURL' });
                    this.player.play().catch(e => console.warn(e));
                }
            } else {
                // Route VOD/Series through Edge Proxy to bypass limits and CORS
                const edgeProxyUrl = `../api/video_proxy.js?url=${encodeURIComponent(btoa(streamUrl))}`;
                this.player.src({ src: edgeProxyUrl });
                this.player.play().catch(e => console.warn(e));
            }
        });
    }

    showVlcFallback(streamUrl) {
        document.getElementById('vlc-fallback').style.display = 'flex';
        const btn = document.getElementById('btn-open-vlc');
        btn.onclick = () => {
            window.location.href = `vlc://${streamUrl}`;
        };
    }

    stopPlayer() {
        document.getElementById('player-overlay').style.display = 'none';
        this.teardownSubPlayers();
    }

    getStreamType(url) {
        const u = url.toLowerCase().split('?')[0];
        if (u.includes('.m3u8')) return 'm3u8';
        if (u.includes('.ts')) return 'ts';
        if (u.includes('.mp4') || u.includes('.mkv') || u.includes('.avi')) return 'mp4';
        return 'ts'; // Default assumption for IPTV
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
});
