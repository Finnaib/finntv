"""
collect_external.py
===================
Downloads and filters external M3U channels from LiveTVCollector.
Filters out: Adult content, duplicates, non-famous channels using strict category whitelists.
"""
import requests
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

SOURCES = {
    'spain': [
        'https://iptv-org.github.io/iptv/countries/es.m3u',
        'https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/main/LiveTV/Spain/LiveTV.m3u'
    ],
    'asia': [
        'https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/main/LiveTV/China/LiveTV.m3u',
        'https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/main/LiveTV/Malaysia/LiveTV.m3u',
        'https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/main/LiveTV/Thailand/LiveTV.m3u',
        'https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/main/LiveTV/Vietnam/LiveTV.m3u',
        'https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/main/LiveTV/Mixed/LiveTV.m3u'
    ],
    'india_extra': [
        'https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/main/LiveTV/India/LiveTV.m3u',
        'https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/main/LiveTV/Bangladesh/LiveTV.m3u',
        'https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/main/LiveTV/Pakistan/LiveTV.m3u'
    ],
    'sport_extra': [
        'https://raw.githubusercontent.com/la22lo/sports/refs/heads/main/futbol.m3u'
    ]
}

# 18+ / Adult keywords to block
BLOCK_KEYWORDS = [
    'xxx', 'adult', 'porn', '18+', 'pink', 'blue', 'erotic', 'brazers', 'redlight', 'private',
    'sexy', 'lust', 'passion', 'night', 'strictly', 'playboy', 'penthouse', 'hustler'
]

# Source-specific whitelists
SPAIN_KEYWORDS = [
    'tve', 'rtve', 'la 1', 'la 2', 'antena 3', 'telecinco', 'cuatro', 'la sexta', 'telemadrid',
    '24h', 'clan', 'movistar', 'gol play', 'real madrid', 'barcelona tv', 'deportes',
    'esport', 'canal sur', 'tv3', 'tv canaria', 'etb', 'tdp', '3cat', 'aragontv', 'canal extremadura', 
    'ib3', 'la 8', 'trece', 'ten', 'energy', 'fdf', 'divinity', 'neox', 'nova', 'mega', 
    'atreseries', 'bemad', 'dmax', 'dkiss', 'boing', 'laliga', 'liga de campeones', 'champions', 'sol musica'
]

INDIA_KEYWORDS = [
    'zee', 'sony', 'star', 'colors', 'sab tv', 'sab hd', 'geo news', 'geo ent', 'geo tv', 'ary news', 
    'ary digital', 'ary zindagi', 'hum tv', 'hum masala', 'bol tv', 'somoy', 'jamuna', 'ntv', 
    'channel i', 'desh tv', 'somoy tv', '24 ghanta', 'ptv', 'express news', 'dawn news', 'samaa',
    'etv', 'sun tv', 'kairali', 'asianet', 'surya', 'suvarna', 'vijay', 'raj tv', 'maa tv', 'gemini',
    'mazhavil', 'manorama', 'colors bangla', 'colors gujarati', 'colors kannada', 'colors marathi',
    'kids', 'cartoon', 'willow', 'cricket', 'ten cricket', 'pvt cricket',
    'doraemon', 'shinchan', 'oggy', 'pokemon', 'pogo', 'disney', 'nick', 'sonic', 'hungama', 'yay', 
    'baby', 'toon', 'spacetoon', 'stars', 'buddy', 'junior'
]

ASIA_KEYWORDS = [
    'tvn', 'kbs', 'sbs', 'mbc', 'nhk', 'cctv', 'cgtn', 'astro', '8tv', 'ntv7', 'tonton', 
    'ch3', 'ch7', 'one31', 'gmm25', 'vtv', 'htv', 'thvl'
]

SPORT_KEYWORDS = [
    'espn', 'star sports', 'bein', 'arena sport', 'sky sport', 'dazn', 'eurosport', 'ten sports', 
    'sony ten', 'match!', 'futbol', 'super sport', 'tring sport', 'ksport', 'art sport', 'nova sport', 
    'ct sport', 'btvd', 'fox sports', 'willow', 'espn news', 'nbc sports', 'cbs sports', 'tnt sports', 
    'premier sports', 'ziggo sport', 'vsport'
]

def is_filtered(name, whitelist, strict=True):
    nl = name.lower()
    # Check block list
    if any(k in nl for k in BLOCK_KEYWORDS):
        return True
    
    # Basic validation
    if not name or len(nl) < 3:
        return True
    
    # Filter out technical IDs and paths
    if nl.startswith('q_') or nl.startswith('q-') or '/' in nl or '\\' in nl:
        return True
    
    if re.search(r'\(20\d{2}\)', nl) or re.search(r'\[20\d{2}\]', nl) or re.search(r'\b19\d{2}\b', nl):
        return True

    if re.match(r'^[0-9a-f\-]+$', nl) and len(nl) > 5:
        return True

    # If not strict, we only block NSFW
    if not strict:
        return False

    # Check matches for famous brands in category whitelist
    for k in whitelist:
        if len(k) <= 3:
            # Word boundary check for short keywords to prevent false positives
            pattern = r'\b' + re.escape(k) + r'(?:\d+)?\b'
            if re.search(pattern, nl):
                return False
        else:
            if k in nl:
                return False
        
    return True

