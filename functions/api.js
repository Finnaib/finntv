const path = require('path');

// Load Data ONCE (Cold Start Optimization)
// Load Data ONCE (Cold Start Optimization)
let CACHED_DATA = null;
let LOAD_ERROR = null;

function loadData() {
    if (CACHED_DATA) return CACHED_DATA;
    try {
        // Force Bundler to include it by using require
        // Note: This file must exist at build time (created by build_data.js)
        CACHED_DATA = require('./data.json');
        return CACHED_DATA;
    } catch (e) {
        console.error("Failed to load data.json", e);
        LOAD_ERROR = e.message;
        // Fallback or empty
        return { live_categories: [], live_streams: [], vod_categories: [], vod_streams: [], series: [], series_categories: [] };
    }
}

// Users DB (Hardcoded matching PHP config)
const USERS_DB = {
    "finn": { password: "finn123", created_at: 1735689600 },
    "tabby": { password: "tabby123" },
    "test": { password: "test" },
    "shoaibwwe01@gmail.com": { password: "Fatima786@" }
};

exports.handler = async (event, context) => {
    // 1. CORS Headers
    const headers = {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
        'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Requested-With',
        'Content-Type': 'application/json; charset=utf-8'
    };

    if (event.httpMethod === 'OPTIONS') {
        return { statusCode: 200, headers, body: '' };
    }

    // 2. Parse Input
    const params = event.queryStringParameters || {};
    const username = params.username || '';
    const password = params.password || '';
    const action = params.action || '';

    // 3. Auth Logic
    let isAuth = false;
    let userData = {};

    if (USERS_DB[username]) {
        const u = USERS_DB[username];
        if (u.password === password) {
            isAuth = true;
            // Dynamic Exp Logic
            const createdAt = u.created_at || Math.floor(Date.now() / 1000);
            const expDate = createdAt + 31536000; // +1 Year
            userData = { created_at: createdAt, exp_date: expDate };
        }
    }

    if (!isAuth) {
        return {
            statusCode: 200,
            headers,
            body: JSON.stringify({ user_info: { auth: 0 } })
        };
    }

    // 4. Response Structures (Strict Types)
    const userInfo = {
        username: String(username),
        password: String(password),
        message: 'Login Successful',
        auth: 1,
        status: 'Active',
        active_cons: '0',
        max_connections: '5',
        is_trial: '0',
        created_at: String(userData.created_at),
        exp_date: String(userData.exp_date),
        allowed_output_formats: ['m3u8', 'ts', 'rtmp']
    };

    const host = event.headers.host || 'localhost';
    const proto = event.headers['x-forwarded-proto'] || 'http';
    const baseUrl = `${proto}://${host}/`;

    const serverInfo = {
        url: baseUrl,
        port: '80', // Virtual
        https_port: '443',
        server_protocol: proto,
        rtmp_port: '88',
        timezone: 'UTC',
        timestamp_now: Math.floor(Date.now() / 1000),
        time_now: new Date().toISOString().replace('T', ' ').substring(0, 19),
        process: true,
        server_name: 'FinnTV Netlify'
    };

    // 5. Action Routing
    let responseBody = {};

    if (!action || action === 'get_panel_info') {
        // Login does NOT need data.json (Huge Optimization)
        responseBody = {
            user_info: userInfo,
            server_info: serverInfo
        };
    } else {
        // Only load massive data if we actually need it
        const data = loadData();

        if (LOAD_ERROR) {
            return {
                statusCode: 500,
                headers,
                body: JSON.stringify({ error: "Server Data Error", debug: LOAD_ERROR })
            };
        }

        if (action === 'get_live_categories') {
            responseBody = data.live_categories;

        } else if (action === 'get_live_streams') {
            const catId = params.category_id;
            if (catId) {
                responseBody = data.live_streams.filter(s => String(s.category_id) === String(catId));
            } else {
                responseBody = data.live_streams;
            }

        } else if (action === 'get_vod_categories') {
            responseBody = data.vod_categories;

        } else if (action === 'get_vod_streams') {
            const catId = params.category_id;
            if (catId) {
                responseBody = data.vod_streams.filter(s => String(s.category_id) === String(catId));
            } else {
                responseBody = data.vod_streams.map(s => {
                    const copy = { ...s };
                    delete copy.stream_icon; // Optimization
                    return copy;
                });
            }
        } else if (action === 'get_series_categories') {
            responseBody = data.series_categories;

        } else if (action === 'get_series') {
            const catId = params.category_id;
            let list = data.series;
            if (catId) {
                list = list.filter(s => String(s.category_id) === String(catId));
            }
            responseBody = list.map(s => ({
                num: s.num,
                name: s.name,
                series_id: s.series_id,
                cover: s.cover,
                plot: '',
                cast: '',
                director: '',
                genre: '',
                releaseDate: '',
                last_modified: String(Math.floor(Date.now() / 1000)),
                rating: '5',
                rating_5based: '5',
                backdrop_path: [],
                youtube_trailer: '',
                episode_run_time: '0',
                category_id: s.category_id
            }));
        } else {
            responseBody = [];
        }
    }

    return {
        statusCode: 200,
        headers,
        body: JSON.stringify(responseBody)
    };
};
