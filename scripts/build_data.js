const fs = require('fs');
const path = require('path');
const glob = require('glob');

// Since we don't want external dependencies if possible, we'll implement simple glob
// or just readdir since we know the structure.

const M3U_DIR = path.join(__dirname, '../m3u');
const DATA_FILE = path.join(__dirname, '../data/data.json');
const ID_MAP_FILE = path.join(__dirname, '../id_map.json');

// Ensure data dir exists
if (!fs.existsSync(path.dirname(DATA_FILE))) {
    fs.mkdirSync(path.dirname(DATA_FILE), { recursive: true });
}

const data = {
    live_streams: [],
    live_categories: [],
    vod_streams: [],
    vod_categories: [],
    series: [],
    series_categories: []
};

const idMap = {};
const catMap = {
    live: {},
    movie: {},
    series: {}
};

let streamIndex = 1;

function parseM3uFiles() {
    if (!fs.existsSync(M3U_DIR)) {
        console.error("M3U Directory not found:", M3U_DIR);
        return;
    }

    const files = fs.readdirSync(M3U_DIR).filter(f => f.endsWith('.m3u')).sort();
    
    console.log(`Found ${files.length} M3U files.`);

    for (const file of files) {
        const fullPath = path.join(M3U_DIR, file);
        console.log(`Parsing ${file}...`);
        
        const content = fs.readFileSync(fullPath, 'utf8');
        const lines = content.split(/\r?\n/);

        // Heuristics
        const lowerName = file.toLowerCase();
        let isVodFile = (lowerName.includes('vod') || lowerName.includes('movie'));
        let isSeriesFile = lowerName.includes('series');
        // 'xtream' or 'live' usually implies live, but we check per-item metadata too in strict mode logic

        let currentGroup = "Uncategorized";
        let currentLogo = "";
        let meta = null;

        for (const line of lines) {
            const trimmed = line.trim();
            if (!trimmed) continue;

            if (trimmed.startsWith('#EXTINF')) {
                // Parsing Attributes
                const groupMatch = trimmed.match(/group-title="([^"]*)"/);
                currentGroup = groupMatch ? groupMatch[1] : "Uncategorized";
                
                const logoMatch = trimmed.match(/tvg-logo="([^"]*)"/);
                currentLogo = logoMatch ? logoMatch[1] : "";

                const nameMatch = trimmed.match(/,(.*)$/);
                const name = nameMatch ? nameMatch[1] : "Unknown Channel";

                // Classification Logic (Porting from PHP)
                let isVod = false;
                let isSeries = false;

                const groupLower = currentGroup.toLowerCase();
                
                if (groupLower.includes('series') || groupLower.includes('season')) {
                    isSeries = true;
                } else if (
                    (groupLower.includes('movie') && !groupLower.includes('bein')) ||
                    groupLower.includes('cinema') ||
                    groupLower.includes('vod') ||
                    groupLower.includes('film') ||
                    groupLower.includes('4k')
                ) {
                    isVod = true;
                } else if (isSeriesFile) {
                    isSeries = true;
                } else if (isVodFile) {
                    isVod = true;
                }

                const type = isSeries ? 'series' : (isVod ? 'movie' : 'live');
                
                // CRC32ish for Cat ID (simple hash)
                const uniqueGroupStr = (type === 'live' ? 'L_' : (type === 'movie' ? 'M_' : 'S_')) + currentGroup;
                const catId = stringHash(uniqueGroupStr);

                meta = {
                    id: streamIndex++,
                    name: name,
                    logo: currentLogo,
                    group: currentGroup,
                    type: type,
                    catId: catId
                };

            } else if (trimmed.startsWith('http') && meta) {
                // Determine Extension
                let ext = 'ts';
                const dotIdx = trimmed.lastIndexOf('.');
                if (dotIdx !== -1 && trimmed.length - dotIdx <= 5) {
                    ext = trimmed.substring(dotIdx + 1);
                }

                // Add Category
                // Using objects for map to dedup
                if (!catMap[meta.type][meta.catId]) {
                    catMap[meta.type][meta.catId] = {
                        category_id: String(meta.catId),
                        category_name: meta.group,
                        parent_id: 0
                    };
                }

                const stream = {
                    num: meta.id,
                    name: meta.name,
                    stream_id: meta.id,
                    stream_icon: meta.logo,
                    category_id: String(meta.catId),
                    container_extension: ext,
                    direct_source: trimmed
                };

                // Add to Maps
                if (meta.type === 'live') {
                    stream.stream_type = 'live';
                    data.live_streams.push(stream);
                    idMap[stream.num] = stream.direct_source;
                } else if (meta.type === 'movie') {
                    stream.stream_type = 'movie';
                    stream.rating = '5';
                    stream.added = String(Math.floor(Date.now() / 1000));
                    data.vod_streams.push(stream);
                    idMap[stream.num] = stream.direct_source;
                } else if (meta.type === 'series') {
                    stream.series_id = meta.id;
                    stream.cover = meta.logo;
                    data.series.push(stream);
                    // Series ID Map Logic (Optional, usually episode based handling)
                    idMap[stream.num] = stream.direct_source; 
                }

                meta = null;
            }
        }
    }

    // Convert Cat Maps to Arrays
    data.live_categories = Object.values(catMap.live);
    data.vod_categories = Object.values(catMap.movie);
    data.series_categories = Object.values(catMap.series);

    console.log(`Live: ${data.live_streams.length}`);
    console.log(`VOD: ${data.vod_streams.length}`);
    console.log(`Series: ${data.series.length}`);

    fs.writeFileSync(DATA_FILE, JSON.stringify(data)); 
    fs.writeFileSync(ID_MAP_FILE, JSON.stringify(idMap));
    console.log(`Saved to ${DATA_FILE} and ${ID_MAP_FILE}`);
}

// Simple Hash Function (DJB2 variant) for numeric ID
function stringHash(str) {
    let hash = 5381;
    for (let i = 0; i < str.length; i++) {
        hash = (hash * 33) ^ str.charCodeAt(i);
    }
    return (hash >>> 0); // Ensure positive unsigned integer
}

parseM3uFiles();
