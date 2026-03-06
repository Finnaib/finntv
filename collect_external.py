"""
collect_external.py
===================
Downloads and filters external M3U channels from LiveTVCollector.
Filters out: Adult content, duplicates, non-famous channels.
"""
import requests
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

SOURCES = {
    'indonesia': [
        'https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/main/LiveTV/Indonesia/LiveTV.m3u'
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

# Famous brand keywords to whitelist (Case Insensitive)
# Only channels matching these (or prefixes) will be kept from external sources
FAMOUS_KEYWORDS = [
    # General Premium
    'hbo', 'fox', 'axn', 'tvn', 'kbs', 'sbs', 'mbc', 'nhk', 'cctv', 'cgtn', 'warner', 'paramount', 
    'universal', 'cinema', 'discovery', 'nat geo', 'animal planet', 'cnn', 'bbc', 'al jazeera', 
    'bloomberg', 'espn', 'star sports', 'bein', 'disney', 'nick', 'cartoon', 'pogo', 'tv5monde',
    # Sports Specific
    'arena sport', 'sky sport', 'dazn', 'eurosport', 'ten sports', 'sony ten', 'match!', 'futbol',
    'super sport', 'tring sport', 'ksport', 'art sport', 'nova sport', 'ct sport', 'btvd', 'fox sports',
    'willow', 'espn news', 'nbc sports', 'cbs sports', 'tnt sports', 'premier sports', 'ziggo sport', 'vsport',
    # India / Subcontinent
    'zee', 'sony', 'star', 'colors', 'sab tv', 'sab hd', 'geo news', 'geo ent', 'geo tv', 'ary news', 
    'ary digital', 'ary zindagi', 'hum tv', 'hum masala', 'bol tv', 'somoy', 'jamuna', 'ntv', 
    'channel i', 'desh tv', 'somoy tv', '24 ghanta', 'ptv', 'express news', 'dawn news', 'samaa',
    'etv', 'sun tv', 'kairali', 'asianet', 'surya', 'suvarna', 'vijay', 'raj tv', 'maa tv', 'gemini',
    'mazhavil', 'manorama', 'colors bangla', 'colors gujarati', 'colors kannada', 'colors marathi'
]

def is_filtered(name):
    nl = name.lower()
    # Check block list
    if any(k in nl for k in BLOCK_KEYWORDS):
        return True
    
    # --- STRICT NAME VALIDATION ---
    if not name or len(nl) < 3:
        return True
    
    # Filter out technical IDs and paths like "q_85/..."
    if nl.startswith('q_') or nl.startswith('q-') or '/' in nl or '\\' in nl:
        return True
    
    # Filter out VOD/Movie patterns (Years like 2023, 2024, etc.)
    if re.search(r'\(20\d{2}\)', nl) or re.search(r'\[20\d{2}\]', nl) or re.search(r'\b19\d{2}\b', nl):
        return True

    # Filter out names that are just numbers/hex IDs
    if re.match(r'^[0-9a-f\-]+$', nl) and len(nl) > 5:
        return True

    # Check matches for famous brands
    if any(k in nl for k in FAMOUS_KEYWORDS):
        return False
        
    return True

def fetch_and_filter(url):
    print(f"Fetching {url}...")
    try:
        response = requests.get(url, timeout=15)
        response.raise_for_status()
        lines = response.text.splitlines()
        
        filtered_channels = []
        current_extinf = None
        
        # VOD Extension block list
        VOD_EXTS = ['.mp4', '.mkv', '.avi', '.mov', '.wmv', '.flv', '.mpg']
        # VOD Path block list
        VOD_PATHS = ['/movies/', '/vod/', '/series/', '/film/', '/cinema/']
        
        for line in lines:
            if line.startswith("#EXTINF:"):
                # Extract channel name
                name_match = re.search(r',(.+?)$', line)
                name = name_match.group(1).strip() if name_match else ""
                
                if not is_filtered(name):
                    current_extinf = line
                else:
                    current_extinf = None
            elif line.startswith("http") and current_extinf:
                stream_url = line.strip().lower()
                
                # Check if it's a VOD file
                is_vod = any(stream_url.endswith(ext) for ext in VOD_EXTS) or \
                         any(p in stream_url for p in VOD_PATHS)
                
                if not is_vod:
                    filtered_channels.append((current_extinf, line.strip()))
                
                current_extinf = None
                
        return filtered_channels
    except Exception as e:
        print(f"Error: {e}")
        return []

def rebuild_m3u(target_file, source_urls, label):
    all_channels = []
    seen_urls = set()
    
    # India and Sport target files are merges (they contain provider channels already)
    is_merge = target_file in ['india.m3u', 'sport.m3u']
    
    if is_merge:
        # Load existing provider channels first to avoid duplicates
        try:
            with open(f"m3u/{target_file}", "r", encoding="utf-8") as f:
                for line in f:
                    if line.startswith("http"):
                        seen_urls.add(line.strip())
        except FileNotFoundError:
            pass

    for url in source_urls:
        country_match = re.search(r'LiveTV/([^/]+)/', url)
        country = country_match.group(1) if country_match else "General"
        
        channels = fetch_and_filter(url)
        for extinf, stream_url in channels:
            if stream_url not in seen_urls:
                seen_urls.add(stream_url)
                # Clean up group title
                extinf = re.sub(r'group-title="[^"]*"', f'group-title="{country}"', extinf)
                all_channels.append((extinf, stream_url))

    if not all_channels:
        print(f"No new famous channels found for {target_file}")
        return

    # Write/Append
    mode = "a" if is_merge else "w"
    with open(f"m3u/{target_file}", mode, encoding="utf-8") as f:
        if mode == "w":
            f.write("#EXTM3U\n")
        
        f.write("\n" + "#" * 80 + "\n")
        f.write(f"# EXTERNAL: FAMOUS {label} CHANNELS\n")
        f.write("#" * 80 + "\n\n")
        
        for extinf, stream_url in all_channels:
            f.write(extinf + "\n")
            f.write(stream_url + "\n\n")
            
    print(f"Updated m3u/{target_file} with {len(all_channels)} new famous channels.")

if __name__ == "__main__":
    rebuild_m3u('indonesia.m3u', SOURCES['indonesia'], 'Indonesia')
    rebuild_m3u('asia.m3u', SOURCES['asia'], 'Asia')
    rebuild_m3u('india.m3u', SOURCES['india_extra'], 'Subcontinent')
    rebuild_m3u('sport.m3u', SOURCES['sport_extra'], 'Sports')
    print("\nCollection and filtering finished.")