def fetch_and_filter(url, whitelist, strict=True):
    print(f"Fetching {url} (Strict={strict})...")
    try:
        response = requests.get(url, timeout=15)
        response.raise_for_status()
        lines = response.text.splitlines()
        
        filtered_channels = []
        current_extinf = None
        
        VOD_EXTS = ['.mp4', '.mkv', '.avi', '.mov', '.wmv', '.flv', '.mpg']
        VOD_PATHS = ['/movies/', '/vod/', '/series/', '/film/', '/cinema/']
        
        for line in lines:
            if line.startswith("#EXTINF:"):
                # Extract channel name
                name_match = re.search(r',(.+?)$', line)
                name = name_match.group(1).strip() if name_match else ""
                
                if not is_filtered(name, whitelist, strict):
                    current_extinf = line
                else:
                    current_extinf = None
            elif line.startswith("http") and current_extinf:
                stream_url = line.strip().lower()
                
                is_vod = any(stream_url.endswith(ext) for ext in VOD_EXTS) or \
                         any(p in stream_url for p in VOD_PATHS)
                
                if not is_vod:
                    filtered_channels.append((current_extinf, line.strip()))
                
                current_extinf = None
                
        return filtered_channels
    except Exception as e:
        print(f"Error: {e}")
        return []

def rebuild_m3u(target_file, source_urls, label, whitelist, strict=True):
    all_channels = []
    seen_urls = set()
    
    is_merge = target_file in ['india.m3u', 'sport.m3u']
    
    if is_merge:
        try:
            with open(f"m3u/{target_file}", "r", encoding="utf-8") as f:
                for line in f:
                    if line.startswith("http"):
                        seen_urls.add(line.strip().lower())
        except FileNotFoundError:
            pass

    for url in source_urls:
        country_match = re.search(r'LiveTV/([^/]+)/', url)
        country = country_match.group(1) if country_match else "General"
        
        channels = fetch_and_filter(url, whitelist, strict)
        for extinf, stream_url in channels:
            if stream_url.lower() not in seen_urls:
                seen_urls.add(stream_url.lower())
                
                # Intelligent Categorization
                category = country
                if label == 'Spain':
                    category = 'Spain'
                elif label == 'Asia':
                    category = 'Asia'
                else:
                    lc_extinf = extinf.lower()
                    if any(k in lc_extinf for k in ["kids", "cartoon", "disney", "doraemon", "shinchan", "pogo", "nick", "baby", "sonic", "hungama", "yay", "toon", "spacetoon", "stars", "buddy", "junior"]):
                        category = "KIDS"
                    elif any(k in lc_extinf for k in ["cricket", "willow", "ten cricket"]):
                        category = "CRICKET"
                    elif country == "India":
                        category = "INDIA"
                
                # Ensure group-title exists and is set correctly
                if 'group-title=' not in extinf:
                    extinf = re.sub(r',([^,]+)$', f' group-title="{category}",\\1', extinf)
                else:
                    extinf = re.sub(r'group-title="[^"]*"', f'group-title="{category}"', extinf)
                
                all_channels.append((extinf, stream_url))

    if not all_channels:
        print(f"No new channels found for {target_file}")
        return

    mode = "a" if is_merge else "w"
    with open(f"m3u/{target_file}", mode, encoding="utf-8") as f:
        if mode == "w":
            f.write("#EXTM3U\n")
        
        f.write("\n" + "#" * 80 + "\n")
        f.write(f"# EXTERNAL: {label} CHANNELS\n")
        f.write("#" * 80 + "\n\n")
        
        for extinf, stream_url in all_channels:
            f.write(extinf + "\n")
            f.write(stream_url + "\n\n")
            
    print(f"Updated m3u/{target_file} with {len(all_channels)} new channels.")

if __name__ == "__main__":
    rebuild_m3u('spanish.m3u', SOURCES['spain'], 'Spain', SPAIN_KEYWORDS, strict=True)
    rebuild_m3u('asia.m3u', SOURCES['asia'], 'Asia', ASIA_KEYWORDS, strict=True)
    rebuild_m3u('india.m3u', SOURCES['india_extra'], 'Subcontinent', INDIA_KEYWORDS, strict=True)
    rebuild_m3u('sport.m3u', SOURCES['sport_extra'], 'Sports', SPORT_KEYWORDS, strict=True)

    print("\nCollection and filtering finished.")
