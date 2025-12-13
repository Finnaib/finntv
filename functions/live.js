const path = require('path');

// Load Map ONCE
let ID_MAP = null;

function loadMap() {
    if (ID_MAP) return ID_MAP;
    try {
        // Force Bundler to include it
        ID_MAP = require('./id_map.json');
        return ID_MAP;
    } catch (e) {
        console.error("Failed to load id_map.json", e);
        return {};
    }
}

exports.handler = async (event, context) => {
    // 1. CORS Headers
    const headers = {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, HEAD, OPTIONS',
        'Access-Control-Allow-Headers': '*',
        'Cache-Control': 'no-cache, no-store, must-revalidate'
    };

    if (event.httpMethod === 'OPTIONS') {
        return { statusCode: 200, headers, body: '' };
    }

    // 2. Parse ID from path (Robust Regex)
    // Supports: /live/u/p/123.ts, /movie/u/p/123.mp4, /123.ts
    // Extract the *last* numeric component before the extension
    const match = event.path.match(/\/([0-9]+)\.(ts|m3u8|mp4|mkv)$/);
    const id = match ? match[1] : null;

    // Debug Headers
    const debugHeaders = {
        'X-Debug-Input-Path': event.path,
        'X-Debug-Extracted-ID': String(id),
        'Referrer-Policy': 'no-referrer' // Critical: Hide Netlify from Provider
    };

    if (!id) {
        return {
            statusCode: 404,
            headers: { ...headers, ...debugHeaders },
            body: 'Stream ID not found in path'
        };
    }

    // 3. Lookup
    const map = loadMap();
    const targetUrl = map[id];

    if (!targetUrl) {
        return {
            statusCode: 404,
            headers: { ...headers, ...debugHeaders },
            body: 'Stream not found in map'
        };
    }

    // 4. Redirect
    const headersOut = {
        ...headers,
        ...debugHeaders, // Include debug info
        'Location': targetUrl
    };

    // Hint Content-Type (Good for players before they follow redirect)
    if (targetUrl.endsWith('.m3u8')) {
        headersOut['Content-Type'] = 'application/vnd.apple.mpegurl';
    } else if (targetUrl.endsWith('.ts')) {
        headersOut['Content-Type'] = 'video/mp2t';
    } else {
        headersOut['Content-Type'] = 'video/mp4';
    }

    return {
        statusCode: 302,
        headers: headersOut,
        body: ''
    };
};
