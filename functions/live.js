const fs = require('fs');
const path = require('path');

const ID_MAP_FILE = path.resolve(__dirname, '../id_map.json');
let ID_MAP = null;

function loadMap() {
    if (ID_MAP) return ID_MAP;
    if (fs.existsSync(ID_MAP_FILE)) {
        try {
            ID_MAP = JSON.parse(fs.readFileSync(ID_MAP_FILE, 'utf8'));
            return ID_MAP;
        } catch (e) {
            console.error("Failed to load id_map.json", e);
        }
    }
    return {};
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

    // 2. Parse ID from path
    // Path: /.netlify/functions/live/user/pass/123.ts
    const segments = event.path.split('/');
    const lastSeg = segments[segments.length - 1]; // "123.ts"
    const id = lastSeg.replace(/\.(ts|m3u8|mp4|mkv)$/, '');

    // 3. Lookup
    const map = loadMap();
    const targetUrl = map[id];

    if (!targetUrl) {
        return {
            statusCode: 404,
            headers,
            body: 'Stream not found'
        };
    }

    // 4. Redirect
    return {
        statusCode: 302,
        headers: {
            ...headers,
            'Location': targetUrl
        },
        body: ''
    };
};
