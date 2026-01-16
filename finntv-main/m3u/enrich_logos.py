
import os
import re
import requests
import urllib3
import json

urllib3.disable_warnings()

BASE_DIR = r"c:\Users\Administrator\Downloads\finntv.atwebpages.com (3)\xtream_server\m3u"
LOGOS_URL = "https://iptv-org.github.io/api/channels.json"

# Hardcoded High-Quality Logos for VOD Genres
GENRE_MAP = {
    # Genre Keyword : Logo URL
    "animation": "https://upload.wikimedia.org/wikipedia/commons/thumb/d/d2/Pixar.svg/1200px-Pixar.svg.png", 
    "kids": "https://upload.wikimedia.org/wikipedia/commons/thumb/c/c8/Disney_Channel_logo.svg/1200px-Disney_Channel_logo.svg.png",
    "disney": "https://upload.wikimedia.org/wikipedia/commons/thumb/3/3e/Disney%2B_logo.svg/1200px-Disney%2B_logo.svg.png",
    "netflix": "https://upload.wikimedia.org/wikipedia/commons/thumb/0/08/Netflix_2015_logo.svg/1200px-Netflix_2015_logo.svg.png",
    "amazon": "https://upload.wikimedia.org/wikipedia/commons/thumb/1/11/Amazon_Prime_Video_logo.svg/1200px-Amazon_Prime_Video_logo.svg.png",
    "prime": "https://upload.wikimedia.org/wikipedia/commons/thumb/1/11/Amazon_Prime_Video_logo.svg/1200px-Amazon_Prime_Video_logo.svg.png",
    "action": "https://upload.wikimedia.org/wikipedia/commons/thumb/7/7b/Eye_Action_2015.svg/1200px-Eye_Action_2015.svg.png",
    "comedy": "https://upload.wikimedia.org/wikipedia/commons/thumb/c/c5/Comedy_Central_2018.svg/1200px-Comedy_Central_2018.svg.png",
    "drama": "https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/ITV_Drama_2013_logo.svg/1200px-ITV_Drama_2013_logo.svg.png",
    "sci-fi": "https://upload.wikimedia.org/wikipedia/commons/thumb/4/41/Syfy_2017.svg/1200px-Syfy_2017.svg.png",
    "documentary": "https://upload.wikimedia.org/wikipedia/commons/thumb/f/f1/Discovery_Channel_logo_2019.svg/1200px-Discovery_Channel_logo_2019.svg.png",
    "music": "https://upload.wikimedia.org/wikipedia/commons/thumb/6/68/MTV_2021_logo.svg/1200px-MTV_2021_logo.svg.png"
}

def clean_name(name):
    # Aggressive cleaning for VOD
    clean = re.sub(r'\(.*?\)', '', name)
    clean = re.sub(r'\[.*?\]', '', clean)
    clean = re.sub(r'(?i)S\d+\s*E\d+', '', clean)
    clean = re.sub(r'(?i)Season\s*\d+', '', clean)
    clean = re.sub(r'(?i)Episode\s*\d+', '', clean)
    clean = re.sub(r'\s+((19|20)\d{2})\s*$', '', clean)
    clean = re.sub(r'[^\w\s]', '', clean)
    clean = re.sub(r'\s+', ' ', clean)
    return clean.strip().lower()

def load_logo_db():
    print(f"Fetching logos from {LOGOS_URL}...")
    try:
        r = requests.get(LOGOS_URL, verify=False, timeout=30)
        data = r.json()
        print(f"loaded {len(data)} logos.")
        
        logo_map = {}
        for item in data:
            if 'name' in item and 'logo' in item:
                clean = clean_name(item['name'])
                logo_map[clean] = item['logo']
        return logo_map
    except Exception as e:
        print(f"Error loading logos: {e}")
        return {}

def process_file(filepath, logo_map):
    print(f"Processing {os.path.basename(filepath)}...")
    updated = False
    new_lines = []
    
    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        lines = f.readlines()
        
    for line in lines:
        line = line.strip()
        if not line:
            new_lines.append("")
            continue
            
        if line.startswith("#EXTINF:"):
            # Check for existing logo
            logo_match = re.search(r'tvg-logo="([^"]*)"', line)
            current_logo = logo_match.group(1) if logo_match else ""
            
            if not current_logo:
                found_logo = None
                
                # 1. Try Name Match (Skipped for now, focusing on Group)
                # ... (You can re-add if needed)

                # 2. Try Group Match (Fallback)
                if not found_logo:
                    group_match = re.search(r'group-title="([^"]+)"', line)
                    group_name = group_match.group(1) if group_match else ""
                    
                    if group_name:
                        cg = clean_name(group_name)
                        
                        # Check Manual Genre Map first
                        for genre, logo_url in GENRE_MAP.items():
                            if genre in cg: # Partial match: "animation" in "kids animation"
                                found_logo = logo_url
                                break
                        
                        # Direct Group Match (Fallback to DB)
                        if not found_logo:
                            if cg in logo_map:
                                found_logo = logo_map[cg]
                
                # Apply Logo
                if found_logo:
                    # Robust Replacement Logic
                    if 'tvg-logo=""' in line:
                        line = line.replace('tvg-logo=""', f'tvg-logo="{found_logo}"')
                    elif 'tvg-logo=' not in line:
                        if 'group-title=' in line:
                            line = line.replace('group-title=', f'tvg-logo="{found_logo}" group-title=')
                        elif '#EXTINF:-1 ' in line: 
                            line = line.replace('#EXTINF:-1 ', f'#EXTINF:-1 tvg-logo="{found_logo}" ')
                        else:
                             # Worst case, append to end of tag? 
                             # Assume tag starts with #EXTINF:-1
                             line = line.replace('#EXTINF:-1', f'#EXTINF:-1 tvg-logo="{found_logo}"')
                    
                    updated = True
        
        new_lines.append(line)
        
    if updated:
        with open(filepath, 'w', encoding='utf-8') as f:
            for l in new_lines:
                f.write(l + "\n")
        print(f"Updated {os.path.basename(filepath)}")
    else:
        print(f"No changes for {os.path.basename(filepath)}")

def main():
    logo_map = load_logo_db()
    
    if not logo_map:
        # proceed with empty map if DB fails, GENRE_MAP still works
        logo_map = {}

    print("Scanning playlists...")
    for filename in os.listdir(BASE_DIR):
        if filename.endswith(".m3u"):
            # Check if VOD/Series to prioritize
            if "vod" in filename.lower() or "series" in filename.lower():
                filepath = os.path.join(BASE_DIR, filename)
                process_file(filepath, logo_map)
            
    print("Enrichment complete.")

if __name__ == "__main__":
    main()
