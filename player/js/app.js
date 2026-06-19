import { XtreamAPI, M3UParser } from './api.js';

class App {
    constructor() {
        this.appEl = document.getElementById('app');
        this.api = null;
        this.authMode = 'xtream';
        this.currentView = 'vod'; // Default to VOD (Movies) for Netflix feel
        
        this.categories = [];
        this.streamsByCategory = {};
        
        this.player = null; // Plyr instance
        this.hls = null;

        this.init();
    }

    init() {
        this.renderShell();
        this.showLogin();
        lucide.createIcons();
    }

    renderShell() {
        this.appEl.innerHTML = `
            <!-- Login Screen -->
            <div id="login-screen" class="screen">
                <div class="login-card">
                    <div class="login-header">
                        <h1 class="brand-font" style="color:var(--primary)">FINNTV</h1>
                        <p style="color:#aaa">Sign in to start watching.</p>
                    </div>
                    
                    <form id="login-form">
                        <input type="text" id="x-user" class="form-input" placeholder="Username" required>
                        <input type="password" id="x-pass" class="form-input" placeholder="Password" required>
                        <input type="url" id="x-url" class="form-input" placeholder="http://domain.com:port" required>
                        <button type="submit" class="btn-primary">Sign In</button>
                    </form>
                </div>
            </div>

            <!-- Main Dashboard (Netflix UI) -->
            <div id="dashboard-screen" class="screen" style="background: var(--bg-base);">
                <nav class="netflix-nav" id="netflix-nav">
                    <div class="nav-left">
                        <div class="nav-brand">FINNTV</div>
                        <div class="nav-links">
                            <button class="nav-btn" data-view="live">Live TV</button>
                            <button class="nav-btn active" data-view="vod">Movies</button>
                            <button class="nav-btn" data-view="series">Series</button>
                        </div>
                    </div>
                    <div class="nav-right">
                        <button id="btn-logout" class="btn-logout">Sign Out</button>
                    </div>
                </nav>
                
                <header class="hero-banner" id="hero-banner">
                    <div class="hero-content">
                        <h1 class="hero-title" id="hero-title">Welcome</h1>
                        <p class="hero-desc" id="hero-desc">Select a category to start watching.</p>
                        <div class="hero-buttons">
                            <button class="btn-play" id="hero-play"><i-lucide name="play" fill="black"></i-lucide> Play</button>
                        </div>
                    </div>
                    <div class="hero-fade-bottom"></div>
                </header>

                <main class="netflix-rows" id="netflix-rows">
                    <!-- Category rows will be injected here -->
                </main>
            </div>

            <!-- Player Overlay -->
            <div id="player-overlay">
                <div class="player-header">
                    <button class="btn-close-player" id="btn-close-player"><i-lucide name="arrow-left" size="32"></i-lucide></button>
                    <h2 id="np-title" style="margin:0; font-weight:600"></h2>
                </div>
                <div style="flex:1; width:100%; height:100%; background:black; display:flex; align-items:center; justify-content:center; position:relative">
                    <video id="plyr-player" playsinline controls style="width:100%; height:100%;"></video>
                    <!-- VLC Fallback Overlay -->
                    <div id="vlc-fallback" style="display:none; position:absolute; z-index:100; text-align:center; background:rgba(0,0,0,0.8); padding:40px; border-radius:8px;">
                        <i-lucide name="alert-triangle" color="var(--primary)" size="48" style="margin-bottom:16px"></i-lucide>
                        <h2>Format Not Supported by Browser</h2>
                        <p style="color:#aaa; margin:16px 0; max-width:400px">This video uses an MKV/HEVC codec which browsers cannot natively play. You can play it using an external player.</p>
                        <button id="btn-open-vlc" class="btn-primary" style="width:auto; padding:12px 24px; font-size:1.1rem">Open in VLC Media Player</button>
                    </div>
                </div>
            </div>

            <!-- Series Overlay -->
            <div id="series-overlay">
                <button class="btn-close-player" id="btn-close-series" style="position:absolute; top:24px; left:24px; z-index:100"><i-lucide name="arrow-left" size="32"></i-lucide></button>
                <div class="series-hero" id="series-hero">
                    <div class="series-hero-content">
                        <h1 class="hero-title" id="series-title">Series Name</h1>
                    </div>
                </div>
                <div class="series-body">
                    <div class="season-selector">
                        <select id="season-select"></select>
                    </div>
                    <div id="series-episodes" style="display:flex; flex-direction:column;"></div>
                </div>
            </div>

            <!-- Global Loader -->
            <div id="loader" class="loader-screen" style="display:none;">
                <div class="spinner"></div>
            </div>
        `;

        document.getElementById('login-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });

        const navBtns = document.querySelectorAll('.nav-btn');
        navBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                navBtns.forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.currentView = e.target.dataset.view;
                this.loadNetflixData();
            });
        });

        document.getElementById('btn-logout').addEventListener('click', () => {
            this.api = null;
            this.showLogin();
        });

        document.getElementById('btn-close-player').addEventListener('click', () => {
            this.closePlayer();
        });

        document.getElementById('btn-close-series').addEventListener('click', () => {
            document.getElementById('series-overlay').style.display = 'none';
        });

        // Navbar Scroll Effect
        const dashScreen = document.getElementById('dashboard-screen');
        dashScreen.addEventListener('scroll', () => {
            if (dashScreen.scrollTop > 50) {
                document.getElementById('netflix-nav').classList.add('scrolled');
            } else {
                document.getElementById('netflix-nav').classList.remove('scrolled');
            }
        });
        
        lucide.createIcons();
    }

    showLogin() {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById('login-screen').classList.add('active');
    }

    setLoading(isLoading) {
        document.getElementById('loader').style.display = isLoading ? 'flex' : 'none';
    }

    async handleLogin() {
        const user = document.getElementById('x-user').value;
        const pass = document.getElementById('x-pass').value;
        const url = document.getElementById('x-url').value;
        
        this.setLoading(true);
        try {
            this.api = new XtreamAPI(url, user, pass);
            await this.api.authenticate();
            document.getElementById('login-screen').classList.remove('active');
            document.getElementById('dashboard-screen').classList.add('active');
            this.loadNetflixData();
        } catch (e) {
            alert(e.message);
        }
        this.setLoading(false);
    }

    async loadNetflixData() {
        this.setLoading(true);
        try {
            // 1. Fetch Categories
            this.categories = await this.api.getCategories(this.currentView);
            
            // 2. Fetch ALL streams for this view to build rows efficiently without 100 API calls
            let allStreams = [];
            if (this.currentView === 'live') allStreams = await this.api.getLiveStreams();
            if (this.currentView === 'vod') allStreams = await this.api.getVodStreams();
            if (this.currentView === 'series') allStreams = await this.api.getSeries();
            
            // 3. Group streams by category
            this.streamsByCategory = {};
            allStreams.forEach(stream => {
                const catId = stream.category_id;
                if (!this.streamsByCategory[catId]) this.streamsByCategory[catId] = [];
                this.streamsByCategory[catId].push(stream);
            });
            
            this.renderNetflixRows();
        } catch (e) {
            console.error(e);
            alert("Failed to load content.");
        }
        this.setLoading(false);
    }

    renderNetflixRows() {
        const rowsContainer = document.getElementById('netflix-rows');
        rowsContainer.innerHTML = '';
        
        // Pick a random featured movie/series for Hero Banner
        const randomCat = this.categories[Math.floor(Math.random() * Math.min(10, this.categories.length))];
        let featuredStream = null;
        if (randomCat && this.streamsByCategory[randomCat.category_id]) {
            featuredStream = this.streamsByCategory[randomCat.category_id][0];
        }

        if (featuredStream) {
            this.updateHeroBanner(featuredStream);
        }

        // Render first 15 categories as rows to prevent memory overload
        const displayCategories = this.categories.slice(0, 15);
        
        displayCategories.forEach(cat => {
            const streams = this.streamsByCategory[cat.category_id];
            if (!streams || streams.length === 0) return;

            const row = document.createElement('div');
            row.className = 'row';
            
            row.innerHTML = `<h2 class="row-title">${cat.category_name}</h2>`;
            
            const postersDiv = document.createElement('div');
            postersDiv.className = 'row-posters';
            
            // Limit to 30 items per row
            streams.slice(0, 30).forEach(stream => {
                const poster = document.createElement('div');
                const isLive = this.currentView === 'live';
                poster.className = `row-poster ${isLive ? 'live' : ''}`;
                
                let name = stream.name;
                let img = stream.stream_icon || stream.cover || '';
                
                if (img) {
                    poster.innerHTML = `<img src="${img}" style="width:100%; height:100%; object-fit:cover; border-radius:4px" onerror="this.style.display='none'">`;
                } else {
                    poster.innerHTML = `<div class="poster-fallback">${name}</div>`;
                }
                
                poster.onclick = () => {
                    this.updateHeroBanner(stream);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                };
                postersDiv.appendChild(poster);
            });
            
            row.appendChild(postersDiv);
            rowsContainer.appendChild(row);
        });
    }

    updateHeroBanner(stream) {
        const hero = document.getElementById('hero-banner');
        const title = document.getElementById('hero-title');
        const desc = document.getElementById('hero-desc');
        const playBtn = document.getElementById('hero-play');

        title.innerText = stream.name;
        desc.innerText = stream.plot || (this.currentView === 'live' ? 'Watch Live TV' : 'Watch now on FinnTV');
        
        const img = stream.stream_icon || stream.backdrop_path?.[0] || stream.cover || '';
        if (img) {
            hero.style.backgroundImage = `url('${img}')`;
        } else {
            hero.style.background = 'linear-gradient(to right, #141414, #333)';
        }

        playBtn.onclick = () => {
            if (this.currentView === 'series') {
                this.showSeriesDetails(stream.series_id, stream.name, stream);
            } else {
                const ext = stream.container_extension || (this.currentView === 'vod' ? 'mp4' : 'ts');
                const url = this.api.buildStreamUrl(stream.stream_id, this.currentView === 'vod' ? 'movie' : 'live', ext);
                this.playVideo(url, stream.name);
            }
        };
        lucide.createIcons();
    }

    async showSeriesDetails(seriesId, seriesName, seriesObj) {
        this.setLoading(true);
        try {
            const data = await this.api.getSeriesInfo(seriesId);
            const info = data.info || seriesObj || {};
            
            document.getElementById('series-overlay').style.display = 'block';
            document.getElementById('series-title').innerText = info.name || seriesName;
            
            const hero = document.getElementById('series-hero');
            const backdrop = info.backdrop_path?.[0] || info.cover || '';
            if (backdrop) {
                hero.style.backgroundImage = `url('${backdrop}')`;
            } else {
                hero.style.background = 'linear-gradient(to bottom, #333, var(--bg-base))';
            }

            const seasonSelect = document.getElementById('season-select');
            const episodesContainer = document.getElementById('series-episodes');
            seasonSelect.innerHTML = '';
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
            if (seasons.length === 0) throw new Error("No episodes found");
            
            seasons.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s;
                opt.innerText = `Season ${s}`;
                seasonSelect.appendChild(opt);
            });

            const renderEpisodes = (season) => {
                episodesContainer.innerHTML = '';
                const eps = groupedEps[season] || [];
                eps.forEach(ep => {
                    const row = document.createElement('div');
                    row.className = 'episode-row';
                    const img = ep.info?.movie_image ? `<img src="${ep.info.movie_image}">` : '';
                    row.innerHTML = `
                        <div style="font-size:1.5rem; color:#aaa; font-weight:700; width:40px">${ep.episode_num}</div>
                        <div class="episode-thumb">${img}</div>
                        <div>
                            <h3 style="font-size:1.1rem; margin-bottom:8px">${ep.title || 'Episode ' + ep.episode_num}</h3>
                            <p style="color:#aaa; font-size:0.9rem">${ep.info?.plot || ''}</p>
                        </div>
                    `;
                    row.onclick = () => {
                        const ext = ep.container_extension || 'mp4';
                        const url = this.api.buildStreamUrl(ep.id, 'series', ext);
                        this.playVideo(url, ep.title || `S${season} E${ep.episode_num}`);
                    };
                    episodesContainer.appendChild(row);
                });
            };

            seasonSelect.onchange = (e) => renderEpisodes(e.target.value);
            renderEpisodes(seasons[0]);

        } catch (e) {
            console.error(e);
            alert("Could not load series details.");
        }
        this.setLoading(false);
    }

    // Advanced Playback Logic using Plyr and Protocol Upgrades
    playVideo(url, title) {
        const overlay = document.getElementById('player-overlay');
        document.getElementById('np-title').innerText = title;
        document.getElementById('vlc-fallback').style.display = 'none';
        overlay.style.display = 'flex';

        // Destroy existing player
        if (this.player) {
            this.player.destroy();
        }
        if (this.hls) {
            this.hls.destroy();
        }

        const videoEl = document.getElementById('plyr-player');
        
        // Protocol Upgrade: If stream is http://, try to upgrade to https:// to prevent Mixed Content
        let streamUrl = url;
        if (window.location.protocol === 'https:' && streamUrl.startsWith('http://')) {
            // Attempt protocol upgrade
            streamUrl = streamUrl.replace('http://', 'https://');
        }

        const ext = streamUrl.split('.').pop().split('?')[0].toLowerCase();
        
        // HLS Logic (Live TV)
        if (ext === 'm3u8' || ext === 'ts') {
            // Use stream_proxy ONLY for Live TV if needed, or directly
            let finalUrl = streamUrl;
            if (ext === 'ts') {
                // XTream API allows treating TS as M3U8 directly by changing extension
                finalUrl = streamUrl.replace('.ts', '.m3u8');
            }
            
            // Route through PHP proxy for CORS and to ensure HLS chunking works
            const encoded = encodeURIComponent(btoa(finalUrl));
            const proxyUrl = `../api/stream_proxy.php?url=${encoded}`;

            if (Hls.isSupported()) {
                this.hls = new Hls();
                this.hls.loadSource(proxyUrl);
                this.hls.attachMedia(videoEl);
                this.player = new Plyr(videoEl, { autoplay: true });
                this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    this.player.play();
                });
            } else if (videoEl.canPlayType('application/vnd.apple.mpegurl')) {
                videoEl.src = proxyUrl;
                this.player = new Plyr(videoEl, { autoplay: true });
            }
        } 
        // Direct MP4/MKV (VOD/Series)
        else {
            this.player = new Plyr(videoEl, { autoplay: true });
            
            // Handle native HTML5 video errors for MKV/HEVC
            videoEl.addEventListener('error', (e) => {
                const error = videoEl.error;
                // If format is unsupported (code 4)
                if (error && error.code === 4) {
                    this.showVlcFallback(streamUrl);
                }
            });

            // Set source directly (bypass proxy to prevent 4.5MB Vercel crash)
            videoEl.src = streamUrl;
            this.player.play().catch(e => {
                console.warn("Autoplay or decode failed", e);
                // The error listener above will catch format issues
            });
        }
    }

    showVlcFallback(streamUrl) {
        document.getElementById('vlc-fallback').style.display = 'block';
        const btn = document.getElementById('btn-open-vlc');
        btn.onclick = () => {
            // Uses standard VLC protocol handler
            window.location.href = `vlc://${streamUrl}`;
        };
    }

    closePlayer() {
        document.getElementById('player-overlay').style.display = 'none';
        if (this.player) {
            this.player.stop();
            this.player.destroy();
            this.player = null;
        }
        if (this.hls) {
            this.hls.destroy();
            this.hls = null;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
});
