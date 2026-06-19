export class XtreamAPI {
    constructor(serverUrl, username, password) {
        // Ensure server URL doesn't end with slash
        this.serverUrl = serverUrl.replace(/\/$/, '');
        this.username = username;
        this.password = password;
        this.baseParams = `?username=${username}&password=${password}`;
    }

    getProxyUrl(actionUrl) {
        return `../api/xtream_proxy.php?url=${encodeURIComponent(btoa(unescape(encodeURIComponent(actionUrl))))}`;
    }

    async authenticate() {
        try {
            const url = `${this.serverUrl}/player_api.php${this.baseParams}`;
            const res = await fetch(this.getProxyUrl(url));
            if (!res.ok) throw new Error("Network response was not ok");
            const data = await res.json();
            if (data.user_info && data.user_info.auth === 1) {
                return { success: true, info: data.user_info };
            }
            return { success: false, error: "Invalid credentials" };
        } catch (e) {
            console.error("Auth error:", e);
            return { success: false, error: "Failed to connect to server" };
        }
    }

    async getLiveCategories() {
        return this._fetchAction('get_live_categories');
    }

    async getLiveStreams(categoryId = null) {
        const action = categoryId ? `get_live_streams&category_id=${categoryId}` : 'get_live_streams';
        return this._fetchAction(action);
    }

    async getVodCategories() {
        return this._fetchAction('get_vod_categories');
    }

    async getVodStreams(categoryId = null) {
        const action = categoryId ? `get_vod_streams&category_id=${categoryId}` : 'get_vod_streams';
        return this._fetchAction(action);
    }

    async getSeriesCategories() {
        return this._fetchAction('get_series_categories');
    }

    async getSeries(categoryId = null) {
        const action = categoryId ? `get_series&category_id=${categoryId}` : 'get_series';
        return this._fetchAction(action);
    }

    buildStreamUrl(streamId, type = 'live', extension = 'ts') {
        if (type === 'live') {
            return `${this.serverUrl}/live/${this.username}/${this.password}/${streamId}.${extension}`;
        } else if (type === 'movie') {
            return `${this.serverUrl}/movie/${this.username}/${this.password}/${streamId}.${extension}`;
        } else if (type === 'series') {
            return `${this.serverUrl}/series/${this.username}/${this.password}/${streamId}.${extension}`;
        }
    }

    async _fetchAction(action) {
        try {
            const url = `${this.serverUrl}/player_api.php${this.baseParams}&action=${action}`;
            const res = await fetch(this.getProxyUrl(url));
            return await res.json();
        } catch (e) {
            console.error(`Error fetching ${action}:`, e);
            return [];
        }
    }
}

export class M3UParser {
    static async fetchAndParse(url) {
        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error("Failed to load M3U");
            const text = await res.text();
            return this.parse(text);
        } catch (e) {
            console.error(e);
            throw e;
        }
    }

    static parse(content) {
        const lines = content.replace(/\r/g, '').split('\n');
        const channels = [];
        let currentChannel = null;

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            if (line.startsWith('#EXTINF:')) {
                currentChannel = {
                    name: 'Unknown',
                    group: 'All',
                    logo: '',
                    url: ''
                };
                
                // Extract group
                const groupMatch = line.match(/group-title="([^"]+)"/);
                if (groupMatch) currentChannel.group = groupMatch[1];
                
                // Extract logo
                const logoMatch = line.match(/tvg-logo="([^"]+)"/);
                if (logoMatch) currentChannel.logo = logoMatch[1];
                
                // Extract name
                const parts = line.split(',');
                if (parts.length > 1) {
                    currentChannel.name = parts[parts.length - 1].trim();
                }
            } else if (line.startsWith('http') && currentChannel) {
                currentChannel.url = line;
                channels.push(currentChannel);
                currentChannel = null;
            }
        }
        return channels;
    }
}
